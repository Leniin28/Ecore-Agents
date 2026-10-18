<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\OrdenDetalle;
use App\Models\OrdenEmpresarial;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('erp.dashboard', [
            ...$this->data(),
            'recientes' => OrdenEmpresarial::query()->with('cliente')->latest('id')->limit(5)->get(),
        ]);
    }

    public function reportes(): View
    {
        return view('erp.reportes', [
            ...$this->data(),
            'inventario' => Producto::query()->with('proveedor')->orderBy('nombre')->paginate(15),
            'clientesComerciales' => Cliente::query()
                ->whereHas('ordenesEmpresariales', fn ($q) => $q->where('estado', OrdenEmpresarial::PROCESADA))
                ->withCount(['ordenesEmpresariales as ordenes_procesadas' => fn ($q) => $q->where('estado', OrdenEmpresarial::PROCESADA)])
                ->withSum(['ordenesEmpresariales as ventas_totales' => fn ($q) => $q->where('estado', OrdenEmpresarial::PROCESADA)], 'total')
                ->orderByDesc('ventas_totales')->limit(5)->get(),
        ]);
    }

    public function metricas(): JsonResponse
    {
        return response()->json(['data' => $this->data()]);
    }

    private function data(): array
    {
        $procesadas = OrdenEmpresarial::query()->where('estado', OrdenEmpresarial::PROCESADA);
        $porEstado = OrdenEmpresarial::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $estados = collect(OrdenEmpresarial::ESTADOS)->mapWithKeys(fn ($estado) => [$estado => (int) ($porEstado[$estado] ?? 0)])->all();
        $inicio = now()->startOfMonth()->subMonths(5);
        $ventas = (clone $procesadas)->whereBetween('procesada_at', [$inicio, now()->endOfMonth()])
            ->selectRaw("strftime('%Y-%m', procesada_at) as mes, SUM(total) as ventas")
            ->groupBy('mes')->pluck('ventas', 'mes');
        $meses = [];
        $nombres = [1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        for ($i = 0; $i < 6; $i++) {
            $fecha = $inicio->copy()->addMonths($i);
            $meses[] = ['mes' => $fecha->format('Y-m'), 'etiqueta' => $nombres[$fecha->month],
                'ventas' => round((float) ($ventas[$fecha->format('Y-m')] ?? 0), 2)];
        }
        $vendidos = OrdenDetalle::query()->whereHas('ordenEmpresarial', fn ($q) => $q->where('estado', OrdenEmpresarial::PROCESADA));
        $top = (clone $vendidos)->join('productos', 'productos.id', '=', 'orden_detalles.producto_id')
            ->selectRaw('productos.id as producto_id, productos.nombre, SUM(orden_detalles.cantidad) as cantidad')
            ->groupBy('productos.id', 'productos.nombre')->orderByDesc('cantidad')->orderBy('productos.nombre')->limit(5)->get()
            ->map(fn ($fila) => ['producto_id' => $fila->producto_id, 'nombre' => $fila->nombre, 'cantidad' => (int) $fila->cantidad]);

        return [
            'ventas_totales' => round((float) (clone $procesadas)->sum('total'), 2),
            'ordenes_procesadas' => $estados[OrdenEmpresarial::PROCESADA],
            'productos_vendidos' => (int) (clone $vendidos)->sum('cantidad'),
            'clientes_activos' => Cliente::query()->where('estado', Cliente::ESTADO_ACTIVO)->count(),
            'inventario_disponible' => (int) Producto::query()->sum('stock_actual'),
            'ordenes_por_estado' => $estados,
            'ventas_mensuales' => $meses,
            'recursos_mas_vendidos' => $top,
        ];
    }
}
