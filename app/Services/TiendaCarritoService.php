<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\CarritoDetalle;
use App\Models\Cliente;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TiendaCarritoService
{
    /**
     * Obtiene o crea el carrito persistente para el cliente del usuario autenticado.
     */
    public function obtenerCarrito(User $user): Carrito
    {
        $cliente = $user->obtenerCliente();

        return Carrito::firstOrCreate(
            ['cliente_id' => $cliente->id],
            ['version' => 1]
        );
    }

    /**
     * Agrega un producto o plan configurado al carrito del cliente.
     * Si la línea ya existe con la misma configuración equivalente, incrementa la cantidad.
     */
    public function agregarProducto(User $user, int $productoId, int $cantidad = 1, ?array $modulosSeleccionados = null): CarritoDetalle
    {
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad mínima es 1 unidad.']);
        }

        $producto = Producto::query()->where('visible_tienda', true)->findOrFail($productoId);

        if ($producto->stock_actual < 1) {
            throw ValidationException::withMessages(['stock' => "El producto {$producto->nombre} está agotado."]);
        }

        $carrito = $this->obtenerCarrito($user);

        // Procesar configuración de módulos si aplica (ej. desde el configurador personalizado)
        [$configuracion, $configHash] = $this->procesarConfiguracion($producto, $modulosSeleccionados);

        /** @var CarritoDetalle|null $detalleExistente */
        $detalleExistente = $carrito->detalles()
            ->where('producto_id', $producto->id)
            ->where('configuracion_hash', $configHash)
            ->first();

        $cantidadActualEnCarrito = (int) $carrito->detalles()
            ->where('producto_id', $producto->id)
            ->sum('cantidad');

        $cantidadTotalRequerida = $cantidadActualEnCarrito + $cantidad;

        if ($cantidadTotalRequerida > $producto->stock_actual) {
            $disponible = max(0, $producto->stock_actual - $cantidadActualEnCarrito);
            throw ValidationException::withMessages([
                'cantidad' => "Solo hay {$producto->stock_actual} unidades disponibles de {$producto->nombre}.".($disponible > 0 ? " Puedes agregar hasta {$disponible} más en total." : ' Ya tienes el cupo máximo disponible en tu carrito entre tus configuraciones.'),
            ]);
        }

        if ($detalleExistente) {
            $detalleExistente->update(['cantidad' => $detalleExistente->cantidad + $cantidad]);

            return $detalleExistente->fresh();
        }

        return $carrito->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'configuracion' => $configuracion,
            'configuracion_hash' => $configHash,
        ]);
    }

    /**
     * Actualiza la cantidad de una línea del carrito.
     */
    public function actualizarCantidad(User $user, int $detalleId, int $cantidad): CarritoDetalle
    {
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser de al menos 1 unidad.']);
        }

        $carrito = $this->obtenerCarrito($user);
        $detalle = $carrito->detalles()->with('producto')->findOrFail($detalleId);

        $otrasLineasCantidad = (int) $carrito->detalles()
            ->where('producto_id', $detalle->producto_id)
            ->where('id', '!=', $detalle->id)
            ->sum('cantidad');

        $cantidadTotalRequerida = $otrasLineasCantidad + $cantidad;

        if ($cantidadTotalRequerida > $detalle->producto->stock_actual) {
            $disponible = max(0, $detalle->producto->stock_actual - $otrasLineasCantidad);
            throw ValidationException::withMessages([
                'cantidad' => "Stock insuficiente: solo hay {$detalle->producto->stock_actual} unidades disponibles de {$detalle->producto->nombre} (tienes {$otrasLineasCantidad} en otra configuración). Máximo permitido para esta línea: {$disponible}.",
            ]);
        }

        $detalle->update(['cantidad' => $cantidad]);

        return $detalle->fresh();
    }

    /**
     * Elimina una línea del carrito.
     */
    public function eliminarDetalle(User $user, int $detalleId): void
    {
        $carrito = $this->obtenerCarrito($user);
        $carrito->detalles()->where('id', $detalleId)->delete();
    }

    /**
     * Vacía el carrito completo del usuario.
     */
    public function vaciar(User $user): void
    {
        $carrito = $this->obtenerCarrito($user);
        $carrito->detalles()->delete();
    }

    /**
     * Calcula importes completos en el servidor:
     * subtotal, descuento por promoción, base gravable, impuestos y total.
     * Todo en centavos enteros con redondeo consistente.
     *
     * @return array<string, mixed>
     */
    public function calcularTotales(Carrito $carrito, ?string $codigoPromocion = null): array
    {
        $detalles = $carrito->detalles()->with('producto')->get();
        $lineas = [];
        $subtotalCentavos = 0;
        $totalItems = 0;
        $alertasStock = [];

        foreach ($detalles as $detalle) {
            $producto = $detalle->producto;
            $stockDisponible = $producto->stock_actual;

            if ($stockDisponible < $detalle->cantidad) {
                $alertasStock[] = "El stock de {$producto->nombre} cambió a {$stockDisponible} unidades disponibles.";
            }

            // Precio base del producto en centavos
            $precioBaseCentavos = $producto->precio_venta_centavos;
            $extrasCentavos = (int) ($detalle->configuracion['extras_total_centavos'] ?? 0);
            $precioUnitarioCentavos = $precioBaseCentavos + $extrasCentavos;
            $subtotalLineaCentavos = $precioUnitarioCentavos * $detalle->cantidad;

            $subtotalCentavos += $subtotalLineaCentavos;
            $totalItems += $detalle->cantidad;

            $lineas[] = [
                'detalle_id' => $detalle->id,
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'categoria' => $producto->categoria,
                'imagen_url' => $producto->imagen_url,
                'cantidad' => $detalle->cantidad,
                'stock_actual' => $stockDisponible,
                'precio_base_centavos' => $precioBaseCentavos,
                'extras_centavos' => $extrasCentavos,
                'precio_unitario_centavos' => $precioUnitarioCentavos,
                'subtotal_linea_centavos' => $subtotalLineaCentavos,
                'configuracion' => $detalle->configuracion,
            ];
        }

        // Evaluar promoción si se proporcionó código
        $promocion = null;
        $descuentoCentavos = 0;
        $mensajePromocion = null;
        $promocionValida = false;

        if ($codigoPromocion) {
            $promocion = Promocion::query()->where('codigo', strtoupper(trim($codigoPromocion)))->first();

            if (! $promocion) {
                $mensajePromocion = 'El código de promoción no existe.';
            } elseif (! $promocion->isVigente()) {
                $mensajePromocion = 'El código de promoción está vencido o inactivo.';
            } else {
                // Calcular base aplicable (solo productos autorizados en la promoción)
                $baseAplicableCentavos = 0;
                foreach ($lineas as $linea) {
                    if ($promocion->aplicaAProducto($linea['producto_id'])) {
                        $baseAplicableCentavos += $linea['subtotal_linea_centavos'];
                    }
                }

                if ($baseAplicableCentavos <= 0) {
                    $mensajePromocion = 'La promoción no aplica a ninguno de los productos en tu carrito.';
                } else {
                    $descuentoCentavos = $promocion->calcularDescuento($baseAplicableCentavos);
                    $descuentoCentavos = min($subtotalCentavos, $descuentoCentavos);
                    $promocionValida = true;
                    $mensajePromocion = "Promoción aplicada: {$promocion->nombre} ({$promocion->etiquetaValor()})";
                }
            }
        }

        // Base gravable: subtotal después del descuento aplicable
        $baseGravableCentavos = max(0, $subtotalCentavos - $descuentoCentavos);

        // Impuestos configurados y activos calculados sobre la base gravable
        $impuestosActivos = Impuesto::query()->where('activo', true)->get();
        $impuestosDesglose = [];
        $totalImpuestosCentavos = 0;

        foreach ($impuestosActivos as $impuesto) {
            $montoCentavos = $impuesto->calcular($baseGravableCentavos);
            $totalImpuestosCentavos += $montoCentavos;
            $impuestosDesglose[] = [
                'id' => $impuesto->id,
                'nombre' => $impuesto->nombre,
                'puntos_base' => $impuesto->puntos_base,
                'porcentaje' => $impuesto->porcentajeFormateado(),
                'monto_centavos' => $montoCentavos,
                'monto_formateado' => '$'.number_format($montoCentavos / 100, 2).' MXN',
            ];
        }

        $totalCentavos = $baseGravableCentavos + $totalImpuestosCentavos;

        return [
            'total_items' => $totalItems,
            'lineas' => $lineas,
            'subtotal_centavos' => $subtotalCentavos,
            'subtotal_formateado' => '$'.number_format($subtotalCentavos / 100, 2).' MXN',
            'promocion' => $promocionValida ? $promocion : null,
            'codigo_promocion' => $promocionValida ? $promocion->codigo : null,
            'descuento_centavos' => $descuentoCentavos,
            'descuento_formateado' => '$'.number_format($descuentoCentavos / 100, 2).' MXN',
            'mensaje_promocion' => $mensajePromocion,
            'promocion_valida' => $promocionValida,
            'base_gravable_centavos' => $baseGravableCentavos,
            'base_gravable_formateado' => '$'.number_format($baseGravableCentavos / 100, 2).' MXN',
            'impuestos' => $impuestosDesglose,
            'total_impuestos_centavos' => $totalImpuestosCentavos,
            'total_impuestos_formateado' => '$'.number_format($totalImpuestosCentavos / 100, 2).' MXN',
            'total_centavos' => $totalCentavos,
            'total_formateado' => '$'.number_format($totalCentavos / 100, 2).' MXN',
            'alertas_stock' => $alertasStock,
        ];
    }

    /**
     * Valida del lado del servidor los módulos seleccionados para un plan configurable.
     * Calcula los precios exclusivamente desde config/ecore.php para evitar manipulación.
     *
     * @return array{0: array|null, 1: string}
     */
    private function procesarConfiguracion(Producto $producto, ?array $modulosSeleccionados): array
    {
        if (empty($modulosSeleccionados)) {
            return [null, ''];
        }

        // Obtener el catálogo oficial de módulos de config/ecore.php
        $catalogoModulos = [];
        foreach (config('ecore.modules', []) as $tier => $tierModules) {
            foreach ($tierModules as $item) {
                $catalogoModulos[$item['name']] = [
                    'name' => $item['name'],
                    'price' => (int) $item['price'],
                    'tier' => $tier,
                ];
            }
        }

        $modulosValidados = [];
        $extrasCentavos = 0;

        foreach ($modulosSeleccionados as $nombreModulo) {
            $nombreLimpio = trim((string) $nombreModulo);
            if (isset($catalogoModulos[$nombreLimpio])) {
                $def = $catalogoModulos[$nombreLimpio];
                $modulosValidados[] = $def;
                $extrasCentavos += ($def['price'] * 100);
            }
        }

        if (empty($modulosValidados)) {
            return [null, ''];
        }

        // Ordenar nombres alfabéticamente para asegurar un hash determinista
        usort($modulosValidados, fn ($a, $b) => strcmp($a['name'], $b['name']));
        $nombresOrdenados = array_column($modulosValidados, 'name');
        $configHash = md5(json_encode($nombresOrdenados));

        $configuracion = [
            'plan_base' => $producto->nombre,
            'modulos' => $modulosValidados,
            'extras_total_centavos' => $extrasCentavos,
            'extras_total_formateado' => '$'.number_format($extrasCentavos / 100, 2).' MXN',
        ];

        return [$configuracion, $configHash];
    }
}
