<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpOrdersTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_order_reuses_client_and_products_calculates_price_and_does_not_change_stock(): void
    {
        $user = $this->erpUser();
        $client = Cliente::factory()->create();
        $first = $this->erpProduct();
        $second = $this->erpProduct(price: '0.20');
        $this->actingAs($user)->postJson('/api/erp/ordenes', [
            'cliente_id' => $client->id, 'usuario_id' => 999, 'estado' => 'procesada', 'total' => 1,
            'detalles' => [
                ['producto_id' => $first->id, 'cantidad' => 3, 'precio_unitario' => 1, 'subtotal' => 1],
                ['producto_id' => $second->id, 'cantidad' => 2],
            ],
        ])->assertCreated()->assertJsonPath('data.total', '1.45')
            ->assertJsonPath('data.estado', 'pendiente')->assertJsonPath('data.usuario_id', $user->id)
            ->assertJsonPath('data.detalles.0.precio_unitario', '0.35');
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('productos', 2);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(100, $first->fresh()->stock_actual);
        $this->assertSame($client->id, OrdenEmpresarial::first()->cliente->id);
        $this->assertDatabaseHas('auditorias', ['accion' => 'crear_orden_erp']);
    }

    public function test_snapshot_survives_product_price_change(): void
    {
        $product = $this->erpProduct();
        $order = $this->erpOrder($this->erpUser(), [['producto_id' => $product->id, 'cantidad' => 10]]);
        $product->update(['precio_venta_unitario' => '9.99']);
        $this->getJson('/api/erp/ordenes/'.$order->id)->assertOk()->assertJsonPath('data.total', '3.50')
            ->assertJsonPath('data.detalles.0.precio_unitario', '0.35');
    }

    public function test_invalid_empty_duplicate_and_nonpositive_lines_are_rejected_atomically(): void
    {
        $user = $this->erpUser();
        $product = $this->erpProduct();
        $client = Cliente::factory()->create();
        foreach ([[], [['producto_id' => $product->id, 'cantidad' => 0]],
            [['producto_id' => 9999, 'cantidad' => 1]],
            [['producto_id' => $product->id, 'cantidad' => -1]],
            [['producto_id' => $product->id, 'cantidad' => 1], ['producto_id' => $product->id, 'cantidad' => 1]],
        ] as $lines) {
            $this->actingAs($user)->postJson('/api/erp/ordenes', ['cliente_id' => $client->id, 'detalles' => $lines])->assertUnprocessable();
        }
        $this->postJson('/api/erp/ordenes', ['cliente_id' => 99999, 'detalles' => [['producto_id' => $product->id, 'cantidad' => 1]]])->assertUnprocessable();
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('orden_detalles', 0);
    }

    public function test_web_wizard_listing_detail_and_api_permissions(): void
    {
        $product = $this->erpProduct();
        $order = $this->erpOrder($this->erpUser(), [['producto_id' => $product->id, 'cantidad' => 2]]);
        $this->get('/erp/ordenes/create')->assertOk()->assertSee('Seleccionar cliente')->assertSee('Resumen y confirmación');
        $this->get('/erp/ordenes')->assertOk()->assertSee($order->folio());
        $this->get('/erp/ordenes/'.$order->id)->assertOk()->assertSee('Precio pactado');
        $this->getJson('/api/erp/ordenes')->assertOk()->assertJsonPath('data.data.0.id', $order->id);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CLIENTE]))->getJson('/api/erp/ordenes/'.$order->id)->assertForbidden();
        $this->postJson('/api/erp/ordenes', [])->assertForbidden();
        $this->actingAs($this->erpUser(['crm']))->get('/erp/ordenes/create')->assertForbidden();
    }

    public function test_erp_references_protect_customer_and_product_deletion(): void
    {
        $product = $this->erpProduct();
        $order = $this->erpOrder($this->erpUser(), [['producto_id' => $product->id, 'cantidad' => 1]]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->deleteJson('/api/clientes/'.$order->cliente_id)->assertUnprocessable();
        $this->deleteJson('/api/scm/productos/'.$product->id)->assertUnprocessable();
        $this->assertDatabaseHas('clientes', ['id' => $order->cliente_id]);
        $this->assertDatabaseHas('productos', ['id' => $product->id]);
    }
}
