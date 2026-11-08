@extends('layouts.app')
@section('title', 'Atender Ticket '.$ticket->folio().' | Admin ECore')

@section('content')
<div class="admin-layout">
    @if(request()->routeIs('crm.*'))
        @include('admin._sidebar')
    @else
        @include('admin.tienda._sidebar')
    @endif

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ request()->routeIs('crm.*') ? route('crm.tickets.index') : route('admin.tienda.tickets.index') }}">
                &larr; Volver a la lista de tickets
            </a>
        </div>

        <div style="display:grid;grid-template-columns:minmax(0, 1.2fr) minmax(320px, 1fr);gap:28px;align-items:start">
            <!-- Consulta del cliente -->
            <article class="checkout-card" style="padding:28px;margin:0">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:20px">
                    <div>
                        <span class="section-eyebrow">Ticket de atención</span>
                        <h1 style="margin:4px 0 6px;font-size:1.8rem">{{ $ticket->folio() }}: {{ $ticket->asunto }}</h1>
                        <p style="color:var(--muted);margin:0;font-size:.9rem">Registrado el {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="store-badge badge-{{ $ticket->estado }}" style="font-size:.9rem;padding:6px 14px">
                            {{ $ticket->estadoLabel() }}
                        </span>
                    </div>
                </div>

                <div style="background:#f8faff;border:1px solid #dbe6f6;border-radius:12px;padding:16px;margin-bottom:24px;font-size:.9rem">
                    <h4 style="margin:0 0 8px;font-size:.95rem">Información del cliente:</h4>
                    <div><strong>Nombre:</strong> {{ $ticket->cliente->nombre }}</div>
                    <div><strong>Correo:</strong> {{ $ticket->cliente->correo }}</div>
                    <div><strong>Teléfono:</strong> {{ $ticket->cliente->telefono ?? 'Sin registrar' }}</div>
                    <div><strong>Empresa:</strong> {{ $ticket->cliente->empresa ?? 'Particular' }}</div>
                    @if($ticket->ordenEmpresarial)
                        <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #cbd5e1">
                            <strong>Compra asociada:</strong>
                            <a href="{{ route('admin.tienda.ordenes.show', $ticket->ordenEmpresarial->pago ?? $ticket->ordenEmpresarial->id) }}" style="color:var(--blue);font-weight:700">
                                {{ $ticket->ordenEmpresarial->folio() }} (${{ number_format((float) $ticket->ordenEmpresarial->total, 2) }} MXN)
                            </a>
                        </div>
                    @endif
                </div>

                <h3 style="font-size:1.1rem;margin:0 0 10px">Mensaje del cliente:</h3>
                <div style="padding:20px;background:#fff;border:1px solid var(--line);border-left:5px solid var(--blue);border-radius:12px;line-height:1.7;white-space:pre-wrap;font-size:.95rem">
                    {{ $ticket->mensaje }}
                </div>

                @if($ticket->respuesta)
                    <h3 style="font-size:1.1rem;margin:24px 0 10px;color:var(--plus)">Última respuesta guardada:</h3>
                    <div style="padding:20px;background:#fbf9ff;border:1px solid #e9d8fd;border-left:5px solid var(--plus);border-radius:12px;line-height:1.7;white-space:pre-wrap;font-size:.95rem">
                        {{ $ticket->respuesta }}
                        <small style="display:block;margin-top:10px;color:var(--muted)">
                            Respondido por {{ $ticket->respondidoPor->name ?? 'Personal' }} el {{ $ticket->respondido_at ? $ticket->respondido_at->format('d/m/Y H:i') : '' }}
                        </small>
                    </div>
                @endif
            </article>

            <!-- Formulario de respuesta y actualización de estado -->
            <article class="form-card" style="padding:28px;margin:0">
                <span class="section-eyebrow">Personal CRM / Soporte</span>
                <h2 style="margin:4px 0 16px;font-size:1.3rem">Responder al cliente</h2>

                @php
                    $actionRoute = request()->routeIs('crm.*')
                        ? route('crm.tickets.responder', $ticket)
                        : route('admin.tienda.tickets.responder', $ticket);
                @endphp

                <form method="post" action="{{ $actionRoute }}" style="display:grid;gap:16px">
                    @csrf

                    <div>
                        <label for="estado" style="font-weight:800;display:block;margin-bottom:6px">Cambiar estado del ticket *</label>
                        <select id="estado" name="estado" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                            @foreach(\App\Models\TicketAtencion::ESTADOS as $k => $label)
                                <option value="{{ $k }}" @selected(old('estado', $ticket->estado) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="respuesta" style="font-weight:800;display:block;margin-bottom:6px">Respuesta al cliente *</label>
                        <textarea id="respuesta" name="respuesta" rows="8" placeholder="Escribe la respuesta oficial que podrá leer el cliente en su portal..." required style="width:100%;padding:12px;border:1px solid #bcc9dc;border-radius:9px;line-height:1.6">{{ old('respuesta', $ticket->respuesta) }}</textarea>
                    </div>

                    <button class="button" type="submit" style="min-height:48px;font-size:1.05rem">
                        Guardar respuesta y actualizar estado
                    </button>
                </form>

                <div style="margin-top:16px;font-size:.82rem;color:var(--muted);line-height:1.5">
                    <strong>Auditoría:</strong> Esta acción queda registrada en la bitácora con tu usuario autenticado ({{ auth()->user()->name }}).
                </div>
            </article>
        </div>
    </section>
</div>
@endsection
