<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available_to_guests(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_a_guest_can_register_as_a_regular_user(): void
    {
        $response = $this->post(route('register'), $this->validData([
            'role' => User::ROLE_ADMIN,
        ]));

        $response->assertRedirect(route('profile'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'usuario@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->post(route('register'), $this->validData([
            'email' => 'repetido@example.com',
        ]))->assertSessionHasErrors([
            'email' => 'Ya existe una cuenta con este correo electrónico.',
        ]);

        $this->assertGuest();
    }

    public function test_registration_password_requires_confirmation(): void
    {
        $this->post(route('register'), $this->validData([
            'password_confirmation' => 'different-password',
        ]))->assertSessionHasErrors([
            'password' => 'La confirmación de contraseña no coincide.',
        ]);

        $this->assertGuest();
    }

    public function test_registration_requires_phone(): void
    {
        $this->post(route('register'), $this->validData([
            'telefono' => '',
        ]))->assertSessionHasErrors(['telefono']);

        $this->assertGuest();
    }

    public function test_registration_creates_cliente_record(): void
    {
        $this->post(route('register'), $this->validData());

        $this->assertDatabaseHas('clientes', ['correo' => 'usuario@example.com']);
    }

    public function test_registration_links_cliente_to_user(): void
    {
        $this->post(route('register'), $this->validData());

        $user = User::query()->where('email', 'usuario@example.com')->firstOrFail();
        $cliente = Cliente::query()->where('correo', 'usuario@example.com')->firstOrFail();

        $this->assertSame($user->id, $cliente->user_id);
        $this->assertTrue($user->cliente->is($cliente));
        $this->assertTrue($cliente->user->is($user));
    }

    public function test_registration_copies_name_to_cliente(): void
    {
        $this->post(route('register'), $this->validData(['name' => 'Cliente QA Nombre']));

        $this->assertDatabaseHas('clientes', [
            'correo' => 'usuario@example.com',
            'nombre' => 'Cliente QA Nombre',
        ]);
    }

    public function test_registration_copies_email_to_cliente(): void
    {
        $this->post(route('register'), $this->validData());

        $cliente = Cliente::query()->where('correo', 'usuario@example.com')->firstOrFail();

        $this->assertSame('usuario@example.com', $cliente->correo);
    }

    public function test_registration_saves_phone_on_cliente(): void
    {
        $this->post(route('register'), $this->validData(['telefono' => '5599998888']));

        $this->assertDatabaseHas('clientes', [
            'correo' => 'usuario@example.com',
            'telefono' => '5599998888',
        ]);
    }

    public function test_registration_saves_optional_company(): void
    {
        $this->post(route('register'), $this->validData(['empresa' => 'Empresa QA']));

        $this->assertDatabaseHas('clientes', [
            'correo' => 'usuario@example.com',
            'empresa' => 'Empresa QA',
        ]);
    }

    public function test_registration_without_company_saves_null(): void
    {
        $this->post(route('register'), $this->validData(['empresa' => null]));

        $cliente = Cliente::query()->where('correo', 'usuario@example.com')->firstOrFail();

        $this->assertNull($cliente->empresa);
    }

    public function test_registration_cliente_starts_active(): void
    {
        $this->post(route('register'), $this->validData());

        $this->assertDatabaseHas('clientes', [
            'correo' => 'usuario@example.com',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);
    }

    public function test_registration_cliente_starts_as_prospecto(): void
    {
        $this->post(route('register'), $this->validData());

        $this->assertDatabaseHas('clientes', [
            'correo' => 'usuario@example.com',
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);
    }

    public function test_registration_cliente_appears_in_customer_listing(): void
    {
        $this->post(route('register'), $this->validData());

        $cliente = Cliente::query()->where('correo', 'usuario@example.com')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee($cliente->nombre);
    }

    public function test_registration_cliente_affects_dashboard_totals(): void
    {
        Cliente::factory()->count(2)->create();

        $this->post(route('register'), $this->validData());

        $this->actingAs(User::factory()->create())
            ->get(route('admin'))
            ->assertViewHas('metricas', fn (array $metricas) => $metricas['total_clientes'] === 3);
    }

    public function test_failed_registration_validation_does_not_leave_orphan_user(): void
    {
        $this->post(route('register'), $this->validData([
            'password_confirmation' => 'different-password',
        ]));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_failed_registration_validation_does_not_leave_orphan_cliente(): void
    {
        $this->post(route('register'), $this->validData([
            'password_confirmation' => 'different-password',
        ]));

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_manually_created_cliente_can_have_null_user_id(): void
    {
        $cliente = Cliente::factory()->create();

        $this->assertNull($cliente->user_id);
    }

    public function test_registration_links_existing_manually_created_cliente_by_email(): void
    {
        $cliente = Cliente::factory()->create([
            'correo' => 'usuario@example.com',
            'estado' => Cliente::ESTADO_INACTIVO,
        ]);

        $this->post(route('register'), $this->validData());

        $user = User::query()->where('email', 'usuario@example.com')->firstOrFail();
        $cliente->refresh();

        $this->assertSame($user->id, $cliente->user_id);
        $this->assertDatabaseCount('clientes', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario Nuevo',
            'email' => 'usuario@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'telefono' => '5512345678',
            'empresa' => 'Empresa QA',
        ], $overrides);
    }

    public function test_user_can_log_in_with_the_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('profile'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_password_does_not_start_a_session(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cannot_open_profile(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guest_cannot_open_admin(): void
    {
        $this->get(route('admin'))->assertRedirect(route('login'));
    }

    public function test_regular_user_can_open_crm_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('Dashboard CRM');
    }

    public function test_admin_can_open_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('Dashboard CRM');
    }
}
