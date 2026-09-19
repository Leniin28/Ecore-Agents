<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'usuario_id', 'cantidad', 'tipo', 'estado', 'origen'])]
class Pedido extends Model
{
    use HasFactory;

    public const TIPO_REPOSICION = 'reposicion';

    public const TIPO_VENTA = 'venta';

    public const TIPOS = [self::TIPO_REPOSICION, self::TIPO_VENTA];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_SURTIDO = 'surtido';

    public const ESTADOS = [self::ESTADO_PENDIENTE, self::ESTADO_SURTIDO];

    public const ORIGEN_AUTOMATICO_PUSH = 'automatico_push';

    public const ORIGEN_MANUAL = 'manual';

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
        return ['cantidad' => 'integer'];
    }
}
