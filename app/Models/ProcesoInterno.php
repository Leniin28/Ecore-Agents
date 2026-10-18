<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['orden_empresarial_id', 'usuario_id', 'tipo', 'descripcion', 'estado', 'fecha'])]
class ProcesoInterno extends Model
{
    protected $table = 'procesos_internos';

    public const TIPOS = [
        'orden_creada' => 'Orden creada', 'stock_validado' => 'Stock validado',
        'inventario_actualizado' => 'Inventario actualizado', 'movimientos_registrados' => 'Movimientos registrados',
        'orden_procesada' => 'Orden procesada', 'orden_cancelada' => 'Orden cancelada',
    ];

    public function ordenEmpresarial(): BelongsTo
    {
        return $this->belongsTo(OrdenEmpresarial::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }
}
