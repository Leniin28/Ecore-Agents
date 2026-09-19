<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'categoria', 'stock_actual', 'stock_minimo', 'proveedor_id', 'costo_unitario', 'estrategia_logistica'])]
class Producto extends Model
{
    use HasFactory;

    public const ESTRATEGIA_PUSH = 'PUSH';

    public const ESTRATEGIA_PULL = 'PULL';

    public const ESTRATEGIAS = [self::ESTRATEGIA_PUSH, self::ESTRATEGIA_PULL];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    protected function casts(): array
    {
        return [
            'stock_actual' => 'integer',
            'stock_minimo' => 'integer',
            'costo_unitario' => 'decimal:2',
        ];
    }
}
