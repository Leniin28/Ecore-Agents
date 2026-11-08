<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Monedero;
use App\Services\MonederoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonederoAdminController extends Controller
{
    public function __construct(
        protected MonederoService $monederoService
    ) {}

    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();

        $monederos = Monedero::query()
            ->with('cliente')
            ->when($buscar, function ($q) use ($buscar) {
                $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('correo', 'like', "%{$buscar}%"));
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tienda.monederos.index', compact('monederos'));
    }

    public function show(Monedero $monedero): View
    {
        $monedero->load('cliente');
        $movimientos = $monedero->movimientos()->with('usuario')->paginate(20);

        return view('admin.tienda.monederos.show', [
            'monedero' => $monedero,
            'movimientos' => $movimientos,
        ]);
    }

    public function ajustar(Request $request, Monedero $monedero): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_ajuste' => ['required', 'in:credito,debito'],
            'monto_pesos' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'referencia_operacion' => ['nullable', 'string', 'max:64'],
        ]);

        $montoCentavos = (int) round(((float) $validated['monto_pesos']) * 100);
        if ($validated['tipo_ajuste'] === 'debito') {
            $montoCentavos = -$montoCentavos;
        }

        $this->monederoService->ajustar(
            $monedero,
            $request->user(),
            $montoCentavos,
            $validated['motivo'],
            'Ajuste administrativo autorizado por '.$request->user()->name,
            $validated['referencia_operacion'] ?? null
        );

        return redirect()->route('admin.tienda.monederos.show', $monedero)
            ->with('success', 'Ajuste de saldo registrado y auditado correctamente.');
    }
}
