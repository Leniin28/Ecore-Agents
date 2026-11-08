<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cliente_id',
    'orden_empresarial_id',
    'asunto',
    'mensaje',
    'estado',
    'respuesta',
    'respondido_por',
    'respondido_at',
])]
class TicketAtencion extends Model
{
    use HasFactory;

    protected $table = 'tickets_atencion';

    public const ESTADO_ABIERTO = 'abierto';

    public const ESTADO_EN_REVISION = 'en_revision';

    public const ESTADO_RESPONDIDO = 'respondido';

    public const ESTADO_CERRADO = 'cerrado';

    public const ESTADOS = [
        self::ESTADO_ABIERTO => 'Abierto',
        self::ESTADO_EN_REVISION => 'En revisión',
        self::ESTADO_RESPONDIDO => 'Respondido',
        self::ESTADO_CERRADO => 'Cerrado',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenEmpresarial(): BelongsTo
    {
        return $this->belongsTo(OrdenEmpresarial::class);
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    public function folio(): string
    {
        return 'TCK-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    protected function casts(): array
    {
        return [
            'respondido_at' => 'datetime',
        ];
    }
}
