<?php

namespace Tests\Feature;

use App\Models\EstadoErp;
use App\Models\ProcesoInterno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpProcessesTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_order_history_records_actual_completed_steps_and_can_be_filtered(): void
    {
        $product = $this->erpProduct();
        $user = $this->erpUser();
        $order = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 2]]);
        $other = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 1]]);
        $this->assertSame(['orden_creada'], $order->procesosInternos()->pluck('tipo')->all());
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertOk();
        $this->assertSame(['orden_creada', 'stock_validado', 'inventario_actualizado', 'movimientos_registrados', 'orden_procesada'],
            $order->procesosInternos()->pluck('tipo')->all());
        $this->assertSame(1, $other->procesosInternos()->count());
        $this->assertSame([$user->id], $order->procesosInternos()->distinct()->pluck('usuario_id')->all());
        $this->get('/erp/procesos?orden='.$order->folio().'&tipo=orden_procesada&estado=completado')->assertOk()
            ->assertSee($order->folio())->assertDontSee($other->folio());
        $this->get('/erp/ordenes/'.$order->id)->assertOk()->assertSee('Historial operativo')->assertSee('Stock validado');
    }

    public function test_failed_processing_leaves_no_completed_processing_steps(): void
    {
        $product = $this->erpProduct(1);
        $order = $this->erpOrder($this->erpUser(), [['producto_id' => $product->id, 'cantidad' => 2]]);
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertUnprocessable();
        $this->assertSame(['orden_creada'], $order->procesosInternos()->pluck('tipo')->all());
        $this->postJson("/api/erp/ordenes/{$order->id}/cancelar")->assertOk();
        $this->assertDatabaseHas('procesos_internos', ['orden_empresarial_id' => $order->id, 'tipo' => 'orden_cancelada']);
    }

    public function test_global_level_validates_values_and_is_audited(): void
    {
        $this->actingAs($this->erpUser())->getJson('/api/erp/estado')->assertOk()->assertJsonPath('data.nivel', 'basico');
        foreach (array_keys(EstadoErp::NIVELES) as $level) {
            $this->putJson('/api/erp/estado', ['nivel' => $level])->assertOk()->assertJsonPath('data.nivel', $level);
        }
        $this->putJson('/api/erp/estado', ['nivel' => 'inventado'])->assertUnprocessable();
        $this->assertSame('optimizado', EstadoErp::actual()->nivel);
        $this->assertDatabaseCount('estado_erp', 1);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambiar_nivel_erp']);
        $this->get('/erp/nivel')->assertOk()->assertSee('Checklist de avance');
        $this->put('/erp/estado', ['nivel' => 'integrado'])->assertRedirect(route('erp.nivel'));
    }

    public function test_empty_processes_and_maturity_are_safe_and_require_erp(): void
    {
        $this->actingAs($this->erpUser())->get('/erp/procesos')->assertOk()->assertSee('Sin procesos registrados.');
        $this->get('/erp/nivel')->assertOk()->assertSee('Básico');
        $this->actingAs($this->erpUser(['scm']))->get('/erp/procesos')->assertForbidden();
        $this->putJson('/api/erp/estado', ['nivel' => 'optimizado'])->assertForbidden();
        $this->assertDatabaseCount('procesos_internos', 0);
    }

    public function test_level_progress_is_cumulative_accessible_and_does_not_change_saved_data(): void
    {
        $this->actingAs($this->erpUser());
        $estado = EstadoErp::actual();
        foreach (array_keys(EstadoErp::NIVELES) as $index => $nivel) {
            $estado->update(['nivel' => $nivel]);
            $saved = $estado->fresh()->getAttributes();
            $percentage = ($index + 1) * 25;
            $step = $index + 1;
            $label = EstadoErp::NIVELES[$nivel];
            $response = $this->get('/erp/nivel')->assertOk()
                ->assertSee('max="100" value="'.$percentage.'"', false)
                ->assertSee('aria-labelledby="erp-level-progress-label"', false)
                ->assertSee('aria-valuetext="'.$label.', nivel '.$step.' de 4, '.$percentage.'%"', false)
                ->assertSee('Nivel '.$step.' de 4')
                ->assertSee('Avance según el nivel declarado por el equipo.');

            $this->assertSame($index, substr_count($response->getContent(), 'class="is-complete"'));
            $this->assertSame(1, substr_count($response->getContent(), 'aria-current="step"'));
            $this->assertSame($saved, $estado->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('auditorias', 0);
    }
}
