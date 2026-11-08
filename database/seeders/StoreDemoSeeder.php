<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categorías normalizadas
        $catPlanes = Categoria::firstOrCreate(
            ['slug' => 'planes'],
            [
                'nombre' => 'Planes',
                'descripcion' => 'Planes comerciales de agentes IA para atención y ventas',
                'activo' => true,
            ]
        );

        $catComplementos = Categoria::firstOrCreate(
            ['slug' => 'complementos'],
            [
                'nombre' => 'Complementos',
                'descripcion' => 'Módulos y capacidades adicionales para potenciar tus agentes',
                'activo' => true,
            ]
        );

        // 2. Proveedor demo para vincular productos con SCM
        $proveedor = Proveedor::firstOrCreate(
            ['nombre' => 'OpenRouter'],
            [
                'contacto' => 'Soporte Plataforma',
                'telefono' => '5550001122',
                'correo' => 'contacto@openrouter.local',
            ]
        );

        // 3. Productos basados en config/ecore.php
        $planesConfig = config('ecore.plans', []);

        $productosCreados = [];

        // Agente Inicio
        if (isset($planesConfig['inicio'])) {
            $p = $planesConfig['inicio'];
            $productosCreados['inicio'] = Producto::firstOrCreate(
                ['nombre' => $p['name']],
                [
                    'descripcion' => $p['summary'],
                    'categoria' => 'Planes',
                    'categoria_id' => $catPlanes->id,
                    'stock_actual' => 30,
                    'stock_minimo' => 5,
                    'proveedor_id' => $proveedor->id,
                    'costo_unitario' => 100.00,
                    'precio_venta_unitario' => (float) $p['price'],
                    'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
                    'visible_tienda' => true,
                    'destacado' => false,
                    'imagen_url' => '/images/plan-inicio.svg',
                ]
            );
        }

        // Agente Plus
        if (isset($planesConfig['plus'])) {
            $p = $planesConfig['plus'];
            $productosCreados['plus'] = Producto::firstOrCreate(
                ['nombre' => $p['name']],
                [
                    'descripcion' => $p['summary'],
                    'categoria' => 'Planes',
                    'categoria_id' => $catPlanes->id,
                    'stock_actual' => 25,
                    'stock_minimo' => 5,
                    'proveedor_id' => $proveedor->id,
                    'costo_unitario' => 250.00,
                    'precio_venta_unitario' => (float) $p['price'],
                    'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
                    'visible_tienda' => true,
                    'destacado' => true,
                    'imagen_url' => '/images/plan-plus.svg',
                ]
            );
        }

        // Agente Avanzado
        if (isset($planesConfig['avanzado'])) {
            $p = $planesConfig['avanzado'];
            $productosCreados['avanzado'] = Producto::firstOrCreate(
                ['nombre' => $p['name']],
                [
                    'descripcion' => $p['summary'],
                    'categoria' => 'Planes',
                    'categoria_id' => $catPlanes->id,
                    'stock_actual' => 20,
                    'stock_minimo' => 5,
                    'proveedor_id' => $proveedor->id,
                    'costo_unitario' => 500.00,
                    'precio_venta_unitario' => (float) $p['price'],
                    'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
                    'visible_tienda' => true,
                    'destacado' => true,
                    'imagen_url' => '/images/plan-avanzado.svg',
                ]
            );
        }

        // Complementos representativos
        $complementosDemo = [
            [
                'nombre' => 'Transcripción de audios',
                'descripcion' => 'Convierte mensajes de voz de WhatsApp en texto con IA.',
                'precio' => 75.00,
                'costo' => 20.00,
            ],
            [
                'nombre' => 'Agenda de citas por WhatsApp',
                'descripcion' => 'Permite que el agente agende citas automáticamente con los clientes.',
                'precio' => 200.00,
                'costo' => 50.00,
            ],
            [
                'nombre' => 'Reportes avanzados',
                'descripcion' => 'Estadísticas e insights de conversaciones para toma de decisiones.',
                'precio' => 150.00,
                'costo' => 30.00,
            ],
            [
                'nombre' => 'Respuestas en audio',
                'descripcion' => 'Generación de respuestas habladas con voz natural sintética.',
                'precio' => 150.00,
                'costo' => 40.00,
            ],
        ];

        foreach ($complementosDemo as $comp) {
            Producto::firstOrCreate(
                ['nombre' => $comp['nombre']],
                [
                    'descripcion' => $comp['descripcion'],
                    'categoria' => 'Complementos',
                    'categoria_id' => $catComplementos->id,
                    'stock_actual' => 50,
                    'stock_minimo' => 10,
                    'proveedor_id' => $proveedor->id,
                    'costo_unitario' => $comp['costo'],
                    'precio_venta_unitario' => $comp['precio'],
                    'estrategia_logistica' => Producto::ESTRATEGIA_PULL,
                    'visible_tienda' => true,
                    'destacado' => false,
                    'imagen_url' => null,
                ]
            );
        }

        // 4. Impuesto base (IVA 16% = 1600 puntos base)
        Impuesto::firstOrCreate(
            ['nombre' => 'IVA 16%'],
            [
                'puntos_base' => 1600,
                'activo' => true,
            ]
        );

        // 5. Promoción funcional existente en config (AVANZADO10: 10% = 1000 puntos base)
        $promocionAvanzado = Promocion::firstOrCreate(
            ['codigo' => 'AVANZADO10'],
            [
                'nombre' => 'Descuento 10% Agente Avanzado',
                'descripcion' => 'Descuento de lanzamiento del 10% en Agente Avanzado (precio $1,619.10 MXN).',
                'tipo_descuento' => Promocion::TIPO_PORCENTAJE,
                'valor' => 1000, // 1000 puntos base = 10.00%
                'fecha_inicio' => now()->subDay(),
                'fecha_fin' => now()->addMonths(6),
                'limite_usos' => null,
                'usos_actuales' => 0,
                'activo' => true,
            ]
        );

        if (isset($productosCreados['avanzado'])) {
            $promocionAvanzado->productos()->syncWithoutDetaching([$productosCreados['avanzado']->id]);
        }

        // Promoción demo adicional por monto fijo ($50 MXN = 5000 centavos)
        Promocion::firstOrCreate(
            ['codigo' => 'BIENVENIDA50'],
            [
                'nombre' => 'Cupón de bienvenida $50 MXN',
                'descripcion' => 'Descuento de $50.00 MXN en cualquier plan o complemento de la tienda.',
                'tipo_descuento' => Promocion::TIPO_MONTO_FIJO,
                'valor' => 5000, // 5000 centavos = $50.00 MXN
                'fecha_inicio' => now()->subDay(),
                'fecha_fin' => now()->addMonths(6),
                'limite_usos' => 100,
                'usos_actuales' => 0,
                'activo' => true,
            ]
        );
    }
}
