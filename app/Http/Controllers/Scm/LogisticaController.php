<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LogisticaController extends Controller
{
    public function index(): View
    {
        return view('scm.logistica.index', ['productos' => Producto::query()->orderBy('nombre')->get()]);
    }

    public function comparativa(): View
    {
        return view('scm.logistica.comparativa', [
            'productos' => Producto::query()->with('proveedor')->orderBy('nombre')->get(),
            'push' => Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PUSH)->count(),
            'pull' => Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PULL)->count(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $this->change($request, $producto);

        return redirect()->route('scm.logistica.index')->with('success', 'Estrategia actualizada correctamente.');
    }

    public function apiUpdate(Request $request, Producto $producto): JsonResponse
    {
        $this->change($request, $producto);

        return response()->json(['data' => $producto->fresh()->only(['id', 'nombre', 'estrategia_logistica'])]);
    }

    private function change(Request $request, Producto $producto): void
    {
        $validated = $request->validate([
            'estrategia_logistica' => ['required', Rule::in(Producto::ESTRATEGIAS)],
        ], ['estrategia_logistica.in' => 'La estrategia debe ser PUSH o PULL.']);

        $anterior = $producto->estrategia_logistica;
        $producto->update($validated);
        Auditoria::registrar($request->user(), 'cambiar_estrategia', 'producto', $producto->id,
            "Cambió la estrategia de {$producto->nombre} de {$anterior} a {$producto->estrategia_logistica}.");
    }
}
