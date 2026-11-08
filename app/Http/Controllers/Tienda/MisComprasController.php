<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\OrdenEmpresarial;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MisComprasController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $cliente = $usuario->cliente;

        $compras = $cliente
            ? OrdenEmpresarial::query()
                ->where('cliente_id', $cliente->id)
                ->with(['detalles.producto', 'pago'])
                ->latest('id')
                ->paginate(10)
            : collect();

        return view('tienda.compras.index', [
            'compras' => $compras,
            'cliente' => $cliente,
        ]);
    }

    public function show(Request $request, OrdenEmpresarial $orden): View
    {
        $usuario = $request->user();

        // Control de aislamiento: el cliente solo accede a compras de su propia titularidad
        if (! $usuario->isAdmin() && (! $usuario->cliente || $usuario->cliente->id !== $orden->cliente_id)) {
            abort(403, 'No tienes autorización para consultar esta compra.');
        }

        $orden->load([
            'detalles.producto',
            'pago.promocion',
            'cliente',
            'procesosInternos',
            'tickets',
        ]);

        return view('tienda.compras.show', [
            'orden' => $orden,
            'pago' => $orden->pago,
        ]);
    }
}
