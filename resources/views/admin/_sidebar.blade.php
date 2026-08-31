<aside class="admin-sidebar" aria-label="Menú CRM">
    <div><span class="demo-badge">Etapa 3</span><h2>CRM</h2></div>
    @if(auth()->user()->isAdmin())
        <a class="admin-menu-button {{ request()->routeIs('admin') ? 'is-active' : '' }}" href="{{ route('admin') }}" @if(request()->routeIs('admin')) aria-current="page" @endif>Dashboard</a>
    @endif
    <a class="admin-menu-button {{ request()->routeIs('clientes.*') ? 'is-active' : '' }}" href="{{ route('clientes.index') }}" @if(request()->routeIs('clientes.*')) aria-current="page" @endif>Clientes</a>
    <span class="admin-menu-placeholder">Interacciones <small>Próxima etapa</small></span>
    <span class="admin-menu-placeholder">Reportes <small>Pendiente</small></span>
</aside>
