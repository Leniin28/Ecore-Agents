<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('scm.dashboard', $this->data());
    }

    public function apiMetricas(): JsonResponse
    {
        $data = $this->data();

        return response()->json(['data' => [
            'periodo_dias' => 30,
            'totales' => $data['totales'],
            'estrategias' => $data['estrategias'],
            'recursos_mas_consumidos' => $data['masConsumidos']->map($this->serializeUsage(...)),
            'recursos_baja_utilizacion' => $data['bajaUtilizacion']->map($this->serializeUsage(...)),
            'inventario_critico' => $data['criticos']->map(fn (Producto $producto) => $producto->only(['id', 'nombre', 'stock_actual', 'stock_minimo'])),
            'pedidos_recientes' => $data['pedidosRecientes']->map(fn (Pedido $pedido) => $pedido->only(['id', 'producto_id', 'cantidad', 'tipo', 'origen', 'estado', 'created_at'])),
        ]]);
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        $desde = now()->subDays(30);
        $productosConConsumo = Producto::query()
            ->with('proveedor')
            ->withSum(['movimientos as consumo_30_dias' => fn ($query) => $query
                ->where('tipo', MovimientoInventario::TIPO_SALIDA)
                ->where('fecha', '>=', $desde)], 'cantidad');

        $push = Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PUSH)->count();
        $pull = Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PULL)->count();
        $totalEstrategias = $push + $pull;

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
                'porcentaje_push' => $totalEstrategias ? round(($push / $totalEstrategias) * 100) : 0,
                'porcentaje_pull' => $totalEstrategias ? round(($pull / $totalEstrategias) * 100) : 0,
            ],
            'masConsumidos' => (clone $productosConConsumo)->orderByDesc('consumo_30_dias')->orderBy('nombre')->limit(5)->get(),
            'bajaUtilizacion' => (clone $productosConConsumo)->orderByRaw('COALESCE(consumo_30_dias, 0) ASC')->orderBy('nombre')->limit(5)->get(),
            'criticos' => Producto::query()->with('proveedor')->whereColumn('stock_actual', '<=', 'stock_minimo')->orderBy('stock_actual')->get(),
            'pedidosRecientes' => Pedido::query()->with('producto')->latest()->limit(5)->get(),
        ];
    }

    private function serializeUsage(Producto $producto): array
    {
        return [
            ...$producto->only(['id', 'nombre', 'categoria']),
            'consumo_30_dias' => (int) ($producto->consumo_30_dias ?? 0),
        ];
    }
}
