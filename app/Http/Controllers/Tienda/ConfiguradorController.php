<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\TiendaCarritoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ConfiguradorController extends Controller
{
    public function __construct(
        protected TiendaCarritoService $carritoService
    ) {}

    public function index(): View
    {
        $basePlan = null;
        if (Schema::hasTable('productos')) {
            $basePlan = Producto::query()
                ->where('visible_tienda', true)
                ->where('nombre', 'like', '%Inicio%')
                ->first();
        }

        return view('plans.custom', [
            'modules' => config('ecore.modules', []),
            'basePlan' => $basePlan,
        ]);
    }

    public function agregar(Request $request): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('warning', 'Inicia sesión para agregar tu configuración personalizada al carrito.');
        }

        $request->validate([
            'modulos' => ['nullable', 'array'],
            'modulos.*' => ['string', 'max:255'],
        ]);

        $modulos = $request->input('modulos', []);

        // Obtener el producto base Agente Inicio
        $producto = Producto::query()
            ->where('visible_tienda', true)
            ->where('nombre', 'like', '%Inicio%')
            ->first();

        if (! $producto) {
            // Alternativa: buscar el primer plan visible
            $producto = Producto::query()->where('visible_tienda', true)->first();
        }

        if (! $producto) {
            return back()->withErrors(['error' => 'No hay planes base disponibles en este momento.']);
        }

        $this->carritoService->agregarProducto(
            $request->user(),
            $producto->id,
            1,
            $modulos
        );

        return redirect()->route('carrito.index')->with('success', '¡Agente personalizado agregado al carrito!');
    }
}
