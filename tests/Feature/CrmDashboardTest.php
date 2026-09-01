<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CrmDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_crm_dashboard(): void
    {
        $this->get(route('admin'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_crm_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('Métricas CRM');
    }

    public function test_dashboard_has_correct_total_customers(): void
    {
        Cliente::factory()->count(3)->create();

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['total_clientes'] === 3);
    }

    public function test_dashboard_has_correct_active_customers(): void
    {
        Cliente::factory()->count(2)->create();
        Cliente::factory()->inactivo()->create();

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['clientes_activos'] === 2);
    }

    public function test_dashboard_has_correct_inactive_customers(): void
    {
        Cliente::factory()->create();
        Cliente::factory()->inactivo()->count(2)->create();

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['clientes_inactivos'] === 2);
    }

    public function test_dashboard_has_correct_total_interactions(): void
    {
        Interaccion::factory()->count(4)->create();

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['total_interacciones'] === 4);
    }

    public function test_customer_without_interactions_counts_as_without_recent_interaction(): void
    {
        Cliente::factory()->create();

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['clientes_sin_interaccion_reciente'] === 1);
    }

    public function test_old_interaction_counts_as_not_recent(): void
    {
        Interaccion::factory()->create(['fecha' => now()->subDays(31)]);

        $this->dashboard()->assertViewHas('metricas', fn (array $metricas) => $metricas['clientes_sin_interaccion_reciente'] === 1);
    }

    public function test_recent_interaction_does_not_make_customer_at_risk(): void
    {
        $cliente = Cliente::factory()->create();
        Interaccion::factory()->create(['cliente_id' => $cliente->id, 'fecha' => now()->subDays(2)]);

        $this->dashboard()->assertViewHas(
            'clientesEnRiesgo',
            fn (Collection $clientes) => ! $clientes->contains($cliente),
        );
    }

    public function test_inactive_customer_does_not_appear_at_risk(): void
    {
        $cliente = Cliente::factory()->inactivo()->create();

        $this->dashboard()->assertViewHas(
            'clientesEnRiesgo',
            fn (Collection $clientes) => ! $clientes->contains($cliente),
        );
    }

    public function test_dashboard_counts_interactions_per_customer(): void
    {
        $cliente = Cliente::factory()->create();
        Interaccion::factory()->count(3)->create(['cliente_id' => $cliente->id]);

        $this->dashboard()->assertViewHas(
            'clientesConMayorActividad',
            fn (Collection $clientes) => $clientes->firstWhere('id', $cliente->id)?->interacciones_count === 3,
        );
    }

    public function test_dashboard_works_without_customers(): void
    {
        $this->dashboard()
            ->assertOk()
            ->assertSee('Aún no hay clientes para mostrar en la gráfica.')
            ->assertViewHas('porcentajeActivos', 0)
            ->assertViewHas('porcentajeInactivos', 0);
    }

    public function test_guest_cannot_access_crm_metrics_api(): void
    {
        $this->getJson(route('api.crm.metricas'))->assertUnauthorized();
    }

    public function test_authenticated_user_receives_crm_metrics_json(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.crm.metricas'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'total_clientes',
                'clientes_activos',
                'clientes_inactivos',
                'total_interacciones',
                'clientes_sin_interaccion_reciente',
            ]]);
    }

    public function test_crm_metrics_api_returns_correct_values(): void
    {
        $user = User::factory()->create();
        $activo = Cliente::factory()->create();
        Cliente::factory()->inactivo()->create();
        Interaccion::factory()->count(2)->create(['cliente_id' => $activo->id, 'fecha' => now()->subDay()]);

        $this->actingAs($user)
            ->getJson(route('api.crm.metricas'))
            ->assertJsonPath('data.total_clientes', 2)
            ->assertJsonPath('data.clientes_activos', 1)
            ->assertJsonPath('data.clientes_inactivos', 1)
            ->assertJsonPath('data.total_interacciones', 2)
            ->assertJsonPath('data.clientes_sin_interaccion_reciente', 1);
    }

    public function test_guest_cannot_open_my_activity(): void
    {
        $this->get(route('mi-actividad'))->assertRedirect(route('login'));
    }

    public function test_user_sees_their_own_interactions(): void
    {
        $user = User::factory()->create();
        $interaccion = Interaccion::factory()->create(['usuario_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('mi-actividad'))
            ->assertOk()
            ->assertSee($interaccion->descripcion)
            ->assertSee($interaccion->cliente->nombre);
    }

    public function test_user_does_not_see_another_users_interactions(): void
    {
        $user = User::factory()->create();
        $propia = Interaccion::factory()->create(['usuario_id' => $user->id]);
        $ajena = Interaccion::factory()->create();

        $this->actingAs($user)
            ->get(route('mi-actividad'))
            ->assertSee($propia->descripcion)
            ->assertDontSee($ajena->descripcion);
    }

    public function test_my_activity_is_ordered_newest_first(): void
    {
        $user = User::factory()->create();
        Interaccion::factory()->create(['usuario_id' => $user->id, 'descripcion' => 'Actividad anterior', 'fecha' => now()->subDays(3)]);
        Interaccion::factory()->create(['usuario_id' => $user->id, 'descripcion' => 'Actividad reciente', 'fecha' => now()->subDay()]);

        $this->actingAs($user)
            ->get(route('mi-actividad'))
            ->assertSeeInOrder(['Actividad reciente', 'Actividad anterior']);
    }

    public function test_user_without_activity_sees_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('mi-actividad'))
            ->assertOk()
            ->assertSee('Sin actividad registrada');
    }

    private function dashboard()
    {
        return $this->actingAs(User::factory()->create())->get(route('admin'));
    }
}
