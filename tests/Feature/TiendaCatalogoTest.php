<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor Demo IA',
            'contacto' => 'Soporte',
            'telefono' => '5511223344',
            'correo' => 'proveedor@demo.local',
        ]);
    }

    public function test_catalog_displays_only_visible_store_products_and_excludes_internal_resources(): void
    {
        $productoVisible = Producto::create([
            'nombre' => 'Agente Inicio Visible',
            'descripcion' => 'Plan de atención esencial',
            'categoria' => 'Planes',
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $productoInterno = Producto::create([
            'nombre' => 'Recurso Interno SCM Token',
            'descripcion' => 'Capacidad técnica no comercial',
            'categoria' => 'Tokens',
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 50,
            'precio_venta_unitario' => 0,
            'visible_tienda' => false,
        ]);

        $response = $this->get(route('catalogo.index'));

        $response->assertOk();
        $response->assertSee('Agente Inicio Visible');
        $response->assertDontSee('Recurso Interno SCM Token');
    }

    public function test_catalog_detail_shows_visible_product_and_aborts_for_internal_resource(): void
    {
        $productoVisible = Producto::create([
            'nombre' => 'Agente Plus Comercial',
            'descripcion' => 'Seguimiento inteligente',
            'categoria' => 'Planes',
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 200,
            'precio_venta_unitario' => 999.00,
            'visible_tienda' => true,
        ]);

        $productoInterno = Producto::create([
            'nombre' => 'Capacidad Oculta',
            'descripcion' => 'Solo SCM',
            'categoria' => 'Infra',
            'stock_actual' => 20,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 10,
            'precio_venta_unitario' => 0,
            'visible_tienda' => false,
        ]);

        $this->get(route('catalogo.show', $productoVisible))
            ->assertOk()
            ->assertSee('Agente Plus Comercial')
            ->assertSee('999.00');

        $this->get(route('catalogo.show', $productoInterno))
            ->assertNotFound();
    }

    public function test_catalog_filters_by_search_term_and_category(): void
    {
        $catPlanes = Categoria::create(['nombre' => 'Planes', 'slug' => 'planes', 'activo' => true]);
        $catComps = Categoria::create(['nombre' => 'Complementos', 'slug' => 'complementos', 'activo' => true]);

        Producto::create([
            'nombre' => 'Agente Inicio',
            'descripcion' => 'Plan inicial',
            'categoria' => 'Planes',
            'categoria_id' => $catPlanes->id,
            'stock_actual' => 10,
            'stock_minimo' => 1,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        Producto::create([
            'nombre' => 'Transcripción de audios',
            'descripcion' => 'Módulo de voz',
            'categoria' => 'Complementos',
            'categoria_id' => $catComps->id,
            'stock_actual' => 20,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 20,
            'precio_venta_unitario' => 75.00,
            'visible_tienda' => true,
        ]);

        // Filtrar por búsqueda
        $resBuscar = $this->get(route('catalogo.index', ['buscar' => 'Transcripción']));
        $resBuscar->assertOk();
        $resBuscar->assertSee('Transcripción de audios');
        $resBuscar->assertDontSee('Agente Inicio');

        // Filtrar por categoría
        $resCat = $this->get(route('catalogo.index', ['categoria_id' => $catPlanes->id]));
        $resCat->assertOk();
        $resCat->assertSee('Agente Inicio');
        $resCat->assertDontSee('Transcripción de audios');
    }

    public function test_catalog_displays_promotional_discount_when_promotion_is_active(): void
    {
        $producto = Producto::create([
            'nombre' => 'Agente Avanzado Promo',
            'descripcion' => 'Automatización completa',
            'categoria' => 'Planes',
            'stock_actual' => 8,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 400,
            'precio_venta_unitario' => 1799.00,
            'visible_tienda' => true,
        ]);

        $promo = Promocion::create([
            'codigo' => 'AVANZADO10',
            'nombre' => 'Descuento Lanzamiento 10%',
            'tipo_descuento' => Promocion::TIPO_PORCENTAJE,
            'valor' => 1000, // 10.00%
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addMonth(),
            'activo' => true,
        ]);
        $promo->productos()->sync([$producto->id]);

        $response = $this->get(route('catalogo.index'));
        $response->assertOk();
        $response->assertSee('AVANZADO10');
        // $1799 - 10% ($179.90) = $1,619.10
        $response->assertSee('1,619.10');
    }

    public function test_custom_configurator_adds_to_cart_with_server_validated_module_prices(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $basePlan = Producto::create([
            'nombre' => 'Agente Inicio',
            'descripcion' => 'Base para personalizar',
            'categoria' => 'Planes',
            'stock_actual' => 15,
            'stock_minimo' => 2,
            'proveedor_id' => $this->proveedor->id,
            'costo_unitario' => 100,
            'precio_venta_unitario' => 499.00,
            'visible_tienda' => true,
        ]);

        $modulosElegidos = [
            'Transcripción de audios', // $75 en config/ecore.php
            'Agenda de citas',        // $200 en config/ecore.php
        ];

        $response = $this->actingAs($user)
            ->post(route('plans.custom.agregar'), [
                'modulos' => $modulosElegidos,
            ]);

        $response->assertRedirect(route('carrito.index'));

        $this->assertDatabaseHas('carrito_detalles', [
            'producto_id' => $basePlan->id,
            'cantidad' => 1,
        ]);

        $detalle = $user->cliente->carrito->detalles()->first();
        $this->assertNotNull($detalle->configuracion);
        // Base $499 + Extras $275 = $774
        $this->assertSame(27500, $detalle->configuracion['extras_total_centavos']);
    }
}
