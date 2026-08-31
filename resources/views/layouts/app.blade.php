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
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin') }}" @if(request()->routeIs('admin')) aria-current="page" @endif>Administración</a>
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
                <span class="section-eyebrow">Etapa 2</span>
                <h2 id="policy-title">Demostración académica</h2>
                <p>La autenticación utiliza sesiones Laravel. El proyecto todavía no procesa pagos ni almacena datos de CRM o de operación comercial.</p>
                <button class="button" type="button" data-policy-close>Entendido</button>
            </section>
        </div>

        @stack('scripts')
    </body>
</html>
