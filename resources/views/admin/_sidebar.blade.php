<aside class="admin-sidebar" aria-label="Menú CRM">
    <div><span class="demo-badge">Etapa 5</span><h2>CRM</h2></div>
    <a class="admin-menu-button {{ request()->routeIs('admin') ? 'is-active' : '' }}" href="{{ route('admin') }}" @if(request()->routeIs('admin')) aria-current="page" @endif>Dashboard</a>
    <a class="admin-menu-button {{ request()->routeIs('clientes.*') ? 'is-active' : '' }}" href="{{ route('clientes.index') }}" @if(request()->routeIs('clientes.*')) aria-current="page" @endif>Clientes</a>
    <a class="admin-menu-button {{ request()->routeIs('mi-actividad') ? 'is-active' : '' }}" href="{{ route('mi-actividad') }}" @if(request()->routeIs('mi-actividad')) aria-current="page" @endif>Mi actividad</a>
    <span class="admin-menu-placeholder">Reportes <small>Pendiente</small></span>
</aside>
