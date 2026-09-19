<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'usuario_id', 'tipo', 'cantidad', 'motivo', 'fecha'])]
class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SALIDA = 'salida';

    public const TIPOS = [self::TIPO_ENTRADA, self::TIPO_SALIDA];

    public const MOTIVO_VENTA = 'venta';

    public const MOTIVO_AJUSTE = 'ajuste';

    public const MOTIVO_REPOSICION = 'reposicion';

    public const MOTIVOS = [self::MOTIVO_VENTA, self::MOTIVO_AJUSTE, self::MOTIVO_REPOSICION];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'fecha' => 'datetime'];
    }
}
