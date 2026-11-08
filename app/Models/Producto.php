<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'nombre',
    'descripcion',
    'categoria',
    'categoria_id',
    'stock_actual',
    'stock_minimo',
    'proveedor_id',
    'costo_unitario',
    'precio_venta_unitario',
    'estrategia_logistica',
    'visible_tienda',
    'destacado',
    'imagen_url',
])]
class Producto extends Model
{
    use HasFactory;

    public const ESTRATEGIA_PUSH = 'PUSH';

    public const ESTRATEGIA_PULL = 'PULL';

    public const ESTRATEGIAS = [self::ESTRATEGIA_PUSH, self::ESTRATEGIA_PULL];

    protected static function booted(): void
    {
        static::saving(function (Producto $producto) {
            // Sincronización bidireccional segura entre categoría de texto y relación normalizada
            if ($producto->categoria_id && empty($producto->categoria)) {
                $categoria = Categoria::find($producto->categoria_id);
                if ($categoria) {
                    $producto->categoria = $categoria->nombre;
                }
            } elseif (! empty($producto->categoria) && empty($producto->categoria_id)) {
                $categoria = Categoria::firstOrCreate(
                    ['nombre' => $producto->categoria],
                    ['slug' => Str::slug($producto->categoria) ?: 'cat-'.Str::random(6), 'activo' => true]
                );
                $producto->categoria_id = $categoria->id;
            }
        });
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function categoriaRelacion(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function ordenDetalles(): HasMany
    {
        return $this->hasMany(OrdenDetalle::class);
    }

    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_producto');
    }

    public function carritoDetalles(): HasMany
    {
        return $this->hasMany(CarritoDetalle::class);
    }

    public function scopeVisibleTienda($query)
    {
        return $query->where('visible_tienda', true);
    }

    public function scopeDestacados($query)
    {
        return $query->where('visible_tienda', true)->where('destacado', true);
    }

    public function getPrecioVentaCentavosAttribute(): int
    {
        $precio = (string) $this->precio_venta_unitario;
        if (! str_contains($precio, '.')) {
            return (int) $precio * 100;
        }
        [$entero, $decimal] = explode('.', $precio);

        return ((int) $entero * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0', STR_PAD_RIGHT);
    }

    protected function casts(): array
    {
        return [
            'categoria_id' => 'integer',
            'stock_actual' => 'integer',
            'stock_minimo' => 'integer',
            'costo_unitario' => 'decimal:2',
            'precio_venta_unitario' => 'decimal:2',
            'visible_tienda' => 'boolean',
            'destacado' => 'boolean',
        ];
    }
}
