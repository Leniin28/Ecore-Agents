<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\OrdenDetalle;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use App\Models\ProcesoInterno;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TiendaCheckoutService
{
    public function __construct(
        protected TiendaCarritoService $carritoService,
        protected InventarioService $inventarioService,
        protected MonederoService $monederoService
    ) {}

    /**
     * Procesa la compra completa de extremo a extremo dentro de una sola transacción atómica.
     * Revalida productos, stock, precios, promociones, impuestos y saldos en el momento definitivo.
     *
     * @param  array<string, mixed>  $datosEntrega
     * @return array{orden: OrdenEmpresarial, pago: Pago}
     */
    public function procesarCompra(
        User $usuario,
        array $datosEntrega,
        string $metodoPago,
        ?string $codigoPromocion = null,
        ?string $idempotencyKey = null,
        bool $simularRechazo = false
    ): array {
        if (! array_key_exists($metodoPago, Pago::METODOS)) {
            throw ValidationException::withMessages(['metodo' => 'El método de pago seleccionado no es válido.']);
        }

        $cliente = $usuario->obtenerCliente();

        // Protección frente a doble clic o reintentos mediante clave de idempotencia
        if ($idempotencyKey) {
            $pagoExistente = Pago::query()
                ->where('referencia', $idempotencyKey)
                ->where('cliente_id', $cliente->id)
                ->first();

            if ($pagoExistente) {
                return [
                    'orden' => $pagoExistente->ordenEmpresarial,
                    'pago' => $pagoExistente,
                ];
            }
        }

        return DB::transaction(function () use ($usuario, $cliente, $datosEntrega, $metodoPago, $codigoPromocion, $idempotencyKey, $simularRechazo) {
            $carrito = $this->carritoService->obtenerCarrito($usuario);
            $detalles = $carrito->detalles()->with('producto')->get();

            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages(['carrito' => 'Tu carrito de compras está vacío. Agrega planes o complementos antes de pagar.']);
            }

            // Bloqueo pesimista de productos para validar stock y evitar condiciones de carrera
            $productoIds = $detalles->pluck('producto_id')->all();
            $productos = Producto::query()
                ->whereIn('id', $productoIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle->producto_id);
                if (! $producto || ! $producto->visible_tienda) {
                    throw ValidationException::withMessages([
                        'carrito' => 'Uno de los productos en tu carrito ya no está disponible en la tienda comercial.',
                    ]);
                }

                if ($producto->stock_actual < $detalle->cantidad) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock insuficiente para {$producto->nombre}. Hay {$producto->stock_actual} cupos disponibles y tienes {$detalle->cantidad} en tu carrito.",
                    ]);
                }
            }

            // Recálculo formal en servidor de todos los importes, promociones e impuestos
            $totales = $this->carritoService->calcularTotales($carrito, $codigoPromocion);
            $subtotalCentavos = (int) $totales['subtotal_centavos'];
            $descuentoCentavos = (int) $totales['descuento_centavos'];
            $baseGravableCentavos = (int) $totales['base_gravable_centavos'];
            $impuestoCentavos = (int) $totales['total_impuestos_centavos'];
            $totalCentavos = (int) $totales['total_centavos'];

            // Reproducir rechazo de pago demo a petición del usuario
            if ($simularRechazo) {
                throw ValidationException::withMessages([
                    'pago' => 'El pago demo fue rechazado según lo solicitado para fines de prueba. No se realizó ningún cargo ni se modificó el inventario.',
                ]);
            }

            // Si el método es monedero, verificar y bloquear saldo
            $monedero = null;
            if ($metodoPago === Pago::METODO_MONEDERO) {
                $monedero = $this->monederoService->obtenerMonedero($cliente);
                if ($monedero->saldo_centavos < $totalCentavos) {
                    throw ValidationException::withMessages([
                        'metodo' => "Saldo insuficiente en tu monedero demo. Saldo disponible: {$monedero->saldo_formateado}. Total requerido: {$totales['total_formateado']}.",
                    ]);
                }
            }

            // Las transferencias demo se registran como pendientes de comprobación en este flujo;
            // tarjeta demo, PayPal demo y monedero demo se confirman y procesan inmediatamente.
            $esPendiente = ($metodoPago === Pago::METODO_TRANSFERENCIA);
            $estadoOrden = $esPendiente ? OrdenEmpresarial::PENDIENTE : OrdenEmpresarial::PROCESADA;
            $estadoPago = $esPendiente ? Pago::ESTADO_PENDIENTE : Pago::ESTADO_APROBADO;

            $totalDecimal = number_format($totalCentavos / 100, 2, '.', '');

            // 1. Crear Orden Empresarial ERP
            $orden = OrdenEmpresarial::create([
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'fecha' => now(),
                'estado' => $estadoOrden,
                'total' => $totalDecimal,
                'procesada_at' => $esPendiente ? null : now(),
            ]);

            // 2. Crear detalles de orden con precios y configuración históricos
            $detallesCreados = [];
            foreach ($totales['lineas'] as $linea) {
                $precioUnitarioDecimal = number_format($linea['precio_unitario_centavos'] / 100, 2, '.', '');
                $subtotalLineaDecimal = number_format($linea['subtotal_linea_centavos'] / 100, 2, '.', '');

                $detalleOrden = $orden->detalles()->create([
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $precioUnitarioDecimal,
                    'subtotal' => $subtotalLineaDecimal,
                    'configuracion' => $linea['configuracion'],
                ]);

                $detallesCreados[] = [
                    'modelo' => $detalleOrden,
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                ];
            }

            // Registrar procesos internos de la orden
            $this->registrarProceso($orden, $usuario, 'orden_creada', 'Orden generada desde la tienda en línea');

            // 3. Si la orden fue procesada inmediatamente, descontar inventario SCM
            if (! $esPendiente) {
                $this->registrarProceso($orden, $usuario, 'stock_validado', 'Disponibilidad de cupos confirmada');

                foreach ($detallesCreados as $item) {
                    $this->inventarioService->registrar(
                        $item['producto_id'],
                        $usuario,
                        [
                            'tipo' => MovimientoInventario::TIPO_SALIDA,
                            'cantidad' => $item['cantidad'],
                            'motivo' => MovimientoInventario::MOTIVO_VENTA,
                            'fecha' => now(),
                        ],
                        $item['modelo']->id
                    );
                }

                $this->registrarProceso($orden, $usuario, 'inventario_actualizado', 'Inventario SCM actualizado');
                $this->registrarProceso($orden, $usuario, 'movimientos_registrados', 'Movimientos de salida registrados');
                $this->registrarProceso($orden, $usuario, 'orden_procesada', 'Compra completada exitosamente');
            } else {
                $this->registrarProceso($orden, $usuario, 'pago_pendiente_transferencia', 'Esperando validación de transferencia demo');
            }

            // 4. Generar referencia UUID única para el pago
            $referencia = $idempotencyKey ?: (string) Str::uuid();

            // 5. Aplicar cargo a monedero si se utilizó dicho método
            $movimientoMonedero = null;
            if ($metodoPago === Pago::METODO_MONEDERO && $monedero) {
                $movimientoMonedero = $this->monederoService->debitarParaCompra(
                    $monedero,
                    $usuario,
                    $totalCentavos,
                    "Pago de orden {$orden->folio()}",
                    $referencia
                );
            }

            // 6. Si se aplicó una promoción válida, incrementar sus usos
            if (! empty($totales['promocion'])) {
                $totales['promocion']->increment('usos_actuales');
            }

            // 7. Crear el registro del Pago con su desglose histórico inmutable
            $pago = Pago::create([
                'orden_empresarial_id' => $orden->id,
                'cliente_id' => $cliente->id,
                'referencia' => $referencia,
                'metodo' => $metodoPago,
                'estado' => $estadoPago,
                'subtotal_centavos' => $subtotalCentavos,
                'descuento_centavos' => $descuentoCentavos,
                'base_gravable_centavos' => $baseGravableCentavos,
                'impuesto_centavos' => $impuestoCentavos,
                'total_centavos' => $totalCentavos,
                'promocion_id' => $totales['promocion']?->id,
                'desglose' => [
                    'lineas' => $totales['lineas'],
                    'subtotal_centavos' => $subtotalCentavos,
                    'descuento_centavos' => $descuentoCentavos,
                    'codigo_promocion' => $totales['codigo_promocion'],
                    'base_gravable_centavos' => $baseGravableCentavos,
                    'impuestos' => $totales['impuestos'],
                    'total_centavos' => $totalCentavos,
                ],
                'datos_entrega' => $datosEntrega,
            ]);

            // Vincular pago al movimiento de monedero si aplica
            if ($movimientoMonedero) {
                $movimientoMonedero->update(['pago_id' => $pago->id]);
            }

            // 8. Vaciar carrito e incrementar versión para invalidar estados obsoletos
            $carrito->detalles()->delete();
            $carrito->increment('version');

            // 9. Registrar auditoría general
            Auditoria::registrar(
                $usuario,
                'compra_tienda',
                'orden_empresarial',
                $orden->id,
                "Registró compra comercial {$orden->folio()} por {$pago->total_formateado} con método {$pago->metodoLabel()}."
            );

            return [
                'orden' => $orden->fresh(['detalles.producto', 'cliente', 'pago']),
                'pago' => $pago->fresh(['ordenEmpresarial', 'cliente']),
            ];
        }, 3);
    }

    private function registrarProceso(OrdenEmpresarial $orden, User $usuario, string $tipo, ?string $descripcion = null): void
    {
        $orden->procesosInternos()->create([
            'usuario_id' => $usuario->id,
            'tipo' => $tipo,
            'descripcion' => $descripcion ?? (ProcesoInterno::TIPOS[$tipo] ?? $tipo),
            'estado' => 'completado',
            'fecha' => now(),
        ]);
    }
}
