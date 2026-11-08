<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoAdminController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $filtroTienda = $request->string('tienda')->toString(); // 'visibles', 'ocultos', 'todos'
        $categoriaId = $request->string('categoria_id')->trim()->toString();

        $productos = Producto::query()
            ->with(['categoriaRelacion', 'proveedor'])
            ->when($buscar, fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->when($filtroTienda === 'visibles', fn ($q) => $q->where('visible_tienda', true))
            ->when($filtroTienda === 'ocultos', fn ($q) => $q->where('visible_tienda', false))
            ->when($categoriaId !== '', function ($q) use ($categoriaId) {
                if (ctype_digit($categoriaId)) {
                    $q->where('categoria_id', (int) $categoriaId);
                } else {
                    $q->where('categoria', $categoriaId);
                }
            })
            ->orderByDesc('visible_tienda')
            ->orderByDesc('destacado')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        $categorias = Categoria::query()->orderBy('nombre')->get();

        return view('admin.tienda.productos.index', [
            'productos' => $productos,
            'categorias' => $categorias,
        ]);
    }

    public function edit(Producto $producto): View
    {
        return view('admin.tienda.productos.edit', [
            'producto' => $producto,
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'visible_tienda' => ['required', 'boolean'],
            'destacado' => ['required', 'boolean'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'precio_venta_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'imagen_url' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ]);

        $categoria = null;
        if (! empty($validated['categoria_id'])) {
            $categoria = Categoria::find($validated['categoria_id']);
        }

        $producto->update([
            'visible_tienda' => (bool) $validated['visible_tienda'],
            'destacado' => (bool) $validated['destacado'],
            'categoria_id' => $validated['categoria_id'] ?? null,
            'categoria' => $categoria ? $categoria->nombre : $producto->categoria,
            'precio_venta_unitario' => $validated['precio_venta_unitario'],
            'imagen_url' => $validated['imagen_url'] ?? null,
            'descripcion' => $validated['descripcion'] ?? $producto->descripcion,
        ]);

        Auditoria::registrar(
            $request->user(),
            'actualizar_oferta_comercial',
            'producto',
            $producto->id,
            "Actualizó configuración comercial de {$producto->nombre} (visible: ".($producto->visible_tienda ? 'Sí' : 'No').', precio: $'.$producto->precio_venta_unitario.').'
        );

        return redirect()->route('admin.tienda.productos.index')
            ->with('success', "Configuración comercial de {$producto->nombre} guardada correctamente.");
    }

    public function toggleVisibilidad(Request $request, Producto $producto): RedirectResponse
    {
        $nuevoEstado = ! $producto->visible_tienda;
        $producto->update(['visible_tienda' => $nuevoEstado]);

        Auditoria::registrar(
            $request->user(),
            'cambiar_visibilidad_tienda',
            'producto',
            $producto->id,
            ($nuevoEstado ? 'Publicó' : 'Ocultó')." en tienda comercial el producto {$producto->nombre}."
        );

        return back()->with('success', "Producto {$producto->nombre} ahora está ".($nuevoEstado ? 'visible en tienda' : 'oculto en tienda').'.');
    }
}
