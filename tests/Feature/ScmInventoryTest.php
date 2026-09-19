<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScmInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_and_exit_update_stock_and_keep_authenticated_responsible(): void
    {
        $user = $this->scmUser();
        $product = $this->product(10, 5);

        $this->actingAs($user)->post(route('scm.movimientos.store'), $this->movement($product, 'entrada', 4))
            ->assertSessionHasNoErrors();
        $this->assertSame(14, $product->fresh()->stock_actual);

        $this->actingAs($user)->post(route('scm.movimientos.store'), $this->movement($product, 'salida', 6, [
            'usuario_id' => User::factory()->create()->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(8, $product->fresh()->stock_actual);
        $this->assertSame($user->id, MovimientoInventario::query()->latest('id')->firstOrFail()->usuario_id);
        $this->assertDatabaseHas('auditorias', ['accion' => 'registrar_movimiento', 'entidad' => 'movimiento_inventario']);
    }

    public function test_invalid_or_excessive_exit_does_not_change_stock_or_create_movement(): void
    {
        $user = $this->scmUser();
        $product = $this->product(3, 2);

        $this->actingAs($user)->post(route('scm.movimientos.store'), $this->movement($product, 'salida', 4))
            ->assertSessionHasErrors('cantidad');
        $this->actingAs($user)->post(route('scm.movimientos.store'), $this->movement($product, 'entrada', 0))
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(3, $product->fresh()->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_inventory_marks_low_stock_and_supports_search(): void
    {
        $user = $this->scmUser();
        $low = $this->product(5, 5, 'Recurso crítico');
        $this->product(6, 5, 'Recurso normal');

        $this->actingAs($user)->get(route('scm.inventario.index', ['buscar' => 'crítico']))
            ->assertOk()->assertSee($low->nombre)->assertSee('STOCK BAJO')->assertDontSee('Recurso normal');
    }

    public function test_movement_filters_history_and_product_deletion_is_restricted(): void
    {
        $user = $this->scmUser();
        $product = $this->product(10, 2);
        $this->actingAs($user)->post(route('scm.movimientos.store'), $this->movement($product, 'entrada', 2));

        $this->actingAs($user)->get(route('scm.movimientos.index', ['tipo' => 'entrada', 'producto_id' => $product->id]))
            ->assertOk()->assertSee($product->nombre);
        $this->actingAs($user)->get(route('scm.productos.movimientos', $product))->assertOk()->assertSee('entrada');
        $this->actingAs($user)->delete(route('scm.productos.destroy', $product))->assertSessionHasErrors('producto');
    }

    public function test_inventory_api_creates_and_lists_movements_with_real_stock(): void
    {
        $user = $this->scmUser();
        $product = $this->product(10, 2);

        $this->actingAs($user)->postJson(route('api.scm.movimientos.store'), $this->movement($product, 'salida', 3))
            ->assertCreated()->assertJsonPath('data.usuario.id', $user->id)->assertJsonPath('data.producto.stock_actual', 7);
        $this->actingAs($user)->getJson(route('api.scm.productos.movimientos', $product))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.cantidad', 3);
    }

    private function scmUser(): User
    {
        return User::factory()->create(['permissions' => ['scm']]);
    }

    private function product(int $stock, int $minimum, string $name = 'Recurso IA'): Producto
    {
        $supplier = Proveedor::create(['nombre' => 'OpenRouter '.uniqid()]);

        return Producto::create([
            'nombre' => $name, 'categoria' => 'General', 'stock_actual' => $stock, 'stock_minimo' => $minimum,
            'proveedor_id' => $supplier->id, 'costo_unitario' => 1, 'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
        ]);
    }

    private function movement(Producto $product, string $type, int $quantity, array $overrides = []): array
    {
        return array_merge([
            'producto_id' => $product->id, 'tipo' => $type, 'cantidad' => $quantity,
            'motivo' => $type === 'entrada' ? 'reposicion' : 'venta', 'fecha' => '2026-09-19 10:30:00',
        ], $overrides);
    }
}
