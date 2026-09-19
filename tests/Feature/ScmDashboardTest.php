<?php

namespace Tests\Feature;

use App\Models\ConfiguracionScm;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScmDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_dashboard_and_metrics_are_safe(): void
    {
        $user = $this->scmUser();
        $this->actingAs($user)->get(route('scm.dashboard'))->assertOk()->assertSee('Dashboard SCM')->assertSee('Sin recursos registrados.');
        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.totales.productos', 0)->assertJsonPath('data.estrategias.porcentaje_push', 0)
            ->assertJsonPath('data.estrategias.porcentaje_pull', 0);
    }

    public function test_metrics_include_critical_rankings_strategy_comparison_and_recent_orders(): void
    {
        $user = $this->scmUser();
        $supplier = Proveedor::create(['nombre' => 'OpenRouter']);
        $critical = $this->product($supplier, 'Crítico', 4, 5, 'PUSH');
        $normal = $this->product($supplier, 'Normal', 20, 5, 'PULL');
        $this->movement($critical, $user, 8, now()->subDays(2));
        $this->movement($critical, $user, 99, now()->subDays(40));
        $this->movement($normal, $user, 2, now()->subDay());
        Pedido::create(['producto_id' => $critical->id, 'usuario_id' => null, 'cantidad' => 5,
            'tipo' => 'reposicion', 'estado' => 'pendiente', 'origen' => 'automatico_push']);

        $this->actingAs($user)->get(route('scm.dashboard'))->assertOk()->assertSee('Crítico')->assertSee('Normal')->assertSee('Últimos 30 días');
        $this->actingAs($user)->getJson(route('api.scm.metricas'))->assertOk()
            ->assertJsonPath('data.totales.productos', 2)->assertJsonPath('data.totales.proveedores', 1)
            ->assertJsonPath('data.totales.pedidos_pendientes', 1)->assertJsonPath('data.totales.inventario_critico', 1)
            ->assertJsonPath('data.estrategias.push', 1)->assertJsonPath('data.estrategias.pull', 1)
            ->assertJsonPath('data.recursos_mas_consumidos.0.nombre', 'Crítico')
            ->assertJsonPath('data.recursos_mas_consumidos.0.consumo_30_dias', 8)
            ->assertJsonPath('data.recursos_baja_utilizacion.0.nombre', 'Normal')->assertJsonCount(1, 'data.inventario_critico');
    }

    public function test_maturity_level_has_default_state_can_change_and_is_audited(): void
    {
        $user = $this->scmUser();
        $this->actingAs($user)->getJson(route('scm.estado'))->assertOk()->assertJsonPath('data.nivel_scm', 'inicial');
        $this->actingAs($user)->put(route('scm.nivel.update'), ['nivel_scm' => 'optimizado'])
            ->assertRedirect(route('scm.madurez.index'));
        $this->assertSame('optimizado', ConfiguracionScm::actual()->nivel_scm);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambiar_nivel_scm', 'entidad' => 'configuracion_scm']);
        $this->actingAs($user)->putJson(route('api.scm.nivel.update'), ['nivel_scm' => 'en_desarrollo'])
            ->assertOk()->assertJsonPath('data.nivel_label', 'En desarrollo');
    }

    public function test_invalid_maturity_level_is_rejected(): void
    {
        $this->actingAs($this->scmUser())->put(route('scm.nivel.update'), ['nivel_scm' => 'inventado'])
            ->assertSessionHasErrors('nivel_scm');
        $this->assertSame('inicial', ConfiguracionScm::actual()->nivel_scm);
    }

    public function test_dashboard_maturity_and_metrics_require_scm_permission(): void
    {
        $crmOnly = User::factory()->create(['permissions' => ['crm']]);
        $this->actingAs($crmOnly)->get(route('scm.dashboard'))->assertForbidden();
        $this->actingAs($crmOnly)->get(route('scm.madurez.index'))->assertForbidden();
        $this->actingAs($crmOnly)->getJson(route('api.scm.metricas'))->assertForbidden();
    }

    private function scmUser(): User
    {
        return User::factory()->create(['permissions' => ['scm']]);
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
