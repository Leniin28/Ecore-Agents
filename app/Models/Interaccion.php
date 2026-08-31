<?php

namespace App\Models;

use Database\Factories\InteraccionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cliente_id', 'usuario_id', 'tipo', 'descripcion', 'fecha'])]
class Interaccion extends Model
{
    /** @use HasFactory<InteraccionFactory> */
    use HasFactory;

    protected $table = 'interacciones';

    public const TIPO_LLAMADA = 'llamada';

    public const TIPO_CORREO = 'correo';

    public const TIPO_REUNION = 'reunion';

    /**
     * @return array<string, string>
     */
    public static function tipos(): array
    {
        return [
            self::TIPO_LLAMADA => 'Llamada',
            self::TIPO_CORREO => 'Correo',
            self::TIPO_REUNION => 'Reunión',
        ];
    }

    public function tipoLabel(): string
    {
        return self::tipos()[$this->tipo];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }
}
