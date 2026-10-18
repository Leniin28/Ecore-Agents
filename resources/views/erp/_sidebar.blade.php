<aside class="admin-sidebar" aria-label="Menú ERP">
    <div><span class="demo-badge">Etapa 3</span><h2>ERP</h2></div>
    @foreach(['dashboard' => 'Dashboard', 'ordenes.index' => 'Órdenes', 'recursos' => 'Recursos', 'procesos' => 'Procesos internos', 'nivel' => 'Nivel ERP', 'reportes' => 'Reportes'] as $ruta => $texto)
        <a class="admin-menu-button {{ request()->routeIs('erp.'.$ruta) || ($ruta === 'ordenes.index' && request()->routeIs('erp.ordenes.*')) ? 'is-active' : '' }}" href="{{ route('erp.'.$ruta) }}">{{ $texto }}</a>
    @endforeach
</aside>
