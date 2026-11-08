<?php

namespace Tests\Feature;

use App\Models\Impuesto;
use App\Models\MovimientoInventario;
use App\Models\OrdenDetalle;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\TiendaCarritoService;
use App\Services\TiendaCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TiendaMultiConfigSameProductTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor SCM OpenRouter',
            'contacto' => 'Soporte',
            'telefono' => '5511223344',
            'correo' => 'scm@openrouter.local',
        ]);

        Impuesto::create([
            'nombre' => 'IVA 16%',
            'puntos_base' => 1600,
            'activo' => true,
        ]);
    }

    public function test_same_plan_with_two_different_configurations_creates_independent_order_details_and_movements(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $basePlan = Producto::create([
            'nombre' => 'Agente Inicio',
            'descripcion' => 'Plan base configurable',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);

        // Línea 1: Agente Inicio con módulo de citas (+$200) = $699.00 MXN (cant: 2)
        $carritoService->agregarProducto($user, $basePlan->id, 2, ['Agenda de citas']);

        // Línea 2: Agente Inicio con módulo de voz (+$150) = $649.00 MXN (cant: 1)
        $carritoService->agregarProducto($user, $basePlan->id, 1, ['Respuestas en audio']);

        $carrito = $user->obtenerCliente()->carrito->fresh(['detalles']);
        $this->assertCount(2, $carrito->detalles);

        $checkoutService = app(TiendaCheckoutService::class);
        $resultado = $checkoutService->procesarCompra(
            $user,
            [
                'nombre_contacto' => 'Comprador Personalizado',
                'correo_contacto' => $user->email,
                'telefono_contacto' => '5511223344',
            ],
            'tarjeta_demo'
        );

        /** @var OrdenEmpresarial $orden */
        $orden = $resultado['orden'];
        $pago = $resultado['pago'];

        $this->assertSame(Pago::ESTADO_APROBADO, $pago->estado);
        $this->assertSame(OrdenEmpresarial::PROCESADA, $orden->estado);

        // Se deben haber creado 2 detalles independientes para el mismo producto_id
        $this->assertCount(2, $orden->detalles);
        $detalles = $orden->detalles()->get();

        $detalleCitas = $detalles->first(fn ($d) => in_array('Agenda de citas', array_column($d->configuracion['modulos'] ?? [], 'name')));
        $detalleVoz = $detalles->first(fn ($d) => in_array('Respuestas en audio', array_column($d->configuracion['modulos'] ?? [], 'name')));

        // Detalle con Citas: 2 unidades ($699.00 cada uno, subtotal $1,398.00)
        $this->assertNotNull($detalleCitas);
        $this->assertSame($basePlan->id, $detalleCitas->producto_id);
        $this->assertSame(2, $detalleCitas->cantidad);
        $this->assertEquals(699.00, (float) $detalleCitas->precio_unitario);
        $this->assertEquals(1398.00, (float) $detalleCitas->subtotal);

        // Detalle con Voz: 1 unidad ($649.00 cada uno, subtotal $649.00)
        $this->assertNotNull($detalleVoz);
        $this->assertSame($basePlan->id, $detalleVoz->producto_id);
        $this->assertSame(1, $detalleVoz->cantidad);
        $this->assertEquals(649.00, (float) $detalleVoz->precio_unitario);
        $this->assertEquals(649.00, (float) $detalleVoz->subtotal);

        // Total orden: Subtotal $2,047.00 + IVA 16% ($327.52) = $2,374.52 MXN
        $this->assertEquals(2374.52, (float) $orden->total);

        // SCM: Stock se decrementó por la suma total requerida (2 + 1 = 3 unidades)
        $basePlan->refresh();
        $this->assertSame(7, $basePlan->stock_actual);

        // Movimientos SCM registrados para cada detalle independientemente
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $basePlan->id,
            'orden_detalle_id' => $detalleCitas->id,
            'tipo' => MovimientoInventario::TIPO_SALIDA,
            'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $basePlan->id,
            'orden_detalle_id' => $detalleVoz->id,
            'tipo' => MovimientoInventario::TIPO_SALIDA,
            'cantidad' => 1,
        ]);
    }

    public function test_purchase_rejected_atomically_when_joint_stock_is_insufficient(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        // Stock disponible = 3 unidades
        $producto = Producto::create([
            'nombre' => 'Agente Escaso',
            'descripcion' => 'Pocos cupos',
            'categoria' => 'Planes',
            'stock_actual' => 3,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);

        // Agregar 2 unidades con config A
        $carritoService->agregarProducto($user, $producto->id, 2, ['Módulo de Citas']);

        // Si intentamos agregar 2 unidades con config B desde el carrito, la suma (2 + 2 = 4 > 3) debe ser rechazada
        $this->expectException(ValidationException::class);
        $carritoService->agregarProducto($user, $producto->id, 2, ['Módulo de Voz']);

        // Verificar que el stock no fue alterado y no hay órdenes ni pagos
        $producto->refresh();
        $this->assertSame(3, $producto->stock_actual);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_checkout_rejects_joint_stock_when_stock_decreased_concurrently_before_confirmation(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente Concurrente',
            'descripcion' => 'Cupos',
            'categoria' => 'Planes',
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 2, ['Módulo de Citas']);
        $carritoService->agregarProducto($user, $producto->id, 2, ['Módulo de Voz']);

        // Simular que el stock bajó externamente de 5 a 3 antes del checkout (requiere 4 en total)
        $producto->update(['stock_actual' => 3]);

        $checkoutService = app(TiendaCheckoutService::class);

        try {
            $checkoutService->procesarCompra(
                $user,
                ['nombre_contacto' => 'Test', 'correo_contacto' => $user->email, 'telefono_contacto' => '55'],
                'tarjeta_demo'
            );
            $this->fail('Se esperaba ValidationException por stock conjunto insuficiente.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('stock', $e->errors());
        }

        // Nada fue creado ni descontado
        $producto->refresh();
        $this->assertSame(3, $producto->stock_actual);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_compatibility_with_erp_orders_without_configuration(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cliente = $admin->obtenerCliente();

        $producto = Producto::create([
            'nombre' => 'Recurso Base ERP',
            'descripcion' => 'Recurso interno',
            'categoria' => 'Infraestructura',
            'stock_actual' => 20,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 250.00,
            'visible_tienda' => false,
        ]);

        // Crear orden empresarial ERP estándar (sin configuración)
        $orden = OrdenEmpresarial::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $admin->id,
            'fecha' => now(),
            'estado' => OrdenEmpresarial::PENDIENTE,
            'total' => '500.00',
        ]);

        $detalle = OrdenDetalle::create([
            'orden_empresarial_id' => $orden->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => '250.00',
            'subtotal' => '500.00',
            'configuracion' => null, // Compatible: sin configuración
        ]);

        $this->assertNull($detalle->configuracion);
        $this->assertDatabaseHas('orden_detalles', [
            'id' => $detalle->id,
            'orden_empresarial_id' => $orden->id,
            'producto_id' => $producto->id,
            'configuracion' => null,
        ]);
    }
}
