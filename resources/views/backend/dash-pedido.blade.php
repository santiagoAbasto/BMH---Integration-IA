@extends('layouts.plantilla-back')

@section('styles')
<style>
    table .btn{
        height: 31px;
    }
    tr td:last-child {
      text-align: end;
    }
    td{
        align-content: center;
    }
</style>
@endsection

@section('content')

  <h1 class='mb-4'>Pedido (orden #{{$pedido->id}})</h1>

  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif
  @if(session('warning'))
      <div class="alert alert-danger">
          {{ session('warning') }}
      </div>
  @endif

    <div class="row">
        <label class='mb-3 pb-2 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#C10B17;'>Datos de facturación</label><br>
        <div class='col-6 mb-2'><strong>Razón social: </strong>{{$pedido->nombre}}</div>
        <div class='col-6 mb-2'><strong>DNI / CUIT: </strong>{{$pedido->dni}}</div>
        <div class='col-6 mb-2'><strong>Email: </strong>{{$pedido->mail}}</div>
        <div class='col-6 mb-2'><strong>Celular: </strong>{{$pedido->celular}}</div>
        <div class='col-6 mb-2'><strong>Dirección: </strong>{{$pedido->direccion}}</div>
        <div class='col-6 mb-2'><strong>Provincia: </strong>{{$provincias[array_search($pedido->provincia, array_column($provincias, 'id'))]['nombre']}}</div>
        <div class='col-6 mb-2'><strong>Localidad: </strong>{{$pedido->localidad}}</div>
        <div class='col-6 mb-2'><strong>Código postal: </strong>{{$pedido->cp}}</div>
        {{-- <div class='col-6 mb-2'><strong>Tipo de pago: </strong>{{$pedido->tipo_pago}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Método de envío: </strong>{{$pedido->tipo_envio}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Mensaje: </strong>{{$pedido->notas}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Archivo adjunto: </strong>{{$pedido->archivo ? 'disponible en el mail' : 'ninguno'}}</div> --}}
    </div>

    {{-- <div class="row">
        <label class='mb-3 pt-3 pb-2 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#C10B17;'>Datos de envío</label><br>
        @if($pedido->direccion2 != null)
            <div class='col-6 mb-2'><strong>Dirección: </strong>{{$pedido->direccion2}}</div>
            <div class='col-6 mb-2'><strong>Provincia: </strong>{{$provincias[array_search($pedido->provincia2, array_column($provincias, 'id'))]['nombre']}}</div>
            <div class='col-6 mb-2'><strong>Localidad: </strong>{{$pedido->localidad2}}</div>
            <div class='col-6 mb-2'><strong>Código postal: </strong>{{$pedido->cp2}}</div>
        @else
        <div>Mismos que facturación</div>
        @endif
    </div> --}}

    <label class='mb-3 pb-2 pt-3 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#C10B17;'>Productos</label><br>

    @include('backend/components/pedido-tabla')
        
@endsection
