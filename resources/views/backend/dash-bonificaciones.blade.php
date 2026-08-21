@extends('layouts.plantilla-back')

@section('content')

  <h1 class='mb-4'>Bonificaciones</h1>

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


  @foreach ($bonificaciones as $bonificacion)
  
  <form id="formulario-bonificacion" action="{{route('carrito.bonificaciones')}}" method="POST">
    @csrf
    <div class="d-flex flex-column">
        <div class="row d-flex">
            <input type="text" name="idBonificacion" value="{{$bonificacion->id}}" style="display: none">
            <div class="col-lg-1 mt-2">
                <label>N°</label>
                <input type="number" class="form-control"  value="{{$bonificacion->id}}" readonly>
            </div>
            @if ($bonificacion->orden == 'gg')
            <div class="col-lg-6 mt-2">
                <label>Más de:</label>
                <input type="number" min="0" class="form-control" name="desde" value="{{$bonificacion->desde}}" >
            </div>
            @else
            <div class="col-lg-3 mt-2">
                <label>Desde:</label>
                <input type="number" min="0" class="form-control" name="desde" value="{{$bonificacion->desde}}" >
            </div>
            @endif
            @if ($bonificacion->orden !== 'gg')
            <div class="col-lg-3 mt-2">
                <label>Hasta:</label>
                <input type="number" min="0" class="form-control" name="hasta" value="{{$bonificacion->hasta}}" >
            </div>
            @endif
            <div class="col-lg-4 mt-2">
                <label>Descuento:</label>
                <input type="text" min="0" class="form-control" name="porcentaje" value="{{$bonificacion->porcentaje}}" >
            </div>

            <div class="col-lg-1 mt-3">

                <button  data-form-id="formulario-bonificacion" type="submit" class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
            </div>
        </div>
        </div>
      
    </form>
        @endforeach





  @endsection
  

