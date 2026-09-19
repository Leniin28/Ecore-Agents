<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScmReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_permissions_and_navigation_are_consistent(): void
    {
        $scm = User::factory()->create(['role' => User::ROLE_EMPLEADO, 'permissions' => [User::MODULE_SCM]]);
        $crm = User::factory()->create(['role' => User::ROLE_EMPLEADO, 'permissions' => [User::MODULE_CRM]]);
        $client = User::factory()->create(['role' => User::ROLE_CLIENTE, 'permissions' => []]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => []]);

        $this->actingAs($scm)->get(route('scm.reportes'))->assertOk()->assertSee('Madurez')->assertSee('Reportes');
        $this->actingAs($admin)->get(route('scm.reportes'))->assertOk();
        $this->actingAs($crm)->get(route('scm.reportes'))->assertForbidden();
        $this->actingAs($client)->get(route('scm.reportes'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin'))->assertOk()->assertSee('Usuarios');
        $this->actingAs($scm)->get(route('scm.dashboard'))->assertOk()->assertDontSee('Usuarios');
    }

    public function test_report_kpis_come_from_persisted_records(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor Uno']);
        $this->product($supplier, 'Crítico', 3, 5, 'PUSH');
        $normal = $this->product($supplier, 'Normal', 12, 5, 'PULL');
        Pedido::create(['producto_id' => $normal->id, 'usuario_id' => $user->id, 'cantidad' => 4, 'tipo' => 'venta', 'estado' => 'pendiente', 'origen' => 'manual']);

        $this->actingAs($user)->get(route('scm.reportes'))->assertOk()
            ->assertSeeInOrder(['Recursos IA', '>2<', 'Proveedores', '>1<', 'Pedidos pendientes', 'Stock bajo']);
        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.totales.productos', 2)
            ->assertJsonPath('data.totales.proveedores', 1)
            ->assertJsonPath('data.totales.pedidos_pendientes', 1)
            ->assertJsonPath('data.totales.inventario_critico', 1);
    }

    public function test_top_consumption_uses_only_exits_from_the_last_thirty_days(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor']);
        $first = $this->product($supplier, 'Primero', 20, 5, 'PUSH');
        $second = $this->product($supplier, 'Segundo', 20, 5, 'PULL');
        $this->movement($first, $user, 9, now()->subDays(3));
        $this->movement($first, $user, 100, now()->subDays(31));
        $this->movement($second, $user, 11, now()->subDays(2));
        MovimientoInventario::create(['producto_id' => $first->id, 'usuario_id' => $user->id, 'tipo' => 'entrada', 'cantidad' => 200, 'motivo' => 'compra', 'fecha' => now()->subDay()]);

        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.recursos_mas_consumidos.0.nombre', 'Segundo')
            ->assertJsonPath('data.recursos_mas_consumidos.0.consumo_30_dias', 11)
            ->assertJsonPath('data.recursos_mas_consumidos.1.consumo_30_dias', 9);
    }

    public function test_rotation_classifies_high_medium_and_low_and_calculates_percentage(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor']);
        $high = $this->product($supplier, 'Alta', 20, 10, 'PUSH');
        $medium = $this->product($supplier, 'Media', 20, 10, 'PULL');
        $this->product($supplier, 'Baja', 20, 10, 'PULL');
        $this->movement($high, $user, 10, now()->subDay());
        $this->movement($medium, $user, 4, now()->subDay());

        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.rotacion.alta', 1)
            ->assertJsonPath('data.rotacion.media', 1)
            ->assertJsonPath('data.rotacion.baja', 1)
            ->assertJsonPath('data.rotacion.porcentaje_movimiento', 67);
    }

    public function test_empty_reports_are_safe_and_rotation_percentage_is_zero(): void
    {
        $user = $this->scmUser();
        $this->actingAs($user)->get(route('scm.reportes'))->assertOk()
            ->assertSee('Sin recursos registrados.')
            ->assertSee('No hay recursos en nivel crítico.')
            ->assertSee('0%');
        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.rotacion.total', 0)
            ->assertJsonPath('data.rotacion.porcentaje_movimiento', 0)
            ->assertJsonCount(6, 'data.consumo_push_pull_mensual.meses');
    }

    public function test_critical_products_are_ordered_by_largest_shortage(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor']);
        $this->product($supplier, 'Déficit mayor', 1, 10, 'PUSH');
        $this->product($supplier, 'Déficit menor', 8, 10, 'PULL');
        $this->product($supplier, 'Normal', 11, 10, 'PULL');

        $this->actingAs($user)->get(route('scm.reportes'))->assertOk()
            ->assertSee('Inventario crítico')
            ->assertSeeInOrder(['Déficit mayor', 'Déficit menor'])
            ->assertDontSee('Normal</td>', false);
    }

    public function test_monthly_consumption_uses_six_months_and_current_strategy(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(15));
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'Proveedor']);
        $product = $this->product($supplier, 'Recurso', 20, 5, 'PULL');
        $this->movement($product, $user, 7, now()->startOfMonth()->subMonths(2)->addDays(4));

        $monthKey = now()->startOfMonth()->subMonths(2)->format('Y-m');
        $response = $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk();
        $months = collect($response->json('data.consumo_push_pull_mensual.meses'));
        $target = $months->firstWhere('clave', $monthKey);

        $this->assertCount(6, $months);
        $this->assertSame(0, $target['push']);
        $this->assertSame(7, $target['pull']);
    }

    public function test_maturity_page_does_not_contain_report_charts(): void
    {
        $this->actingAs($this->scmUser())->get(route('scm.madurez.index'))->assertOk()
            ->assertSee('Madurez SCM')
            ->assertDontSee('Top 5 recursos más consumidos')
            ->assertDontSee('Consumo mensual por estrategia actual');
    }

    private function scmUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_EMPLEADO, 'permissions' => [User::MODULE_SCM]]);
    }

    private function product(Proveedor $supplier, string $name, int $stock, int $minimum, string $strategy): Producto
    {
        return Producto::create(['nombre' => $name, 'categoria' => 'IA', 'stock_actual' => $stock, 'stock_minimo' => $minimum,
            'proveedor_id' => $supplier->id, 'costo_unitario' => 1, 'estrategia_logistica' => $strategy]);
    }

    private function movement(Producto $product, User $user, int $quantity, $date): MovimientoInventario
    {
        return MovimientoInventario::create(['producto_id' => $product->id, 'usuario_id' => $user->id, 'tipo' => 'salida',
            'cantidad' => $quantity, 'motivo' => 'venta', 'fecha' => $date]);
    }
}
