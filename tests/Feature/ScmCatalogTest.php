<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScmCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_scm_permission_is_required_for_web_and_api(): void
    {
        $crmEmployee = User::factory()->create(['permissions' => ['crm']]);
        $scmEmployee = User::factory()->create(['permissions' => ['scm']]);

        $this->actingAs($crmEmployee)->get(route('scm.productos.index'))->assertForbidden();
        $this->actingAs($crmEmployee)->getJson(route('api.scm.productos.index'))->assertForbidden();
        $this->actingAs($scmEmployee)->get(route('scm.productos.index'))->assertOk();
        $this->actingAs($scmEmployee)->getJson(route('api.scm.productos.index'))->assertOk();
    }

    public function test_supplier_crud_and_audit_work(): void
    {
        $user = $this->scmUser();

        $this->actingAs($user)->post(route('scm.proveedores.store'), [
            'nombre' => 'OpenRouter', 'contacto' => 'Soporte', 'correo' => 'support@example.test', 'telefono' => '555-0101',
        ])->assertRedirect(route('scm.proveedores.index'));

        $supplier = Proveedor::query()->firstOrFail();
        $this->actingAs($user)->put(route('scm.proveedores.update', $supplier), [
            'nombre' => 'OpenRouter API', 'contacto' => '', 'correo' => '', 'telefono' => '',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('proveedores', ['id' => $supplier->id, 'nombre' => 'OpenRouter API']);
        $this->assertDatabaseHas('auditorias', ['accion' => 'editar', 'entidad' => 'proveedor', 'entidad_id' => $supplier->id]);

        $this->actingAs($user)->delete(route('scm.proveedores.destroy', $supplier))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('proveedores', ['id' => $supplier->id]);
    }

    public function test_product_crud_validation_relations_and_filters_work(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'OpenRouter']);

        $this->actingAs($user)->post(route('scm.productos.store'), $this->productData($supplier, [
            'nombre' => 'Modelo académico', 'categoria' => 'Lenguaje', 'estrategia_logistica' => Producto::ESTRATEGIA_PUSH,
        ]))->assertSessionHasNoErrors();

        $product = Producto::query()->firstOrFail();
        $this->assertTrue($product->proveedor->is($supplier));
        $this->assertTrue($supplier->fresh()->productos->contains($product));

        $this->actingAs($user)->get(route('scm.productos.index', ['buscar' => 'académico', 'categoria' => 'Lenguaje', 'estrategia' => 'PUSH']))
            ->assertOk()->assertSee('Modelo académico');

        $this->actingAs($user)->post(route('scm.productos.store'), $this->productData($supplier, ['stock_actual' => -1]))
            ->assertSessionHasErrors('stock_actual');

        $this->actingAs($user)->delete(route('scm.productos.destroy', $product))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('productos', ['id' => $product->id]);
    }

    public function test_supplier_with_products_cannot_be_deleted(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor ocupado']);
        Producto::create($this->productData($supplier));

        $this->actingAs($user)->delete(route('scm.proveedores.destroy', $supplier))
            ->assertSessionHasErrors('proveedor');
        $this->assertDatabaseHas('proveedores', ['id' => $supplier->id]);
    }

    public function test_catalog_api_supports_create_list_update_delete_and_filters(): void
    {
        $user = $this->scmUser();
        $supplierResponse = $this->actingAs($user)->postJson(route('api.scm.proveedores.store'), [
            'nombre' => 'OpenRouter', 'correo' => 'api@example.test',
        ])->assertCreated()->assertJsonPath('data.nombre', 'OpenRouter');
        $supplier = Proveedor::findOrFail($supplierResponse->json('data.id'));

        $response = $this->actingAs($user)->postJson(route('api.scm.productos.store'), $this->productData($supplier, [
            'nombre' => 'Llama QA', 'categoria' => 'Texto',
        ]))->assertCreated()->assertJsonPath('data.proveedor.nombre', 'OpenRouter');
        $product = Producto::findOrFail($response->json('data.id'));

        $this->actingAs($user)->getJson(route('api.scm.productos.index', ['categoria' => 'Texto']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'Llama QA');
        $this->actingAs($user)->putJson(route('api.scm.productos.update', $product), $this->productData($supplier, ['nombre' => 'Llama actualizado']))
            ->assertOk()->assertJsonPath('data.nombre', 'Llama actualizado');
        $this->actingAs($user)->deleteJson(route('api.scm.productos.destroy', $product))->assertNoContent();
        $this->actingAs($user)->deleteJson(route('api.scm.proveedores.destroy', $supplier))->assertNoContent();
    }

    private function scmUser(): User
    {
        return User::factory()->create(['permissions' => ['scm']]);
    }

    private function productData(Proveedor $supplier, array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Recurso IA', 'descripcion' => 'Capacidad académica', 'categoria' => 'General',
            'stock_actual' => 20, 'stock_minimo' => 5, 'proveedor_id' => $supplier->id,
            'costo_unitario' => 1.25, 'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
        ], $overrides);
    }
}
