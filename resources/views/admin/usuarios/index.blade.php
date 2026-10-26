@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Usuarios y accesos')

@section('title', $erpContext ? 'Usuarios y accesos | ERP | ECore Agents' : 'Usuarios internos | ECore Agents')

@section($erpContext ? 'erp-content' : 'content')
<section class="section admin-shell">
    <div class="section-heading">

@unless($erpContext)
<span class="section-eyebrow">Administración</span>
        <h1>Usuarios y accesos</h1>
@endunless

        <p>Los clientes provienen del registro público. Desde aquí se crean empleados y administradores.</p>
        <div class="form-actions" style="margin-top: 14px;">
            <a class="button" href="{{ $moduleRoute('admin.usuarios.create') }}">Crear usuario interno</a>
            <a class="back-button" href="{{ $moduleRoute('erp.dashboard') }}">Volver al ERP</a>
        </div>
    </div>
    @if(session('success') && ! $erpContext) <p class="success-alert" role="status" style="margin-bottom: 16px;">{{ session('success') }}</p> @endif
    @if(session('error')) <p class="error-alert" role="alert" style="margin-bottom: 16px;">{{ session('error') }}</p> @endif
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Tipo</th><th>Permisos</th><th>Cliente relacionado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->name }}</td><td>{{ $usuario->email }}</td><td>{{ $usuario->roleLabel() }}@if($usuario->isEmployee() && $usuario->perfil_empresarial)<br>{{ \App\Models\User::PERFILES[$usuario->perfil_empresarial] ?? 'Sin asignar' }}@endif</td>
                    <td>{{ $usuario->isAdmin() ? 'Acceso total' : ($usuario->permissions ? strtoupper(implode(', ', $usuario->permissions)) : 'Sin acceso interno') }}</td>
                    <td>{{ $usuario->cliente?->nombre ?? '—' }}</td>
                    <td class="table-actions">
                        @unless($usuario->isClient()) <a href="{{ $moduleRoute('admin.usuarios.edit', $usuario) }}">Editar</a> @endunless
                        <a href="{{ $moduleRoute('admin.usuarios.password.edit', $usuario) }}">Restablecer contraseña</a>
                        @if(!$usuario->isClient() && !$usuario->cliente && $usuario->id !== auth()->id())
                            <form method="post" action="{{ $moduleRoute('admin.usuarios.destroy', $usuario) }}" data-confirm-delete="¿Confirmas la eliminación del usuario {{ $usuario->name }} ({{ $usuario->email }})? Esta acción no se puede deshacer.">
                                @csrf
                                @method('delete')
                                <button type="submit" aria-label="Eliminar usuario {{ $usuario->name }}">Eliminar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No hay usuarios.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $usuarios->links() }}
</section>
@endsection
