<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaSeedStoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_store_command_creates_demo_catalog_idempotently(): void
    {
        // 1. Ejecutar el comando por primera vez
        $this->artisan('ecore:seed-store')
            ->expectsOutputToContain('Categorías, planes, complementos')
            ->assertSuccessful();

        // Verificar que las categorías se crearon
        $this->assertDatabaseHas('categorias', ['slug' => 'planes']);
        $this->assertDatabaseHas('categorias', ['slug' => 'complementos']);

        // Verificar productos comerciales creados
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Agente Inicio',
            'visible_tienda' => true,
        ]);
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Agente Plus',
            'visible_tienda' => true,
        ]);
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Agente Avanzado',
            'visible_tienda' => true,
        ]);

        // Verificar impuestos demo (IVA 16%)
        $this->assertDatabaseHas('impuestos', [
            'nombre' => 'IVA 16%',
            'puntos_base' => 1600,
            'activo' => true,
        ]);

        // Verificar promoción demo
        $this->assertDatabaseHas('promociones', [
            'codigo' => 'AVANZADO10',
            'tipo_descuento' => Promocion::TIPO_PORCENTAJE,
            'valor' => 1000,
            'activo' => true,
        ]);

        $conteoProductosInicial = Producto::count();
        $conteoCategoriasInicial = Categoria::count();
        $conteoImpuestosInicial = Impuesto::count();
        $conteoPromocionesInicial = Promocion::count();

        // 2. Ejecutar el comando por segunda vez para verificar idempotencia sin duplicados
        $this->artisan('ecore:seed-store')
            ->assertSuccessful();

        $this->assertSame($conteoProductosInicial, Producto::count());
        $this->assertSame($conteoCategoriasInicial, Categoria::count());
        $this->assertSame($conteoImpuestosInicial, Impuesto::count());
        $this->assertSame($conteoPromocionesInicial, Promocion::count());
    }
}
