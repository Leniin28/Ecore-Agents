<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\TicketAtencion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $cliente = $usuario->cliente;

        $tickets = $cliente
            ? TicketAtencion::query()
                ->where('cliente_id', $cliente->id)
                ->with(['ordenEmpresarial', 'respondidoPor'])
                ->latest('id')
                ->paginate(10)
            : collect();

        return view('tienda.tickets.index', [
            'tickets' => $tickets,
            'cliente' => $cliente,
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();
        $cliente = $usuario->cliente;

        $ordenes = $cliente
            ? OrdenEmpresarial::query()
                ->where('cliente_id', $cliente->id)
                ->latest('id')
                ->get()
            : collect();

        $ordenPreseleccionada = $request->integer('orden_id') ?: null;

        return view('tienda.tickets.create', [
            'ordenes' => $ordenes,
            'ordenPreseleccionada' => $ordenPreseleccionada,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $cliente = $usuario->cliente;
        if (! $cliente) {
            $cliente = Cliente::create([
                'user_id' => $usuario->id,
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'telefono' => 'Sin registrar',
                'empresa' => null,
                'fecha_registro' => now()->toDateString(),
                'estado' => Cliente::ESTADO_ACTIVO,
                'etapa_crm' => Cliente::ETAPA_PROSPECTO,
            ]);
        }

        $validated = $request->validate([
            'asunto' => ['required', 'string', 'max:255'],
            'mensaje' => ['required', 'string', 'max:5000'],
            'orden_empresarial_id' => ['nullable', 'integer', 'exists:ordenes_empresariales,id'],
        ]);

        $ordenId = $validated['orden_empresarial_id'] ?? null;
        if ($ordenId) {
            $pertenece = OrdenEmpresarial::query()
                ->where('id', $ordenId)
                ->where('cliente_id', $cliente->id)
                ->exists();

            if (! $pertenece) {
                abort(403, 'La orden seleccionada no corresponde a tu cuenta.');
            }
        }

        $ticket = TicketAtencion::create([
            'cliente_id' => $cliente->id,
            'orden_empresarial_id' => $ordenId,
            'asunto' => $validated['asunto'],
            'mensaje' => $validated['mensaje'],
            'estado' => TicketAtencion::ESTADO_ABIERTO,
        ]);

        Auditoria::registrar(
            $usuario,
            'crear_ticket_atencion',
            'ticket_atencion',
            $ticket->id,
            "El cliente {$cliente->nombre} abrió el ticket {$ticket->folio()}: '{$ticket->asunto}'."
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->folio()} creado correctamente. Nuestro equipo revisará tu mensaje.");
    }

    public function show(Request $request, TicketAtencion $ticket): View
    {
        $usuario = $request->user();

        // El cliente solo puede consultar tickets propios; admin puede ver cualquiera
        if (! $usuario->isAdmin() && (! $usuario->cliente || $usuario->cliente->id !== $ticket->cliente_id)) {
            abort(403, 'No tienes autorización para consultar este ticket.');
        }

        $ticket->load(['cliente', 'ordenEmpresarial', 'respondidoPor']);

        return view('tienda.tickets.show', [
            'ticket' => $ticket,
        ]);
    }
}
