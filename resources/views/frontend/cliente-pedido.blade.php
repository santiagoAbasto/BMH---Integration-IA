@extends('layouts.plantilla-front')

@section('styles')
<style>
    .modal-body{
        padding: 30px;
    }
    tr td:last-child {
        text-align: end;
    }
    .dropdown-menu{
        border-radius: 0px 0px 15px 15px;
        background: #FFF;
        box-shadow: 0px 1px 3px 0px rgba(0, 0, 0, 0.35);
        padding:0;
    }
    .dropdown-menu li{
        width:245px;
        border-bottom:1px solid #d4d4d4;
        padding: 0;
    }
    .dropdown-item:hover{
        background-color: rgba(78, 153, 212, 0.22);
    }
</style>
@endsection


@section('content')


    <section style='padding-top:69px;padding-bottom:125px'>
        
        {{-- <h2 class='pb-4' style='text-align:center;'>Historial de compras</h2> --}}

        

        <div class='container'>
            <a href="javascript:history.back()"><div class='mb-3'>< Volver</div></a>
            <div class="row">
                <label class='mb-3 pb-2 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#0098DA;'>Datos de facturación</label><br>
                <div class='col-6 mb-2'><strong>Razón social: </strong>{{$pedido->nombre}}</div>
                <div class='col-6 mb-2'><strong>DNI / CUIT: </strong>{{$pedido->dni}}</div>
                <div class='col-6 mb-2'><strong>Email: </strong>{{$pedido->mail}}</div>
                <div class='col-6 mb-2'><strong>Celular: </strong>{{$pedido->celular}}</div>
                <div class='col-6 mb-2'><strong>Dirección: </strong>{{$pedido->direccion}}</div>
                <div class='col-6 mb-2'><strong>Provincia: </strong>{{$provincias[array_search($pedido->provincia, array_column($provincias, 'id'))]['nombre']}}</div>
                <div class='col-6 mb-2'><strong>Localidad: </strong>{{$pedido->localidad}}</div>
                <div class='col-6 mb-2'><strong>Código postal: </strong>{{$pedido->cp}}</div>
                <div class='col-6 mb-2'><strong>Tipo de pago: </strong>{{$pedido->tipo_pago}}</div>
                {{-- <div class='col-6 mb-2'><strong>Método de envío: </strong>{{$pedido->tipo_envio}}</div> --}}
                <div class='col-6 mb-2'><strong>Mensaje: </strong>{{$pedido->notas}}</div>
                <div class='col-6 mb-2'><strong>Archivo adjunto: </strong>{{$pedido->archivo ? 'disponible en el mail' : 'ninguno'}}</div>
            </div>
        
            <div class="row">
                <label class='mb-3 pt-3 pb-2 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#0098DA;'>Datos de envío</label><br>
                @if($pedido->direccion2 != null)
                    <div class='col-6 mb-2'><strong>Dirección: </strong>{{$pedido->direccion2}}</div>
                    <div class='col-6 mb-2'><strong>Provincia: </strong>{{$provincias[array_search($pedido->provincia2, array_column($provincias, 'id'))]['nombre']}}</div>
                    <div class='col-6 mb-2'><strong>Localidad: </strong>{{$pedido->localidad2}}</div>
                    <div class='col-6 mb-2'><strong>Código postal: </strong>{{$pedido->cp2}}</div>
                @else
                <div>Mismos que facturación</div>
                @endif
            </div>
        
            <label class='mb-3 pb-2 pt-3 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#0098DA;'>Productos</label><br>
        
            <table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th scope="col">ID</th>
      <th scope="col">Art铆culo</th>
      <th scope="col">Descripci贸n</th>
      <th scope="col">P. lista</th>
      @if(Auth::guard('web')->user()->descuento > 0)
      <th scope="col">Descuento</th>
      @endif
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
    @if(Auth::guard('web')->user()->descuento > 0)
    <td>{{ $cliente->descuento > 0 ? $cliente->descuento : 0 }}</td>
    @endif
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


        </div>
    </section>
    
    
@endsection

@section('script')
<script>
    document.querySelector('.submit').addEventListener('click', (event) =>{
        var form = event.target.getAttribute('data-form-id')
        document.getElementById(form).submit()
    })
</script>
@endsection
