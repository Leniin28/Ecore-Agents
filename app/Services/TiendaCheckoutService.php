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
use App\Models\Promocion;
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

            // Agrupar cantidades requeridas por producto_id para validar stock conjunto
            // incluso cuando existan varias líneas con configuraciones diferentes del mismo producto.
            $cantidadesTotalesPorProducto = [];
            foreach ($detalles as $detalle) {
                $cantidadesTotalesPorProducto[$detalle->producto_id] = ($cantidadesTotalesPorProducto[$detalle->producto_id] ?? 0) + $detalle->cantidad;
            }

            foreach ($cantidadesTotalesPorProducto as $prodId => $cantTotal) {
                $producto = $productos->get($prodId);
                if (! $producto || ! $producto->visible_tienda) {
                    throw ValidationException::withMessages([
                        'carrito' => 'Uno de los productos en tu carrito ya no está disponible en la tienda comercial.',
                    ]);
                }

                if ($producto->stock_actual < $cantTotal) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock insuficiente para {$producto->nombre}. Hay {$producto->stock_actual} cupos disponibles y requieres {$cantTotal} en conjunto.",
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

    /**
     * Confirma una transferencia bancaria demo pendiente, validando stock y actualizando SCM/ERP de manera atómica.
     */
    public function confirmarTransferencia(Pago $pago, User $admin): array
    {
        abort_unless($admin->isAdmin(), 403, 'Solo un administrador autorizado puede confirmar transferencias bancarias demo.');

        return DB::transaction(function () use ($pago, $admin) {
            $pago = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            $orden = OrdenEmpresarial::query()->lockForUpdate()->findOrFail($pago->orden_empresarial_id);

            // Idempotencia: si ya fue aprobada y procesada previamente, retornar sin duplicar movimientos ni procesos
            if ($pago->estado === Pago::ESTADO_APROBADO && $orden->estado === OrdenEmpresarial::PROCESADA) {
                return [
                    'orden' => $orden->fresh(['detalles.producto', 'cliente', 'pago']),
                    'pago' => $pago->fresh(['ordenEmpresarial', 'cliente']),
                ];
            }

            if ($pago->estado !== Pago::ESTADO_PENDIENTE || $orden->estado !== OrdenEmpresarial::PENDIENTE) {
                throw ValidationException::withMessages([
                    'transferencia' => "No es posible confirmar la transferencia porque se encuentra en estado '{$pago->estado}' y la orden en '{$orden->estado}'. Solo se pueden confirmar transferencias pendientes.",
                ]);
            }

            $detalles = $orden->detalles()->with('producto')->get();
            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'detalles' => 'La orden no contiene líneas de detalle para procesar.',
                ]);
            }

            // Agrupar cantidades por producto_id para revalidar stock conjunto con bloqueo pesimista
            $cantidadesTotales = [];
            foreach ($detalles as $detalle) {
                $cantidadesTotales[$detalle->producto_id] = ($cantidadesTotales[$detalle->producto_id] ?? 0) + $detalle->cantidad;
            }

            $productos = Producto::query()
                ->whereIn('id', array_keys($cantidadesTotales))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cantidadesTotales as $prodId => $cantReq) {
                $producto = $productos->get($prodId);
                if (! $producto || $producto->stock_actual < $cantReq) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock comercial insuficiente para confirmar la transferencia del producto '{$producto?->nombre}'. Se requieren {$cantReq} cupos y solo hay {$producto?->stock_actual} disponibles.",
                    ]);
                }
            }

            // Registrar procesos internos de validación de stock
            $this->registrarProceso($orden, $admin, 'stock_validado', 'Disponibilidad de cupos confirmada para transferencia');

            // Descontar inventario SCM para cada detalle
            foreach ($detalles as $detalle) {
                $this->inventarioService->registrar(
                    $detalle->producto_id,
                    $admin,
                    [
                        'tipo' => MovimientoInventario::TIPO_SALIDA,
                        'cantidad' => $detalle->cantidad,
                        'motivo' => MovimientoInventario::MOTIVO_VENTA,
                        'fecha' => now(),
                    ],
                    $detalle->id
                );
            }

            // Actualizar estado de la orden y del pago conjuntamente
            $orden->update([
                'estado' => OrdenEmpresarial::PROCESADA,
                'procesada_at' => now(),
            ]);

            $pago->update([
                'estado' => Pago::ESTADO_APROBADO,
            ]);

            // Procesos internos ERP
            $this->registrarProceso($orden, $admin, 'inventario_actualizado', 'Inventario SCM actualizado tras confirmar transferencia');
            $this->registrarProceso($orden, $admin, 'pago_transferencia_confirmado', 'Transferencia demo comprobada y autorizada por administración');
            $this->registrarProceso($orden, $admin, 'orden_procesada', 'Compra completada exitosamente');

            // Auditoría administrativa
            Auditoria::registrar(
                $admin,
                'confirmar_transferencia_demo',
                'orden_empresarial',
                $orden->id,
                "Confirmó la transferencia demo {$pago->referencia} de la orden {$orden->folio()} por {$pago->total_formateado}."
            );

            return [
                'orden' => $orden->fresh(['detalles.producto', 'cliente', 'pago']),
                'pago' => $pago->fresh(['ordenEmpresarial', 'cliente']),
            ];
        }, 3);
    }

    /**
     * Rechaza una transferencia bancaria demo pendiente, cancelando la orden y liberando la promoción si aplica.
     */
    public function rechazarTransferencia(Pago $pago, User $admin, ?string $motivo = null): array
    {
        abort_unless($admin->isAdmin(), 403, 'Solo un administrador autorizado puede rechazar transferencias bancarias demo.');

        return DB::transaction(function () use ($pago, $admin, $motivo) {
            $pago = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            $orden = OrdenEmpresarial::query()->lockForUpdate()->findOrFail($pago->orden_empresarial_id);

            // Idempotencia: si ya fue rechazada y cancelada, retornar sin cambios
            if ($pago->estado === Pago::ESTADO_RECHAZADO && $orden->estado === OrdenEmpresarial::CANCELADA) {
                return [
                    'orden' => $orden->fresh(['detalles.producto', 'cliente', 'pago']),
                    'pago' => $pago->fresh(['ordenEmpresarial', 'cliente']),
                ];
            }

            if ($pago->estado !== Pago::ESTADO_PENDIENTE || $orden->estado !== OrdenEmpresarial::PENDIENTE) {
                throw ValidationException::withMessages([
                    'transferencia' => "No es posible rechazar una transferencia en estado '{$pago->estado}'. Solo se pueden rechazar transferencias pendientes.",
                ]);
            }

            // Actualizar estados
            $orden->update([
                'estado' => OrdenEmpresarial::CANCELADA,
            ]);

            $pago->update([
                'estado' => Pago::ESTADO_RECHAZADO,
            ]);

            // Liberar cupo de promoción si fue aplicada y estaba reservada
            if ($pago->promocion_id) {
                $promo = Promocion::query()->lockForUpdate()->find($pago->promocion_id);
                if ($promo && $promo->usos_actuales > 0) {
                    $promo->decrement('usos_actuales');
                }
            }

            // Registrar procesos internos de rechazo
            $descripcionRechazo = $motivo ? "Transferencia rechazada: {$motivo}" : 'Comprobante de transferencia bancaria demo rechazado por la administración';
            $this->registrarProceso($orden, $admin, 'pago_transferencia_rechazado', $descripcionRechazo);
            $this->registrarProceso($orden, $admin, 'orden_cancelada', 'Orden comercial cancelada por rechazo de pago');

            // Auditoría
            Auditoria::registrar(
                $admin,
                'rechazar_transferencia_demo',
                'orden_empresarial',
                $orden->id,
                "Rechazó la transferencia demo {$pago->referencia} de la orden {$orden->folio()}." . ($motivo ? " Motivo: {$motivo}" : '')
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
