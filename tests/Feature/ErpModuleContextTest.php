<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpModuleContextTest extends TestCase
{
    use ErpFixtures, RefreshDatabase;

    public function test_shared_screens_keep_the_erp_shell_and_active_menu_without_duplicate_sidebars(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $client = Cliente::factory()->create();
        $product = $this->erpProduct();
        $employee = User::factory()->create();
        foreach ([
            '/erp/clientes' => 'Clientes', '/erp/clientes/create' => 'Clientes',
            '/erp/clientes/'.$client->id => 'Clientes', '/erp/clientes/'.$client->id.'/edit' => 'Clientes',
            '/erp/productos' => 'Productos', '/erp/productos/create' => 'Productos',
            '/erp/productos/'.$product->id.'/edit' => 'Productos', '/erp/inventario' => 'Inventario',
            '/erp/productos/'.$product->id.'/movimientos' => 'Inventario', '/erp/movimientos' => 'Inventario',
            '/erp/movimientos/create' => 'Inventario', '/erp/usuarios' => 'Usuarios',
            '/erp/usuarios/create' => 'Usuarios', '/erp/usuarios/'.$employee->id.'/edit' => 'Usuarios',
            '/erp/usuarios/'.$employee->id.'/password' => 'Usuarios',
        ] as $url => $active) {
            $html = $this->get($url)->assertOk()->assertSee('Etapa 3 · ERP')->getContent();
            $this->assertSame(1, substr_count($html, 'aria-label="Menú ERP"'), $url);
            $this->assertStringNotContainsString('aria-label="Menú CRM"', $html);
            $this->assertStringNotContainsString('aria-label="Menú SCM"', $html);
            $this->assertSame(1, substr_count($html, '<h1>'), $url);
            preg_match('/<aside[^>]*aria-label="Menú ERP"[\s\S]*?<\/aside>/', $html, $sidebar);
            $this->assertSame(1, substr_count($sidebar[0], 'aria-current="page"'), $url);
            $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>'.preg_quote($active, '/').'<\/a>/', $sidebar[0]);
        }
    }

    public function test_client_crud_links_forms_validation_and_redirects_remain_in_erp(): void
    {
        $this->actingAs(User::factory()->create(['permissions' => ['crm', 'erp']]));
        $payload = ['nombre' => 'Cliente compartido', 'correo' => 'shared@example.test', 'telefono' => '0000000000', 'estado' => 'activo'];
        $this->get('/erp/clientes/create')->assertOk()->assertSee('action="'.url('/erp/clientes').'"', false)
            ->assertSee('href="'.url('/erp/clientes').'"', false);
        $this->post('/erp/clientes', $payload)->assertRedirect('/erp/clientes/1');
        $client = Cliente::sole();
        $this->get('/erp/clientes?buscar=compartido')->assertOk()->assertSee('Cliente compartido')
            ->assertSee('href="'.url('/erp/clientes/'.$client->id.'/edit').'"', false);
        $this->put('/erp/clientes/'.$client->id, [...$payload, 'nombre' => 'Cliente editado'])->assertRedirect('/erp/clientes/'.$client->id);
        $this->get('/clientes/'.$client->id)->assertOk()->assertSee('Cliente editado')->assertSee('Menú CRM');
        $this->put('/erp/clientes/'.$client->id.'/etapa', ['etapa_crm' => 'activo'])->assertRedirect('/erp/clientes/'.$client->id);
        $this->post('/erp/interacciones', ['cliente_id' => $client->id, 'tipo' => 'llamada', 'descripcion' => 'Seguimiento', 'fecha' => now()->toDateTimeString()])
            ->assertRedirect('/erp/clientes/'.$client->id);
        $this->from('/erp/clientes/create')->post('/erp/clientes', [])->assertRedirect('/erp/clientes/create')->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_product_crud_and_movement_use_shared_stock_and_preserve_erp_redirects(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->erpProduct();
        $payload = $product->only(['nombre', 'categoria', 'proveedor_id', 'stock_actual', 'stock_minimo', 'costo_unitario', 'precio_venta_unitario', 'estrategia_logistica']);
        $this->get('/erp/productos')->assertOk()->assertSee('href="'.url('/erp/productos/'.$product->id.'/edit').'"', false);
        $this->put('/erp/productos/'.$product->id, [...$payload, 'nombre' => 'Compartido ERP'])->assertRedirect('/erp/productos');
        $this->get('/scm/productos')->assertOk()->assertSee('Compartido ERP')->assertSee('Menú SCM');
        $this->post('/erp/productos', [...$payload, 'nombre' => 'Nuevo compartido'])->assertRedirect('/erp/productos');
        $new = Producto::where('nombre', 'Nuevo compartido')->sole();
        $this->delete('/erp/productos/'.$new->id)->assertRedirect('/erp/productos');
        $this->post('/erp/movimientos', ['producto_id' => $product->id, 'tipo' => 'entrada', 'cantidad' => 5,
            'motivo' => 'ajuste', 'fecha' => now()->toDateTimeString()])->assertRedirect('/erp/movimientos');
        $this->assertSame(105, $product->fresh()->stock_actual);
        $this->assertDatabaseCount('productos', 1);
        $this->get('/erp/inventario?buscar=Compartido')->assertOk()->assertSee('href="'.url('/erp/productos/'.$product->id.'/movimientos').'"', false);
    }

    public function test_users_stay_inside_erp_and_keep_existing_delete_guards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get('/erp/usuarios/create')->assertOk()->assertSee('action="'.url('/erp/usuarios').'"', false);
        $this->post('/erp/usuarios', ['name' => 'Temporal', 'email' => 'temp@example.test', 'role' => 'usuario',
            'permissions' => ['erp'], 'password' => 'TestOnly2026!', 'password_confirmation' => 'TestOnly2026!'])->assertRedirect('/erp/usuarios');
        $employee = User::where('email', 'temp@example.test')->sole();
        $this->put('/erp/usuarios/'.$employee->id, ['name' => 'Actualizado', 'role' => 'usuario', 'permissions' => ['erp']])->assertRedirect('/erp/usuarios');
        $this->delete('/erp/usuarios/'.$admin->id)->assertRedirect('/erp/usuarios')->assertSessionHas('error');
        $this->delete('/erp/usuarios/'.$employee->id)->assertRedirect('/erp/usuarios')->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_native_context_is_not_changed_by_an_erp_visit_or_a_query_parameter(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/erp/clientes')->assertOk()->assertSee('Menú ERP');
        $this->get('/clientes?context=erp')->assertOk()->assertSee('Menú CRM')->assertDontSee('Menú ERP');
        $this->get('/scm/productos')->assertOk()->assertSee('Menú SCM')->assertDontSee('Menú ERP');
        $this->get('/admin/usuarios/create')->assertOk()->assertSee('action="'.url('/admin/usuarios').'"', false)->assertDontSee('Menú ERP');
    }

    public function test_erp_routes_require_both_erp_and_the_original_permissions(): void
    {
        foreach ([['erp'], ['crm'], ['scm'], ['erp', 'crm'], ['erp', 'scm']] as $permissions) {
            $this->actingAs(User::factory()->create(['permissions' => $permissions]));
            $this->get('/erp/clientes')->assertStatus(in_array('erp', $permissions) && in_array('crm', $permissions) ? 200 : 403);
            $this->get('/erp/productos')->assertStatus(in_array('erp', $permissions) && in_array('scm', $permissions) ? 200 : 403);
            $this->get('/erp/inventario')->assertStatus(in_array('erp', $permissions) && in_array('scm', $permissions) ? 200 : 403);
            $this->get('/erp/usuarios')->assertForbidden();
            $this->post('/erp/usuarios', [])->assertForbidden();
            $this->post('/erp/productos', [])->assertStatus(in_array('erp', $permissions) && in_array('scm', $permissions) ? 302 : 403);
        }
        $client = User::factory()->create(['role' => 'cliente', 'permissions' => ['erp', 'crm', 'scm']]);
        $this->actingAs($client)->get('/erp/clientes')->assertForbidden();
        $this->get('/erp/productos')->assertForbidden();
        $this->get('/erp/usuarios')->assertForbidden();
    }
}
