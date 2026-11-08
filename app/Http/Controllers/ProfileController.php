<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\OrdenEmpresarial;
use App\Models\TicketAtencion;
use App\Services\MonederoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected MonederoService $monederoService
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $cliente = $user->cliente;

        $monedero = null;
        $comprasRecientes = collect();
        $ticketsRecientes = collect();
        $planesAdquiridos = collect();

        if ($cliente) {
            $monedero = $this->monederoService->obtenerMonedero($cliente);

            $comprasRecientes = OrdenEmpresarial::query()
                ->where('cliente_id', $cliente->id)
                ->with(['detalles.producto', 'pago'])
                ->latest('id')
                ->limit(3)
                ->get();

            $ticketsRecientes = TicketAtencion::query()
                ->where('cliente_id', $cliente->id)
                ->latest('id')
                ->limit(3)
                ->get();

            // Extraer planes adquiridos en órdenes procesadas
            $planesAdquiridos = OrdenEmpresarial::query()
                ->where('cliente_id', $cliente->id)
                ->where('estado', OrdenEmpresarial::PROCESADA)
                ->with('detalles.producto')
                ->get()
                ->flatMap(fn ($o) => $o->detalles)
                ->take(6);
        }

        return view('profile', [
            'user' => $user,
            'cliente' => $cliente,
            'monedero' => $monedero,
            'comprasRecientes' => $comprasRecientes,
            'ticketsRecientes' => $ticketsRecientes,
            'planesAdquiridos' => $planesAdquiridos,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'empresa' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update(['name' => $validated['name']]);

        if ($user->cliente) {
            $user->cliente->update([
                'nombre' => $validated['name'],
                'telefono' => $validated['telefono'] ?? $user->cliente->telefono,
                'empresa' => $validated['empresa'] ?? $user->cliente->empresa,
            ]);
        }

        Auditoria::registrar($user, 'actualizar_perfil', 'user', $user->id, "Actualizó sus datos personales de perfil.");

        return redirect()->route('profile')->with('success', 'Tus datos fueron actualizados correctamente.');
    }
}
