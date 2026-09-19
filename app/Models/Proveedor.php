<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'contacto', 'correo', 'telefono'])]
class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
