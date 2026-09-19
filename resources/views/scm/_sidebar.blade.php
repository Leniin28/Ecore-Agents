<aside class="admin-sidebar" aria-label="Menú SCM">
    <div><span class="demo-badge">Etapa 2</span><h2>SCM</h2></div>
    <a class="admin-menu-button {{ request()->routeIs('scm.productos.*') ? 'is-active' : '' }}" href="{{ route('scm.productos.index') }}">Productos / Recursos IA</a>
    <a class="admin-menu-button {{ request()->routeIs('scm.proveedores.*') ? 'is-active' : '' }}" href="{{ route('scm.proveedores.index') }}">Proveedores</a>
</aside>
