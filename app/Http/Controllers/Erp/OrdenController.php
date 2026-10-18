<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrdenController extends Controller
{
    public function index(Request $request): View
    {
        return view('erp.ordenes.index', ['ordenes' => $this->query($request)->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        return view('erp.ordenes.create', [
            'clientes' => Cliente::query()->orderBy('nombre')->get(),
            'productos' => Producto::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orden = $this->crear($request);

        return redirect()->route('erp.ordenes.show', $orden)->with('success', "{$orden->folio()} creada como pendiente.");
    }

    public function show(OrdenEmpresarial $orden): View
    {
        return view('erp.ordenes.show', ['orden' => $orden->load(['cliente', 'usuario', 'detalles.producto'])]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->paginate(20)->withQueryString()]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crear($request)->load(['cliente', 'usuario:id,name', 'detalles.producto'])], 201);
    }

    public function apiShow(OrdenEmpresarial $orden): JsonResponse
    {
        return response()->json(['data' => $orden->load(['cliente', 'usuario:id,name', 'detalles.producto'])]);
    }

    private function crear(Request $request): OrdenEmpresarial
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'detalles' => ['required', 'array', 'min:1', 'max:100'],
            'detalles.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $productos = Producto::query()->whereIn('id', array_column($data['detalles'], 'producto_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $detalles = [];
            $totalCentavos = 0;
            foreach ($data['detalles'] as $linea) {
                $producto = $productos->get($linea['producto_id']);
                if (! $producto) {
                    throw ValidationException::withMessages(['detalles' => 'Uno de los recursos ya no está disponible.']);
                }
                // Centavos enteros para evitar errores de redondeo al sumar precios.
                $precio = $producto->precio_venta_unitario;
                [$entero, $decimal] = explode('.', $precio);
                $centavos = ((int) $entero * 100) + (int) $decimal;
                $subtotal = $centavos * (int) $linea['cantidad'];
                $totalCentavos += $subtotal;
                if ($totalCentavos > 99999999999999) {
                    throw ValidationException::withMessages(['detalles' => 'El total supera el límite permitido para una orden.']);
                }
                $detalles[] = [
                    'producto_id' => $producto->id, 'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $precio, 'subtotal' => $this->decimal($subtotal),
                ];
            }
            $orden = OrdenEmpresarial::create([
                'cliente_id' => $data['cliente_id'], 'usuario_id' => $request->user()->id,
                'fecha' => now(), 'estado' => OrdenEmpresarial::PENDIENTE, 'total' => $this->decimal($totalCentavos),
            ]);
            $orden->detalles()->createMany($detalles);
            Auditoria::registrar($request->user(), 'crear_orden_erp', 'orden_empresarial', $orden->id, "Creó {$orden->folio()}.");

            return $orden;
        });
    }

    private function decimal(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    private function query(Request $request): Builder
    {
        $estado = $request->string('estado')->toString();
        $buscar = $request->string('buscar')->trim()->toString();

        return OrdenEmpresarial::query()->with(['cliente', 'usuario:id,name'])
            ->when(in_array($estado, OrdenEmpresarial::ESTADOS, true), fn ($query) => $query->where('estado', $estado))
            ->when($buscar, fn ($query) => $query->where(function ($query) use ($buscar) {
                $query->whereHas('cliente', fn ($cliente) => $cliente->where('nombre', 'like', "%{$buscar}%")->orWhere('empresa', 'like', "%{$buscar}%"));
                $folio = preg_replace('/^ORD-0*/i', '', $buscar);
                if (ctype_digit($folio)) $query->orWhere('id', (int) $folio);
            }))
            ->latest('id');
    }
}
