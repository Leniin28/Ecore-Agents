<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cliente_id', 'usuario_id', 'fecha', 'estado', 'total'])]
class OrdenEmpresarial extends Model
{
    protected $table = 'ordenes_empresariales';

    public const PENDIENTE = 'pendiente';
    public const PROCESADA = 'procesada';
    public const CANCELADA = 'cancelada';
    public const ESTADOS = [self::PENDIENTE, self::PROCESADA, self::CANCELADA];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenDetalle::class);
    }

    public function folio(): string
    {
        return 'ORD-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'total' => 'decimal:2'];
    }
}
