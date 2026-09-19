<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_client_cannot_access_internal_modules(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENTE, 'permissions' => null]);

        $this->actingAs($client)->get(route('admin'))->assertForbidden();
        $this->actingAs($client)->get('/scm')->assertForbidden();
    }

    public function test_admin_can_create_employee_with_module_permissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => null]);

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Empleado SCM', 'email' => 'scm@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'role' => User::ROLE_EMPLEADO, 'permissions' => ['crm', 'scm'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $employee = User::query()->where('email', 'scm@example.com')->firstOrFail();
        $this->assertSame(['crm', 'scm'], $employee->permissions);
        $this->assertTrue(Hash::check('Password123!', $employee->password));
        $this->assertDatabaseHas('auditorias', ['accion' => 'crear', 'entidad_id' => $employee->id]);
    }

    public function test_admin_can_create_another_admin_without_stored_permissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Segundo Admin', 'email' => 'admin2@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'role' => User::ROLE_ADMIN, 'permissions' => ['crm'],
        ])->assertSessionHasNoErrors();

        $created = User::query()->where('email', 'admin2@example.com')->firstOrFail();
        $this->assertTrue($created->isAdmin());
        $this->assertNull($created->permissions);
    }

    public function test_employee_access_is_limited_to_assigned_module(): void
    {
        $crm = User::factory()->create(['permissions' => ['crm']]);
        $scm = User::factory()->create(['permissions' => ['scm']]);

        $this->actingAs($crm)->get(route('admin'))->assertOk();
        $this->actingAs($crm)->get('/scm')->assertForbidden();
        $this->actingAs($scm)->get(route('admin'))->assertForbidden();
        $this->actingAs($scm)->get('/scm')->assertRedirect(route('scm.productos.index'));
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $admin), [
            'name' => $admin->name,
            'role' => User::ROLE_EMPLEADO,
            'permissions' => ['crm'],
        ])->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_client_cannot_be_converted_from_internal_user_editor(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = User::factory()->create(['role' => User::ROLE_CLIENTE, 'permissions' => null]);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $client), [
            'name' => $client->name, 'role' => User::ROLE_EMPLEADO, 'permissions' => ['crm'],
        ])->assertForbidden();
    }

    public function test_only_admin_can_reset_password_and_no_secret_is_audited(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();

        $this->actingAs($employee)->put(route('admin.usuarios.password.update', $employee), [
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertForbidden();

        $this->actingAs($admin)->put(route('admin.usuarios.password.update', $employee), [
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertTrue(Hash::check('NewPassword123!', $employee->fresh()->password));
        $audit = Auditoria::query()->where('accion', 'restablecer_password')->firstOrFail();
        $this->assertStringNotContainsString('NewPassword123!', $audit->descripcion);
        $this->assertStringNotContainsString($employee->password, $audit->descripcion);
    }

    public function test_audit_panel_is_admin_only(): void
    {
        $employee = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($employee)->get(route('admin.auditoria.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.auditoria.index'))->assertOk();
    }
}
