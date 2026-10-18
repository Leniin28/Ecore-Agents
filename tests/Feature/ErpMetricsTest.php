<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpMetricsTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_metrics_count_only_processed_orders_and_reuse_crm_and_scm(): void
    {
        $user = $this->erpUser();
        $first = $this->erpProduct(100, price: '0.35');
        $second = $this->erpProduct(50, price: '0.20');
        $processed = $this->erpOrder($user, [['producto_id' => $first->id, 'cantidad' => 10], ['producto_id' => $second->id, 'cantidad' => 5]]);
        $pending = $this->erpOrder($user, [['producto_id' => $first->id, 'cantidad' => 20]]);
        $cancelled = $this->erpOrder($user, [['producto_id' => $first->id, 'cantidad' => 30]]);
        $this->postJson("/api/erp/ordenes/{$processed->id}/procesar")->assertOk();
        $this->postJson("/api/erp/ordenes/{$cancelled->id}/cancelar")->assertOk();
        Cliente::factory()->inactivo()->create();

        $this->getJson('/api/erp/metricas')->assertOk()
            ->assertJsonPath('data.ventas_totales', 4.5)
            ->assertJsonPath('data.ordenes_procesadas', 1)
            ->assertJsonPath('data.productos_vendidos', 15)
            ->assertJsonPath('data.clientes_activos', 3)
            ->assertJsonPath('data.inventario_disponible', 135)
            ->assertJsonPath('data.ordenes_por_estado.pendiente', 1)
            ->assertJsonPath('data.ordenes_por_estado.cancelada', 1)
            ->assertJsonPath('data.recursos_mas_vendidos.0.cantidad', 10);
        $this->get('/erp')->assertOk()->assertSee('Ventas por mes')->assertSee('Unidades IA vendidas');
        $this->get('/erp/reportes')->assertOk()->assertSee('Clientes con mayor actividad comercial')
            ->assertSee($processed->cliente->nombre)->assertSee('Inventario disponible SCM');
    }

    public function test_month_chart_uses_processing_date_and_excludes_older_sales(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(15));
        $user = $this->erpUser();
        $product = $this->erpProduct();
        $order = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 10]]);
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertOk();
        $order->update(['fecha' => now()->subMonths(8), 'procesada_at' => now()->startOfMonth()->subMonths(2)]);
        $response = $this->getJson('/api/erp/metricas')->assertOk();
        $months = $response->json('data.ventas_mensuales');
        $this->assertCount(6, $months);
        $this->assertSame(now()->startOfMonth()->subMonths(5)->format('Y-m'), $months[0]['mes']);
        $this->assertSame(3.5, (float) $months[3]['ventas']);
        $order->update(['procesada_at' => now()->subMonths(7)]);
        $response = $this->getJson('/api/erp/metricas')->assertOk();
        $this->assertSame(0.0, (float) array_sum(array_column($response->json('data.ventas_mensuales'), 'ventas')));
        $this->assertSame(3.5, (float) $response->json('data.ventas_totales'));
    }

    public function test_empty_dashboard_reports_and_api_return_safe_zero_values(): void
    {
        $this->actingAs($this->erpUser())->getJson('/api/erp/metricas')->assertOk()
            ->assertJsonPath('data.ordenes_procesadas', 0)->assertJsonPath('data.productos_vendidos', 0)
            ->assertJsonPath('data.inventario_disponible', 0)->assertJsonCount(6, 'data.ventas_mensuales');
        $this->get('/erp')->assertOk()->assertSee('Sin órdenes registradas.')->assertSee('Aún no hay recursos vendidos.');
        $this->get('/erp/reportes')->assertOk()->assertSee('Sin inventario registrado.')->assertSee('Sin actividad comercial procesada.');
        $this->assertDatabaseCount('ordenes_empresariales', 0);
    }

    public function test_reporting_routes_require_erp_and_menus_follow_real_permissions(): void
    {
        foreach ([
            [User::ROLE_EMPLEADO, ['crm', 'erp'], ['CRM', 'ERP'], ['SCM']],
            [User::ROLE_EMPLEADO, ['scm', 'erp'], ['SCM', 'ERP'], ['CRM']],
        ] as [$role, $permissions, $shown, $hidden]) {
            $user = User::factory()->create(['role' => $role, 'permissions' => $permissions]);
            $html = $this->actingAs($user)->get('/erp')->assertOk()->getContent();
            preg_match('/<nav class="site-nav".*?<\/nav>/s', $html, $nav);
            foreach ($shown as $label) $this->assertStringContainsString('>'.$label.'</a>', $nav[0]);
            foreach ($hidden as $label) $this->assertStringNotContainsString('>'.$label.'</a>', $nav[0]);
            $this->get('/erp/reportes')->assertOk();
        }
        foreach ([$this->erpUser(['crm']), User::factory()->create(['role' => 'cliente'])] as $user) {
            $this->actingAs($user)->get('/erp/reportes')->assertForbidden();
            $this->getJson('/api/erp/metricas')->assertForbidden();
        }
    }
}
