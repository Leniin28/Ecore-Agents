@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Historial de inventario')
@section('title', $erpContext ? 'Historial de inventario | ERP | ECore Agents' : 'Historial de inventario | ECore Agents')
@section($erpContext ? 'erp-content' : 'content')<section class="section"><div class="section-heading">
@unless($erpContext)
<span class="section-eyebrow">SCM</span><h1>Movimientos de {{ $producto->nombre }}</h1>
@endunless
<p>Stock actual: {{ $producto->stock_actual }} unidades.</p><a class="button" href="{{ $moduleRoute('scm.movimientos.create', ['producto_id' => $producto->id]) }}">Registrar movimiento</a></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Responsable</th></tr></thead><tbody>@forelse($movimientos as $movimiento)<tr><td>{{ $movimiento->fecha->format('d/m/Y H:i') }}</td><td>{{ $movimiento->tipo }}</td><td>{{ $movimiento->cantidad }}</td><td>{{ $movimiento->motivo }}</td><td>{{ $movimiento->usuario->name }}</td></tr>@empty<tr><td colspan="5">Sin movimientos.</td></tr>@endforelse</tbody></table></div>{{ $movimientos->links() }}</section>@endsection
