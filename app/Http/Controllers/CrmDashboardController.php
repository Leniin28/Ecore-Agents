<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Interaccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public const DIAS_INTERACCION_RECIENTE = 30;

    public function index(): View
    {
        $metricas = $this->metricas();
        $totalClientes = $metricas['total_clientes'];

        return view('admin.index', [
            'metricas' => $metricas,
            'clientesEnRiesgo' => $this->clientesEnRiesgo(),
            'clientesConMayorActividad' => Cliente::query()
                ->withCount('interacciones')
                ->orderByDesc('interacciones_count')
                ->orderBy('nombre')
                ->limit(5)
                ->get(),
            'porcentajeActivos' => $totalClientes > 0
                ? round(($metricas['clientes_activos'] / $totalClientes) * 100)
                : 0,
            'porcentajeInactivos' => $totalClientes > 0
                ? round(($metricas['clientes_inactivos'] / $totalClientes) * 100)
                : 0,
            'diasInteraccionReciente' => self::DIAS_INTERACCION_RECIENTE,
        ]);
    }

    public function miActividad(Request $request): View
    {
        $interacciones = $request->user()
            ->interacciones()
            ->with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return view('mi-actividad', compact('interacciones'));
    }

    public function apiMetricas(): JsonResponse
    {
        return response()->json(['data' => $this->metricas()]);
    }

    /**
     * @return array<string, int>
     */
    private function metricas(): array
    {
        $limiteReciente = now()->subDays(self::DIAS_INTERACCION_RECIENTE);

        return [
            'total_clientes' => Cliente::query()->count(),
            'clientes_activos' => Cliente::query()->where('estado', Cliente::ESTADO_ACTIVO)->count(),
            'clientes_inactivos' => Cliente::query()->where('estado', Cliente::ESTADO_INACTIVO)->count(),
            'total_interacciones' => Interaccion::query()->count(),
            'clientes_sin_interaccion_reciente' => Cliente::query()
                ->whereDoesntHave('interacciones', fn (Builder $query) => $query->where('fecha', '>=', $limiteReciente))
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Cliente>
     */
    private function clientesEnRiesgo(): Collection
    {
        $limiteReciente = now()->subDays(self::DIAS_INTERACCION_RECIENTE);

        return Cliente::query()
            ->where('estado', Cliente::ESTADO_ACTIVO)
            ->whereDoesntHave('interacciones', fn (Builder $query) => $query->where('fecha', '>=', $limiteReciente))
            ->with('ultimaInteraccion')
            ->orderBy('nombre')
            ->get()
            ->each(function (Cliente $cliente): void {
                $cliente->dias_sin_interaccion = $cliente->ultimaInteraccion
                    ? (int) $cliente->ultimaInteraccion->fecha->startOfDay()->diffInDays(now()->startOfDay())
                    : null;
            });
    }
}
