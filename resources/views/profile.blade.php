@extends('layouts.app')

@section('title', 'Mi perfil y cuenta | ECore Agents')
@section('body-class', 'profile-page')

@section('content')
    <section class="profile-section" style="max-width:1140px;margin:0 auto;padding-top:40px">
        <div class="profile-container" style="max-width:100%">
            <div class="profile-heading" style="margin-bottom:24px">
                <div>
                    <span class="section-eyebrow">Portal de usuario</span>
                    <h1 style="margin:4px 0">{{ $user->name }}</h1>
                    <p style="color:var(--muted);margin:0">Gestión de cuenta, compras, saldo de monedero y tickets de soporte.</p>
                </div>
                <span class="demo-badge">{{ $user->roleLabel() }}</span>
            </div>

            <!-- Accesos rápidos del cliente -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:28px">
                <a href="{{ route('monedero.index') }}" style="text-decoration:none;display:block">
                    <div class="checkout-card" style="margin:0;padding:20px;border-left:5px solid var(--blue);transition:transform 140ms ease">
                        <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Mi Monedero</small>
                        <h3 style="margin:6px 0;font-size:1.6rem;color:var(--blue-dark)">{{ $monedero ? $monedero->saldo_formateado : '$0.00 MXN' }}</h3>
                        <span style="font-size:.85rem;color:var(--blue);font-weight:700">Ver movimientos o recargar &rarr;</span>
                    </div>
                </a>

                <a href="{{ route('mis-compras.index') }}" style="text-decoration:none;display:block">
                    <div class="checkout-card" style="margin:0;padding:20px;border-left:5px solid var(--plus);transition:transform 140ms ease">
                        <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Mis Compras</small>
                        <h3 style="margin:6px 0;font-size:1.6rem;color:var(--blue-dark)">{{ $comprasRecientes->count() }} órdenes</h3>
                        <span style="font-size:.85rem;color:var(--plus);font-weight:700">Ver todas mis compras &rarr;</span>
                    </div>
                </a>

                <a href="{{ route('tickets.index') }}" style="text-decoration:none;display:block">
                    <div class="checkout-card" style="margin:0;padding:20px;border-left:5px solid var(--advanced);transition:transform 140ms ease">
                        <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Atención a Clientes</small>
                        <h3 style="margin:6px 0;font-size:1.6rem;color:var(--blue-dark)">{{ $ticketsRecientes->count() }} tickets</h3>
                        <span style="font-size:.85rem;color:var(--advanced);font-weight:700">Consultar respuestas &rarr;</span>
                    </div>
                </a>
            </div>

            <!-- Datos editables del usuario / cliente -->
            <div style="display:grid;grid-template-columns:minmax(0, 1.2fr) minmax(300px, 1fr);gap:28px;align-items:start">
                <article class="form-card" style="padding:28px">
                    <span class="section-eyebrow">Mi cuenta</span>
                    <h2 style="margin:4px 0 16px;font-size:1.35rem">Datos personales y de contacto</h2>

                    <form method="post" action="{{ route('profile.update') }}" style="display:grid;gap:14px">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" style="font-weight:800;display:block;margin-bottom:6px">Nombre completo *</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        </div>

                        <div>
                            <label for="email" style="font-weight:800;display:block;margin-bottom:6px">Correo electrónico (solo lectura)</label>
                            <input id="email" type="email" value="{{ $user->email }}" disabled style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #dbe3ef;border-radius:9px;background:#f8fafc;color:var(--muted)">
                            <small style="color:var(--muted)">El correo identifica de forma segura tu cuenta y compras.</small>
                        </div>

                        <div>
                            <label for="telefono" style="font-weight:800;display:block;margin-bottom:6px">Teléfono de contacto / WhatsApp</label>
                            <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $cliente->telefono ?? '') }}" placeholder="Ej. 5511223344" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        </div>

                        <div>
                            <label for="empresa" style="font-weight:800;display:block;margin-bottom:6px">Empresa o negocio</label>
                            <input id="empresa" name="empresa" type="text" value="{{ old('empresa', $cliente->empresa ?? '') }}" placeholder="Nombre comercial de tu empresa" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        </div>

                        <button class="button" type="submit" style="margin-top:8px">Guardar cambios en mis datos</button>
                    </form>
                </article>

                <div style="display:grid;gap:24px">
                    <!-- Planes adquiridos -->
                    <article class="checkout-card" style="margin:0;padding:24px">
                        <span class="section-eyebrow">Capacidad comercial</span>
                        <h2 style="margin:4px 0 14px;font-size:1.2rem">Planes y cupos contratados</h2>
                        @if($planesAdquiridos->isEmpty())
                            <p style="color:var(--muted);font-size:.9rem;margin:0">Aún no cuentas con planes activos confirmados. Explora el catálogo para comenzar.</p>
                            <div style="margin-top:14px">
                                <a class="back-button" href="{{ route('catalogo.index') }}" style="display:inline-block;width:100%;text-align:center">Ver planes disponibles</a>
                            </div>
                        @else
                            <ul style="padding-left:18px;margin:0;line-height:1.8;font-size:.95rem">
                                @foreach($planesAdquiridos as $linea)
                                    <li>
                                        <strong>{{ $linea->producto->nombre ?? 'Recurso' }}</strong> ({{ $linea->cantidad }} cupos)
                                        @if(!empty($linea->configuracion['modulos']))
                                            <small style="display:block;color:var(--muted)">{{ count($linea->configuracion['modulos']) }} módulos personalizados</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>

                    <!-- Compras recientes -->
                    <article class="checkout-card" style="margin:0;padding:24px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                            <h2 style="margin:0;font-size:1.2rem">Últimas compras</h2>
                            <a href="{{ route('mis-compras.index') }}" style="color:var(--blue);font-weight:700;font-size:.85rem">Ver todas</a>
                        </div>
                        @if($comprasRecientes->isEmpty())
                            <p style="color:var(--muted);font-size:.9rem;margin:0">Sin compras recientes.</p>
                        @else
                            <div style="display:grid;gap:8px">
                                @foreach($comprasRecientes as $compra)
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.9rem">
                                        <div>
                                            <a href="{{ route('mis-compras.show', $compra) }}" style="font-weight:800;color:var(--blue-dark)">{{ $compra->folio() }}</a>
                                            <small style="display:block;color:var(--muted)">{{ $compra->fecha->format('d/m/Y') }}</small>
                                        </div>
                                        <div style="text-align:right">
                                            <span style="font-weight:900">${{ number_format((float) $compra->total, 2) }}</span>
                                            <div><span class="store-badge badge-{{ $compra->estado }}">{{ ucfirst($compra->estado) }}</span></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                </div>
            </div>

            <div style="margin-top:28px">
                <a class="back-button" href="{{ route('home') }}">Volver al inicio</a>
            </div>
        </div>
    </section>
@endsection
