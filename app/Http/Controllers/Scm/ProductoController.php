<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        return view('scm.productos.index', [
            'productos' => $this->query($request)->paginate(20)->withQueryString(),
            'categorias' => Producto::query()->distinct()->orderBy('categoria')->pluck('categoria'),
        ]);
    }

    public function create(): View
    {
        return view('scm.productos.create', ['proveedores' => Proveedor::query()->orderBy('nombre')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $producto = Producto::create($this->validated($request));
        Auditoria::registrar($request->user(), 'crear', 'producto', $producto->id, "Creó el recurso IA {$producto->nombre}.");

        return redirect()->route('scm.productos.index')->with('success', 'Producto/recurso IA creado correctamente.');
    }

    public function edit(Producto $producto): View
    {
        return view('scm.productos.edit', [
            'producto' => $producto,
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validated($request));
        Auditoria::registrar($request->user(), 'editar', 'producto', $producto->id, "Editó el recurso IA {$producto->nombre}.");

        return redirect()->route('scm.productos.index')->with('success', 'Producto/recurso IA actualizado correctamente.');
    }

    public function destroy(Request $request, Producto $producto): RedirectResponse
    {
        $this->ensureWithoutMovements($producto);
        Auditoria::registrar($request->user(), 'eliminar', 'producto', $producto->id, "Eliminó el recurso IA {$producto->nombre}.");
        $producto->delete();

        return redirect()->route('scm.productos.index')->with('success', 'Producto/recurso IA eliminado correctamente.');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->get()->map($this->serialize(...))]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $producto = Producto::create($this->validated($request));
        Auditoria::registrar($request->user(), 'crear', 'producto', $producto->id, "Creó el recurso IA {$producto->nombre} mediante API.");

        return response()->json(['data' => $this->serialize($producto->load('proveedor'))], 201);
    }

    public function apiUpdate(Request $request, Producto $producto): JsonResponse
    {
        $producto->update($this->validated($request));
        Auditoria::registrar($request->user(), 'editar', 'producto', $producto->id, "Editó el recurso IA {$producto->nombre} mediante API.");

        return response()->json(['data' => $this->serialize($producto->fresh()->load('proveedor'))]);
    }

    public function apiDestroy(Request $request, Producto $producto): Response
    {
        $this->ensureWithoutMovements($producto);
        Auditoria::registrar($request->user(), 'eliminar', 'producto', $producto->id, "Eliminó el recurso IA {$producto->nombre} mediante API.");
        $producto->delete();

        return response()->noContent();
    }

    private function query(Request $request): Builder
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $categoria = $request->string('categoria')->trim()->toString();
        $estrategia = $request->string('estrategia')->toString();

        return Producto::query()->with('proveedor')
            ->when($buscar, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('descripcion', 'like', "%{$buscar}%")))
            ->when($categoria, fn (Builder $query) => $query->where('categoria', $categoria))
            ->when(in_array($estrategia, Producto::ESTRATEGIAS, true), fn (Builder $query) => $query->where('estrategia_logistica', $estrategia))
            ->orderBy('nombre');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'categoria' => ['required', 'string', 'max:100'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'estrategia_logistica' => ['required', Rule::in(Producto::ESTRATEGIAS)],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'stock_actual.min' => 'El stock actual no puede ser negativo.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'costo_unitario.min' => 'El costo no puede ser negativo.',
            'estrategia_logistica.in' => 'La estrategia debe ser PUSH o PULL.',
        ]);
    }

    private function serialize(Producto $producto): array
    {
        return [
            ...$producto->only(['id', 'nombre', 'descripcion', 'categoria', 'stock_actual', 'stock_minimo', 'proveedor_id', 'costo_unitario', 'estrategia_logistica', 'created_at', 'updated_at']),
            'proveedor' => $producto->proveedor?->only(['id', 'nombre']),
        ];
    }

    private function ensureWithoutMovements(Producto $producto): void
    {
        if ($producto->movimientos()->exists() || $producto->pedidos()->exists()) {
            throw ValidationException::withMessages(['producto' => 'No se puede eliminar un producto con movimientos o pedidos.']);
        }
    }
}
