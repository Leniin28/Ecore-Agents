@csrf
@if($method === 'PUT')
    @method('PUT')
@endif

<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" type="text" value="{{ old('nombre', $cliente?->nombre) }}" required autofocus @error('nombre') aria-invalid="true" @enderror>
@error('nombre') <p class="field-error">{{ $message }}</p> @enderror

<label for="correo">Correo electrónico</label>
<input id="correo" name="correo" type="email" value="{{ old('correo', $cliente?->correo) }}" required @error('correo') aria-invalid="true" @enderror>
@error('correo') <p class="field-error">{{ $message }}</p> @enderror

<label for="telefono">Teléfono</label>
<input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $cliente?->telefono) }}" maxlength="30" required @error('telefono') aria-invalid="true" @enderror>
@error('telefono') <p class="field-error">{{ $message }}</p> @enderror

<label for="empresa">Empresa <small>(opcional)</small></label>
<input id="empresa" name="empresa" type="text" value="{{ old('empresa', $cliente?->empresa) }}" @error('empresa') aria-invalid="true" @enderror>
@error('empresa') <p class="field-error">{{ $message }}</p> @enderror

<label for="estado">Estado</label>
<select id="estado" name="estado" required @error('estado') aria-invalid="true" @enderror>
    <option value="activo" @selected(old('estado', $cliente?->estado ?? 'activo') === 'activo')>Activo</option>
    <option value="inactivo" @selected(old('estado', $cliente?->estado) === 'inactivo')>Inactivo</option>
</select>
@error('estado') <p class="field-error">{{ $message }}</p> @enderror

<div class="form-actions">
    <button class="button" type="submit">{{ $submitLabel }}</button>
    <a class="back-button" href="{{ $cliente ? route('clientes.show', $cliente) : route('clientes.index') }}">Cancelar</a>
</div>
