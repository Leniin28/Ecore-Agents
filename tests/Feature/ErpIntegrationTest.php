<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_erp_access_uses_permissions_not_business_profile(): void
    {
        foreach ([
            [User::ROLE_CLIENTE, ['erp'], null, 403],
            [User::ROLE_EMPLEADO, ['crm'], 'ventas', 403],
            [User::ROLE_EMPLEADO, ['erp'], null, 200],
            [User::ROLE_ADMIN, [], null, 200],
        ] as [$role, $permissions, $profile, $status]) {
            $user = User::factory()->create(['role' => $role, 'permissions' => $permissions, 'perfil_empresarial' => $profile]);
            $this->actingAs($user)->get('/erp/recursos')->assertStatus($status);
            $this->actingAs($user)->get('/erp')->assertStatus($status);
        }
    }

    public function test_scm_sale_price_is_exposed_in_erp_without_copying_products(): void
    {
        $supplier = Proveedor::create(['nombre' => 'Proveedor integrado']);
        $user = User::factory()->create(['permissions' => ['scm', 'erp']]);
        $this->actingAs($user)->postJson('/api/scm/productos', [
            'nombre' => 'Capacidad compartida', 'categoria' => 'IA', 'proveedor_id' => $supplier->id,
            'stock_actual' => 50, 'stock_minimo' => 5, 'costo_unitario' => '0.10',
            'precio_venta_unitario' => '0.35', 'estrategia_logistica' => 'PULL',
        ])->assertCreated()->assertJsonPath('data.precio_venta_unitario', '0.35');

        $this->get('/erp/recursos')->assertOk()->assertSee('Capacidad compartida')->assertSee('$0.35');
        $this->assertDatabaseCount('productos', 1);
        $this->assertSame('0.35', Producto::first()->precio_venta_unitario);
    }

    public function test_admin_can_assign_profile_and_override_suggested_permissions_with_audit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $this->actingAs($admin)->put(route('admin.usuarios.update', $employee), [
            'name' => 'Ventas demo', 'role' => User::ROLE_EMPLEADO,
            'perfil_empresarial' => 'ventas', 'permissions' => ['erp'],
        ])->assertRedirect();
        $this->assertSame('ventas', $employee->fresh()->perfil_empresarial);
        $this->assertSame(['erp'], $employee->fresh()->permissions);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambiar_perfil_empresarial', 'entidad_id' => $employee->id]);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambiar_permisos', 'entidad_id' => $employee->id]);
        $this->put(route('admin.usuarios.update', $employee), [
            'name' => 'Ventas demo', 'role' => User::ROLE_EMPLEADO, 'perfil_empresarial' => 'otro',
        ])->assertSessionHasErrors('perfil_empresarial');
        $this->actingAs($employee->fresh())->get(route('admin.usuarios.create'))->assertForbidden();
    }
}
