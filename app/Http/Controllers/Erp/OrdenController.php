<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\ProcesoInterno;
use App\Services\InventarioService;
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
        return view('erp.ordenes.show', ['orden' => $orden->load(['cliente', 'usuario', 'detalles.producto', 'procesosInternos.usuario'])]);
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
        return response()->json(['data' => $orden->load(['cliente', 'usuario:id,name', 'detalles.producto', 'procesosInternos'])]);
    }

    public function procesar(Request $request, OrdenEmpresarial $orden): RedirectResponse
    {
        $this->procesarOrden($request, $orden);

        return redirect()->route('erp.ordenes.exito', $orden);
    }

    public function apiProcesar(Request $request, OrdenEmpresarial $orden): JsonResponse
    {
        return response()->json(['data' => $this->procesarOrden($request, $orden)->load('detalles.movimiento')]);
    }

    public function exito(OrdenEmpresarial $orden): View
    {
        abort_unless($orden->estado === OrdenEmpresarial::PROCESADA, 404);

        return view('erp.ordenes.exito', ['orden' => $orden->load(['cliente', 'detalles.movimiento', 'procesosInternos'])]);
    }

    public function cancelar(Request $request, OrdenEmpresarial $orden): RedirectResponse
    {
        $this->cancelarOrden($request, $orden);

        return redirect()->route('erp.ordenes.show', $orden)->with('success', 'Orden cancelada correctamente.');
    }

    public function apiCancelar(Request $request, OrdenEmpresarial $orden): JsonResponse
    {
        return response()->json(['data' => $this->cancelarOrden($request, $orden)]);
    }

    private function cancelarOrden(Request $request, OrdenEmpresarial $orden): OrdenEmpresarial
    {
        return DB::transaction(function () use ($request, $orden) {
            $cambio = OrdenEmpresarial::query()->whereKey($orden->id)->where('estado', OrdenEmpresarial::PENDIENTE)
                ->update(['estado' => OrdenEmpresarial::CANCELADA]);
            if ($cambio !== 1) {
                throw ValidationException::withMessages(['estado' => 'Solo se puede cancelar una orden pendiente.']);
            }
            Auditoria::registrar($request->user(), 'cancelar_orden_erp', 'orden_empresarial', $orden->id, "Canceló {$orden->folio()}.");
            $this->registrarProceso($orden, $request, 'orden_cancelada');

            return $orden->fresh();
        });
    }

    private function procesarOrden(Request $request, OrdenEmpresarial $orden): OrdenEmpresarial
    {
        return DB::transaction(function () use ($request, $orden) {
            $orden = OrdenEmpresarial::query()->lockForUpdate()->findOrFail($orden->id);
            if ($orden->estado !== OrdenEmpresarial::PENDIENTE) {
                throw ValidationException::withMessages(['estado' => 'Solo se puede procesar una orden pendiente. Esta orden ya fue procesada o cancelada.']);
            }
            $detalles = $orden->detalles()->orderBy('producto_id')->get();
            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages(['detalles' => 'La orden no tiene recursos.']);
            }
            $productos = Producto::query()->whereIn('id', $detalles->pluck('producto_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle->producto_id);
                if (! $producto || $producto->stock_actual < $detalle->cantidad) {
                    throw ValidationException::withMessages(['stock' => 'Stock insuficiente para '.($producto?->nombre ?? 'un recurso').'. No se descontó ninguna línea.']);
                }
            }
            $this->registrarProceso($orden, $request, 'stock_validado');
            // Reclamo condicional: otro intento no puede procesar la misma orden.
            $cambio = OrdenEmpresarial::query()->whereKey($orden->id)->where('estado', OrdenEmpresarial::PENDIENTE)
                ->update(['estado' => OrdenEmpresarial::PROCESADA, 'procesada_at' => now()]);
            if ($cambio !== 1) {
                throw ValidationException::withMessages(['estado' => 'La orden ya fue procesada o cancelada.']);
            }
            foreach ($detalles as $detalle) {
                app(InventarioService::class)->registrar($detalle->producto_id, $request->user(), [
                    'tipo' => MovimientoInventario::TIPO_SALIDA, 'cantidad' => $detalle->cantidad,
                    'motivo' => MovimientoInventario::MOTIVO_VENTA, 'fecha' => now(),
                ], $detalle->id);
            }
            Auditoria::registrar($request->user(), 'procesar_orden_erp', 'orden_empresarial', $orden->id, "Procesó {$orden->folio()} y actualizó el inventario SCM.");
            $this->registrarProceso($orden, $request, 'inventario_actualizado');
            $this->registrarProceso($orden, $request, 'movimientos_registrados');
            $this->registrarProceso($orden, $request, 'orden_procesada');

            return $orden->fresh();
        }, 3);
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
            $this->registrarProceso($orden, $request, 'orden_creada');
            Auditoria::registrar($request->user(), 'crear_orden_erp', 'orden_empresarial', $orden->id, "Creó {$orden->folio()}.");

            return $orden;
        });
    }

    private function registrarProceso(OrdenEmpresarial $orden, Request $request, string $tipo): void
    {
        $orden->procesosInternos()->create([
            'usuario_id' => $request->user()->id, 'tipo' => $tipo,
            'descripcion' => ProcesoInterno::TIPOS[$tipo], 'estado' => 'completado', 'fecha' => now(),
        ]);
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
