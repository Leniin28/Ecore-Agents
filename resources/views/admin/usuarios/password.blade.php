@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Restablecer contraseña')
@section('title', $erpContext ? 'Restablecer contraseña | ERP | ECore Agents' : 'Restablecer contraseña | ECore Agents')
@section($erpContext ? 'erp-content' : 'content')
<section class="section narrow-section">
    <div class="section-heading">
@unless($erpContext)
<span class="section-eyebrow">Administración</span><h1>Restablecer contraseña</h1>
@endunless
<p>Usuario: {{ $usuario->name }} ({{ $usuario->email }})</p></div>
    <form class="form-card" method="post" action="{{ $moduleRoute('admin.usuarios.password.update', $usuario) }}">
        @csrf @method('put')
        <label>Nueva contraseña<input type="password" name="password" required minlength="8"></label>
        <label>Confirmación<input type="password" name="password_confirmation" required minlength="8"></label>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
        <div class="form-actions">
            <button class="button" type="submit">Restablecer contraseña</button>
            <a class="back-button" href="{{ $moduleRoute('admin.usuarios.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
