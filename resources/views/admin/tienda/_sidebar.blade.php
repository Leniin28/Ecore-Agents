<aside class="admin-sidebar" aria-label="Menú Tienda Comercial">
    <div><span class="demo-badge">Fase 4</span><h2>Tienda</h2></div>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.productos.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.productos.index') }}">Catálogo comercial</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.categorias.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.categorias.index') }}">Categorías</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.promociones.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.promociones.index') }}">Promociones</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.impuestos.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.impuestos.index') }}">Impuestos</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.monederos.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.monederos.index') }}">Monederos</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.tickets.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.tickets.index') }}">Tickets de soporte</a>
    <a class="admin-menu-button {{ request()->routeIs('admin.tienda.ordenes.*') ? 'is-active' : '' }}" href="{{ route('admin.tienda.ordenes.index') }}">Compras y pagos</a>
    <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--line)">
        <a class="back-button" href="{{ route('catalogo.index') }}" target="_blank" style="display:block;text-align:center;font-size:.85rem;padding:8px">Ver tienda pública &rarr;</a>
    </div>
</aside>
