{{--
    Detalle del pedido (admin, PDF y mail). Precios y descuentos los del
    momento de la compra (Pedido::lineas), para que Subtotal + IVA cierren con
    el TOTAL guardado.
--}}
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th scope="col">ID</th>
      <th scope="col">Artículo</th>
      <th scope="col">Descripción</th>
      <th scope="col">P. lista</th>
      <th scope="col">Descuento</th>
      <th scope="col">P.Neto</th>
      <th scope="col">Cantidad</th>
      <th scope="col">Subtotal item</th>
    </tr>
  </thead>
  <tbody>
    @foreach($pedido->lineas() as $linea)
    <tr>
      <td>{{ $linea->producto->id }}</td>
      <td>{{ $linea->producto->codigo ?? '-' }}</td>
      <td>{{ $linea->producto->nombre ?? '-' }}</td>
      <td>{{ number_format($linea->precio_lista, 2, ',', '.') }}</td>
      <td>{{ $linea->descuento_cliente + 0 }}</td>
      <td>{{ number_format($linea->precio_neto, 2, ',', '.') }}</td>
      <td>{{ $linea->cantidad }}</td>
      <td>{{ number_format($linea->subtotal, 2, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr>
      <th colspan="7" style='text-align:end;'>Subtotal</th>
      <td>$ {{ $pedido->subtotal_format() }}</td>
    </tr>
    <tr>
      <th colspan="7" style='text-align:end;'>IVA ({{ $pedido->porcentajeIva() + 0 }}%)</th>
      <td>+${{ number_format($pedido->iva(), 2, ',', '.') }}</td>
    </tr>
    <tr style='font-size:16px;'>
      <th colspan="7" style='text-align:end;'>TOTAL</th>
      <td style='font-weight:700;'>$ {{ $pedido->total_pedido }}</td>
    </tr>
  </tbody>
</table>
