@extends('layouts.app')
@section('title', 'Abrir Ticket de Soporte | ECore Agents')

@section('content')
<section class="section" style="max-width:760px;margin:0 auto;padding-top:40px">
    <div style="margin-bottom:20px">
        <a class="back-button" href="{{ route('tickets.index') }}">&larr; Volver a mis tickets</a>
    </div>

    <article class="form-card" style="padding:clamp(24px, 5vw, 36px)">
        <span class="section-eyebrow">Atención al cliente</span>
        <h1 style="margin:4px 0 16px;font-size:2rem">Abrir nuevo ticket</h1>
        <p style="color:var(--muted);margin-top:0;margin-bottom:24px">
            Describe tu consulta técnica o comercial. El equipo de CRM y soporte responderá a través de este portal.
        </p>

        <form method="post" action="{{ route('tickets.store') }}" style="display:grid;gap:18px">
            @csrf

            <div>
                <label for="asunto" style="font-weight:800;display:block;margin-bottom:6px">Asunto de tu consulta *</label>
                <input id="asunto" name="asunto" type="text" value="{{ old('asunto') }}" placeholder="Ej. Duda con la integración de Agente Plus..." required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
            </div>

            @if(isset($ordenes) && $ordenes->isNotEmpty())
                <div>
                    <label for="orden_empresarial_id" style="font-weight:800;display:block;margin-bottom:6px">Relacionar con una compra (opcional)</label>
                    <select id="orden_empresarial_id" name="orden_empresarial_id" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        <option value="">Consulta general / Sin orden específica</option>
                        @foreach($ordenes as $ord)
                            <option value="{{ $ord->id }}" @selected(old('orden_empresarial_id', $ordenPreseleccionada) == $ord->id)>
                                {{ $ord->folio() }} · ${{ number_format((float) $ord->total, 2) }} MXN ({{ $ord->fecha->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="mensaje" style="font-weight:800;display:block;margin-bottom:6px">Detalle de tu mensaje *</label>
                <textarea id="mensaje" name="mensaje" rows="6" placeholder="Explica detalladamente en qué podemos ayudarte..." required style="width:100%;padding:12px;border:1px solid #bcc9dc;border-radius:9px;line-height:1.6">{{ old('mensaje') }}</textarea>
            </div>

            <button class="button" type="submit" style="min-height:48px;font-size:1.05rem">
                Enviar ticket de atención
            </button>
        </form>
    </article>
</section>
@endsection
