<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['nombre', 'slug', 'descripcion', 'activo'])]
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected static function booted(): void
    {
        static::saving(function (Categoria $categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nombre);
            }
        });
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
