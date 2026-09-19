<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Los datos demo solo se crean en entornos local o testing.');

            return;
        }

        $empleado = User::query()->updateOrCreate(
            ['email' => 'empleado.scm@ecore.local'],
            [
                'name' => 'Empleado SCM Demo',
                'password' => 'ECoreDemo2026!',
                'role' => User::ROLE_EMPLEADO,
                'permissions' => [User::MODULE_SCM],
            ],
        );

        $proveedor = Proveedor::query()->updateOrCreate(
            ['nombre' => 'OpenRouter Demo'],
            [
                'contacto' => 'Equipo académico ECore',
                'correo' => 'proveedor.demo@ecore.local',
                'telefono' => null,
            ],
        );

        $definiciones = [
            ['nombre' => 'Capacidad de razonamiento', 'descripcion' => 'Recurso demo para tareas de razonamiento.', 'categoria' => 'Razonamiento', 'stock_actual' => 700, 'stock_minimo' => 1000, 'costo_unitario' => 0.08, 'estrategia_logistica' => Producto::ESTRATEGIA_PUSH],
            ['nombre' => 'Capacidad general', 'descripcion' => 'Recurso demo de uso general.', 'categoria' => 'General', 'stock_actual' => 3200, 'stock_minimo' => 800, 'costo_unitario' => 0.03, 'estrategia_logistica' => Producto::ESTRATEGIA_PULL],
            ['nombre' => 'Capacidad multimodal', 'descripcion' => 'Recurso demo para texto e imagen.', 'categoria' => 'Multimodal', 'stock_actual' => 500, 'stock_minimo' => 500, 'costo_unitario' => 0.06, 'estrategia_logistica' => Producto::ESTRATEGIA_PUSH],
            ['nombre' => 'Capacidad ligera', 'descripcion' => 'Recurso demo de baja latencia.', 'categoria' => 'Ligero', 'stock_actual' => 5000, 'stock_minimo' => 400, 'costo_unitario' => 0.01, 'estrategia_logistica' => Producto::ESTRATEGIA_PULL],
            ['nombre' => 'Capacidad de respaldo', 'descripcion' => 'Recurso demo disponible para contingencias.', 'categoria' => 'Respaldo', 'stock_actual' => 0, 'stock_minimo' => 300, 'costo_unitario' => 0.02, 'estrategia_logistica' => Producto::ESTRATEGIA_PUSH],
        ];

        $productos = collect($definiciones)->mapWithKeys(function (array $definicion) use ($proveedor) {
            $producto = Producto::query()->updateOrCreate(
                ['nombre' => $definicion['nombre']],
                [...$definicion, 'proveedor_id' => $proveedor->id],
            );

            return [$producto->nombre => $producto];
        });

        $inicio = now()->startOfMonth()->subMonths(5);
        $consumoRazonamiento = [360, 480, 620, 780, 920, 1400];
        $consumoGeneral = [140, 180, 220, 250, 280, 300];

        foreach (range(0, 5) as $indice) {
            $fecha = $inicio->copy()->addMonths($indice)->addDays(9)->setTime(10, 0);
            $this->movement($productos['Capacidad de razonamiento'], $empleado, $consumoRazonamiento[$indice], $fecha);
            $this->movement($productos['Capacidad general'], $empleado, $consumoGeneral[$indice], $fecha->copy()->addHour());
        }

        $pedidos = [
            ['producto' => 'Capacidad de razonamiento', 'cantidad' => 900, 'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_PENDIENTE, 'origen' => Pedido::ORIGEN_AUTOMATICO_PUSH, 'usuario_id' => null],
            ['producto' => 'Capacidad general', 'cantidad' => 450, 'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_PENDIENTE, 'origen' => Pedido::ORIGEN_MANUAL, 'usuario_id' => $empleado->id],
            ['producto' => 'Capacidad multimodal', 'cantidad' => 300, 'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_SURTIDO, 'origen' => Pedido::ORIGEN_AUTOMATICO_PUSH, 'usuario_id' => null],
            ['producto' => 'Capacidad ligera', 'cantidad' => 200, 'tipo' => Pedido::TIPO_REPOSICION, 'estado' => Pedido::ESTADO_SURTIDO, 'origen' => Pedido::ORIGEN_MANUAL, 'usuario_id' => $empleado->id],
        ];

        foreach ($pedidos as $datos) {
            $producto = $productos[$datos['producto']];
            Pedido::query()->updateOrCreate(
                [
                    'producto_id' => $producto->id,
                    'tipo' => $datos['tipo'],
                    'estado' => $datos['estado'],
                    'origen' => $datos['origen'],
                ],
                ['cantidad' => $datos['cantidad'], 'usuario_id' => $datos['usuario_id']],
            );
        }
    }

    private function movement(Producto $producto, User $empleado, int $cantidad, $fecha): void
    {
        MovimientoInventario::query()->updateOrCreate(
            [
                'producto_id' => $producto->id,
                'tipo' => MovimientoInventario::TIPO_SALIDA,
                'motivo' => MovimientoInventario::MOTIVO_VENTA,
                'fecha' => $fecha,
            ],
            ['usuario_id' => $empleado->id, 'cantidad' => $cantidad],
        );
    }
}
