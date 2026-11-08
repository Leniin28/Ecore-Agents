<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cliente_id', 'version'])]
class Carrito extends Model
{
    use HasFactory;

    protected $table = 'carritos';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CarritoDetalle::class);
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }
}
