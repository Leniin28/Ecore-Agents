@extends('layouts.app')
@section('title', 'Auditoría | ECore Agents')
@section('content')
<section class="section admin-shell">
    <div class="section-heading"><span class="section-eyebrow">Administración</span><h1>Bitácora de auditoría</h1></div>
    <form class="filters" method="get">
        <label>Usuario<select name="usuario_id"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(request('usuario_id') == $usuario->id)>{{ $usuario->name }}</option>@endforeach</select></label>
        <label>Acción<select name="accion"><option value="">Todas</option>@foreach($acciones as $accion)<option @selected(request('accion') === $accion)>{{ $accion }}</option>@endforeach</select></label>
        <label>Entidad<select name="entidad"><option value="">Todas</option>@foreach($entidades as $entidad)<option @selected(request('entidad') === $entidad)>{{ $entidad }}</option>@endforeach</select></label>
        <button class="button" type="submit">Filtrar</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Descripción</th></tr></thead><tbody>
    @forelse($auditorias as $registro)<tr><td>{{ $registro->created_at->format('d/m/Y H:i') }}</td><td>{{ $registro->usuario?->name ?? 'Sistema' }}</td><td>{{ $registro->accion }}</td><td>{{ $registro->entidad }} #{{ $registro->entidad_id ?? '—' }}</td><td>{{ $registro->descripcion }}</td></tr>@empty<tr><td colspan="5">No hay registros con estos filtros.</td></tr>@endforelse
    </tbody></table></div>
    {{ $auditorias->links() }}
</section>
@endsection
