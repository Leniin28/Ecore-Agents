<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="ECore Agents, proyecto académico de agentes de atención para WhatsApp.">
        <title>@yield('title', 'ECore Agents')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="@yield('body-class')">
        <header class="site-header">
            <a class="brand" href="{{ route('home') }}" aria-label="ECore Agents, inicio">ECore Agents</a>
            <nav class="site-nav" aria-label="Navegación principal">
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Inicio</a>
                <a href="{{ route('home') }}#about">Nosotros</a>
                <a href="{{ route('plans.index') }}" @if(request()->routeIs('plans.index', 'plans.show')) aria-current="page" @endif>Planes</a>
                <a href="{{ route('plans.custom') }}" @if(request()->routeIs('plans.custom')) aria-current="page" @endif>Personalizado</a>
                <a href="{{ route('home') }}#contact">Contacto</a>
                @guest
                    <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Iniciar sesión</a>
                    <a href="{{ route('register') }}" @if(request()->routeIs('register')) aria-current="page" @endif>Registro</a>
                @endguest
                @auth
                    <a href="{{ route('profile') }}" @if(request()->routeIs('profile')) aria-current="page" @endif>Perfil</a>
                    @if(auth()->user()->hasModuleAccess('crm'))
                        <a href="{{ route('admin') }}" @if(request()->routeIs('admin', 'clientes.*', 'mi-actividad', 'admin.usuarios.*')) aria-current="page" @endif>CRM</a>
                    @endif
                    @if(auth()->user()->hasModuleAccess('scm'))
                        <a href="{{ route('scm.dashboard') }}" @if(request()->routeIs('scm.*')) aria-current="page" @endif>SCM</a>
                        @if(($cantidadStockBajo ?? 0) > 0)
                            <a class="nav-stock-alert" href="{{ route('scm.inventario.index') }}">⚠ Stock bajo: {{ $cantidadStockBajo }}</a>
                        @endif
                    @endif
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.auditoria.index') }}" @if(request()->routeIs('admin.auditoria.*')) aria-current="page" @endif>Auditoría</a>
                    @endif
                    <form class="nav-logout-form" method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="nav-logout-button" type="submit">Cerrar sesión</button>
                    </form>
                @endauth
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site-footer">
            <p>&copy; {{ date('Y') }} ECore. Todos los derechos reservados.</p>
            <button class="policy-link" type="button" data-policy-open>Políticas y condiciones</button>
        </footer>

        <div class="modal" data-policy-modal hidden>
            <button class="modal-backdrop" type="button" data-policy-close aria-label="Cerrar políticas"></button>
            <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="policy-title">
                <button class="modal-close" type="button" data-policy-close aria-label="Cerrar">&times;</button>
                <span class="section-eyebrow">Etapa 5</span>
                <h2 id="policy-title">Demostración académica</h2>
                <p>La autenticación utiliza sesiones Laravel y el CRM almacena clientes e interacciones para fines académicos. El proyecto todavía no procesa pagos ni operaciones comerciales reales.</p>
                <button class="button" type="button" data-policy-close>Entendido</button>
            </section>
        </div>

        @if(isset($alertasStockBajo) && $alertasStockBajo->isNotEmpty())
            <div class="modal stock-alert-modal" data-stock-alert-modal>
                <button class="modal-backdrop" type="button" data-stock-alert-close aria-label="Cerrar alerta de inventario"></button>
                <section class="modal-card stock-alert-card" role="dialog" aria-modal="true" aria-labelledby="stock-alert-title">
                    <button class="modal-close" type="button" data-stock-alert-close aria-label="Cerrar alerta de inventario">&times;</button>
                    <span class="section-eyebrow">SCM</span>
                    <h2 id="stock-alert-title">⚠ Alerta de inventario</h2>
                    @if($alertasStockBajo->count() === 1)
                        <p>{{ $alertasStockBajo->first()->nombre }} alcanzó un nivel bajo de inventario.</p>
                    @else
                        <p>{{ $alertasStockBajo->count() }} recursos requieren atención.</p>
                    @endif
                    <ul class="stock-alert-list">
                        @foreach($alertasStockBajo as $producto)
                            <li><strong>{{ $producto->nombre }}</strong><span>Stock actual: {{ $producto->stock_actual }} · Stock mínimo: {{ $producto->stock_minimo }}</span></li>
                        @endforeach
                    </ul>
                    <div class="stock-alert-actions">
                        <a class="button" href="{{ route('scm.inventario.index') }}">Ver inventario</a>
                        <button class="back-button" type="button" data-stock-alert-close>Cerrar</button>
                    </div>
                </section>
            </div>
        @endif

        @stack('scripts')
    </body>
</html>
