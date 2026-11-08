<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdenAdminController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $metodo = $request->string('metodo')->toString();
        $estado = $request->string('estado')->toString();

        $pagos = Pago::query()
            ->with(['cliente', 'ordenEmpresarial'])
            ->when($metodo !== '', fn ($q) => $q->where('metodo', $metodo))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('referencia', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('correo', 'like', "%{$buscar}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tienda.ordenes.index', [
            'pagos' => $pagos,
            'metodos' => Pago::METODOS,
            'estados' => Pago::ESTADOS,
        ]);
    }

    public function show(Pago $pago): View
    {
        $pago->load([
            'cliente',
            'ordenEmpresarial.detalles.producto',
            'ordenEmpresarial.procesosInternos.usuario',
            'promocion',
            'monederoMovimiento',
        ]);

        return view('admin.tienda.ordenes.show', compact('pago'));
    }
}
