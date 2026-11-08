<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\TicketAtencion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaSecurityAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_role_is_forbidden_from_internal_erp_scm_and_admin_modules(): void
    {
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        // Intentar acceder a ERP
        $this->actingAs($clientUser)->get(route('erp.dashboard'))->assertForbidden();
        $this->actingAs($clientUser)->get(route('erp.ordenes.index'))->assertForbidden();

        // Intentar acceder a SCM
        $this->actingAs($clientUser)->get(route('scm.dashboard'))->assertForbidden();
        $this->actingAs($clientUser)->get(route('scm.productos.index'))->assertForbidden();

        // Intentar acceder a Admin
        $this->actingAs($clientUser)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($clientUser)->get(route('admin.auditoria.index'))->assertForbidden();
        $this->actingAs($clientUser)->get(route('admin.tienda.productos.index'))->assertForbidden();
    }

    public function test_registration_with_existing_enterprise_client_email_creates_isolated_record_without_hijacking(): void
    {
        // 1. Un cliente corporativo creado previamente con órdenes confidenciales
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clienteEmpresarial = Cliente::create([
            'nombre' => 'Corporativo Secreto SA',
            'correo' => 'compras@corporativo.com',
            'telefono' => '5500000000',
            'empresa' => 'Corporativo Secreto',
            'fecha_registro' => now()->toDateString(),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_FRECUENTE,
        ]);

        OrdenEmpresarial::create([
            'cliente_id' => $clienteEmpresarial->id,
            'usuario_id' => $admin->id,
            'fecha' => now(),
            'estado' => OrdenEmpresarial::PROCESADA,
            'total' => '50000.00',
        ]);

        // 2. Un usuario externo se registra públicamente usando ese mismo correo
        $this->post(route('register'), [
            'name' => 'Atacante o Nuevo Usuario',
            'email' => 'compras@corporativo.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'telefono' => '5599887766',
        ])->assertRedirect(route('profile'));

        $nuevoUsuario = User::query()->where('email', 'compras@corporativo.com')->first();
        $this->assertNotNull($nuevoUsuario);

        // El cliente empresarial original NO fue apropiado
        $clienteEmpresarial->refresh();
        $this->assertNull($clienteEmpresarial->user_id);

        // El nuevo usuario tiene su propio registro de cliente seguro e independiente
        $this->assertNotNull($nuevoUsuario->cliente);
        $this->assertNotSame($clienteEmpresarial->id, $nuevoUsuario->cliente->id);

        // El nuevo usuario NO ve las órdenes del cliente empresarial
        $this->actingAs($nuevoUsuario)
            ->get(route('mis-compras.index'))
            ->assertDontSee('50000.00');
    }

    public function test_cross_client_isolation_orders_and_tickets(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $ordenB = OrdenEmpresarial::create([
            'cliente_id' => $userB->obtenerCliente()->id,
            'usuario_id' => $userB->id,
            'fecha' => now(),
            'estado' => OrdenEmpresarial::PROCESADA,
            'total' => '1200.00',
        ]);

        $ticketB = TicketAtencion::create([
            'cliente_id' => $userB->obtenerCliente()->id,
            'asunto' => 'Secreto B',
            'mensaje' => 'Contenido confidencial',
            'estado' => 'abierto',
        ]);

        // User A intenta consultar la compra de User B
        $this->actingAs($userA)
            ->get(route('mis-compras.show', $ordenB))
            ->assertForbidden();

        // User A intenta consultar el ticket de User B
        $this->actingAs($userA)
            ->get(route('tickets.show', $ticketB))
            ->assertForbidden();
    }
}
