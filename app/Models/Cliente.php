<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function ordenesEmpresariales(): HasMany
    {
        return $this->hasMany(OrdenEmpresarial::class);
    }

    public function carrito(): HasOne
    {
        return $this->hasOne(Carrito::class);
    }

    public function monedero(): HasOne
    {
        return $this->hasOne(Monedero::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function ticketsAtencion(): HasMany
    {
        return $this->hasMany(TicketAtencion::class)->latest('id');
    }

    public function ultimaInteraccion(): HasOne
    {
        return $this->hasOne(Interaccion::class)->latestOfMany('fecha');
    }

    /**
     * Vinculación administrativa explícita y auditada de un cliente CRM con un usuario del sistema.
     * Requiere que el cliente no esté ya vinculado y que el usuario no tenga otro cliente asignado.
     */
    public function vincularAUsuario(User $user, User $adminResponsable, string $motivo): void
    {
        abort_unless($adminResponsable->isAdmin(), 403, 'Solo un administrador puede realizar vinculaciones de clientes.');

        $motivoLimpio = trim($motivo);
        if (mb_strlen($motivoLimpio) < 5) {
            throw ValidationException::withMessages(['motivo' => 'El motivo de vinculación debe tener al menos 5 caracteres.']);
        }

        DB::transaction(function () use ($user, $adminResponsable, $motivoLimpio) {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($this->id);

            if ($cliente->user_id !== null) {
                throw ValidationException::withMessages([
                    'cliente' => 'El cliente ya se encuentra vinculado a una cuenta de usuario.',
                ]);
            }

            // Comprobar que el usuario no tenga ya otro cliente vinculado (clientes.user_id es único)
            $clientePrevio = Cliente::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($clientePrevio) {
                throw ValidationException::withMessages([
                    'user' => 'El usuario ya cuenta con un perfil de cliente asignado.',
                ]);
            }

            $cliente->update(['user_id' => $user->id]);

            Auditoria::registrar(
                $adminResponsable,
                'vincular_cliente_usuario',
                'cliente',
                $cliente->id,
                "Vinculó administrativamente al cliente {$cliente->nombre} con el usuario {$user->name} ({$user->email}). Motivo: {$motivoLimpio}."
            );
        });
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
