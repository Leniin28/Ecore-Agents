<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\TiendaCarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaCarritoTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor SCM Demo',
            'contacto' => 'Soporte',
            'telefono' => '5511223344',
            'correo' => 'scm@demo.local',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_cart(): void
    {
        $this->get(route('carrito.index'))
            ->assertRedirect(route('login'));

        $this->post(route('carrito.store'), ['producto_id' => 1, 'cantidad' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_client_can_add_product_to_persistent_cart(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $producto = Producto::create([
            'nombre' => 'Agente Inicio',
            'descripcion' => 'Esencial',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 2,
            ]);

        $response->assertRedirect(route('carrito.index'));

        $this->assertDatabaseHas('carrito_detalles', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);
    }

    public function test_adding_same_product_with_equivalent_configuration_merges_quantity(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $producto = Producto::create([
            'nombre' => 'Agente Plus',
            'descripcion' => 'Seguimiento',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 200,
            'precio_venta_unitario' => 999.00,
            'visible_tienda' => true,
        ]);

        $service = app(TiendaCarritoService::class);

        // Agregar 1 unidad
        $service->agregarProducto($user, $producto->id, 1);
        // Agregar 2 unidades más de la misma configuración estándar
        $service->agregarProducto($user, $producto->id, 2);

        $carrito = $user->cliente->carrito;
        $this->assertCount(1, $carrito->detalles);
        $this->assertSame(3, $carrito->detalles->first()->cantidad);
    }

    public function test_adding_different_module_configurations_creates_separate_lines(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $producto = Producto::create([
            'nombre' => 'Agente Inicio',
            'descripcion' => 'Base',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $service = app(TiendaCarritoService::class);

        // Config 1: con transcripción
        $service->agregarProducto($user, $producto->id, 1, ['Transcripción de audios']);

        // Config 2: con agenda de citas
        $service->agregarProducto($user, $producto->id, 1, ['Agenda de citas']);

        $carrito = $user->cliente->carrito;
        $this->assertCount(2, $carrito->detalles);
    }

    public function test_cannot_exceed_available_stock_in_cart(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $producto = Producto::create([
            'nombre' => 'Agente Limitado',
            'descripcion' => 'Poco stock',
            'categoria' => 'Planes',
            'stock_actual' => 2,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $this->actingAs($user)
            ->post(route('carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 5, // Supera los 2 disponibles
            ])
            ->assertSessionHasErrors('cantidad');
    }

    public function test_cart_exclusivity_user_cannot_mutate_another_clients_cart(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente A',
            'descripcion' => 'A',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $service = app(TiendaCarritoService::class);
        $detalleA = $service->agregarProducto($userA, $producto->id, 2);

        // User B intenta modificar la línea de User A
        $this->actingAs($userB)
            ->put(route('carrito.update', $detalleA->id), ['cantidad' => 1])
            ->assertNotFound();

        // User B intenta eliminar la línea de User A
        $this->actingAs($userB)
            ->delete(route('carrito.destroy', $detalleA->id))
            ->assertRedirect(route('carrito.index'));

        // La línea de A sigue intacta con 2 unidades
        $this->assertDatabaseHas('carrito_detalles', [
            'id' => $detalleA->id,
            'cantidad' => 2,
        ]);
    }

    public function test_server_calculates_promotions_and_taxes_accurately(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente Avanzado',
            'descripcion' => 'Avanzado',
            'categoria' => 'Planes',
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 400,
            'precio_venta_unitario' => 1000.00, // $1,000.00 MXN = 100,000 centavos
            'visible_tienda' => true,
        ]);

        // Promoción de 10% (1000 puntos base)
        $promo = Promocion::create([
            'codigo' => 'DESC10',
            'nombre' => 'Descuento 10%',
            'tipo_descuento' => Promocion::TIPO_PORCENTAJE,
            'valor' => 1000,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addMonth(),
            'activo' => true,
        ]);

        // Impuesto IVA 16% (1600 puntos base)
        Impuesto::create([
            'nombre' => 'IVA 16%',
            'puntos_base' => 1600,
            'activo' => true,
        ]);

        $service = app(TiendaCarritoService::class);
        $service->agregarProducto($user, $producto->id, 1);
        $carrito = $user->cliente->carrito;

        $totales = $service->calcularTotales($carrito, 'DESC10');

        // Subtotal: 100,000 centavos ($1,000.00)
        $this->assertSame(100000, $totales['subtotal_centavos']);

        // Descuento 10%: 10,000 centavos ($100.00)
        $this->assertSame(10000, $totales['descuento_centavos']);

        // Base gravable: 90,000 centavos ($900.00)
        $this->assertSame(90000, $totales['base_gravable_centavos']);

        // IVA 16% sobre 90,000: 14,400 centavos ($144.00)
        $this->assertSame(14400, $totales['total_impuestos_centavos']);

        // Total: 90,000 + 14,400 = 104,400 centavos ($1,044.00 MXN)
        $this->assertSame(104400, $totales['total_centavos']);
    }

    public function test_expired_or_out_of_scope_promotions_are_not_applied(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $prodA = Producto::create([
            'nombre' => 'Producto A',
            'descripcion' => 'A',
            'categoria' => 'Planes',
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00,
            'visible_tienda' => true,
        ]);

        $prodB = Producto::create([
            'nombre' => 'Producto B',
            'descripcion' => 'B',
            'categoria' => 'Planes',
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00,
            'visible_tienda' => true,
        ]);

        // Promoción exclusiva para Prod B
        $promoSoloB = Promocion::create([
            'codigo' => 'SOLOB',
            'nombre' => 'Solo B',
            'tipo_descuento' => Promocion::TIPO_PORCENTAJE,
            'valor' => 2000, // 20%
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addMonth(),
            'activo' => true,
        ]);
        $promoSoloB->productos()->sync([$prodB->id]);

        $service = app(TiendaCarritoService::class);
        // Cliente solo tiene Producto A en su carrito
        $service->agregarProducto($user, $prodA->id, 1);
        $carrito = $user->cliente->carrito;

        $totales = $service->calcularTotales($carrito, 'SOLOB');
        $this->assertFalse($totales['promocion_valida']);
        $this->assertSame(0, $totales['descuento_centavos']);
    }
}
