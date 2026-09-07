@extends('layouts.plantilla-front')

    {{-- @section('metadatos')
    <meta name='keyword' content='{{$metadatos[0]->keyword}}'>
    <meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
    @endsection --}}

@section('styles')
<style>
    .titulo-busqueda{
        font-size: 21px;
        font-style: normal;
        font-weight: 700;
        line-height: normal;
        text-transform: uppercase;
        margin:0;
    }
</style>
@endsection


<?php
use App\Models\Imagen;
?>


@section('content')

    <section class='titulo'>
        <div class='container d-flex flex-column' >
            <div class='row'>
                <div class='miga'>
                  <div>
                      <a href="{{route('home')}}">Inicio</a> > Búsqueda
                  </div>
                </div>
                <div class='titulo-busqueda' style='padding-top:15px;'>
                    <h2 style='font-size:21px;font-weight:300;'>Resultados de búsqueda por "{{$busqueda}}"</h2>
                </div>
            </div>
        </div>
    </section>

    <section class="services" style='padding-top:80px'>
        <div class="container">
            
          <div class='row'>
            @foreach ($productos as $producto)
            <div class="producto-cont col-lg-12">
                @include('frontend.components.productoBmh')
            </div>
            @endforeach
        </div>
  
        </div>
    </section>
    
@endsection

@section('script')
<script>
</script>
@endsection