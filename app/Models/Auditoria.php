<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'accion', 'entidad', 'entidad_id', 'descripcion'])]
class Auditoria extends Model
{
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public static function registrar(
        ?User $usuario,
        string $accion,
        string $entidad,
        ?int $entidadId,
        string $descripcion,
    ): self {
        return self::create([
            'usuario_id' => $usuario?->id,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'descripcion' => $descripcion,
        ]);
    }
}
