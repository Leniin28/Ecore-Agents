<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromocionAdminController extends Controller
{
    public function index(): View
    {
        $promociones = Promocion::query()
            ->withCount('productos')
            ->latest('id')
            ->paginate(15);

        return view('admin.tienda.promociones.index', compact('promociones'));
    }

    public function create(): View
    {
        return view('admin.tienda.promociones.create', [
            'productos' => Producto::query()->where('visible_tienda', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:promociones,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo_descuento' => ['required', 'string', Rule::in(Promocion::TIPOS)],
            'valor_humano' => ['required', 'numeric', 'min:0.01'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'limite_usos' => ['nullable', 'integer', 'min:1'],
            'activo' => ['required', 'boolean'],
            'productos' => ['nullable', 'array'],
            'productos.*' => ['integer', 'exists:productos,id'],
        ]);

        // Conversión a unidades oficiales:
        // porcentaje -> puntos base (10.00% = 1000 puntos base)
        // monto_fijo -> centavos ($50.00 MXN = 5000 centavos)
        $valorHumano = (float) $validated['valor_humano'];
        $valorCalculado = (int) round($valorHumano * 100);

        if ($validated['tipo_descuento'] === Promocion::TIPO_PORCENTAJE && $valorCalculado > 10000) {
            return back()->withErrors(['valor_humano' => 'El porcentaje no puede ser mayor al 100%.'])->withInput();
        }

        $promocion = Promocion::create([
            'codigo' => strtoupper(trim($validated['codigo'])),
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo_descuento' => $validated['tipo_descuento'],
            'valor' => $valorCalculado,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'limite_usos' => $validated['limite_usos'] ?? null,
            'activo' => (bool) $validated['activo'],
        ]);

        if (! empty($validated['productos'])) {
            $promocion->productos()->sync($validated['productos']);
        }

        Auditoria::registrar($request->user(), 'crear_promocion', 'promocion', $promocion->id, "Creó promoción {$promocion->codigo} ({$promocion->etiquetaValor()}).");

        return redirect()->route('admin.tienda.promociones.index')->with('success', 'Promoción creada correctamente.');
    }

    public function edit(Promocion $promocion): View
    {
        $promocion->load('productos');

        return view('admin.tienda.promociones.edit', [
            'promocion' => $promocion,
            'productos' => Producto::query()->where('visible_tienda', true)->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Promocion $promocion): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('promociones', 'codigo')->ignore($promocion->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo_descuento' => ['required', 'string', Rule::in(Promocion::TIPOS)],
            'valor_humano' => ['required', 'numeric', 'min:0.01'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'limite_usos' => ['nullable', 'integer', 'min:1'],
            'activo' => ['required', 'boolean'],
            'productos' => ['nullable', 'array'],
            'productos.*' => ['integer', 'exists:productos,id'],
        ]);

        $valorHumano = (float) $validated['valor_humano'];
        $valorCalculado = (int) round($valorHumano * 100);

        if ($validated['tipo_descuento'] === Promocion::TIPO_PORCENTAJE && $valorCalculado > 10000) {
            return back()->withErrors(['valor_humano' => 'El porcentaje no puede ser mayor al 100%.'])->withInput();
        }

        $promocion->update([
            'codigo' => strtoupper(trim($validated['codigo'])),
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo_descuento' => $validated['tipo_descuento'],
            'valor' => $valorCalculado,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'limite_usos' => $validated['limite_usos'] ?? null,
            'activo' => (bool) $validated['activo'],
        ]);

        $promocion->productos()->sync($validated['productos'] ?? []);

        Auditoria::registrar($request->user(), 'editar_promocion', 'promocion', $promocion->id, "Actualizó promoción {$promocion->codigo}.");

        return redirect()->route('admin.tienda.promociones.index')->with('success', 'Promoción actualizada correctamente.');
    }

    public function destroy(Request $request, Promocion $promocion): RedirectResponse
    {
        if ($promocion->pagos()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar una promoción que ya fue utilizada en pagos registrados. Puedes desactivarla en su lugar.']);
        }

        Auditoria::registrar($request->user(), 'eliminar_promocion', 'promocion', $promocion->id, "Eliminó promoción {$promocion->codigo}.");
        $promocion->productos()->detach();
        $promocion->delete();

        return redirect()->route('admin.tienda.promociones.index')->with('success', 'Promoción eliminada.');
    }
}
