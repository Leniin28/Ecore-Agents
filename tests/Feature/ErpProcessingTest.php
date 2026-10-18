<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\OrdenEmpresarial;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpProcessingTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_processing_decrements_stock_records_linked_exits_and_push_only_once(): void
    {
        $user = $this->erpUser(['erp', 'scm']);
        $push = $this->erpProduct(1200, 'PUSH', minimum: 1000);
        $pull = $this->erpProduct(1200, 'PULL', minimum: 1000);
        $order = $this->erpOrder($user, [['producto_id' => $push->id, 'cantidad' => 500], ['producto_id' => $pull->id, 'cantidad' => 500]]);
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertOk()
            ->assertJsonPath('data.estado', 'procesada')->assertJsonPath('data.detalles.0.movimiento.tipo', 'salida');
        $this->assertSame(700, $push->fresh()->stock_actual);
        $this->assertSame(700, $pull->fresh()->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertDatabaseHas('movimientos_inventario', ['usuario_id' => $user->id, 'cantidad' => 500, 'motivo' => 'venta']);
        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseHas('pedidos', ['producto_id' => $push->id, 'origen' => 'automatico_push']);
        $this->assertDatabaseMissing('pedidos', ['producto_id' => $pull->id]);
        $this->assertDatabaseHas('auditorias', ['accion' => 'procesar_orden_erp', 'entidad_id' => $order->id]);
        $this->get('/erp/ordenes/'.$order->id.'/exito')->assertOk()->assertSee('Proceso completado')->assertSee('Alerta de inventario');
        $this->get('/erp')->assertSee('Stock bajo: 2');

        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->assertSame(700, $push->fresh()->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $second = $this->erpOrder($user, [['producto_id' => $push->id, 'cantidad' => 1]]);
        $this->postJson("/api/erp/ordenes/{$second->id}/procesar")->assertOk();
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_one_insufficient_line_prevents_all_stock_and_movement_changes(): void
    {
        $user = $this->erpUser();
        $first = $this->erpProduct(100, 'PUSH');
        $second = $this->erpProduct(1);
        $order = $this->erpOrder($user, [['producto_id' => $first->id, 'cantidad' => 95], ['producto_id' => $second->id, 'cantidad' => 2]]);
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->assertSame(100, $first->fresh()->stock_actual);
        $this->assertSame(1, $second->fresh()->stock_actual);
        $this->assertSame('pendiente', $order->fresh()->estado);
        $this->assertNull($order->fresh()->procesada_at);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_failure_after_stock_changes_rolls_back_stock_movements_push_and_order(): void
    {
        $user = $this->erpUser();
        $product = $this->erpProduct(12, 'PUSH');
        $order = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 5]]);
        $beforeAudit = Auditoria::count();
        Event::listen('eloquent.creating: '.Auditoria::class, function (Auditoria $audit) {
            if ($audit->accion === 'procesar_orden_erp') throw new \RuntimeException('Fallo simulado de persistencia');
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson("/api/erp/ordenes/{$order->id}/procesar");
            $this->fail('Se esperaba el fallo simulado.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo simulado de persistencia', $error->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.Auditoria::class);
        }
        $this->assertSame(12, $product->fresh()->stock_actual);
        $this->assertSame('pendiente', $order->fresh()->estado);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame($beforeAudit, Auditoria::count());
    }

    public function test_cancel_is_pending_only_and_does_not_restore_processed_stock(): void
    {
        $user = $this->erpUser();
        $product = $this->erpProduct();
        $order = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 5]]);
        $this->postJson("/api/erp/ordenes/{$order->id}/cancelar")->assertOk()->assertJsonPath('data.estado', 'cancelada');
        $this->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertUnprocessable();
        $this->assertSame(100, $product->fresh()->stock_actual);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cancelar_orden_erp']);

        $processed = $this->erpOrder($user, [['producto_id' => $product->id, 'cantidad' => 5]]);
        $this->post("/erp/ordenes/{$processed->id}/procesar")->assertRedirect(route('erp.ordenes.exito', $processed));
        $this->postJson("/api/erp/ordenes/{$processed->id}/cancelar")->assertUnprocessable();
        $this->assertSame(95, $product->fresh()->stock_actual);
        $this->assertSame(OrdenEmpresarial::PROCESADA, $processed->fresh()->estado);
    }

    public function test_processing_requires_erp_permission_even_with_scm_access(): void
    {
        $product = $this->erpProduct();
        $order = $this->erpOrder($this->erpUser(), [['producto_id' => $product->id, 'cantidad' => 2]]);
        foreach ([$this->erpUser(['scm']), User::factory()->create(['role' => 'cliente', 'permissions' => ['erp']])] as $user) {
            $this->actingAs($user)->postJson("/api/erp/ordenes/{$order->id}/procesar")->assertForbidden();
            $this->postJson("/api/erp/ordenes/{$order->id}/cancelar")->assertForbidden();
        }
        $this->assertSame(100, $product->fresh()->stock_actual);
    }
}
