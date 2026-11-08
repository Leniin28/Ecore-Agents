<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaAdminController extends Controller
{
    public function index(): View
    {
        $categorias = Categoria::query()
            ->withCount('productos')
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.tienda.categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        return view('admin.tienda.categorias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:categorias,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activo' => ['required', 'boolean'],
        ]);

        $categoria = Categoria::create([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']) ?: 'cat-'.Str::random(6),
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => (bool) $validated['activo'],
        ]);

        Auditoria::registrar($request->user(), 'crear_categoria', 'categoria', $categoria->id, "Creó categoría comercial {$categoria->nombre}.");

        return redirect()->route('admin.tienda.categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('admin.tienda.categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($categoria->id)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activo' => ['required', 'boolean'],
        ]);

        $categoria->update([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']) ?: $categoria->slug,
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => (bool) $validated['activo'],
        ]);

        Auditoria::registrar($request->user(), 'editar_categoria', 'categoria', $categoria->id, "Editó categoría comercial {$categoria->nombre}.");

        return redirect()->route('admin.tienda.categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Request $request, Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar una categoría que contiene productos vinculados.']);
        }

        Auditoria::registrar($request->user(), 'eliminar_categoria', 'categoria', $categoria->id, "Eliminó categoría comercial {$categoria->nombre}.");
        $categoria->delete();

        return redirect()->route('admin.tienda.categorias.index')->with('success', 'Categoría eliminada.');
    }
}
