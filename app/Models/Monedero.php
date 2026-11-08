<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cliente_id', 'saldo_centavos'])]
class Monedero extends Model
{
    use HasFactory;

    protected $table = 'monederos';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MonederoMovimiento::class)->latest('id');
    }

    public function getSaldoDecimalAttribute(): string
    {
        return number_format($this->saldo_centavos / 100, 2, '.', '');
    }

    public function getSaldoFormateadoAttribute(): string
    {
        return '$'.number_format($this->saldo_centavos / 100, 2).' MXN';
    }

    protected function casts(): array
    {
        return [
            'saldo_centavos' => 'integer',
        ];
    }
}
