@csrf
@if(isset($usuario)) @method('put') @endif

<label>Nombre
    <input name="name" value="{{ old('name', $usuario->name ?? '') }}" required maxlength="255">
</label>
@error('name') <p class="form-error">{{ $message }}</p> @enderror

@unless(isset($usuario))
    <label>Correo
        <input type="email" name="email" value="{{ old('email') }}" required maxlength="255">
    </label>
    @error('email') <p class="form-error">{{ $message }}</p> @enderror
    <label>Contraseña
        <input type="password" name="password" required minlength="8">
    </label>
    <label>Confirmar contraseña
        <input type="password" name="password_confirmation" required minlength="8">
    </label>
    @error('password') <p class="form-error">{{ $message }}</p> @enderror
@endunless

<label>Rol
    <select name="role" data-role-selector required>
        <option value="usuario" @selected(old('role', $usuario->role ?? 'usuario') === 'usuario')>Empleado</option>
        <option value="admin" @selected(old('role', $usuario->role ?? '') === 'admin')>Administrador</option>
    </select>
</label>
@error('role') <p class="form-error">{{ $message }}</p> @enderror

<label data-business-profile>Perfil empresarial
    <select name="perfil_empresarial" data-profile-selector>
        <option value="">Sin asignar</option>
        @foreach(\App\Models\User::PERFILES as $value => $label)
            <option value="{{ $value }}" @selected(old('perfil_empresarial', $usuario->perfil_empresarial ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>
@error('perfil_empresarial') <p class="form-error">{{ $message }}</p> @enderror
<fieldset data-permissions>
    <legend>Permisos por módulo</legend>
    @php($selected = old('permissions', $usuario->permissions ?? []))
    <label><input type="checkbox" name="permissions[]" value="crm" @checked(in_array('crm', $selected, true))> Acceso CRM</label>
    <label><input type="checkbox" name="permissions[]" value="scm" @checked(in_array('scm', $selected, true))> Acceso SCM</label>
    <label><input type="checkbox" name="permissions[]" value="erp" @checked(in_array('erp', $selected, true))> Acceso ERP</label>
    <small>El perfil sugiere permisos; puedes ajustarlos antes de guardar.</small>
</fieldset>
@error('permissions') <p class="form-error">{{ $message }}</p> @enderror
@error('permissions.*') <p class="form-error">{{ $message }}</p> @enderror

<button class="button" type="submit">{{ isset($usuario) ? 'Guardar cambios' : 'Crear usuario' }}</button>

@push('scripts')
<script>
    const roleSelector = document.querySelector('[data-role-selector]');
    const permissions = document.querySelector('[data-permissions]');
    const profileField = document.querySelector('[data-business-profile]');
    const refreshPermissions = () => {
        permissions.hidden = roleSelector.value !== 'usuario';
        profileField.hidden = roleSelector.value !== 'usuario';
    };
    document.querySelector('[data-profile-selector]').addEventListener('change', (event) => {
        const suggested = { ventas: ['crm', 'erp'], logistica: ['scm', 'erp'] }[event.target.value];
        if (suggested) permissions.querySelectorAll('input').forEach(input => input.checked = suggested.includes(input.value));
    });
    roleSelector.addEventListener('change', refreshPermissions);
    refreshPermissions();
</script>
@endpush
