<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\ConfiguracionScm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MadurezController extends Controller
{
    public function index(): View
    {
        return view('scm.madurez', ['configuracion' => ConfiguracionScm::actual()]);
    }

    public function estado(): JsonResponse
    {
        return response()->json(['data' => $this->serialize(ConfiguracionScm::actual())]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->change($request);

        return redirect()->route('scm.madurez.index')->with('success', 'Nivel SCM actualizado correctamente.');
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->serialize($this->change($request))]);
    }

    private function change(Request $request): ConfiguracionScm
    {
        $validated = $request->validate([
            'nivel_scm' => ['required', Rule::in(ConfiguracionScm::NIVELES)],
        ], ['nivel_scm.in' => 'El nivel SCM seleccionado no es válido.']);

        $configuracion = ConfiguracionScm::actual();
        $anterior = $configuracion->nivel_scm;
        $configuracion->update($validated);
        Auditoria::registrar($request->user(), 'cambiar_nivel_scm', 'configuracion_scm', $configuracion->id,
            "Cambió el nivel SCM de {$anterior} a {$configuracion->nivel_scm}.");

        return $configuracion->fresh();
    }

    private function serialize(ConfiguracionScm $configuracion): array
    {
        return [
            'nivel_scm' => $configuracion->nivel_scm,
            'nivel_label' => $configuracion->nivelLabel(),
            'updated_at' => $configuracion->updated_at?->toISOString(),
        ];
    }
}
