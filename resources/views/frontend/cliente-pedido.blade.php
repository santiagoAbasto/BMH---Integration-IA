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
        
            {{-- Precios y descuentos del momento de la compra (Pedido::lineas), así Subtotal + IVA cierran con el TOTAL. --}}
            @php $conDescuento = $pedido->descuentoCliente() > 0; $columnas = $conDescuento ? 7 : 6; @endphp
            <table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th scope="col">ID</th>
      <th scope="col">Artículo</th>
      <th scope="col">Descripción</th>
      <th scope="col">P. lista</th>
      @if($conDescuento)
      <th scope="col">Descuento</th>
      @endif
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
      @if($conDescuento)
      <td>{{ $linea->descuento_cliente + 0 }}</td>
      @endif
      <td>{{ number_format($linea->precio_neto, 2, ',', '.') }}</td>
      <td>{{ $linea->cantidad }}</td>
      <td>{{ number_format($linea->subtotal, 2, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr>
      <th colspan="{{ $columnas }}" style='text-align:end;'>Subtotal</th>
      <td>$ {{ $pedido->subtotal_format() }}</td>
    </tr>
    <tr>
      <th colspan="{{ $columnas }}" style='text-align:end;'>IVA ({{ $pedido->porcentajeIva() + 0 }}%)</th>
      <td>+${{ number_format($pedido->iva(), 2, ',', '.') }}</td>
    </tr>
    <tr style='font-size:16px;'>
      <th colspan="{{ $columnas }}" style='text-align:end;'>TOTAL</th>
      <td style='font-weight:700;'>$ {{ $pedido->total_pedido }}</td>
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
