<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Impuesto;
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
use Tests\TestCase;

class TiendaAdminManagementTest extends TestCase
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

    public function test_admin_can_toggle_product_store_visibility_safely(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $producto = Producto::create([
            'nombre' => 'Recurso Base',
            'descripcion' => 'Capacidad',
            'categoria' => 'Infra',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 50,
            'precio_venta_unitario' => 150.00,
            'visible_tienda' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tienda.productos.toggle', $producto))
            ->assertSessionHasNoErrors();

        $this->assertTrue($producto->fresh()->visible_tienda);

        // Volver a alternar lo oculta de nuevo
        $this->actingAs($admin)
            ->post(route('admin.tienda.productos.toggle', $producto));

        $this->assertFalse($producto->fresh()->visible_tienda);
    }

    public function test_admin_can_manage_normalized_categories(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Crear categoría
        $this->actingAs($admin)
            ->post(route('admin.tienda.categorias.store'), [
                'nombre' => 'Inteligencia Conversacional',
                'descripcion' => 'Agentes para chats y WhatsApp',
                'activo' => 1,
            ])
            ->assertRedirect(route('admin.tienda.categorias.index'));

        $categoria = Categoria::query()->where('nombre', 'Inteligencia Conversacional')->first();
        $this->assertNotNull($categoria);
        $this->assertSame('inteligencia-conversacional', $categoria->slug);

        // Editar categoría
        $this->actingAs($admin)
            ->put(route('admin.tienda.categorias.update', $categoria), [
                'nombre' => 'Agentes Conversacionales',
                'descripcion' => 'Descripción actualizada',
                'activo' => 1,
            ])
            ->assertRedirect(route('admin.tienda.categorias.index'));

        $this->assertSame('Agentes Conversacionales', $categoria->fresh()->nombre);
    }

    public function test_admin_can_manage_promotions_and_taxes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Crear promoción
        $this->actingAs($admin)
            ->post(route('admin.tienda.promociones.store'), [
                'codigo' => 'BLACKFRIDAY',
                'nombre' => 'Black Friday 20%',
                'tipo_descuento' => 'porcentaje',
                'valor_humano' => '20.00',
                'fecha_inicio' => now()->subDay()->format('Y-m-d H:i:s'),
                'fecha_fin' => now()->addWeek()->format('Y-m-d H:i:s'),
                'activo' => 1,
            ])
            ->assertRedirect(route('admin.tienda.promociones.index'));

        $promo = Promocion::query()->where('codigo', 'BLACKFRIDAY')->first();
        $this->assertNotNull($promo);
        $this->assertSame(2000, $promo->valor); // 2000 puntos base = 20.00%

        // Crear impuesto
        $this->actingAs($admin)
            ->post(route('admin.tienda.impuestos.store'), [
                'nombre' => 'IEPS Digital 8%',
                'porcentaje' => '8.00',
                'activo' => 1,
            ])
            ->assertRedirect(route('admin.tienda.impuestos.index'));

        $impuesto = Impuesto::query()->where('nombre', 'IEPS Digital 8%')->first();
        $this->assertNotNull($impuesto);
        $this->assertSame(800, $impuesto->puntos_base); // 800 puntos base = 8.00%
    }

    public function test_erp_dashboard_integrates_store_orders_without_mixing_wallet_recharges(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $producto = Producto::create([
            'nombre' => 'Agente Inicio ERP',
            'descripcion' => 'Esencial',
            'categoria' => 'Planes',
            'stock_actual' => 20,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 500.00,
            'visible_tienda' => true,
        ]);

        // 1. Recargar monedero con $10,000 MXN (Operación financiera de saldo, NO venta de productos)
        $monederoService = app(MonederoService::class);
        $monederoService->recargar($clientUser->obtenerCliente(), $clientUser, 1000000);

        // 2. Realizar compra en tienda de 1 unidad ($500.00 + IVA $80.00 = $580.00)
        Impuesto::create(['nombre' => 'IVA 16%', 'puntos_base' => 1600, 'activo' => true]);

        $carritoService = app(TiendaCarritoService::class);
        $carritoService->agregarProducto($clientUser, $producto->id, 1);

        $checkoutService = app(TiendaCheckoutService::class);
        $checkoutService->procesarCompra(
            $clientUser,
            ['nombre_contacto' => 'Test', 'correo_contacto' => 't@t.com', 'telefono_contacto' => '55'],
            'tarjeta_demo'
        );

        // 3. Consultar métricas del ERP
        $res = $this->actingAs($admin)->getJson(route('api.erp.metricas'));
        $res->assertOk();

        $data = $res->json('data');

        // Ventas totales cuenta el total de la orden procesada ($580.00), NO los $10,000 del monedero
        $this->assertEquals(580.00, (float) $data['ventas_totales']);
        $this->assertSame(1, $data['ordenes_procesadas']);
        $this->assertSame(1, $data['productos_vendidos']);
    }
}
