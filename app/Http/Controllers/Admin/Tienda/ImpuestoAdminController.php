<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Impuesto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImpuestoAdminController extends Controller
{
    public function index(): View
    {
        $impuestos = Impuesto::query()->orderBy('nombre')->paginate(15);

        return view('admin.tienda.impuestos.index', compact('impuestos'));
    }

    public function create(): View
    {
        return view('admin.tienda.impuestos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'activo' => ['required', 'boolean'],
        ]);

        $puntosBase = (int) round(((float) $validated['porcentaje']) * 100);

        $impuesto = Impuesto::create([
            'nombre' => $validated['nombre'],
            'puntos_base' => $puntosBase,
            'activo' => (bool) $validated['activo'],
        ]);

        Auditoria::registrar($request->user(), 'crear_impuesto', 'impuesto', $impuesto->id, "Creó impuesto {$impuesto->nombre} ({$impuesto->porcentajeFormateado()}).");

        return redirect()->route('admin.tienda.impuestos.index')->with('success', 'Impuesto configurado correctamente.');
    }

    public function edit(Impuesto $impuesto): View
    {
        return view('admin.tienda.impuestos.edit', compact('impuesto'));
    }

    public function update(Request $request, Impuesto $impuesto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'activo' => ['required', 'boolean'],
        ]);

        $puntosBase = (int) round(((float) $validated['porcentaje']) * 100);

        $impuesto->update([
            'nombre' => $validated['nombre'],
            'puntos_base' => $puntosBase,
            'activo' => (bool) $validated['activo'],
        ]);

        Auditoria::registrar($request->user(), 'editar_impuesto', 'impuesto', $impuesto->id, "Actualizó impuesto {$impuesto->nombre} a {$impuesto->porcentajeFormateado()}.");

        return redirect()->route('admin.tienda.impuestos.index')->with('success', 'Impuesto actualizado correctamente.');
    }

    public function toggle(Request $request, Impuesto $impuesto): RedirectResponse
    {
        $nuevo = ! $impuesto->activo;
        $impuesto->update(['activo' => $nuevo]);

        Auditoria::registrar($request->user(), 'toggle_impuesto', 'impuesto', $impuesto->id, ($nuevo ? 'Activó' : 'Desactivó')." el impuesto {$impuesto->nombre}.");

        return back()->with('success', "Impuesto {$impuesto->nombre} ahora está ".($nuevo ? 'activo' : 'inactivo').'.');
    }
}
