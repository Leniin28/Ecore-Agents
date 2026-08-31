<?php

namespace Tests\Feature;

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
        $response = $this->post(route('register'), [
            'name' => 'Usuario Nuevo',
            'email' => 'usuario@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'usuario@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->post(route('register'), [
            'name' => 'Otro Usuario',
            'email' => 'repetido@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors([
            'email' => 'Ya existe una cuenta con este correo electrónico.',
        ]);

        $this->assertGuest();
    }

    public function test_registration_password_requires_confirmation(): void
    {
        $this->post(route('register'), [
            'name' => 'Usuario Nuevo',
            'email' => 'usuario@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors([
            'password' => 'La confirmación de contraseña no coincide.',
        ]);

        $this->assertGuest();
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

    public function test_regular_user_cannot_open_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin'))->assertForbidden();
    }

    public function test_admin_can_open_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('Panel de administrador');
    }
}
