<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\OrdenEmpresarial;
use App\Models\Pago;
use App\Services\TiendaCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdenAdminController extends Controller
{
    public function __construct(
        protected TiendaCheckoutService $checkoutService
    ) {}
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

    public function confirmarTransferencia(Request $request, Pago $pago): RedirectResponse
    {
        $this->checkoutService->confirmarTransferencia($pago, $request->user());

        return redirect()->route('admin.tienda.ordenes.show', $pago)
            ->with('success', "Transferencia demo de la orden {$pago->ordenEmpresarial?->folio()} confirmada exitosamente. Se descontó el inventario SCM y la orden pasó a procesada.");
    }

    public function rechazarTransferencia(Request $request, Pago $pago): RedirectResponse
    {
        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $this->checkoutService->rechazarTransferencia($pago, $request->user(), $validated['motivo'] ?? null);

        return redirect()->route('admin.tienda.ordenes.show', $pago)
            ->with('info', "Transferencia demo de la orden {$pago->ordenEmpresarial?->folio()} rechazada. La orden ha sido cancelada.");
    }
}
