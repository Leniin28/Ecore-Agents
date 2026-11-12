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

#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'perfil_empresarial'])]
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

    public const MODULE_ERP = 'erp';

    public const MODULES = [self::MODULE_CRM, self::MODULE_SCM, self::MODULE_ERP];

    public const PERFILES = ['ventas' => 'Ventas', 'logistica' => 'Logística', 'soporte' => 'Soporte / atención al cliente'];

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

    public function obtenerCliente(): Cliente
    {
        if ($this->relationLoaded('cliente') && $this->cliente) {
            return $this->cliente;
        }

        $cliente = $this->cliente()->first();
        if ($cliente) {
            return $cliente;
        }

        // Regla de seguridad: los métodos auxiliares nunca se apropian silenciosamente
        // de un cliente CRM preexistente por coincidencia de correo.
        // Si el usuario no tiene ficha vinculada, se crea un perfil exclusivo para este usuario con su correo real.
        return Cliente::create([
            'user_id' => $this->id,
            'nombre' => $this->name,
            'correo' => $this->email,
            'telefono' => 'Sin registrar',
            'empresa' => null,
            'fecha_registro' => now()->toDateString(),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);
    }

    public function ordenesEmpresariales(): HasMany
    {
        return $this->hasMany(OrdenEmpresarial::class, 'usuario_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'usuario_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function procesosInternos(): HasMany
    {
        return $this->hasMany(ProcesoInterno::class, 'usuario_id');
    }

    public function monederoMovimientos(): HasMany
    {
        return $this->hasMany(MonederoMovimiento::class, 'usuario_id');
    }

    public function ticketsRespondidos(): HasMany
    {
        return $this->hasMany(TicketAtencion::class, 'respondido_por');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
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
