@csrf
@if(isset($producto)) @method('put') @endif
<label>Nombre<input name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required></label>
@error('nombre') <p class="form-error">{{ $message }}</p> @enderror
<label>Descripción<textarea name="descripcion" maxlength="2000">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea></label>
<label>Categoría<input name="categoria" value="{{ old('categoria', $producto->categoria ?? '') }}" required></label>
<label>Proveedor<select name="proveedor_id" required><option value="">Selecciona</option>@foreach($proveedores as $proveedor)<option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $producto->proveedor_id ?? '') == $proveedor->id)>{{ $proveedor->nombre }}</option>@endforeach</select></label>
@error('proveedor_id') <p class="form-error">{{ $message }}</p> @enderror
<div class="form-grid"><label>Stock actual<input type="number" min="0" name="stock_actual" value="{{ old('stock_actual', $producto->stock_actual ?? 0) }}" required></label><label>Stock mínimo<input type="number" min="0" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}" required></label></div>
<label>Costo unitario<input type="number" min="0" step="0.01" name="costo_unitario" value="{{ old('costo_unitario', $producto->costo_unitario ?? 0) }}" required></label>
<label>Estrategia logística<select name="estrategia_logistica" required><option value="PUSH" @selected(old('estrategia_logistica', $producto->estrategia_logistica ?? 'PULL') === 'PUSH')>PUSH</option><option value="PULL" @selected(old('estrategia_logistica', $producto->estrategia_logistica ?? 'PULL') === 'PULL')>PULL</option></select></label>
@error('estrategia_logistica') <p class="form-error">{{ $message }}</p> @enderror
<button class="button" type="submit">Guardar recurso IA</button>
