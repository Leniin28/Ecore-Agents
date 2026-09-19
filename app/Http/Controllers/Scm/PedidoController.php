<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        return view('scm.pedidos.index', [
            'pedidos' => $this->query($request)->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('scm.pedidos.create', ['productos' => Producto::query()->orderBy('nombre')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pedido = $this->crearManual($request, $this->validated($request));

        return redirect()->route('scm.pedidos.index')->with('success', "Pedido #{$pedido->id} generado correctamente.");
    }

    public function surtir(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->surtirPedido($request, $pedido);

        return redirect()->route('scm.pedidos.index')->with('success', "Pedido #{$pedido->id} surtido correctamente.");
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->get()->map($this->serialize(...))]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $pedido = $this->crearManual($request, $this->validated($request));

        return response()->json(['data' => $this->serialize($pedido->load(['producto.proveedor', 'usuario']))], 201);
    }

    public function apiUpdateEstado(Request $request, Pedido $pedido): JsonResponse
    {
        $request->validate(['estado' => ['required', Rule::in([Pedido::ESTADO_SURTIDO])]]);
        $pedido = $this->surtirPedido($request, $pedido);

        return response()->json(['data' => $this->serialize($pedido->load(['producto.proveedor', 'usuario']))]);
    }

    /** @param array<string, mixed> $data */
    private function crearManual(Request $request, array $data): Pedido
    {
        return DB::transaction(function () use ($request, $data): Pedido {
            $pedido = Pedido::create([
                ...$data,
                'usuario_id' => $request->user()->id,
                'estado' => Pedido::ESTADO_PENDIENTE,
                'origen' => Pedido::ORIGEN_MANUAL,
            ]);
            Auditoria::registrar($request->user(), 'crear_pedido_manual', 'pedido', $pedido->id,
                "Generó manualmente el pedido #{$pedido->id}.");

            return $pedido;
        });
    }

    private function surtirPedido(Request $request, Pedido $pedido): Pedido
    {
        return DB::transaction(function () use ($request, $pedido): Pedido {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if ($pedido->estado !== Pedido::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['estado' => 'El pedido ya fue surtido y no puede surtirse otra vez.']);
            }

            $producto = Producto::query()->lockForUpdate()->findOrFail($pedido->producto_id);
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'usuario_id' => $request->user()->id,
                'tipo' => MovimientoInventario::TIPO_ENTRADA,
                'cantidad' => $pedido->cantidad,
                'motivo' => MovimientoInventario::MOTIVO_REPOSICION,
                'fecha' => now(),
            ]);
            $producto->increment('stock_actual', $pedido->cantidad);
            $pedido->update(['estado' => Pedido::ESTADO_SURTIDO]);
            Auditoria::registrar($request->user(), 'surtir_pedido', 'pedido', $pedido->id,
                "Surtió el pedido #{$pedido->id} y registró la entrada #{$movimiento->id}.");

            return $pedido->fresh();
        });
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'tipo' => ['required', Rule::in(Pedido::TIPOS)],
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'tipo.in' => 'El tipo de pedido debe ser reposición o venta.',
        ]);
    }

    private function query(Request $request): Builder
    {
        $estado = $request->string('estado')->toString();

        return Pedido::query()->with(['producto.proveedor', 'usuario'])
            ->when(in_array($estado, Pedido::ESTADOS, true), fn (Builder $query) => $query->where('estado', $estado))
            ->latest();
    }

    private function serialize(Pedido $pedido): array
    {
        return [
            ...$pedido->only(['id', 'producto_id', 'usuario_id', 'cantidad', 'tipo', 'estado', 'origen', 'created_at', 'updated_at']),
            'producto' => $pedido->producto ? [
                ...$pedido->producto->only(['id', 'nombre', 'stock_actual']),
                'proveedor' => $pedido->producto->proveedor?->only(['id', 'nombre']),
            ] : null,
            'usuario' => $pedido->usuario?->only(['id', 'name']),
        ];
    }
}
