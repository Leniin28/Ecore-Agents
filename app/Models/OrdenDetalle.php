<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['orden_empresarial_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal'])]
class OrdenDetalle extends Model
{
    public function ordenEmpresarial(): BelongsTo
    {
        return $this->belongsTo(OrdenEmpresarial::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'precio_unitario' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }
}
