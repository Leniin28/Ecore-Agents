<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    /** Punto común para entradas y salidas SCM, surtidos y órdenes ERP. */
    public function registrar(int $productoId, User $usuario, array $data, ?int $ordenDetalleId = null): MovimientoInventario
    {
        return DB::transaction(function () use ($productoId, $usuario, $data, $ordenDetalleId) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($productoId);
            $cantidad = (int) $data['cantidad'];
            if ($cantidad < 1 || ! in_array($data['tipo'], MovimientoInventario::TIPOS, true)) {
                throw ValidationException::withMessages(['cantidad' => 'El movimiento debe tener un tipo válido y una cantidad positiva.']);
            }
            if ($data['tipo'] === MovimientoInventario::TIPO_SALIDA) {
                // La condición SQL también protege frente a cambios concurrentes de stock.
                $actualizado = Producto::query()->whereKey($producto->id)->where('stock_actual', '>=', $cantidad)
                    ->decrement('stock_actual', $cantidad);
                if ($actualizado !== 1) {
                    throw ValidationException::withMessages(['cantidad' => "Stock insuficiente para {$producto->nombre}. La salida no puede dejar el stock en negativo."]);
                }
            } else {
                $producto->increment('stock_actual', $cantidad);
            }

            $movimiento = $producto->movimientos()->create([
                'usuario_id' => $usuario->id, 'tipo' => $data['tipo'], 'cantidad' => $cantidad,
                'motivo' => $data['motivo'], 'fecha' => $data['fecha'], 'orden_detalle_id' => $ordenDetalleId,
            ]);
            if ($movimiento->tipo === MovimientoInventario::TIPO_SALIDA) {
                app(ScmReposicionService::class)->generarPushSiCorresponde($producto->fresh());
            }
            Auditoria::registrar($usuario, 'registrar_movimiento', 'movimiento_inventario', $movimiento->id,
                "Registró {$movimiento->tipo} de {$movimiento->cantidad} unidades para {$producto->nombre}.");

            return $movimiento;
        });
    }
}
