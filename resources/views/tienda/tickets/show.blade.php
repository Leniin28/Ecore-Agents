@extends('layouts.app')
@section('title', 'Ticket '.$ticket->folio().' | ECore Agents')

@section('content')
<section class="section" style="max-width:880px;margin:0 auto;padding-top:40px">
    <div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <a class="back-button" href="{{ route('tickets.index') }}">&larr; Volver a mis tickets</a>
        <span class="store-badge badge-{{ $ticket->estado }}" style="font-size:.9rem;padding:6px 14px">
            {{ $ticket->estadoLabel() }}
        </span>
    </div>

    <article class="checkout-card" style="padding:clamp(22px, 4vw, 36px)">
        <div style="border-bottom:1px solid var(--line);padding-bottom:18px;margin-bottom:24px">
            <span class="section-eyebrow">Ticket de soporte</span>
            <h1 style="margin:4px 0 8px;font-size:1.8rem">{{ $ticket->asunto }}</h1>
            <p style="color:var(--muted);margin:0;font-size:.9rem">
                Folio: <strong>{{ $ticket->folio() }}</strong> · Registrado el {{ $ticket->created_at->format('d/m/Y H:i') }}
                @if($ticket->ordenEmpresarial)
                    · Compra asociada:
                    <a href="{{ route('mis-compras.show', $ticket->ordenEmpresarial) }}" style="color:var(--blue);font-weight:700">
                        {{ $ticket->ordenEmpresarial->folio() }}
                    </a>
                @endif
            </p>
        </div>

        <details style="margin-bottom:24px;padding:16px;border:1px solid var(--line);border-radius:12px">
            <summary><strong>Ver correo simulado del ticket</strong></summary>
            <p><strong>Para:</strong> {{ auth()->user()->email }}<br><strong>Asunto:</strong> Ticket {{ $ticket->folio() }} recibido</p>
            <p>Recibimos tu solicitud «{{ $ticket->asunto }}». Estado: {{ $ticket->estadoLabel() }}.</p>
            <p>Es una simulación; no se ha enviado un correo real.</p>
        </details>
        <!-- Conversación -->
        <div class="ticket-chat">
            <!-- Mensaje original del cliente -->
            <div class="ticket-bubble ticket-bubble-client">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <strong style="color:var(--blue)">Tú (Cliente):</strong>
                    <small style="color:var(--muted)">{{ $ticket->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div style="line-height:1.7;white-space:pre-wrap;color:var(--ink)">{{ $ticket->mensaje }}</div>
            </div>

            <!-- Respuesta del equipo -->
            @if($ticket->respuesta)
                <div class="ticket-bubble ticket-bubble-staff">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <strong style="color:var(--plus)">
                            Equipo de Atención ECore
                            @if($ticket->respondidoPor)
                                ({{ $ticket->respondidoPor->name }})
                            @endif
                        </strong>
                        <small style="color:var(--muted)">
                            {{ $ticket->respondido_at ? $ticket->respondido_at->format('d/m/Y H:i') : '' }}
                        </small>
                    </div>
                    <div style="line-height:1.7;white-space:pre-wrap;color:var(--ink)">{{ $ticket->respuesta }}</div>
                </div>
            @else
                <div style="padding:24px;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:14px;color:var(--muted)">
                    <p style="margin:0;font-weight:700">Tu ticket está en espera de respuesta por parte de nuestro equipo de atención.</p>
                    <small style="display:block;margin-top:4px">Te notificaremos y verás la respuesta en esta misma pantalla.</small>
                </div>
            @endif
        </div>

        <div style="margin-top:28px;border-top:1px solid var(--line);padding-top:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <small style="color:var(--muted)">
                Los cambios de estado y respuestas son gestionados exclusivamente por personal autorizado CRM / Admin.
            </small>
            <a class="back-button" href="{{ route('tickets.index') }}">Cerrar vista</a>
        </div>
    </article>
</section>
@endsection
