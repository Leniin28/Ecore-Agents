<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\MovimientoInventario;
use App\Models\OrdenDetalle;
use App\Models\OrdenEmpresarial;
use App\Models\Pedido;
use App\Models\ProcesoInterno;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpMenuAndUserDeletionTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_erp_sidebar_menu_for_admin_shows_all_direct_access_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('erp.dashboard'));
        $response->assertOk();

        // Check direct access links
        $response->assertSee(route('erp.dashboard'));
        $response->assertSee('Inicio');
        $response->assertSee(route('erp.clientes.index'));
        $response->assertSee('Clientes');
        $response->assertSee(route('erp.productos.index'));
        $response->assertSee('Productos');
        $response->assertSee(route('erp.inventario.index'));
        $response->assertSee('Inventario');
        $response->assertSee(route('erp.ordenes.index'));
        $response->assertSee('Órdenes');
        $response->assertSee(route('erp.recursos'));
        $response->assertSee('Recursos');
        $response->assertSee(route('erp.procesos'));
        $response->assertSee('Procesos internos');
        $response->assertSee(route('erp.reportes'));
        $response->assertSee('Reportes');
        $response->assertSee(route('erp.nivel'));
        $response->assertSee('Nivel ERP');
        $response->assertSee(route('erp.usuarios.index'));
        $response->assertSee('Usuarios');

        // Verify active state and aria-current="page" on Inicio
        $content = $response->getContent();
        $this->assertStringContainsString('href="'.route('erp.dashboard').'" aria-current="page"', $content);

        // Ensure "Dashboard" link is not present alongside "Inicio" in the ERP menu
        $this->assertMatchesRegularExpression('/aria-label="Menú ERP"[\s\S]*?<\/aside>/', $content, 'Menú ERP existe');
        preg_match('/aria-label="Menú ERP"([\s\S]*?)<\/aside>/', $content, $matches);
        $sidebarContent = $matches[1] ?? '';
        $this->assertStringNotContainsString('>Dashboard<', $sidebarContent);
    }

    public function test_erp_sidebar_menu_for_employee_respects_crm_and_scm_permissions(): void
    {
        // Empleado con ERP y CRM
        $employeeCrm = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'permissions' => ['erp', 'crm'],
        ]);

        $responseCrm = $this->actingAs($employeeCrm)->get(route('erp.dashboard'));
        $responseCrm->assertOk();
        $responseCrm->assertSee(route('erp.clientes.index'));
        $responseCrm->assertDontSee(route('erp.productos.index'));
        $responseCrm->assertDontSee(route('erp.inventario.index'));
        $responseCrm->assertDontSee(route('erp.usuarios.index'));

        // Empleado con ERP y SCM
        $employeeScm = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'permissions' => ['erp', 'scm'],
        ]);

        $responseScm = $this->actingAs($employeeScm)->get(route('erp.dashboard'));
        $responseScm->assertOk();
        $responseScm->assertDontSee(route('erp.clientes.index'));
        $responseScm->assertSee(route('erp.productos.index'));
        $responseScm->assertSee(route('erp.inventario.index'));
        $responseScm->assertDontSee(route('erp.usuarios.index'));

        // Empleado solo con ERP
        $employeeErpOnly = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'permissions' => ['erp'],
        ]);

        $responseErp = $this->actingAs($employeeErpOnly)->get(route('erp.dashboard'));
        $responseErp->assertOk();
        $responseErp->assertDontSee(route('erp.clientes.index'));
        $responseErp->assertDontSee(route('erp.productos.index'));
        $responseErp->assertDontSee(route('erp.inventario.index'));
        $responseErp->assertDontSee(route('erp.usuarios.index'));
    }

    public function test_business_profile_does_not_automatically_grant_module_access_without_permissions(): void
    {
        // Perfil ventas pero solo con permiso erp (no se le dio crm)
        $employeeVentas = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'perfil_empresarial' => 'ventas',
            'permissions' => ['erp'],
        ]);

        $response = $this->actingAs($employeeVentas)->get(route('erp.dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('erp.clientes.index'));
        $response->assertDontSee(route('erp.productos.index'));

        // Perfil logistica pero solo con permiso erp (no se le dio scm)
        $employeeLogistica = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'perfil_empresarial' => 'logistica',
            'permissions' => ['erp'],
        ]);

        $response2 = $this->actingAs($employeeLogistica)->get(route('erp.dashboard'));
        $response2->assertOk();
        $response2->assertDontSee(route('erp.productos.index'));
        $response2->assertDontSee(route('erp.inventario.index'));
    }

    public function test_user_administration_access_rejected_for_guest_employee_and_client(): void
    {
        $target = User::factory()->create();

        // Invitado
        $this->get(route('admin.usuarios.index'))->assertRedirect(route('login'));
        $this->delete(route('admin.usuarios.destroy', $target))->assertRedirect(route('login'));

        // Empleado con todos los módulos
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLEADO,
            'permissions' => ['crm', 'scm', 'erp'],
        ]);
        $this->actingAs($employee)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($employee)->delete(route('admin.usuarios.destroy', $target))->assertForbidden();

        // Cliente
        $client = User::factory()->create([
            'role' => User::ROLE_CLIENTE,
            'permissions' => null,
        ]);
        $this->actingAs($client)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($client)->delete(route('admin.usuarios.destroy', $target))->assertForbidden();
    }

    public function test_admin_can_safely_delete_internal_user_without_history_with_audit_and_cleanup(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'name' => 'Empleado Seguro',
            'email' => 'seguro@example.com',
            'role' => User::ROLE_EMPLEADO,
        ]);

        // Simular sesión y token de restablecimiento
        DB::table('sessions')->insert([
            'id' => 'session_test_123',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test_session'),
            'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $employee->email,
            'token' => 'test_reset_token',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success');

        // Usuario eliminado
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);

        // Sesión y token eliminados
        $this->assertDatabaseMissing('sessions', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'seguro@example.com']);

        // Auditoría registrada
        $audit = Auditoria::query()->where('accion', 'eliminar')->where('entidad', 'usuario')->firstOrFail();
        $this->assertSame($admin->id, $audit->usuario_id);
        $this->assertSame($employee->id, $audit->entidad_id);
        $this->assertStringContainsString('Empleado Seguro', $audit->descripcion);
        $this->assertStringContainsString('seguro@example.com', $audit->descripcion);
        $this->assertStringNotContainsString('password', strtolower($audit->descripcion));
        $this->assertStringNotContainsString('test_reset_token', $audit->descripcion);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $admin));
        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('error', 'No puedes eliminar tu propia cuenta de usuario.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('auditorias', ['accion' => 'eliminar']);
    }

    public function test_cannot_delete_last_administrator(): void
    {
        $admin1 = User::factory()->create(['role' => User::ROLE_ADMIN]);
        // Solo hay 1 admin en el sistema.
        // Incluso si se intenta invocar el endpoint para admin1:
        $response = $this->actingAs($admin1)->delete(route('admin.usuarios.destroy', $admin1));
        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('users', ['id' => $admin1->id]);

        // Si hay un segundo admin, no se puede eliminar si al hacerlo quedaría 0 admins
        $admin2 = User::factory()->create(['role' => User::ROLE_ADMIN]);
        // Deleting admin2 by admin1 is allowed if no dependencies
        $this->actingAs($admin1)->delete(route('admin.usuarios.destroy', $admin2))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);

        // Ahora solo queda admin1 de nuevo. No se puede eliminar.
        $this->actingAs($admin1)->delete(route('admin.usuarios.destroy', $admin1))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin1->id]);
    }

    public function test_cannot_delete_client_account_or_user_linked_to_client(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Usuario con rol cliente
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE, 'permissions' => null]);
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $clientUser))
            ->assertSessionHas('error', 'No se pueden eliminar cuentas de clientes ni usuarios vinculados a un cliente.');
        $this->assertDatabaseHas('users', ['id' => $clientUser->id]);

        // Usuario vinculado a un registro cliente
        $employeeLinkedToClient = User::factory()->create(['role' => User::ROLE_EMPLEADO]);
        Cliente::factory()->create(['user_id' => $employeeLinkedToClient->id]);

        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employeeLinkedToClient))
            ->assertSessionHas('error', 'No se pueden eliminar cuentas de clientes ni usuarios vinculados a un cliente.');
        $this->assertDatabaseHas('users', ['id' => $employeeLinkedToClient->id]);
    }

    public function test_cannot_delete_user_with_interacciones_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $cliente = Cliente::factory()->create();

        Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $employee->id,
            'tipo' => 'Llamada',
            'descripcion' => 'Seguimiento',
            'fecha' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('interacciones', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_cannot_delete_user_with_ordenes_empresariales_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $orden = OrdenEmpresarial::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $employee->id,
            'fecha' => now(),
            'estado' => 'pendiente',
            'total' => '150.00',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('órdenes empresariales', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_cannot_delete_user_with_movimientos_inventario_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $product = $this->erpProduct();

        MovimientoInventario::create([
            'producto_id' => $product->id,
            'usuario_id' => $employee->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
            'motivo' => 'compra',
            'fecha' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('movimientos de inventario', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_cannot_delete_user_with_pedidos_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $product = $this->erpProduct();

        Pedido::create([
            'producto_id' => $product->id,
            'usuario_id' => $employee->id,
            'cantidad' => 5,
            'tipo' => 'reabastecimiento',
            'estado' => 'pendiente',
            'origen' => 'manual',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('pedidos', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_cannot_delete_user_with_procesos_internos_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $otherUser = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $orden = OrdenEmpresarial::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $otherUser->id,
            'fecha' => now(),
            'estado' => 'procesada',
            'total' => '200.00',
        ]);

        ProcesoInterno::create([
            'orden_empresarial_id' => $orden->id,
            'usuario_id' => $employee->id,
            'tipo' => 'reserva_stock',
            'descripcion' => 'Reserva',
            'estado' => 'completado',
            'fecha' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('procesos internos', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_cannot_delete_user_with_auditorias_as_actor_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();

        Auditoria::registrar(
            $employee,
            'actualizar',
            'cliente',
            1,
            'Actualizó datos de cliente.'
        );

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('auditoría', session('error'));
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_no_partial_changes_when_deletion_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create();
        $cliente = Cliente::factory()->create();

        Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $employee->id,
            'tipo' => 'Mensaje',
            'descripcion' => 'Prueba de atomicidad',
            'fecha' => now(),
        ]);

        // Simular token y sesión
        DB::table('sessions')->insert([
            'id' => 'atomic_session_123',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $employee->email,
            'token' => 'atomic_token_123',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $response->assertSessionHas('error');

        // Confirmar que nada se borró parcialmente
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'atomic_session_123']);
        $this->assertDatabaseHas('password_reset_tokens', ['token' => 'atomic_token_123']);
        $this->assertDatabaseMissing('auditorias', ['accion' => 'eliminar', 'entidad_id' => $employee->id]);
    }

    public function test_ui_renders_return_to_erp_link_and_delete_form_with_csrf_method_and_confirmation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'name' => 'Empleado Target UI',
            'role' => User::ROLE_EMPLEADO,
        ]);
        $client = User::factory()->create([
            'name' => 'Cliente UI',
            'role' => User::ROLE_CLIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.usuarios.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Enlace claro para volver al ERP
        $response->assertSee(route('erp.dashboard'));
        $response->assertSee('Volver al ERP');

        // Formulario de eliminación para empleado
        $this->assertStringContainsString('action="'.route('admin.usuarios.destroy', $employee).'"', $content);
        $this->assertStringContainsString('name="_method" value="delete"', $content);
        $this->assertStringContainsString('name="_token"', $content);
        $this->assertStringContainsString('data-confirm-delete="¿Confirmas la eliminación del usuario Empleado Target UI', $content);
        $this->assertStringNotContainsString('onsubmit=', $content);
        $this->assertStringContainsString('aria-label="Eliminar usuario Empleado Target UI"', $content);

        // No debe ofrecer formulario de eliminación para el usuario logueado ni para clientes
        $this->assertStringNotContainsString('action="'.route('admin.usuarios.destroy', $admin).'"', $content);
        $this->assertStringNotContainsString('action="'.route('admin.usuarios.destroy', $client).'"', $content);
    }

    public function test_delete_form_handles_user_names_with_apostrophes_and_quotes_safely_in_data_confirm_delete(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $complexName = 'D\'Angelo "The Architect" O\'Neil';
        $employee = User::factory()->create([
            'name' => $complexName,
            'email' => 'complex.name@example.com',
            'role' => User::ROLE_EMPLEADO,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.usuarios.index'));
        $response->assertOk();

        $content = $response->getContent();

        // data-confirm-delete debe estar presente y correctamente escapado sin usar onsubmit
        $expectedEscapedName = htmlspecialchars($complexName, ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('data-confirm-delete="¿Confirmas la eliminación del usuario '.$expectedEscapedName.' (complex.name@example.com)? Esta acción no se puede deshacer."', $content);
        $this->assertStringNotContainsString('onsubmit=', $content);

        // La eliminación se procesa correctamente en backend y registra el nombre original en auditoría
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $employee));
        $deleteResponse->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);

        $audit = Auditoria::query()->where('accion', 'eliminar')->where('entidad_id', $employee->id)->firstOrFail();
        $this->assertStringContainsString($complexName, $audit->descripcion);
    }
}
