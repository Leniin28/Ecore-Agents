<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Pedido;
use App\Models\Producto;

class ScmReposicionService
{
    public function generarPushSiCorresponde(Producto $producto): ?Pedido
    {
        if ($producto->estrategia_logistica !== Producto::ESTRATEGIA_PUSH
            || $producto->stock_actual > $producto->stock_minimo
            || $producto->stock_minimo < 1) {
            return null;
        }

        $existente = Pedido::query()
            ->where('producto_id', $producto->id)
            ->where('estado', Pedido::ESTADO_PENDIENTE)
            ->where('tipo', Pedido::TIPO_REPOSICION)
            ->where('origen', Pedido::ORIGEN_AUTOMATICO_PUSH)
            ->first();

        if ($existente) {
            return $existente;
        }

        $pedido = Pedido::create([
            'producto_id' => $producto->id,
            'usuario_id' => null,
            'cantidad' => $producto->stock_minimo,
            'tipo' => Pedido::TIPO_REPOSICION,
            'estado' => Pedido::ESTADO_PENDIENTE,
            'origen' => Pedido::ORIGEN_AUTOMATICO_PUSH,
        ]);

        Auditoria::registrar(null, 'pedido_push_automatico', 'pedido', $pedido->id,
            "El sistema generó un pedido PUSH para {$producto->nombre}.");

        return $pedido;
    }
}
