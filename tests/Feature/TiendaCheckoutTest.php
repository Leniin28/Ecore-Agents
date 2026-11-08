<?php

namespace Tests\Feature;

use App\Models\Impuesto;
use App\Models\MovimientoInventario;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\MonederoService;
use App\Services\TiendaCarritoService;
use App\Services\TiendaCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TiendaCheckoutTest extends TestCase
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

        Impuesto::create([
            'nombre' => 'IVA 16%',
            'puntos_base' => 1600,
            'activo' => true,
        ]);
    }

    public function test_complete_end_to_end_purchase_with_card_updates_scm_crm_and_erp(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente Plus Demo',
            'descripcion' => 'Seguimiento con IA',
            'categoria' => 'Planes',
            'stock_actual' => 20,
            'stock_minimo' => 5,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 250,
            'precio_venta_unitario' => 999.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 2);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Cliente Prueba',
                'correo_contacto' => 'cliente@prueba.local',
                'telefono_contacto' => '5512345678',
                'empresa' => 'Mi Negocio SA',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totales['total_centavos'],
                'cotizacion_hash' => $totales['cotizacion_hash'],
            ]);

        $orden = OrdenEmpresarial::query()->where('cliente_id', $user->cliente->id)->first();
        $this->assertNotNull($orden);
        $this->assertSame(OrdenEmpresarial::PROCESADA, $orden->estado);

        $pago = Pago::query()->where('orden_empresarial_id', $orden->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame(Pago::ESTADO_APROBADO, $pago->estado);
        $this->assertSame('tarjeta_demo', $pago->metodo);

        $response->assertRedirect(route('pagos.confirmacion', $pago->referencia));

        // 1. SCM: Stock se decrementó de 20 a 18
        $producto->refresh();
        $this->assertSame(18, $producto->stock_actual);

        // Movimiento de salida registrado en SCM
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => MovimientoInventario::TIPO_SALIDA,
            'cantidad' => 2,
            'motivo' => MovimientoInventario::MOTIVO_VENTA,
        ]);

        // 2. ERP: Detalles de orden guardados con precios correctos
        $this->assertCount(1, $orden->detalles);
        $this->assertSame(2, $orden->detalles->first()->cantidad);
        $this->assertEquals(999.00, (float) $orden->detalles->first()->precio_unitario);

        // Procesos internos ERP auditados
        $this->assertDatabaseHas('procesos_internos', [
            'orden_empresarial_id' => $orden->id,
            'tipo' => 'orden_procesada',
        ]);

        // 3. Carrito vaciado
        $this->assertCount(0, $user->cliente->carrito->detalles);
    }

    public function test_purchase_with_wallet_deducts_balance_and_records_transaction(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Módulo Voz',
            'descripcion' => 'Voz sintetizada',
            'categoria' => 'Complementos',
            'stock_actual' => 15,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 50,
            'precio_venta_unitario' => 200.00, // Con IVA 16% = $232.00 MXN = 23,200 centavos
            'visible_tienda' => true,
        ]);

        // Recargar $500.00 MXN (50,000 centavos) al monedero
        $cliente = $user->obtenerCliente();
        $monederoService = app(MonederoService::class);
        $monederoService->recargar($cliente, $user, 50000);

        $this->assertSame(50000, $cliente->monedero->fresh()->saldo_centavos);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Cliente Monedero',
                'correo_contacto' => $user->email,
                'telefono_contacto' => '5511223344',
                'metodo_pago' => 'monedero_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totales['total_centavos'],
                'cotizacion_hash' => $totales['cotizacion_hash'],
            ]);

        $orden = OrdenEmpresarial::query()->where('cliente_id', $cliente->id)->first();
        $this->assertNotNull($orden);

        // Saldo restante: 50,000 - 23,200 = 26,800 centavos ($268.00 MXN)
        $this->assertSame(26800, $cliente->monedero->fresh()->saldo_centavos);

        // Movimiento de tipo 'pago_compra'
        $this->assertDatabaseHas('monedero_movimientos', [
            'monedero_id' => $cliente->monedero->id,
            'tipo' => 'pago_compra',
            'monto_centavos' => -23200,
        ]);
    }

    public function test_insufficient_wallet_balance_rejects_checkout(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente Avanzado',
            'descripcion' => 'Avanzado',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 500,
            'precio_venta_unitario' => 1799.00,
            'visible_tienda' => true,
        ]);

        // Monedero con saldo 0
        $cliente = $user->obtenerCliente();
        $monederoService = app(MonederoService::class);
        $monedero = $monederoService->obtenerMonedero($cliente);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Cliente Pobre',
                'correo_contacto' => $user->email,
                'telefono_contacto' => '5511223344',
                'metodo_pago' => 'monedero_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totales['total_centavos'],
                'cotizacion_hash' => $totales['cotizacion_hash'],
            ])
            ->assertSessionHasErrors('metodo');

        // No se creó orden
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        // Stock no se descontó
        $this->assertSame(10, $producto->fresh()->stock_actual);
    }

    public function test_simulated_payment_rejection_aborts_without_deducting_stock(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Plan Rechazo Demo',
            'descripcion' => 'Prueba negativa',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Cliente Prueba Negativa',
                'correo_contacto' => $user->email,
                'telefono_contacto' => '5511223344',
                'metodo_pago' => 'tarjeta_demo',
                'simular_fallo' => 1,
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totales['total_centavos'],
                'cotizacion_hash' => $totales['cotizacion_hash'],
            ])
            ->assertSessionHasErrors('pago');

        // No se creó ninguna orden ni pago
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);

        // Stock permanece exactamente en 10
        $this->assertSame(10, $producto->fresh()->stock_actual);
    }

    public function test_idempotency_key_prevents_duplicate_charge_and_stock_deduction(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Plan Idempotente',
            'descripcion' => 'Prueba idempotencia',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 1);

        $idempotencyKey = (string) Str::uuid();

        $checkoutService = app(TiendaCheckoutService::class);
        $datosEntrega = [
            'nombre_contacto' => 'Idem User',
            'correo_contacto' => 'idem@test.local',
            'telefono_contacto' => '5511223344',
        ];

        // Primer intento
        $res1 = $checkoutService->procesarCompra($user, $datosEntrega, 'tarjeta_demo', null, $idempotencyKey);
        $this->assertNotNull($res1['pago']);

        // Stock descontado una vez: de 10 a 9
        $this->assertSame(9, $producto->fresh()->stock_actual);

        // Segundo intento con la misma clave (simulando doble clic)
        $res2 = $checkoutService->procesarCompra($user, $datosEntrega, 'tarjeta_demo', null, $idempotencyKey);

        // Devuelve la misma orden y pago
        $this->assertSame($res1['pago']->id, $res2['pago']->id);
        $this->assertSame($res1['orden']->id, $res2['orden']->id);

        // Stock NO se vuelve a descontar, permanece en 9
        $this->assertSame(9, $producto->fresh()->stock_actual);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_historical_prices_and_taxes_are_immutable(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Plan Histórico',
            'descripcion' => 'Inmutable',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00, // $500.00
            'visible_tienda' => true,
        ]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $producto->id, 1);

        $checkoutService = app(TiendaCheckoutService::class);
        $res = $checkoutService->procesarCompra($user, ['nombre_contacto' => 'Histo', 'correo_contacto' => 'h@h.com', 'telefono_contacto' => '55'], 'tarjeta_demo');

        $orden = $res['orden'];
        $pago = $res['pago'];

        // El precio histórico grabado fue $500.00
        $this->assertEquals(500.00, (float) $orden->detalles->first()->precio_unitario);

        // Ahora el administrador cambia el precio del producto a $800.00
        $producto->update(['precio_venta_unitario' => 800.00]);

        // Y se cambia la tasa de impuestos
        Impuesto::first()->update(['puntos_base' => 2000]); // 20%

        // La orden y el pago históricos NO cambian
        $orden->refresh();
        $this->assertEquals(500.00, (float) $orden->detalles->first()->precio_unitario);
        $this->assertSame(58000, $pago->fresh()->total_centavos); // $580.00 (500 + 16% IVA)
    }
}
