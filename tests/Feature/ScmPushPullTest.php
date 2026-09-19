<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScmPushPullTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_above_minimum_does_not_generate_order(): void
    {
        $product = $this->product(10, 5, Producto::ESTRATEGIA_PUSH);
        $this->exit($product, 4)->assertSessionHasNoErrors();
        $this->assertSame(6, $product->fresh()->stock_actual);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_push_at_or_below_minimum_generates_order_for_minimum_quantity(): void
    {
        $atMinimum = $this->product(10, 5, Producto::ESTRATEGIA_PUSH, 'Al mínimo');
        $below = $this->product(10, 5, Producto::ESTRATEGIA_PUSH, 'Bajo mínimo');

        $this->exit($atMinimum, 5);
        $this->exit($below, 7);

        $this->assertDatabaseHas('pedidos', ['producto_id' => $atMinimum->id, 'cantidad' => 5, 'origen' => 'automatico_push']);
        $this->assertDatabaseHas('pedidos', ['producto_id' => $below->id, 'cantidad' => 5, 'origen' => 'automatico_push']);
        $this->assertDatabaseHas('auditorias', ['usuario_id' => null, 'accion' => 'pedido_push_automatico']);
    }

    public function test_push_does_not_duplicate_a_pending_automatic_order(): void
    {
        $product = $this->product(10, 5, Producto::ESTRATEGIA_PUSH);
        $this->exit($product, 5);
        $this->exit($product, 1);

        $this->assertSame(1, Pedido::query()->where('producto_id', $product->id)->count());
    }

    public function test_pull_never_generates_automatically_but_allows_manual_order(): void
    {
        $product = $this->product(10, 5, Producto::ESTRATEGIA_PULL);
        $this->exit($product, 7);
        $this->assertDatabaseCount('pedidos', 0);

        $user = $this->scmUser();
        $this->actingAs($user)->post(route('scm.pedidos.store'), [
            'producto_id' => $product->id, 'cantidad' => 8, 'tipo' => Pedido::TIPO_REPOSICION,
        ])->assertRedirect(route('scm.pedidos.index'));

        $this->assertDatabaseHas('pedidos', ['producto_id' => $product->id, 'cantidad' => 8, 'origen' => 'manual', 'usuario_id' => $user->id]);
    }

    public function test_fulfill_is_atomic_increases_stock_and_creates_entry_once(): void
    {
        $user = $this->scmUser();
        $product = $this->product(3, 5, Producto::ESTRATEGIA_PULL);
        $order = Pedido::create([
            'producto_id' => $product->id, 'usuario_id' => $user->id, 'cantidad' => 7,
            'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_PENDIENTE, 'origen' => Pedido::ORIGEN_MANUAL,
        ]);

        $this->actingAs($user)->put(route('scm.pedidos.estado.update', $order), ['estado' => 'surtido'])
            ->assertRedirect(route('scm.pedidos.index'));

        $this->assertSame(10, $product->fresh()->stock_actual);
        $this->assertSame(Pedido::ESTADO_SURTIDO, $order->fresh()->estado);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $product->id, 'usuario_id' => $user->id, 'tipo' => 'entrada', 'motivo' => 'reposicion', 'cantidad' => 7,
        ]);

        $this->actingAs($user)->put(route('scm.pedidos.estado.update', $order), ['estado' => 'surtido'])
            ->assertSessionHasErrors('estado');
        $this->assertSame(10, $product->fresh()->stock_actual);
        $this->assertSame(1, MovimientoInventario::query()->where('producto_id', $product->id)->count());
    }

    public function test_strategy_can_be_changed_and_comparison_counts_products(): void
    {
        $user = $this->scmUser();
        $push = $this->product(10, 5, Producto::ESTRATEGIA_PUSH, 'Push');
        $pull = $this->product(10, 5, Producto::ESTRATEGIA_PULL, 'Pull');

        $this->actingAs($user)->put(route('scm.productos.estrategia.update', $pull), ['estrategia_logistica' => 'PUSH'])
            ->assertSessionHasNoErrors();
        $this->assertSame('PUSH', $pull->fresh()->estrategia_logistica);
        $this->actingAs($user)->get(route('scm.logistica.comparativa'))->assertOk()->assertSee('Push')->assertSee('Pull');
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambiar_estrategia', 'entidad_id' => $pull->id]);
    }

    public function test_orders_api_supports_manual_creation_listing_and_fulfillment(): void
    {
        $user = $this->scmUser();
        $product = $this->product(2, 5, Producto::ESTRATEGIA_PULL);

        $response = $this->actingAs($user)->postJson(route('api.scm.pedidos.store'), [
            'producto_id' => $product->id, 'cantidad' => 5, 'tipo' => 'reposicion',
        ])->assertCreated()->assertJsonPath('data.origen', 'manual');
        $order = Pedido::findOrFail($response->json('data.id'));

        $this->actingAs($user)->getJson(route('api.scm.pedidos.index'))->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->putJson(route('api.scm.pedidos.estado.update', $order), ['estado' => 'surtido'])
            ->assertOk()->assertJsonPath('data.estado', 'surtido')->assertJsonPath('data.producto.stock_actual', 7);
    }

    public function test_scm_permission_protects_orders_and_logistics(): void
    {
        $crmOnly = User::factory()->create(['permissions' => ['crm']]);
        $this->actingAs($crmOnly)->get(route('scm.pedidos.index'))->assertForbidden();
        $this->actingAs($crmOnly)->get(route('scm.logistica.index'))->assertForbidden();
        $this->actingAs($crmOnly)->getJson(route('api.scm.pedidos.index'))->assertForbidden();
    }

    private function exit(Producto $product, int $quantity)
    {
        return $this->actingAs($this->scmUser())->post(route('scm.movimientos.store'), [
            'producto_id' => $product->id, 'tipo' => 'salida', 'cantidad' => $quantity,
            'motivo' => 'venta', 'fecha' => '2026-09-19 12:00:00',
        ]);
    }

    private function scmUser(): User
    {
        return User::factory()->create(['permissions' => ['scm']]);
    }

    private function product(int $stock, int $minimum, string $strategy, string $name = 'Recurso IA'): Producto
    {
        $supplier = Proveedor::create(['nombre' => 'Proveedor '.uniqid()]);

        return Producto::create([
            'nombre' => $name, 'categoria' => 'General', 'stock_actual' => $stock, 'stock_minimo' => $minimum,
            'proveedor_id' => $supplier->id, 'costo_unitario' => 1, 'estrategia_logistica' => $strategy,
        ]);
    }
}
