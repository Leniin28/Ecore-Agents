<?php

namespace Tests\Feature;

use App\Models\TicketAtencion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAndRememberTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_password_reset_only_works_in_local_environment(): void
    {
        $user = User::factory()->create(['password' => 'original123']);
        $data = ['email' => $user->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'];
        $this->post(route('password.demo.store'), $data)->assertNotFound();
        $this->app['env'] = 'local';
        $data['_token'] = 'test-token';
        $this->withSession(['_token' => 'test-token']);
        $this->post(route('password.demo.store'), $data)->assertRedirect(route('login'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nueva12345', $user->fresh()->password));
        $this->post(route('password.demo.store'), array_merge($data, ['email' => 'ausente@example.com']))->assertSessionHasErrors('email');
    }

    public function test_remember_me_sets_a_remember_cookie(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password123', 'remember' => '1']);
        $response->assertRedirect(route('profile'));
        $this->assertNotNull($user->fresh()->getRememberToken());
        $this->assertNotEmpty(array_filter($response->headers->getCookies(), fn ($cookie) => str_starts_with($cookie->getName(), 'remember_')));
    }

    public function test_support_can_answer_tickets_without_access_to_other_modules(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $ticket = TicketAtencion::create(['cliente_id' => $client->obtenerCliente()->id, 'asunto' => 'Ayuda', 'mensaje' => 'Necesito ayuda', 'estado' => TicketAtencion::ESTADO_ABIERTO]);
        $support = User::factory()->create(['role' => User::ROLE_EMPLEADO, 'perfil_empresarial' => 'soporte', 'permissions' => []]);
        $this->actingAs($support)->get(route('soporte.tickets.show', $ticket))->assertOk();
        $this->post(route('soporte.tickets.responder', $ticket), ['respuesta' => 'Con gusto te ayudamos.', 'estado' => TicketAtencion::ESTADO_RESPONDIDO])->assertRedirect(route('soporte.tickets.show', $ticket));
        $this->get('/admin/tienda/productos')->assertForbidden();
        $this->get('/clientes')->assertForbidden();
        $this->assertDatabaseHas('tickets_atencion', ['id' => $ticket->id, 'respondido_por' => $support->id]);
        $this->actingAs($client)->get(route('soporte.tickets.index'))->assertForbidden();
        $this->get(route('tickets.show', $ticket))->assertSee('correo simulado')->assertSee('no se ha enviado un correo real');
    }
}
