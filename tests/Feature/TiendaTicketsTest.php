<?php

namespace Tests\Feature;

use App\Models\OrdenEmpresarial;
use App\Models\TicketAtencion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_with_subject_and_message(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $response = $this->actingAs($user)
            ->post(route('tickets.store'), [
                'asunto' => 'Duda sobre activación de Agente Inicio',
                'mensaje' => '¿Cómo vinculo el agente con mi número de WhatsApp demo?',
            ]);

        $ticket = TicketAtencion::query()->where('cliente_id', $user->obtenerCliente()->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame(TicketAtencion::ESTADO_ABIERTO, $ticket->estado);

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'accion' => 'crear_ticket_atencion',
        ]);
    }

    public function test_customer_cannot_link_ticket_to_another_clients_order(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        // Orden perteneciente a User B
        $ordenB = OrdenEmpresarial::create([
            'cliente_id' => $userB->obtenerCliente()->id,
            'usuario_id' => $userB->id,
            'fecha' => now(),
            'estado' => OrdenEmpresarial::PROCESADA,
            'total' => '499.00',
        ]);

        // User A intenta crear un ticket vinculando la orden de User B
        $this->actingAs($userA)
            ->post(route('tickets.store'), [
                'asunto' => 'Intento de vinculación indebida',
                'mensaje' => 'Mensaje espía',
                'orden_empresarial_id' => $ordenB->id,
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_view_another_clients_ticket(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $ticketB = TicketAtencion::create([
            'cliente_id' => $userB->obtenerCliente()->id,
            'asunto' => 'Ticket privado de B',
            'mensaje' => 'Información confidencial de negocio',
            'estado' => TicketAtencion::ESTADO_ABIERTO,
        ]);

        $this->actingAs($userA)
            ->get(route('tickets.show', $ticketB))
            ->assertForbidden();
    }

    public function test_crm_staff_and_admin_can_reply_and_update_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $ticket = TicketAtencion::create([
            'cliente_id' => $clientUser->obtenerCliente()->id,
            'asunto' => 'Consulta técnica de integración',
            'mensaje' => 'Requiero soporte con el webhook',
            'estado' => TicketAtencion::ESTADO_ABIERTO,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.tienda.tickets.responder', $ticket), [
                'respuesta' => 'Hola, te compartimos la guía de integración y marcamos tu solicitud como resuelta.',
                'estado' => TicketAtencion::ESTADO_RESPONDIDO,
            ]);

        $response->assertRedirect(route('admin.tienda.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(TicketAtencion::ESTADO_RESPONDIDO, $ticket->estado);
        $this->assertSame('Hola, te compartimos la guía de integración y marcamos tu solicitud como resuelta.', $ticket->respuesta);
        $this->assertSame($admin->id, $ticket->respondido_por);

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $admin->id,
            'accion' => 'responder_ticket_atencion',
        ]);
    }
}
