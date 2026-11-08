<?php

namespace Tests\Feature;

use App\Models\Impuesto;
use App\Models\MovimientoInventario;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\MonederoService;
use App\Services\TiendaCarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TiendaCheckoutQuoteDriftTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor SCM Test',
            'contacto' => 'Soporte',
            'telefono' => '5511223344',
            'correo' => 'scm@test.local',
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Plan Plus Agente',
            'descripcion' => 'Plan de prueba',
            'categoria' => 'Planes',
            'stock_actual' => 15,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 1000.00,
            'visible_tienda' => true,
        ]);

        Impuesto::create([
            'nombre' => 'IVA 16%',
            'puntos_base' => 1600,
            'activo' => true,
        ]);
    }

    public function test_stale_cart_version_in_checkout_is_rejected_without_side_effects(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $versionInicial = $carrito->version;

        // Simular que el cliente abrió checkout con la versión inicial
        $totalesIniciales = $carritoService->calcularTotales($carrito);

        // En otra pestaña o sesión, el usuario añade otro producto o cambia la cantidad
        $carritoService->agregarProducto($user, $this->producto->id, 1);
        $carrito->refresh();
        $this->assertGreaterThan($versionInicial, $carrito->version);

        // Envía el formulario de checkout con la versión desactualizada
        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $versionInicial, // Desactualizada
                'total_esperado_centavos' => $totalesIniciales['total_centavos'],
                'cotizacion_hash' => $totalesIniciales['cotizacion_hash'],
                'idempotency_key' => (string) Str::uuid(),
            ]);

        // Redirige al checkout con error de validación en cotizacion
        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['cotizacion']);

        // GARANTÍA: Cero efectos secundarios
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->producto->refresh();
        $this->assertSame(15, $this->producto->stock_actual); // Stock intacto
    }

    public function test_price_change_before_payment_triggers_quote_drift_rejection(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totalesCotizados = $carritoService->calcularTotales($carrito);

        // El administrador actualiza el precio del producto antes de que el cliente pague
        $this->producto->update(['precio_venta_unitario' => 1500.00]);

        // El cliente intenta pagar con el total anterior que vio en su pantalla
        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totalesCotizados['total_centavos'], // Total desactualizado
                'cotizacion_hash' => $totalesCotizados['cotizacion_hash'],
                'idempotency_key' => (string) Str::uuid(),
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['cotizacion']);

        // Cero efectos secundarios
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(15, $this->producto->fresh()->stock_actual);
    }

    public function test_tax_change_before_payment_triggers_quote_drift_rejection(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totalesCotizados = $carritoService->calcularTotales($carrito);

        // Se modifica la tasa del impuesto activo
        Impuesto::first()->update(['puntos_base' => 1800]); // 18%

        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totalesCotizados['total_centavos'],
                'cotizacion_hash' => $totalesCotizados['cotizacion_hash'],
                'idempotency_key' => (string) Str::uuid(),
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['cotizacion']);

        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame(15, $this->producto->fresh()->stock_actual);
    }

    public function test_resubmitting_with_updated_quote_succeeds_and_processes_cleanly(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totalesIniciales = $carritoService->calcularTotales($carrito);

        // Cambia el precio
        $this->producto->update(['precio_venta_unitario' => 1200.00]);

        // Primer intento con cotización vieja falla
        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totalesIniciales['total_centavos'],
                'cotizacion_hash' => $totalesIniciales['cotizacion_hash'],
            ])
            ->assertSessionHasErrors(['cotizacion']);

        // El usuario visualiza la pantalla actualizada de checkout (que calcula nuevos totales)
        $totalesActualizados = $carritoService->calcularTotales($carrito);
        $idempotencyKey = (string) Str::uuid();

        // Segundo intento con los importes actualizados se procesa con éxito
        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totalesActualizados['total_centavos'],
                'cotizacion_hash' => $totalesActualizados['cotizacion_hash'],
                'idempotency_key' => $idempotencyKey,
            ]);

        $orden = OrdenEmpresarial::first();
        $this->assertNotNull($orden);
        $this->assertSame(OrdenEmpresarial::PROCESADA, $orden->estado);
        $this->assertSame(14, $this->producto->fresh()->stock_actual);

        $pago = Pago::first();
        $this->assertNotNull($pago);
        $response->assertRedirect(route('pagos.confirmacion', $pago->referencia));

        // Reintento con la misma idempotency key no duplica órdenes ni movimientos
        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => 'comprador@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totalesActualizados['total_centavos'],
                'cotizacion_hash' => $totalesActualizados['cotizacion_hash'],
                'idempotency_key' => $idempotencyKey,
            ])
            ->assertRedirect(route('pagos.confirmacion', $pago->referencia));

        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertSame(14, $this->producto->fresh()->stock_actual);
    }

    public function test_stale_checkout_does_not_debit_wallet(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $monederoService = app(MonederoService::class);
        $cliente = $user->obtenerCliente();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $monederoService->recargar($cliente, $admin, 500000); // $5,000 MXN

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        // Modificar precio para forzar drift
        $this->producto->update(['precio_venta_unitario' => 2000.00]);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Monedero',
                'correo_contacto' => 'monedero@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'monedero_demo',
                'version_carrito' => $carrito->version,
                'total_esperado_centavos' => $totales['total_centavos'],
                'cotizacion_hash' => $totales['cotizacion_hash'],
            ])
            ->assertSessionHasErrors(['cotizacion']);

        // Saldo del monedero debe seguir intacto ($5,000.00)
        $monedero = $monederoService->obtenerMonedero($cliente);
        $this->assertSame(500000, $monedero->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1); // Solo la recarga inicial
    }

    public function test_missing_quote_confirmation_fields_are_rejected_without_side_effects(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Incompleto',
                'correo_contacto' => 'inc@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                // Omitir intencionalmente version_carrito, total_esperado_centavos y cotizacion_hash
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['version_carrito', 'total_esperado_centavos', 'cotizacion_hash']);

        // Cero efectos secundarios
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(15, $this->producto->fresh()->stock_actual);
    }

    public function test_null_quote_confirmation_fields_are_rejected_without_side_effects(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Nulo',
                'correo_contacto' => 'nulo@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => null,
                'total_esperado_centavos' => null,
                'cotizacion_hash' => null,
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['version_carrito', 'total_esperado_centavos', 'cotizacion_hash']);

        // Cero efectos secundarios
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(15, $this->producto->fresh()->stock_actual);
    }

    public function test_invalid_format_quote_confirmation_fields_are_rejected_without_side_effects(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'nombre_contacto' => 'Comprador Invalido',
                'correo_contacto' => 'invalido@demo.local',
                'telefono_contacto' => '5512345678',
                'metodo_pago' => 'tarjeta_demo',
                'version_carrito' => 'no-es-entero',
                'total_esperado_centavos' => -500,
                'cotizacion_hash' => 'hash-corto',
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors(['version_carrito', 'total_esperado_centavos', 'cotizacion_hash']);

        // Cero efectos secundarios
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(15, $this->producto->fresh()->stock_actual);
    }

    public function test_resending_completed_purchase_returns_existing_result_without_duplication(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($user, $this->producto->id, 1);

        $carrito = $carritoService->obtenerCarrito($user);
        $totales = $carritoService->calcularTotales($carrito);

        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'nombre_contacto' => 'Comprador Idempotente',
            'correo_contacto' => 'idempotente@demo.local',
            'telefono_contacto' => '5512345678',
            'metodo_pago' => 'tarjeta_demo',
            'idempotency_key' => $idempotencyKey,
            'version_carrito' => $carrito->version,
            'total_esperado_centavos' => $totales['total_centavos'],
            'cotizacion_hash' => $totales['cotizacion_hash'],
        ];

        // 1. Primer envío: procesa la compra
        $firstResponse = $this->actingAs($user)->post(route('checkout.store'), $payload);
        $pago = Pago::where('referencia', $idempotencyKey)->first();
        $this->assertNotNull($pago);
        $firstResponse->assertRedirect(route('pagos.confirmacion', $pago->referencia));

        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertSame(14, $this->producto->fresh()->stock_actual);

        // El carrito quedó vacío tras completar la compra
        $this->assertCount(0, $carrito->fresh()->detalles);

        // 2. Reenvío de la misma compra ya completada (mismo payload e idempotency_key)
        $secondResponse = $this->actingAs($user)->post(route('checkout.store'), $payload);
        $secondResponse->assertRedirect(route('pagos.confirmacion', $pago->referencia));

        // GARANTÍA: No se duplicó orden, pago ni salida de inventario
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertSame(14, $this->producto->fresh()->stock_actual);
    }
}
