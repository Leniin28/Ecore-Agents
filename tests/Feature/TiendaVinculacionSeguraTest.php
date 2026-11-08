<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\Monedero;
use App\Models\TicketAtencion;
use App\Models\User;
use App\Services\MonederoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TiendaVinculacionSeguraTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_with_existing_crm_client_email_having_interactions_creates_separate_client(): void
    {
        $salesRep = User::factory()->create(['role' => User::ROLE_EMPLEADO, 'permissions' => [User::MODULE_CRM]]);

        // 1. Cliente CRM previo creado por ventas sin usuario (prospecto)
        $clienteCrm = Cliente::create([
            'nombre' => 'Prospecto Corporativo SA',
            'correo' => 'contacto@empresa.local',
            'telefono' => '5511223344',
            'empresa' => 'Empresa Local SA',
            'fecha_registro' => now()->toDateString(),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);

        // Registrar una interacción confidencial en CRM para ese prospecto
        Interaccion::create([
            'cliente_id' => $clienteCrm->id,
            'usuario_id' => $salesRep->id,
            'tipo' => 'llamada',
            'fecha' => now(),
            'descripcion' => 'Negociación confidencial de contrato corporativo anual.',
        ]);

        // 2. Un usuario externo se registra públicamente usando ese mismo correo
        $this->post(route('register'), [
            'name' => 'Usuario Web Externo',
            'email' => 'contacto@empresa.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'telefono' => '5599887766',
        ])->assertRedirect(route('profile'));

        $user = User::query()->where('email', 'contacto@empresa.local')->firstOrFail();
        $clienteCrm->refresh();

        // REGLA CRÍTICA: El cliente CRM preexistente NO fue apropiado (user_id sigue NULL)
        $this->assertNull($clienteCrm->user_id);

        // El nuevo usuario tiene su propio registro independiente con su correo real (sin prefijos inventados)
        $this->assertNotNull($user->cliente);
        $this->assertNotSame($clienteCrm->id, $user->cliente->id);
        $this->assertSame('contacto@empresa.local', $user->cliente->correo);

        // El nuevo usuario NO tiene acceso a las interacciones privadas del prospecto CRM
        $this->assertFalse($user->cliente->interacciones()->exists());
    }

    public function test_registration_does_not_hijack_client_with_wallet_or_tickets(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Cliente CRM con saldo en monedero y tickets previos
        $clienteExistente = Cliente::create([
            'nombre' => 'Cliente VIP CRM',
            'correo' => 'vip@corporativo.local',
            'telefono' => '5500000000',
            'fecha_registro' => now()->toDateString(),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_ACTIVO,
        ]);

        app(MonederoService::class)->recargar($clienteExistente, $admin, 500000); // $5,000 MXN

        TicketAtencion::create([
            'cliente_id' => $clienteExistente->id,
            'asunto' => 'Soporte VIP Confidencial',
            'mensaje' => 'Datos privados del corporativo',
            'estado' => 'abierto',
        ]);

        // Registro público con el mismo correo
        $this->post(route('register'), [
            'name' => 'Intento Invasor',
            'email' => 'vip@corporativo.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'telefono' => '5544332211',
        ])->assertRedirect(route('profile'));

        $newUser = User::query()->where('email', 'vip@corporativo.local')->firstOrFail();

        // El cliente VIP permanece sin usuario vinculado
        $clienteExistente->refresh();
        $this->assertNull($clienteExistente->user_id);

        // El nuevo usuario tiene su propio cliente nuevo, con saldo 0 en su monedero
        $this->assertNotSame($clienteExistente->id, $newUser->cliente->id);
        $monederoNuevo = app(MonederoService::class)->obtenerMonedero($newUser->cliente);
        $this->assertSame(0, $monederoNuevo->saldo_centavos);

        // El nuevo usuario no ve los tickets del cliente previo
        $this->actingAs($newUser)->get(route('tickets.index'))->assertDontSee('Soporte VIP Confidencial');
    }

    public function test_client_already_linked_to_another_user_cannot_be_usurped(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE, 'email' => 'titular@correo.com']);
        $clienteA = $userA->obtenerCliente();

        $this->assertSame($userA->id, $clienteA->user_id);

        // Intento de registrar a User B con otro email pero que intentar vincular al cliente A
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        // Intentar vinculación administrativa de un cliente que ya tiene user_id debe ser rechazada
        $this->expectException(ValidationException::class);
        $clienteA->vincularAUsuario($userB, $admin, 'Intento de vinculación indebida');
    }

    public function test_explicit_admin_linking_works_with_audit_log(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE, 'email' => 'verificado@portal.com']);

        // Prospecto creado en CRM sin usuario
        $clienteCrm = Cliente::create([
            'nombre' => 'Prospecto Legítimo',
            'correo' => 'verificado@portal.com',
            'telefono' => '5511223344',
            'fecha_registro' => now()->toDateString(),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);

        $this->assertNull($clienteCrm->user_id);

        // Administrador valida titularidad y realiza la vinculación explícita
        $clienteCrm->vincularAUsuario($clientUser, $admin, 'Titularidad verificada presencialmente por comprobante fiscal');

        $clienteCrm->refresh();
        $this->assertSame($clientUser->id, $clienteCrm->user_id);

        // Se registra auditoría
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $admin->id,
            'accion' => 'vincular_cliente_usuario',
            'entidad_id' => $clienteCrm->id,
        ]);
    }

    public function test_regular_user_cannot_invoke_admin_client_linking(): void
    {
        $empleado = User::factory()->create(['role' => User::ROLE_EMPLEADO]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'demo@test.com',
            'telefono' => '55',
            'fecha_registro' => now()->toDateString(),
            'estado' => 'activo',
            'etapa_crm' => 'prospecto',
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $cliente->vincularAUsuario($clientUser, $empleado, 'Intento sin permiso');
    }
}
