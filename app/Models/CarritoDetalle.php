<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['carrito_id', 'producto_id', 'cantidad', 'configuracion', 'configuracion_hash'])]
class CarritoDetalle extends Model
{
    use HasFactory;

    protected $table = 'carrito_detalles';

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'configuracion' => 'array',
        ];
    }
}
