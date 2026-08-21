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
            <div class="producto-cont col-md-3"  data-aos="fade-up">
                <div class='producto'>
                    <a href="{{route('producto', ['id' => $producto->id])}}" >
                        <div class='producto-portada' style='position: relative;background-image: url("{{asset('imagenes/'.$producto->portada()->path)}}"); background-size: cover; background-position: center;background-repeat:no-repeat;'>
                            <div style='position:absolute;background-color:#DF0E15;width:12.5px;height:2px;bottom:0;left:50%;border-top-right-radius:30%;border-bottom-right-radius:30%;'>
  
                            </div>
                            <div style='position:absolute;background-color:#DF0E15;width:12.5px;height:2px;bottom:0;right:50%;border-top-left-radius:30%;border-bottom-left-radius:30%;'>
  
                            </div>
                            @if($producto->descuento != null)
                                <div style='position:absolute;background-color:#DF0E15;width:93px;height:37px;top:23px;text-align:center;'>
                                    <div class='d-flex flex-column justify-content-center h-100'
                                    style="color: #FFF;
                                    font-size: 20px;
                                    font-style: normal;
                                    font-weight: 400;
                                    line-height: 21px;">
                                        Oferta
                                    </div>
                                </div>
                            @endif
                            <div class='middle'>
                                <div class='d-flex'>
                                    @if($producto->cantidad > 0)
                                    <a class='producto-btn btn-cart agregar-carrito' style='margin-right:2.5px' onclick='agregar_carrito(event)' data-id='{{$producto->id}}' data-nombre='{{$producto->nombre}}' data-precio={{$producto->precio_final()}}>
                                        <img class="search-icon img-search" src="imagenes/iconos/shopping-cart.svg" alt="icon">
                                    </a>
                                    @endif
                                    <a href='{{route('producto', ['id' => $producto->id])}}' class='producto-btn btn-search' style='margin-left:2.5px'>
                                        <img class="search-icon img-search" src="imagenes/iconos/search.svg" alt="icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class='producto-texto d-flex flex-column justify-content-between' style='padding:17px 17px 23px 17px;height:157px;text-align:center;'>
                          <div>
                              <div class='producto-subcat'>
                                  @if($producto->subcategoria()->first() != null)
                                  {{$producto->subcategoria()->first()->nombre}}
                                  @endif
                              </div>
                              <div class='producto-nombre'>
                                  {{ucfirst($producto->nombre)}}
                              </div>
                              
                          </div>
                          <div class='producto-precio'>
                              @if($producto->descuento == null)
                                  $ {{number_format((fmod($producto->precio_final(), 1) == 0.0) ? $producto->precio_final() : $producto->precio_final(), 2, ',', '.')}}
                              @else
                                  <div class='d-flex w-100 justify-content-between'>
                                      <div style='color: rgba(0, 0, 0, 0.33);
                                      font-size: 20px;
                                      font-weight: 300;
                                      line-height: 21px;
                                      text-decoration-line: line-through;'>
                                          $ {{number_format((fmod($producto->precio(), 1) == 0.0) ? number_format($producto->precio(), 0, ',', '') : $producto->precio(), 2, ',', '.')}}
                                      </div>
                                      <div>
                                          $ {{number_format((fmod($producto->precio_final(), 1) == 0.0) ? $producto->precio_final() : $producto->precio_final(), 2, ',', '.')}}
                                      </div>
                                  </div>
                              @endif
                          </div>
                        </div>
                    </a>
                </div>
                
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