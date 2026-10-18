<div class="admin-summary-grid crm-summary-grid">
    <article class="admin-summary-card"><span>Ventas totales</span><strong>${{ number_format($ventas_totales, 2) }}</strong><small>Órdenes procesadas</small></article>
    <article class="admin-summary-card"><span>Órdenes procesadas</span><strong>{{ $ordenes_procesadas }}</strong></article>
    <article class="admin-summary-card"><span>Unidades IA vendidas</span><strong>{{ $productos_vendidos }}</strong></article>
    <article class="admin-summary-card"><span>Clientes activos</span><strong>{{ $clientes_activos }}</strong><small>CRM</small></article>
    <article class="admin-summary-card"><span>Inventario disponible</span><strong>{{ $inventario_disponible }}</strong><small>Unidades SCM</small></article>
</div>
