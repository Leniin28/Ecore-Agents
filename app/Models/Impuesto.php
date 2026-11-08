<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'puntos_base', 'activo'])]
class Impuesto extends Model
{
    use HasFactory;

    protected $table = 'impuestos';

    /**
     * Calcula el impuesto en centavos para una base gravable en centavos.
     * puntos_base en unidades de 1/100 de 1% (1600 = 16.00%).
     */
    public function calcular(int $baseGravableCentavos): int
    {
        if ($baseGravableCentavos <= 0 || ! $this->activo) {
            return 0;
        }

        return (int) round(($baseGravableCentavos * $this->puntos_base) / 10000);
    }

    public function porcentajeFormateado(): string
    {
        return rtrim(rtrim(number_format($this->puntos_base / 100, 2), '0'), '.').'%';
    }

    protected function casts(): array
    {
        return [
            'puntos_base' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
