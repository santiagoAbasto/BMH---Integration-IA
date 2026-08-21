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
      {{-- <th scope="col">Precio unitario</th> --}}
      <th scope="col">Subtotal item</th>
    </tr>
  </thead>
  <tbody>
    <?php $subtotal = 0;
    use App\Models\User;

    $cliente = User::find($pedido->cliente_id);
    
    
    ?>
    @foreach($pedido->productos()->get() as $producto)
  <tr>
    <td>{{ $producto->id }}</td>
    <td>{{ $producto->codigo ?? '-' }}</td>
    <td>{{ $producto->nombre ?? '-' }}</td>
    <td>{{ number_format($producto->precio() ?? 0, 2, ',', '.') }}</td>
    
    {{-- Descuento del usuario --}}
    <td>{{ $cliente->descuento > 0 ? $cliente->descuento : 0 }}</td>
    
    {{-- Precio con descuento del usuario --}}
    <td>
        {{ number_format(
            ($producto->precio() ?? 0) * (1 - (($cliente->descuento ?? 0) / 100)),
            2, ',', '.'
        ) }}
    </td>
    
    {{-- Cantidad del producto --}}
    <td>{{ $producto->pivot->cantidad ?? 0 }}</td>

    {{-- Precio total con todos los descuentos aplicados --}}
    <td>
        {{ number_format(
            ($producto->precio() ?? 0) * 
            (1 - (($producto->descuento ?? 0) / 100)) * 
            (1 - (($cliente->descuento ?? 0) / 100)) * 
            ($producto->pivot->cantidad ?? 0),
            2, ',', '.'
        ) }}
    </td>
</tr>


    @endforeach
    <tr>
      <th colspan="7" style='text-align:end;'>Subtotal</th>
      <td>$ {{$pedido->subtotal_format()}}</td>
    </tr>
    {{-- <tr>
      <th colspan="5" style='text-align:end;'>Bonificacion ({{$pedido->bonificacion()}}%)</th>
      <td>-${{$pedido->total_bonificacion()}}</td>
    </tr> --}}
    <tr>
      <th colspan="7" style='text-align:end;'>IVA (21%)</th>
      <td>+${{number_format($pedido->subtotal() * 0.21, 2, ',', '.')}}</td>
    </tr>
    <tr style='font-size:16px;'>
      <th colspan="7" style='text-align:end;'>TOTAL</th>
      <td style='font-weight:700;'>$ {{$pedido->total_pedido}}</td>
    </tr>
    
  </tbody>
</table>