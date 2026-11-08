@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Vincular cliente a usuario')

@section('title', $erpContext ? 'Vincular cliente | ERP | ECore Agents' : 'Vincular cliente | ECore Agents')
@section('body-class', 'admin-page')

@section($erpContext ? 'erp-content' : 'content')
    @unless($erpContext)
    <section class="admin-hero">
        <span class="section-eyebrow">Vinculación administrativa</span>
        <h1>Vincular cliente a cuenta de usuario</h1>
        <p>Asocia explícitamente el perfil CRM de {{ $cliente->nombre }} a una cuenta de usuario del sistema.</p>
    </section>

    <section class="admin-layout">
        @include('admin._sidebar')

        <div class="admin-content customer-content">
    @endunless

    <article class="customer-detail" style="margin-bottom:24px">
        <div class="customer-heading">
            <div>
                <span class="section-eyebrow">Ficha CRM</span>
                <h2>{{ $cliente->nombre }}</h2>
            </div>
            <div class="customer-badges">
                <span class="status-badge status-{{ $cliente->estado }}">{{ ucfirst($cliente->estado) }}</span>
                <span class="stage-badge stage-{{ $cliente->etapa_crm }}">{{ $cliente->etapaCrmLabel() }}</span>
            </div>
        </div>

        <dl class="customer-data">
            <div><dt>Correo CRM</dt><dd>{{ $cliente->correo }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $cliente->telefono }}</dd></div>
            <div><dt>Empresa</dt><dd>{{ $cliente->empresa ?: 'Sin empresa' }}</dd></div>
            <div><dt>Registro</dt><dd>{{ $cliente->fecha_registro->format('d/m/Y') }}</dd></div>
        </dl>
    </article>

    @if($usuarios->isEmpty())
        <div class="alert-banner alert-warning" style="margin-bottom:20px">
            No hay cuentas de usuario disponibles sin un cliente ya asignado.
        </div>
        <div class="form-actions">
            <a class="back-button" href="{{ $moduleRoute('clientes.show', $cliente) }}">Volver a la ficha</a>
        </div>
    @else
        <article class="customer-detail">
            <div class="customer-heading">
                <div>
                    <span class="section-eyebrow">Asociación formal</span>
                    <h2>Seleccionar cuenta y justificar motivo</h2>
                </div>
            </div>

            <form class="customer-form" method="post" action="{{ $moduleRoute('clientes.vincular.store', $cliente) }}">
                @csrf

                <div style="margin-bottom:16px">
                    <label for="user_id" style="font-weight:700;display:block;margin-bottom:6px">Cuenta de usuario *</label>
                    <select id="user_id" name="user_id" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        <option value="">-- Selecciona una cuenta de usuario --</option>
                        @foreach($usuarios as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                {{ $user->name }} ({{ $user->email }}) &mdash; Rol: {{ ucfirst($user->rol) }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="field-error" style="color:#b42318;margin-top:4px">{{ $message }}</p> @enderror
                </div>

                <div style="margin-bottom:16px">
                    <label for="motivo" style="font-weight:700;display:block;margin-bottom:6px">Motivo de vinculación *</label>
                    <textarea id="motivo" name="motivo" rows="4" required minlength="5" maxlength="500" placeholder="Describe la justificación administrativa (mínimo 5 caracteres)..." style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px">{{ old('motivo') }}</textarea>
                    @error('motivo') <p class="field-error" style="color:#b42318;margin-top:4px">{{ $message }}</p> @enderror
                </div>

                <div style="background:#f4f6fa;border:1px solid #d0d7e2;padding:14px;border-radius:8px;margin-bottom:20px;font-size:.9rem;color:var(--muted)">
                    <strong>Seguridad y trazabilidad:</strong> Esta acción vinculará formalmente el perfil del cliente con la cuenta de usuario seleccionada. Se registrará un evento de auditoría obligatorio con el administrador responsable y el motivo justificado. No se sobreescriben vinculaciones existentes.
                </div>

                <div class="form-actions">
                    <button class="button" type="submit">Confirmar vinculación</button>
                    <a class="back-button" href="{{ $moduleRoute('clientes.show', $cliente) }}">Cancelar</a>
                </div>
            </form>
        </article>
    @endif

    @unless($erpContext)
        </div>
    </section>
    @endunless
@endsection
