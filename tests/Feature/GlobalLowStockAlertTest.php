<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalLowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_stock_at_or_below_minimum_appears_in_one_global_modal(): void
    {
        $this->product(101, 100, 'Normal');
        $this->product(100, 100, 'En mínimo');
        $this->product(70, 100, 'Bajo mínimo');

        $response = $this->actingAs($this->user(['scm']))->get(route('profile'));

        $response->assertOk()->assertSee('2 recursos requieren atención.')
            ->assertSee('En mínimo')->assertSee('Bajo mínimo')
            ->assertSee('Stock bajo: 2')->assertDontSee('Normal')
            ->assertSee('role="dialog"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="stock-alert-title"'));
    }

    public function test_admin_and_scm_employee_receive_alert_on_non_inventory_pages(): void
    {
        $this->product(5, 5, 'Capacidad IA');

        $this->actingAs($this->user([], User::ROLE_ADMIN))->get(route('admin.usuarios.index'))
            ->assertOk()->assertSee('Alerta de inventario')->assertSee('Stock bajo: 1');

        $this->flushSession();
        $this->actingAs($this->user(['scm']))->get(route('profile'))
            ->assertOk()->assertSee('Alerta de inventario')->assertSee('Stock bajo: 1');
    }

    public function test_crm_employee_client_and_visitor_never_receive_scm_alerts(): void
    {
        $this->product(0, 5);

        foreach ([$this->user(['crm']), $this->user([], User::ROLE_CLIENTE)] as $user) {
            $this->actingAs($user)->get(route('profile'))
                ->assertOk()->assertDontSee('Alerta de inventario')->assertDontSee('Stock bajo:');
        }

        $this->app['auth']->logout();
        $this->get(route('home'))->assertOk()
            ->assertDontSee('Alerta de inventario')->assertDontSee('Stock bajo:');
    }

    public function test_modal_is_not_repeated_but_indicator_remains_until_recovery_and_realerts_after_new_drop(): void
    {
        $product = $this->product(5, 5);
        $this->actingAs($this->user(['scm']));

        $this->get(route('profile'))->assertSee('Alerta de inventario')->assertSee('Stock bajo: 1');
        $this->get(route('scm.dashboard'))->assertDontSee('Alerta de inventario')->assertSee('Stock bajo: 1');

        $product->update(['stock_actual' => 6]);
        $this->get(route('profile'))->assertDontSee('Stock bajo:')->assertDontSee('Alerta de inventario');

        $product->update(['stock_actual' => 5]);
        $this->get(route('profile'))->assertSee('Alerta de inventario')->assertSee('Stock bajo: 1');
    }

    public function test_exit_to_minimum_alerts_after_redirect_and_preserves_push_order(): void
    {
        $product = $this->product(120, 100, 'API prueba', Producto::ESTRATEGIA_PUSH);
        $this->actingAs($this->user(['scm']));

        $this->get(route('profile'))->assertDontSee('Alerta de inventario');
        $this->post(route('scm.movimientos.store'), $this->movement($product, 'salida', 20))
            ->assertRedirect(route('scm.movimientos.index'));

        $this->assertSame(100, $product->fresh()->stock_actual);
        $this->get(route('scm.movimientos.index'))->assertSee('Alerta de inventario')
            ->assertSee('API prueba')->assertSee('Stock actual: 100')
            ->assertSee('Stock mínimo: 100')->assertSee('Stock bajo: 1');
        $this->assertDatabaseHas('pedidos', [
            'producto_id' => $product->id, 'origen' => Pedido::ORIGEN_AUTOMATICO_PUSH,
        ]);
    }

    public function test_pull_alerts_without_automatic_order_and_fulfillment_clears_indicator(): void
    {
        $product = $this->product(120, 100, 'Recurso PULL');
        $user = $this->user(['scm']);
        $this->actingAs($user);

        $this->post(route('scm.movimientos.store'), $this->movement($product, 'salida', 20))
            ->assertRedirect(route('scm.movimientos.index'));
        $this->get(route('profile'))->assertSee('Alerta de inventario')->assertSee('Stock bajo: 1');
        $this->assertDatabaseCount('pedidos', 0);

        $order = Pedido::create([
            'producto_id' => $product->id, 'usuario_id' => $user->id, 'cantidad' => 20,
            'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_PENDIENTE,
            'origen' => Pedido::ORIGEN_MANUAL,
        ]);
        $this->put(route('scm.pedidos.estado.update', $order), ['estado' => Pedido::ESTADO_SURTIDO])
            ->assertRedirect(route('scm.pedidos.index'));

        $this->assertSame(120, $product->fresh()->stock_actual);
        $this->get(route('scm.pedidos.index'))->assertDontSee('Stock bajo:')->assertDontSee('Alerta de inventario');

        $this->post(route('scm.movimientos.store'), $this->movement($product, 'salida', 20));
        $this->get(route('profile'))->assertSee('Alerta de inventario');
    }

    private function user(array $permissions, string $role = User::ROLE_EMPLEADO): User
    {
        return User::factory()->create(['role' => $role, 'permissions' => $permissions]);
    }

    private function product(int $stock, int $minimum, string $name = 'Recurso IA', string $strategy = Producto::ESTRATEGIA_PULL): Producto
    {
        $supplier = Proveedor::create(['nombre' => 'Proveedor '.uniqid()]);

        return Producto::create([
            'nombre' => $name, 'categoria' => 'General', 'stock_actual' => $stock,
            'stock_minimo' => $minimum, 'proveedor_id' => $supplier->id,
            'costo_unitario' => 1, 'estrategia_logistica' => $strategy,
        ]);
    }

    private function movement(Producto $product, string $type, int $quantity): array
    {
        return [
            'producto_id' => $product->id, 'tipo' => $type, 'cantidad' => $quantity,
            'motivo' => $type === 'entrada' ? 'reposicion' : 'venta',
            'fecha' => '2026-09-19 12:00:00',
        ];
    }
}
