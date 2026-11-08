<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'orden_empresarial_id',
    'cliente_id',
    'referencia',
    'metodo',
    'estado',
    'subtotal_centavos',
    'descuento_centavos',
    'base_gravable_centavos',
    'impuesto_centavos',
    'total_centavos',
    'promocion_id',
    'desglose',
    'datos_entrega',
])]
class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    public const METODO_TARJETA = 'tarjeta_demo';

    public const METODO_PAYPAL = 'paypal_demo';

    public const METODO_TRANSFERENCIA = 'transferencia_demo';

    public const METODO_MONEDERO = 'monedero_demo';

    public const METODOS = [
        self::METODO_TARJETA => 'Tarjeta demo',
        self::METODO_PAYPAL => 'PayPal demo',
        self::METODO_TRANSFERENCIA => 'Transferencia demo',
        self::METODO_MONEDERO => 'Monedero demo',
    ];

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADOS = [
        self::ESTADO_APROBADO => 'Aprobado',
        self::ESTADO_RECHAZADO => 'Rechazado',
        self::ESTADO_PENDIENTE => 'Pendiente',
    ];

    public function ordenEmpresarial(): BelongsTo
    {
        return $this->belongsTo(OrdenEmpresarial::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function monederoMovimiento(): HasOne
    {
        return $this->hasOne(MonederoMovimiento::class);
    }

    public function metodoLabel(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getTotalDecimalAttribute(): string
    {
        return number_format($this->total_centavos / 100, 2, '.', '');
    }

    public function getTotalFormateadoAttribute(): string
    {
        return '$'.number_format($this->total_centavos / 100, 2).' MXN';
    }

    public function getSubtotalFormateadoAttribute(): string
    {
        return '$'.number_format($this->subtotal_centavos / 100, 2).' MXN';
    }

    public function getDescuentoFormateadoAttribute(): string
    {
        return '$'.number_format($this->descuento_centavos / 100, 2).' MXN';
    }

    public function getImpuestoFormateadoAttribute(): string
    {
        return '$'.number_format($this->impuesto_centavos / 100, 2).' MXN';
    }

    protected function casts(): array
    {
        return [
            'subtotal_centavos' => 'integer',
            'descuento_centavos' => 'integer',
            'base_gravable_centavos' => 'integer',
            'impuesto_centavos' => 'integer',
            'total_centavos' => 'integer',
            'desglose' => 'array',
            'datos_entrega' => 'array',
        ];
    }
}
