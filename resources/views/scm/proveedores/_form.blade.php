@csrf
@if(isset($proveedor)) @method('put') @endif
<label>Nombre<input name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required maxlength="255"></label>
@error('nombre') <p class="form-error">{{ $message }}</p> @enderror
<label>Contacto<input name="contacto" value="{{ old('contacto', $proveedor->contacto ?? '') }}" maxlength="255"></label>
<label>Correo<input type="email" name="correo" value="{{ old('correo', $proveedor->correo ?? '') }}" maxlength="255"></label>
@error('correo') <p class="form-error">{{ $message }}</p> @enderror
<label>Teléfono<input name="telefono" value="{{ old('telefono', $proveedor->telefono ?? '') }}" maxlength="30"></label>
<button class="button" type="submit">Guardar proveedor</button>
