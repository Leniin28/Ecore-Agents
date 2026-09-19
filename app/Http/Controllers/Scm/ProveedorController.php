<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        return view('scm.proveedores.index', [
            'proveedores' => $this->query($request)->withCount('productos')->paginate(20)->withQueryString(),
            'buscar' => $request->string('buscar')->trim()->toString(),
        ]);
    }

    public function create(): View
    {
        return view('scm.proveedores.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $proveedor = Proveedor::create($this->validated($request));
        Auditoria::registrar($request->user(), 'crear', 'proveedor', $proveedor->id, "Creó al proveedor {$proveedor->nombre}.");

        return redirect()->route('scm.proveedores.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('scm.proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($this->validated($request));
        Auditoria::registrar($request->user(), 'editar', 'proveedor', $proveedor->id, "Editó al proveedor {$proveedor->nombre}.");

        return redirect()->route('scm.proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $this->ensureUnused($proveedor);
        Auditoria::registrar($request->user(), 'eliminar', 'proveedor', $proveedor->id, "Eliminó al proveedor {$proveedor->nombre}.");
        $proveedor->delete();

        return redirect()->route('scm.proveedores.index')->with('success', 'Proveedor eliminado correctamente.');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->get()->map($this->serialize(...))]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $proveedor = Proveedor::create($this->validated($request));
        Auditoria::registrar($request->user(), 'crear', 'proveedor', $proveedor->id, "Creó al proveedor {$proveedor->nombre} mediante API.");

        return response()->json(['data' => $this->serialize($proveedor)], 201);
    }

    public function apiUpdate(Request $request, Proveedor $proveedor): JsonResponse
    {
        $proveedor->update($this->validated($request));
        Auditoria::registrar($request->user(), 'editar', 'proveedor', $proveedor->id, "Editó al proveedor {$proveedor->nombre} mediante API.");

        return response()->json(['data' => $this->serialize($proveedor->fresh())]);
    }

    public function apiDestroy(Request $request, Proveedor $proveedor): Response
    {
        $this->ensureUnused($proveedor);
        Auditoria::registrar($request->user(), 'eliminar', 'proveedor', $proveedor->id, "Eliminó al proveedor {$proveedor->nombre} mediante API.");
        $proveedor->delete();

        return response()->noContent();
    }

    private function query(Request $request): Builder
    {
        $buscar = $request->string('buscar')->trim()->toString();

        return Proveedor::query()
            ->when($buscar, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('contacto', 'like', "%{$buscar}%")
                ->orWhere('correo', 'like', "%{$buscar}%")))
            ->orderBy('nombre');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.email' => 'El correo debe tener un formato válido.',
        ]);
    }

    private function ensureUnused(Proveedor $proveedor): void
    {
        if ($proveedor->productos()->exists()) {
            throw ValidationException::withMessages(['proveedor' => 'No se puede eliminar un proveedor que tiene productos asociados.']);
        }
    }

    private function serialize(Proveedor $proveedor): array
    {
        return $proveedor->only(['id', 'nombre', 'contacto', 'correo', 'telefono', 'created_at', 'updated_at']);
    }
}
