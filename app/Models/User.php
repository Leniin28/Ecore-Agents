<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EMPLEADO = 'usuario';

    public const ROLE_USER = self::ROLE_EMPLEADO;

    public const ROLE_CLIENTE = 'cliente';

    public const MODULE_CRM = 'crm';

    public const MODULE_SCM = 'scm';

    public const MODULES = [self::MODULE_CRM, self::MODULE_SCM];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLEADO;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENTE;
    }

    public function hasModuleAccess(string $module): bool
    {
        return $this->isAdmin()
            || ($this->isEmployee() && in_array($module, $this->permissions ?? [], true));
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_EMPLEADO => 'Empleado',
            self::ROLE_CLIENTE => 'Cliente',
            default => 'Sin tipo',
        };
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(Interaccion::class, 'usuario_id');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
}
