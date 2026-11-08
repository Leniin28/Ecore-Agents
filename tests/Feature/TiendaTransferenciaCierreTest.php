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
use App\Services\TiendaCarritoService;
use App\Services\TiendaCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TiendaTransferenciaCierreTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;
    private User $admin;
    private User $empleado;
    private User $clienteUser;
    private Producto $producto;

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

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->empleado = User::factory()->create(['role' => User::ROLE_EMPLEADO, 'permissions' => [User::MODULE_CRM]]);
        $this->clienteUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $this->producto = Producto::create([
            'nombre' => 'Agente Transferencia',
            'descripcion' => 'Plan para probar transferencias',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00,
            'visible_tienda' => true,
        ]);
    }

    private function crearCompraTransferenciaPendiente(?string $codigoPromo = null): array
    {
        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($this->clienteUser, $this->producto->id, 2);

        $checkoutService = app(TiendaCheckoutService::class);

        return $checkoutService->procesarCompra(
            $this->clienteUser,
            [
                'nombre_contacto' => 'Comprador Demo',
                'correo_contacto' => $this->clienteUser->email,
                'telefono_contacto' => '5511223344',
            ],
            'transferencia_demo',
            $codigoPromo
        );
    }

    public function test_transfer_starts_as_pending_without_deducting_inventory_initially(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        /** @var OrdenEmpresarial $orden */
        $orden = $resultado['orden'];
        /** @var Pago $pago */
        $pago = $resultado['pago'];

        $this->assertSame(OrdenEmpresarial::PENDIENTE, $orden->estado);
        $this->assertSame(Pago::ESTADO_PENDIENTE, $pago->estado);
        $this->assertNull($orden->procesada_at);

        // El inventario NO se descuenta mientras la transferencia esté pendiente
        $this->producto->refresh();
        $this->assertSame(10, $this->producto->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_admin_can_confirm_pending_transfer_and_updates_scm_and_erp_atomically(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];
        $orden = $resultado['orden'];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));

        $response->assertRedirect(route('admin.tienda.ordenes.show', $pago));

        $pago->refresh();
        $orden->refresh();

        $this->assertSame(Pago::ESTADO_APROBADO, $pago->estado);
        $this->assertSame(OrdenEmpresarial::PROCESADA, $orden->estado);
        $this->assertNotNull($orden->procesada_at);

        // SCM: Se descuentan las 2 unidades
        $this->producto->refresh();
        $this->assertSame(8, $this->producto->stock_actual);

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->producto->id,
            'tipo' => MovimientoInventario::TIPO_SALIDA,
            'cantidad' => 2,
            'motivo' => MovimientoInventario::MOTIVO_VENTA,
        ]);

        // Procesos y auditoría ERP registrados
        $this->assertDatabaseHas('procesos_internos', [
            'orden_empresarial_id' => $orden->id,
            'tipo' => 'pago_transferencia_confirmado',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $this->admin->id,
            'accion' => 'confirmar_transferencia_demo',
        ]);
    }

    public function test_admin_can_reject_pending_transfer_and_cancels_order_without_inventory_movement(): void
    {
        $promo = Promocion::create([
            'codigo' => 'DESC50',
            'nombre' => 'Descuento 50 MXN',
            'tipo_descuento' => Promocion::TIPO_MONTO_FIJO,
            'valor' => 5000,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addMonth(),
            'activo' => true,
            'usos_actuales' => 0,
        ]);

        $resultado = $this->crearCompraTransferenciaPendiente('DESC50');
        $pago = $resultado['pago'];
        $orden = $resultado['orden'];

        // Al crear la orden pendiente, el cupo de la promo se reservó
        $promo->refresh();
        $this->assertSame(1, $promo->usos_actuales);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tienda.ordenes.rechazar-transferencia', $pago), [
                'motivo' => 'Comprobante bancario ilegible en demo',
            ]);

        $response->assertRedirect(route('admin.tienda.ordenes.show', $pago));

        $pago->refresh();
        $orden->refresh();

        $this->assertSame(Pago::ESTADO_RECHAZADO, $pago->estado);
        $this->assertSame(OrdenEmpresarial::CANCELADA, $orden->estado);

        // Inventario intacto
        $this->producto->refresh();
        $this->assertSame(10, $this->producto->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 0);

        // El cupo de la promoción se libera al rechazar
        $promo->refresh();
        $this->assertSame(0, $promo->usos_actuales);

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $this->admin->id,
            'accion' => 'rechazar_transferencia_demo',
        ]);
    }

    public function test_repeated_confirmation_is_idempotent_and_does_not_duplicate_inventory_exit(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];

        // Primera confirmación
        $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));
        $this->producto->refresh();
        $this->assertSame(8, $this->producto->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 1);

        // Segunda confirmación repetida (ej. dos clics o dos pestañas)
        $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));

        // El stock no se vuelve a descontar y no se duplican movimientos
        $this->producto->refresh();
        $this->assertSame(8, $this->producto->stock_actual);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    public function test_confirmation_after_rejection_is_rejected(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];

        // Rechazar primero
        $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.rechazar-transferencia', $pago));

        // Intentar confirmar después de rechazo
        $response = $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));
        $response->assertSessionHasErrors('transferencia');
    }

    public function test_rejection_after_approval_is_rejected(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];

        // Aprobar primero
        $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));

        // Intentar rechazar después de aprobación
        $response = $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.rechazar-transferencia', $pago));
        $response->assertSessionHasErrors('transferencia');
    }

    public function test_confirmation_fails_atomically_if_stock_became_insufficient(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];
        $orden = $resultado['orden'];

        // Simular que el stock bajó a 1 mientras la transferencia estaba pendiente (se requieren 2)
        $this->producto->update(['stock_actual' => 1]);

        $response = $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));
        $response->assertSessionHasErrors('stock');

        // Estado sigue pendiente sin cambios parciales
        $pago->refresh();
        $orden->refresh();
        $this->assertSame(Pago::ESTADO_PENDIENTE, $pago->estado);
        $this->assertSame(OrdenEmpresarial::PENDIENTE, $orden->estado);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_unauthorized_user_and_client_cannot_confirm_or_reject_transfers(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];

        // Intento por parte del cliente
        $this->actingAs($this->clienteUser)
            ->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago))
            ->assertForbidden();

        // Intento por parte de empleado sin rol admin
        $this->actingAs($this->empleado)
            ->post(route('admin.tienda.ordenes.rechazar-transferencia', $pago))
            ->assertForbidden();
    }

    public function test_legacy_erp_endpoints_cannot_desynchronize_commercial_order(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $orden = $resultado['orden'];

        // Intentar procesar la orden comercial desde el endpoint clásico del ERP
        $this->actingAs($this->admin)
            ->post(route('erp.ordenes.procesar', $orden))
            ->assertSessionHasErrors('pago');

        // Intentar cancelar desde el endpoint clásico del ERP
        $this->actingAs($this->admin)
            ->post(route('erp.ordenes.cancelar', $orden))
            ->assertSessionHasErrors('pago');
    }

    public function test_erp_metrics_count_transfer_sale_only_after_confirmation(): void
    {
        $resultado = $this->crearCompraTransferenciaPendiente();
        $pago = $resultado['pago'];

        // 1. Mientras esté pendiente, no cuenta en métricas de venta procesada
        $resAntes = $this->actingAs($this->admin)->getJson(route('api.erp.metricas'));
        $dataAntes = $resAntes->json('data');
        $this->assertEquals(0, (float) $dataAntes['ventas_totales']);

        // 2. Tras confirmación, cuenta una sola vez
        $this->actingAs($this->admin)->post(route('admin.tienda.ordenes.confirmar-transferencia', $pago));

        $resDespues = $this->actingAs($this->admin)->getJson(route('api.erp.metricas'));
        $dataDespues = $resDespues->json('data');
        // $1,000.00 subtotal + IVA 16% ($160.00) = $1,160.00
        $this->assertEquals(1160.00, (float) $dataDespues['ventas_totales']);
        $this->assertSame(1, $dataDespues['ordenes_procesadas']);
    }
}
