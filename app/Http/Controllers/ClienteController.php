<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        return view('clientes.index', [
            'clientes' => $this->filteredQuery($request)->get(),
            'buscar' => $request->string('buscar')->trim()->toString(),
            'estado' => $request->string('estado')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = Cliente::create([
            ...$this->validateCliente($request),
            'fecha_registro' => now()->toDateString(),
        ]);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validateCliente($request, $cliente));

        return redirect()->route('clientes.show', $cliente)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->authorizeDeletion($request);
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->filteredQuery($request)->get()->map($this->serializeCliente(...)),
        ]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $cliente = Cliente::create([
            ...$this->validateCliente($request),
            'fecha_registro' => now()->toDateString(),
        ]);

        return response()->json(['data' => $this->serializeCliente($cliente)], 201);
    }

    public function apiShow(Cliente $cliente): JsonResponse
    {
        return response()->json(['data' => $this->serializeCliente($cliente)]);
    }

    public function apiUpdate(Request $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($this->validateCliente($request, $cliente));

        return response()->json(['data' => $this->serializeCliente($cliente->fresh())]);
    }

    public function apiDestroy(Request $request, Cliente $cliente): Response
    {
        $this->authorizeDeletion($request);
        $cliente->delete();

        return response()->noContent();
    }

    private function filteredQuery(Request $request): Builder
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $estado = $request->string('estado')->toString();

        return Cliente::query()
            ->when($buscar, function (Builder $query, string $buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $query->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('correo', 'like', "%{$buscar}%")
                        ->orWhere('empresa', 'like', "%{$buscar}%");
                });
            })
            ->when(
                in_array($estado, [Cliente::ESTADO_ACTIVO, Cliente::ESTADO_INACTIVO], true),
                fn (Builder $query) => $query->where('estado', $estado),
            )
            ->orderBy('nombre');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCliente(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate(
            [
                'nombre' => ['required', 'string', 'max:255'],
                'correo' => ['required', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($cliente)],
                'telefono' => ['required', 'string', 'max:30'],
                'empresa' => ['nullable', 'string', 'max:255'],
                'estado' => ['required', Rule::in([Cliente::ESTADO_ACTIVO, Cliente::ESTADO_INACTIVO])],
            ],
            [
                'nombre.required' => 'El nombre es obligatorio.',
                'correo.required' => 'El correo es obligatorio.',
                'correo.email' => 'El correo debe tener un formato válido.',
                'correo.unique' => 'Ya existe un cliente con este correo.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'estado.required' => 'El estado es obligatorio.',
                'estado.in' => 'El estado debe ser activo o inactivo.',
                'nombre.max' => 'El nombre no debe superar :max caracteres.',
                'correo.max' => 'El correo no debe superar :max caracteres.',
                'telefono.max' => 'El teléfono no debe superar :max caracteres.',
                'empresa.max' => 'La empresa no debe superar :max caracteres.',
            ],
        );
    }

    private function authorizeDeletion(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCliente(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'correo' => $cliente->correo,
            'telefono' => $cliente->telefono,
            'empresa' => $cliente->empresa,
            'fecha_registro' => $cliente->fecha_registro->format('Y-m-d'),
            'estado' => $cliente->estado,
            'created_at' => $cliente->created_at?->toISOString(),
            'updated_at' => $cliente->updated_at?->toISOString(),
        ];
    }
}
