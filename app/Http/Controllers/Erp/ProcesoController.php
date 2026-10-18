<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\ProcesoInterno;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcesoController extends Controller
{
    public function index(Request $request): View
    {
        $orden = $request->string('orden')->trim()->toString();
        $id = preg_replace('/^ORD-0*/i', '', $orden);
        $tipo = $request->string('tipo')->toString();

        return view('erp.procesos', ['procesos' => ProcesoInterno::query()->with(['ordenEmpresarial.cliente', 'usuario:id,name'])
            ->when($orden !== '', fn ($query) => $query->where('orden_empresarial_id', ctype_digit($id) ? (int) $id : 0))
            ->when(isset(ProcesoInterno::TIPOS[$tipo]), fn ($query) => $query->where('tipo', $tipo))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')->toString()))
            ->latest('fecha')->latest('id')->paginate(20)->withQueryString()]);
    }
}
