<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\ProcesoInterno;
use App\Models\EstadoErp;
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
                'permissions' => [User::MODULE_SCM, User::MODULE_ERP],
                'perfil_empresarial' => 'logistica',
            ],
        );

        $proveedor = Proveedor::query()->updateOrCreate(
            ['nombre' => 'OpenRouter'],
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
                [...$definicion, 'proveedor_id' => $proveedor->id, 'precio_venta_unitario' => match ($definicion['categoria']) {
                    'Razonamiento' => '0.35', 'General' => '0.20', 'Multimodal' => '0.45', 'Ligero' => '0.10', default => '0.15',
                }],
            );

            return [$producto->nombre => $producto];
        });

        $inicio = now()->startOfMonth()->subMonths(5);
        $consumoRazonamiento = [360, 480, 620, 780, 920, 1400];
        $consumoGeneral = [140, 180, 220, 250, 280, 300];

        foreach (range(0, 5) as $indice) {
            $mes = $inicio->copy()->addMonths($indice);
            $fecha = $mes->addDays($indice === 5 ? min(9, now()->day - 1) : 9)->startOfDay();
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

        $this->seedErp($productos, $empleado);
    }

    private function seedErp($productos, User $logistica): void
    {
        $ventas = User::query()->updateOrCreate(['email' => 'ventas.demo@ecore.local'], [
            'name' => 'Ventas Demo', 'password' => 'ECoreDemo2026!', 'role' => User::ROLE_EMPLEADO,
            'perfil_empresarial' => 'ventas', 'permissions' => [User::MODULE_CRM, User::MODULE_ERP],
        ]);
        $clientes = collect(['aurora', 'nexo'])->map(fn ($nombre) => Cliente::query()->updateOrCreate(
            ['correo' => $nombre.'.demo@ecore.local'],
            ['nombre' => 'Cliente Demo '.ucfirst($nombre), 'empresa' => 'Empresa Demo '.ucfirst($nombre),
                'telefono' => '0000000000', 'fecha_registro' => now()->startOfMonth()->subMonths(6)->toDateString(),
                'estado' => Cliente::ESTADO_ACTIVO, 'etapa_crm' => Cliente::ETAPA_ACTIVO],
        ));
        $consumos = [];
        foreach (range(0, 5) as $indice) {
            $fecha = now()->startOfMonth()->subMonths(5 - $indice);
            $estado = $indice < 4 ? OrdenEmpresarial::PROCESADA : ($indice === 4 ? OrdenEmpresarial::PENDIENTE : OrdenEmpresarial::CANCELADA);
            $orden = OrdenEmpresarial::query()->updateOrCreate(
                ['cliente_id' => $clientes[$indice % 2]->id, 'fecha' => $fecha],
                ['usuario_id' => $ventas->id, 'estado' => $estado, 'procesada_at' => $estado === OrdenEmpresarial::PROCESADA ? $fecha : null, 'total' => '0.00'],
            );
            $totalCentavos = 0;
            foreach (['Capacidad de razonamiento' => 10, 'Capacidad general' => 20] as $nombre => $base) {
                $producto = $productos[$nombre];
                $cantidad = $base + $indice * 10;
                $precioCentavos = $nombre === 'Capacidad de razonamiento' ? 35 : 20;
                $subtotal = $cantidad * $precioCentavos;
                $totalCentavos += $subtotal;
                $detalle = $orden->detalles()->updateOrCreate(['producto_id' => $producto->id], [
                    'cantidad' => $cantidad, 'precio_unitario' => $precioCentavos / 100, 'subtotal' => $subtotal / 100,
                ]);
                if ($estado === OrdenEmpresarial::PROCESADA) {
                    MovimientoInventario::query()->updateOrCreate(['orden_detalle_id' => $detalle->id], [
                        'producto_id' => $producto->id, 'usuario_id' => $logistica->id,
                        'tipo' => MovimientoInventario::TIPO_SALIDA, 'cantidad' => $cantidad,
                        'motivo' => MovimientoInventario::MOTIVO_VENTA, 'fecha' => $fecha,
                    ]);
                    $consumos[$producto->id] = ($consumos[$producto->id] ?? 0) + $cantidad;
                }
            }
            $orden->update(['total' => $totalCentavos / 100]);
            $tipos = ['orden_creada'];
            if ($estado === OrdenEmpresarial::PROCESADA) {
                $tipos = ['orden_creada', 'stock_validado', 'inventario_actualizado', 'movimientos_registrados', 'orden_procesada'];
            } elseif ($estado === OrdenEmpresarial::CANCELADA) {
                $tipos[] = 'orden_cancelada';
            }
            foreach ($tipos as $tipo) {
                $orden->procesosInternos()->updateOrCreate(['tipo' => $tipo], [
                    'usuario_id' => $tipo === 'orden_creada' ? $ventas->id : $logistica->id,
                    'descripcion' => ProcesoInterno::TIPOS[$tipo], 'estado' => 'completado', 'fecha' => $fecha,
                ]);
            }
        }
        // Las definiciones anteriores restablecen el punto de partida demo en cada ejecución.
        foreach ($productos as $producto) {
            $producto->update(['stock_actual' => $producto->stock_actual - ($consumos[$producto->id] ?? 0)]);
        }
        EstadoErp::query()->updateOrCreate(['id' => 1], ['nivel' => 'automatizado']);
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
