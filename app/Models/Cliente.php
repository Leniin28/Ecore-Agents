<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'nombre', 'correo', 'telefono', 'empresa', 'fecha_registro', 'estado', 'etapa_crm'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_INACTIVO = 'inactivo';

    public const ETAPA_PROSPECTO = 'prospecto';

    public const ETAPA_ACTIVO = 'activo';

    public const ETAPA_FRECUENTE = 'frecuente';

    public const ETAPA_INACTIVO = 'inactivo';

    /**
     * @return array<string, string>
     */
    public static function etapasCrm(): array
    {
        return [
            self::ETAPA_PROSPECTO => 'Prospecto',
            self::ETAPA_ACTIVO => 'Activo',
            self::ETAPA_FRECUENTE => 'Frecuente',
            self::ETAPA_INACTIVO => 'Inactivo',
        ];
    }

    public function etapaCrmLabel(): string
    {
        return self::etapasCrm()[$this->etapa_crm];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(Interaccion::class);
    }

    public function ultimaInteraccion(): HasOne
    {
        return $this->hasOne(Interaccion::class)->latestOfMany('fecha');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
        ];
    }
}
