<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('scm.dashboard', $this->dashboardData());
    }

    public function reportes(): View
    {
        return view('scm.reportes', $this->reportData());
    }

    public function apiMetricas(): JsonResponse
    {
        $data = $this->reportData();

        return response()->json(['data' => [
            'periodo_dias' => 30,
            'totales' => $data['totales'],
            'estrategias' => $data['estrategias'],
            'recursos_mas_consumidos' => $data['masConsumidos']->map($this->serializeUsage(...)),
            'recursos_baja_utilizacion' => $data['bajaUtilizacion']->map($this->serializeUsage(...)),
            'inventario_critico' => $data['criticos']->map(fn (Producto $producto) => $producto->only(['id', 'nombre', 'stock_actual', 'stock_minimo'])),
            'pedidos_recientes' => $data['pedidosRecientes']->map(fn (Pedido $pedido) => $pedido->only(['id', 'producto_id', 'cantidad', 'tipo', 'origen', 'estado', 'created_at'])),
            'rotacion' => $data['rotacion'],
            'consumo_push_pull_mensual' => $data['consumoMensual'],
        ]]);
    }

    /** @return array<string, mixed> */
    private function dashboardData(): array
    {
        $base = $this->baseMetrics();

        return [
            ...$base,
            'pedidosRecientes' => Pedido::query()->with('producto')->latest()->limit(5)->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function reportData(): array
    {
        $desde = now()->subDays(30);
        $productos = Producto::query()
            ->with('proveedor')
            ->withSum(['movimientos as consumo_30_dias' => fn ($query) => $query
                ->where('tipo', MovimientoInventario::TIPO_SALIDA)
                ->where('fecha', '>=', $desde)], 'cantidad')
            ->get();

        $ordenados = $productos->sortBy([
            fn (Producto $a, Producto $b) => ((int) ($b->consumo_30_dias ?? 0)) <=> ((int) ($a->consumo_30_dias ?? 0)),
            fn (Producto $a, Producto $b) => $a->nombre <=> $b->nombre,
        ])->values();

        $rotacion = ['alta' => 0, 'media' => 0, 'baja' => 0, 'porcentaje_movimiento' => 0, 'total' => $productos->count()];
        foreach ($productos as $producto) {
            $consumo = (int) ($producto->consumo_30_dias ?? 0);
            $nivel = $consumo === 0 ? 'baja' : ($consumo >= $producto->stock_minimo ? 'alta' : 'media');
            $rotacion[$nivel]++;
        }
        if ($rotacion['total'] > 0) {
            $rotacion['porcentaje_movimiento'] = (int) round((($rotacion['alta'] + $rotacion['media']) / $rotacion['total']) * 100);
        }

        $base = $this->baseMetrics();

        return [
            ...$base,
            'masConsumidos' => $ordenados->take(5),
            'maxConsumo' => max(1, (int) ($ordenados->first()?->consumo_30_dias ?? 0)),
            'bajaUtilizacion' => $productos->sortBy(fn (Producto $producto) => [(int) ($producto->consumo_30_dias ?? 0), $producto->nombre])->take(5)->values(),
            'criticos' => Producto::query()->with('proveedor')->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->orderByRaw('(stock_actual - stock_minimo) ASC')->orderBy('nombre')->get(),
            'pedidosRecientes' => Pedido::query()->with('producto')->latest()->limit(5)->get(),
            'rotacion' => $rotacion,
            'consumoMensual' => $this->monthlyConsumption(),
        ];
    }

    /** @return array<string, mixed> */
    private function baseMetrics(): array
    {
        $push = Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PUSH)->count();
        $pull = Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PULL)->count();
        $total = $push + $pull;

        return [
            'totales' => [
                'productos' => Producto::query()->count(),
                'proveedores' => Proveedor::query()->count(),
                'pedidos_pendientes' => Pedido::query()->where('estado', Pedido::ESTADO_PENDIENTE)->count(),
                'inventario_critico' => Producto::query()->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            ],
            'estrategias' => [
                'push' => $push,
                'pull' => $pull,
                'porcentaje_push' => $total ? round(($push / $total) * 100) : 0,
                'porcentaje_pull' => $total ? round(($pull / $total) * 100) : 0,
            ],
        ];
    }

    /** @return array{meses: array<int, array{clave: string, etiqueta: string, push: int, pull: int}>, maximo: int} */
    private function monthlyConsumption(): array
    {
        $inicio = now()->startOfMonth()->subMonths(5);
        $filas = MovimientoInventario::query()
            ->join('productos', 'productos.id', '=', 'movimientos_inventario.producto_id')
            ->where('movimientos_inventario.tipo', MovimientoInventario::TIPO_SALIDA)
            ->where('movimientos_inventario.fecha', '>=', $inicio)
            ->selectRaw("strftime('%Y-%m', movimientos_inventario.fecha) as mes, productos.estrategia_logistica as estrategia, SUM(movimientos_inventario.cantidad) as total")
            ->groupBy('mes', 'estrategia')
            ->get()
            ->keyBy(fn ($fila) => $fila->mes.'-'.$fila->estrategia);

        $nombres = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $meses = [];
        $maximo = 0;
        for ($i = 0; $i < 6; $i++) {
            $fecha = $inicio->copy()->addMonths($i);
            $clave = $fecha->format('Y-m');
            $push = (int) ($filas->get($clave.'-'.Producto::ESTRATEGIA_PUSH)?->total ?? 0);
            $pull = (int) ($filas->get($clave.'-'.Producto::ESTRATEGIA_PULL)?->total ?? 0);
            $maximo = max($maximo, $push, $pull);
            $meses[] = ['clave' => $clave, 'etiqueta' => $nombres[(int) $fecha->format('n')], 'push' => $push, 'pull' => $pull];
        }

        return ['meses' => $meses, 'maximo' => max(1, $maximo)];
    }

    private function serializeUsage(Producto $producto): array
    {
        return [
            ...$producto->only(['id', 'nombre', 'categoria']),
            'consumo_30_dias' => (int) ($producto->consumo_30_dias ?? 0),
        ];
    }
}
