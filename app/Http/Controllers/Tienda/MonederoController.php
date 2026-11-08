<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\MonederoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonederoController extends Controller
{
    public function __construct(
        protected MonederoService $monederoService
    ) {}

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $cliente = $usuario->obtenerCliente();

        $monedero = $this->monederoService->obtenerMonedero($cliente);
        $movimientos = $monedero->movimientos()->paginate(15);

        return view('tienda.monedero', [
            'monedero' => $monedero,
            'movimientos' => $movimientos,
            'cliente' => $cliente,
        ]);
    }

    public function recargar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'monto_pesos' => ['required', 'numeric', 'min:10', 'max:50000'],
            'referencia_operacion' => ['nullable', 'string', 'max:64'],
        ], [
            'monto_pesos.required' => 'Indica el monto que deseas recargar.',
            'monto_pesos.min' => 'El monto mínimo de recarga demo es de $10.00 MXN.',
            'monto_pesos.max' => 'El monto máximo por recarga demo es de $50,000.00 MXN.',
        ]);

        $montoCentavos = (int) round(((float) $validated['monto_pesos']) * 100);

        $usuario = $request->user();
        $cliente = $usuario->obtenerCliente();

        $movimiento = $this->monederoService->recargar(
            $cliente,
            $usuario,
            $montoCentavos,
            "Recarga demostrativa solicitada desde el portal por el usuario {$usuario->name}",
            $validated['referencia_operacion'] ?? null
        );

        return redirect()->route('monedero.index')
            ->with('success', "Recarga demo de {$movimiento->monto_formateado} aplicada correctamente. Nuevo saldo: {$movimiento->saldo_formateado}.");
    }
}
