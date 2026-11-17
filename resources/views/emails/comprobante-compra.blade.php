<h1>Comprobante de compra</h1>
<p>Orden: {{ $pago->ordenEmpresarial->folio() }}<br>
Estado del pago: {{ $pago->estadoLabel() }}<br>
Método: {{ $pago->metodoLabel() }}<br>
Referencia: {{ $pago->referencia }}</p>
<ul>@foreach($pago->desglose['lineas'] ?? [] as $linea)
    <li>{{ $linea['nombre'] }} — {{ $linea['cantidad'] }} × ${{ number_format($linea['precio_unitario_centavos'] / 100, 2) }} MXN</li>
@endforeach</ul>
<p>Subtotal: {{ $pago->subtotal_formateado }}<br>
Descuento: {{ $pago->descuento_formateado }}<br>
Impuestos: ${{ number_format($pago->impuesto_centavos / 100, 2) }} MXN<br>
<strong>Total: {{ $pago->total_formateado }}</strong></p>
<p>La compra y el pago son simulados con fines académicos.</p>
