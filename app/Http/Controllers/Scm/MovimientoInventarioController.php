<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\ScmReposicionService;

class MovimientoInventarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('scm.movimientos.index', [
            'movimientos' => $this->query($request)->paginate(20)->withQueryString(),
            'productos' => Producto::query()->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('scm.movimientos.create', ['productos' => Producto::query()->orderBy('nombre')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $movimiento = $this->registrar($request, $this->validated($request));

        return redirect()->route('scm.movimientos.index')
            ->with('success', "Movimiento {$movimiento->tipo} registrado correctamente.");
    }

    public function porProducto(Producto $producto): View
    {
        return view('scm.movimientos.producto', [
            'producto' => $producto,
            'movimientos' => $producto->movimientos()->with('usuario')->latest('fecha')->paginate(20),
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->get()->map($this->serialize(...))]);
    }

    public function apiPorProducto(Producto $producto): JsonResponse
    {
        return response()->json(['data' => $producto->movimientos()->with(['producto', 'usuario'])->latest('fecha')->get()->map($this->serialize(...))]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $movimiento = $this->registrar($request, $this->validated($request));

        return response()->json(['data' => $this->serialize($movimiento->load(['producto', 'usuario']))], 201);
    }

    /** @param array<string, mixed> $data */
    private function registrar(Request $request, array $data): MovimientoInventario
    {
        return DB::transaction(function () use ($request, $data): MovimientoInventario {
            $producto = Producto::query()->lockForUpdate()->findOrFail($data['producto_id']);

            if ($data['tipo'] === MovimientoInventario::TIPO_SALIDA && $producto->stock_actual < $data['cantidad']) {
                throw ValidationException::withMessages(['cantidad' => 'La salida no puede dejar el stock en negativo.']);
            }

            $nuevoStock = $data['tipo'] === MovimientoInventario::TIPO_ENTRADA
                ? $producto->stock_actual + $data['cantidad']
                : $producto->stock_actual - $data['cantidad'];

            $movimiento = $producto->movimientos()->create([
                'usuario_id' => $request->user()->id,
                'tipo' => $data['tipo'],
                'cantidad' => $data['cantidad'],
                'motivo' => $data['motivo'],
                'fecha' => $data['fecha'],
            ]);
            $producto->update(['stock_actual' => $nuevoStock]);
            if ($movimiento->tipo === MovimientoInventario::TIPO_SALIDA) {
                app(ScmReposicionService::class)->generarPushSiCorresponde($producto->fresh());
            }
            Auditoria::registrar($request->user(), 'registrar_movimiento', 'movimiento_inventario', $movimiento->id,
                "Registró {$movimiento->tipo} de {$movimiento->cantidad} unidades para {$producto->nombre}.");

            return $movimiento;
        });
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'tipo' => ['required', Rule::in(MovimientoInventario::TIPOS)],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', Rule::in(MovimientoInventario::MOTIVOS)],
            'fecha' => ['required', 'date'],
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'tipo.in' => 'El tipo debe ser entrada o salida.',
            'cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'motivo.in' => 'El motivo debe ser venta, ajuste o reposición.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);
    }

    private function query(Request $request): Builder
    {
        $tipo = $request->string('tipo')->toString();
        $productoId = $request->integer('producto_id');

        return MovimientoInventario::query()->with(['producto', 'usuario'])
            ->when(in_array($tipo, MovimientoInventario::TIPOS, true), fn (Builder $query) => $query->where('tipo', $tipo))
            ->when($productoId, fn (Builder $query) => $query->where('producto_id', $productoId))
            ->latest('fecha')->latest('id');
    }

    private function serialize(MovimientoInventario $movimiento): array
    {
        return [
            ...$movimiento->only(['id', 'producto_id', 'usuario_id', 'tipo', 'cantidad', 'motivo']),
            'fecha' => $movimiento->fecha->toISOString(),
            'producto' => $movimiento->producto?->only(['id', 'nombre', 'stock_actual']),
            'usuario' => $movimiento->usuario?->only(['id', 'name']),
        ];
    }
}
