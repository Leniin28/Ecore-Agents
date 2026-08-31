<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Interaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InteraccionController extends Controller
{
    public function index(Cliente $cliente): View
    {
        $this->loadHistory($cliente);

        return view('clientes.show', compact('cliente'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInteraccion($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $cliente->interacciones()->create([
            'usuario_id' => $request->user()->id,
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'fecha' => $validated['fecha'],
        ]);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', 'Interacción registrada correctamente.');
    }

    public function apiIndex(Cliente $cliente): JsonResponse
    {
        $interacciones = $cliente->interacciones()
            ->with('usuario')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $interacciones->map($this->serializeInteraccion(...)),
        ]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $validated = $this->validateInteraccion($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $interaccion = $cliente->interacciones()->create([
            'usuario_id' => $request->user()->id,
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'fecha' => $validated['fecha'],
        ]);

        return response()->json([
            'data' => $this->serializeInteraccion($interaccion->load('usuario')),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateInteraccion(Request $request): array
    {
        return $request->validate(
            [
                'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
                'tipo' => ['required', 'string', Rule::in(array_keys(Interaccion::tipos()))],
                'descripcion' => ['required', 'string', 'max:2000'],
                'fecha' => ['required', 'date'],
            ],
            [
                'cliente_id.required' => 'El cliente es obligatorio.',
                'cliente_id.integer' => 'El cliente seleccionado debe ser válido.',
                'cliente_id.exists' => 'El cliente seleccionado no existe.',
                'tipo.required' => 'El tipo de interacción es obligatorio.',
                'tipo.string' => 'El tipo de interacción debe ser texto.',
                'tipo.in' => 'El tipo debe ser llamada, correo o reunión.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.string' => 'La descripción debe ser texto.',
                'descripcion.max' => 'La descripción no debe superar :max caracteres.',
                'fecha.required' => 'La fecha y hora son obligatorias.',
                'fecha.date' => 'La fecha y hora deben tener un formato válido.',
            ],
        );
    }

    private function loadHistory(Cliente $cliente): void
    {
        $cliente->load([
            'interacciones' => fn ($query) => $query
                ->with('usuario')
                ->orderByDesc('fecha')
                ->orderByDesc('id'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInteraccion(Interaccion $interaccion): array
    {
        return [
            'id' => $interaccion->id,
            'cliente_id' => $interaccion->cliente_id,
            'tipo' => $interaccion->tipo,
            'descripcion' => $interaccion->descripcion,
            'fecha' => $interaccion->fecha->toISOString(),
            'usuario' => [
                'id' => $interaccion->usuario->id,
                'name' => $interaccion->usuario->name,
            ],
            'created_at' => $interaccion->created_at?->toISOString(),
        ];
    }
}
