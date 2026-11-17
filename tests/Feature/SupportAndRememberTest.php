<?php

namespace Tests\Feature;

use App\Models\TicketAtencion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAndRememberTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_requires_valid_single_use_email_token(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = User::factory()->create(['password' => 'original123']);
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        \Illuminate\Support\Facades\Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class, function ($notification) use ($user) {
            $data = ['email' => $user->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345', 'code' => $notification->token === '123456' ? '123457' : '123456'];
            $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
            $data['code'] = $notification->token;
            $this->post(route('password.update'), $data)->assertRedirect(route('login'));
            $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
            return true;
        });
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nueva12345', $user->fresh()->password));
        $this->post(route('password.email'), ['email' => 'ausente@example.com'])->assertSessionHas('status');
    }

    public function test_remember_me_sets_a_remember_cookie(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password123', 'remember' => '1']);
        $response->assertRedirect(route('profile'));
        $this->assertNotNull($user->fresh()->getRememberToken());
        $this->assertNotEmpty(array_filter($response->headers->getCookies(), fn ($cookie) => str_starts_with($cookie->getName(), 'remember_')));
    }

    public function test_expired_and_exhausted_codes_cannot_reset_password(): void
    {
        $user = User::factory()->create(['password' => 'original123']);
        $entry = ['email' => $user->email, 'code_hash' => \Illuminate\Support\Facades\Hash::make('123456'), 'attempts' => 0, 'created_at' => now()->subMinutes(16)];
        \Illuminate\Support\Facades\DB::table('password_reset_codes')->insert($entry);
        $data = ['email' => $user->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345', 'code' => '123456'];
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        \Illuminate\Support\Facades\DB::table('password_reset_codes')->where('email', $user->email)->update(['created_at' => now(), 'attempts' => 5]);
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('original123', $user->fresh()->password));
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
