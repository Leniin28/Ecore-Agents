<aside class="admin-sidebar" aria-label="Menú ERP">
    <div><span class="demo-badge">Etapa 3</span><h2>ERP</h2></div>
    <a class="admin-menu-button {{ request()->routeIs('erp.dashboard') ? 'is-active' : '' }}" href="{{ route('erp.dashboard') }}" @if(request()->routeIs('erp.dashboard'))aria-current="page"@endif>Inicio</a>
    @if(auth()->user()->isAdmin() || auth()->user()->hasModuleAccess('crm'))
        <a class="admin-menu-button {{ request()->routeIs('erp.clientes.*') ? 'is-active' : '' }}" href="{{ route('erp.clientes.index') }}" @if(request()->routeIs('erp.clientes.*'))aria-current="page"@endif>Clientes</a>
    @endif
    @if(auth()->user()->isAdmin() || auth()->user()->hasModuleAccess('scm'))
        <a class="admin-menu-button {{ (request()->routeIs('erp.productos.*') && ! request()->routeIs('erp.productos.movimientos')) ? 'is-active' : '' }}" href="{{ route('erp.productos.index') }}" @if((request()->routeIs('erp.productos.*') && ! request()->routeIs('erp.productos.movimientos')))aria-current="page"@endif>Productos</a>
        <a class="admin-menu-button {{ request()->routeIs('erp.inventario.*', 'erp.movimientos.*', 'erp.productos.movimientos') ? 'is-active' : '' }}" href="{{ route('erp.inventario.index') }}" @if(request()->routeIs('erp.inventario.*', 'erp.movimientos.*', 'erp.productos.movimientos'))aria-current="page"@endif>Inventario</a>
    @endif
    <a class="admin-menu-button {{ request()->routeIs('erp.ordenes.*') ? 'is-active' : '' }}" href="{{ route('erp.ordenes.index') }}" @if(request()->routeIs('erp.ordenes.*'))aria-current="page"@endif>Órdenes</a>
    <a class="admin-menu-button {{ request()->routeIs('erp.recursos') ? 'is-active' : '' }}" href="{{ route('erp.recursos') }}" @if(request()->routeIs('erp.recursos'))aria-current="page"@endif>Recursos</a>
    <a class="admin-menu-button {{ request()->routeIs('erp.procesos') ? 'is-active' : '' }}" href="{{ route('erp.procesos') }}" @if(request()->routeIs('erp.procesos'))aria-current="page"@endif>Procesos internos</a>
    <a class="admin-menu-button {{ request()->routeIs('erp.reportes') ? 'is-active' : '' }}" href="{{ route('erp.reportes') }}" @if(request()->routeIs('erp.reportes'))aria-current="page"@endif>Reportes</a>
    <a class="admin-menu-button {{ request()->routeIs('erp.nivel', 'erp.estado') ? 'is-active' : '' }}" href="{{ route('erp.nivel') }}" @if(request()->routeIs('erp.nivel', 'erp.estado'))aria-current="page"@endif>Nivel ERP</a>
    @if(auth()->user()->isAdmin())
        <a class="admin-menu-button {{ request()->routeIs('erp.usuarios.*') ? 'is-active' : '' }}" href="{{ route('erp.usuarios.index') }}" @if(request()->routeIs('erp.usuarios.*'))aria-current="page"@endif>Usuarios</a>
    @endif
</aside>
