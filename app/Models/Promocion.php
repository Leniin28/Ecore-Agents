<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codigo',
    'nombre',
    'descripcion',
    'tipo_descuento',
    'valor',
    'fecha_inicio',
    'fecha_fin',
    'limite_usos',
    'usos_actuales',
    'activo',
])]
class Promocion extends Model
{
    use HasFactory;

    protected $table = 'promociones';

    public const TIPO_PORCENTAJE = 'porcentaje';

    public const TIPO_MONTO_FIJO = 'monto_fijo';

    public const TIPOS = [self::TIPO_PORCENTAJE, self::TIPO_MONTO_FIJO];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'promocion_producto');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function isVigente(): bool
    {
        if (! $this->activo) {
            return false;
        }

        $ahora = now();

        if ($ahora->lt($this->fecha_inicio) || $ahora->gt($this->fecha_fin)) {
            return false;
        }

        if ($this->limite_usos !== null && $this->usos_actuales >= $this->limite_usos) {
            return false;
        }

        return true;
    }

    public function aplicaAProducto(int $productoId): bool
    {
        if ($this->productos()->count() === 0) {
            return true;
        }

        return $this->productos()->where('productos.id', $productoId)->exists();
    }

    /**
     * Calcula el descuento en centavos sobre la base aplicable.
     * Documentación de unidades:
     * - 'porcentaje': valor en puntos base (ej. 1000 = 10.00%, 1500 = 15.00%).
     * - 'monto_fijo': valor en centavos enteros (ej. 5000 = $50.00 MXN).
     */
    public function calcularDescuento(int $baseCentavos): int
    {
        if ($baseCentavos <= 0) {
            return 0;
        }

        if ($this->tipo_descuento === self::TIPO_PORCENTAJE) {
            $descuento = (int) round(($baseCentavos * $this->valor) / 10000);
        } else {
            $descuento = (int) $this->valor;
        }

        return max(0, min($baseCentavos, $descuento));
    }

    public function etiquetaValor(): string
    {
        if ($this->tipo_descuento === self::TIPO_PORCENTAJE) {
            return ($this->valor / 100).'%';
        }

        return '$'.number_format($this->valor / 100, 2).' MXN';
    }

    protected function casts(): array
    {
        return [
            'valor' => 'integer',
            'limite_usos' => 'integer',
            'usos_actuales' => 'integer',
            'activo' => 'boolean',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }
}
