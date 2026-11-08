<?php

namespace App\Http\Controllers\Admin\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\TicketAtencion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketAdminController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->string('estado')->toString();
        $buscar = $request->string('buscar')->trim()->toString();

        $tickets = TicketAtencion::query()
            ->with(['cliente', 'ordenEmpresarial', 'respondidoPor'])
            ->when(in_array($estado, array_keys(TicketAtencion::ESTADOS), true), fn ($q) => $q->where('estado', $estado))
            ->when($buscar, function ($q) use ($buscar) {
                $q->where('asunto', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('correo', 'like', "%{$buscar}%"));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tienda.tickets.index', [
            'tickets' => $tickets,
            'estadoActual' => $estado,
        ]);
    }

    public function show(TicketAtencion $ticket): View
    {
        $ticket->load(['cliente', 'ordenEmpresarial.detalles.producto', 'respondidoPor']);

        return view('admin.tienda.tickets.show', compact('ticket'));
    }

    public function responder(Request $request, TicketAtencion $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'respuesta' => ['required', 'string', 'max:5000'],
            'estado' => ['required', Rule::in(array_keys(TicketAtencion::ESTADOS))],
        ]);

        $ticket->update([
            'respuesta' => $validated['respuesta'],
            'estado' => $validated['estado'],
            'respondido_por' => $request->user()->id,
            'respondido_at' => now(),
        ]);

        Auditoria::registrar(
            $request->user(),
            'responder_ticket_atencion',
            'ticket_atencion',
            $ticket->id,
            "Respondió al ticket {$ticket->folio()} del cliente {$ticket->cliente->nombre} (nuevo estado: {$ticket->estadoLabel()})."
        );

        return redirect()->route('admin.tienda.tickets.show', $ticket)
            ->with('success', "Respuesta registrada y ticket actualizado a '{$ticket->estadoLabel()}'.");
    }
}
