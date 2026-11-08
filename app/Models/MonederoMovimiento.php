<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'monedero_id',
    'pago_id',
    'usuario_id',
    'referencia',
    'tipo',
    'monto_centavos',
    'saldo_centavos',
    'concepto',
    'motivo',
])]
class MonederoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'monedero_movimientos';

    public const TIPO_RECARGA = 'recarga_demo';

    public const TIPO_PAGO = 'pago_compra';

    public const TIPO_AJUSTE = 'ajuste_admin';

    public const TIPOS = [
        self::TIPO_RECARGA => 'Recarga simulada',
        self::TIPO_PAGO => 'Pago de compra',
        self::TIPO_AJUSTE => 'Ajuste administrativo',
    ];

    public function monedero(): BelongsTo
    {
        return $this->belongsTo(Monedero::class);
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getMontoFormateadoAttribute(): string
    {
        $signo = $this->monto_centavos > 0 ? '+' : '';

        return $signo.'$'.number_format($this->monto_centavos / 100, 2).' MXN';
    }

    public function getSaldoFormateadoAttribute(): string
    {
        return '$'.number_format($this->saldo_centavos / 100, 2).' MXN';
    }

    protected function casts(): array
    {
        return [
            'monto_centavos' => 'integer',
            'saldo_centavos' => 'integer',
        ];
    }
}
