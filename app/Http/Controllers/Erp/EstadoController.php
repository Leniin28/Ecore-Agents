<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\EstadoErp;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EstadoController extends Controller
{
    public function index(): View
    {
        return view('erp.nivel', ['estado' => EstadoErp::actual()]);
    }

    public function estado(): JsonResponse
    {
        return response()->json(['data' => $this->serialize(EstadoErp::actual())]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->change($request);

        return redirect()->route('erp.nivel')->with('success', 'Nivel ERP actualizado correctamente.');
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->serialize($this->change($request))]);
    }

    private function change(Request $request): EstadoErp
    {
        $data = $request->validate(['nivel' => ['required', Rule::in(array_keys(EstadoErp::NIVELES))]]);

        return DB::transaction(function () use ($request, $data) {
            $estado = EstadoErp::actual();
            $anterior = $estado->nivel;
            $estado->update($data);
            Auditoria::registrar($request->user(), 'cambiar_nivel_erp', 'estado_erp', $estado->id,
                "Cambió el nivel ERP de {$anterior} a {$estado->nivel}.");

            return $estado;
        });
    }

    private function serialize(EstadoErp $estado): array
    {
        return ['nivel' => $estado->nivel, 'nivel_label' => EstadoErp::NIVELES[$estado->nivel],
            'descripcion' => EstadoErp::DESCRIPCIONES[$estado->nivel], 'checklist' => $estado->checklist()];
    }
}
