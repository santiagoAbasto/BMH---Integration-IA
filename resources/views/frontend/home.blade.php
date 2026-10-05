@extends('layouts.plantilla-front')

@section('metadatos')
    <meta name='keyword' content='{{ App\Models\Metadatos::all()[0]->keyword }}'>
    <meta name='descripcion' content='{{ App\Models\Metadatos::all()[0]->descripcion }}'>
@endsection

@section('styles')
    <style>
        .poster--imagen {
            background-image: var(--poster-desktop);
            background-position: center;
            background-size: cover;
        }

        @media (max-width: 767px) {
            .poster--imagen.has-mobile {
                background-image: var(--poster-mobile);
            }
        }

        /* estilos del anuncio ahora en styles2.css */
    </style>
@endsection

@section('content')

    <section>
        {{-- <div class='container' style='position:relative;'>
      <div class='row'>
        <div class='portada-cuadro col-6'>
          <div class='portada-texto'>
          
            <div class='portada-interno' data-aos="fade-left">
                <div class="botones-slider">
                  @for ($i = 0; $i < sizeof($home_slider); $i++)
                    @if ($i == 0)
                      <button class='btn-carousel active' type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="{{$i}}"></button>
                    @else
                      <button class='btn-carousel' type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="{{$i}}"></button>
                    @endif
                  @endfor
                </div>
            </div>  
          </div>
        </div>
      </div>
    </div> --}}

        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel" style='position:relative;'>
            <div class="carousel-indicators" style='z-index:100;display:none;'>
                @for ($i = 0; $i < count($home_slider); $i++)
                    @if ($i == 0)
                        <button type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide-to="{{ $i }}" class='active indicator'></button>
                    @else
                        <button type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide-to="{{ $i }}" class='indicator'></button>
                    @endif
                @endfor
            </div>

            <div class="carousel-inner">

                @for ($i = 0; $i < count($home_slider); $i++)
                    <div class="carousel-item {{ $i == 0 ? 'active' : '' }}">
                        @if ($home_slider[$i]->tipo == 'imagen')
                            <div class="poster poster--imagen d-flex justify-content-center {{ $home_slider[$i]->path_mobile ? 'has-mobile' : '' }}"
                                style="--poster-desktop:url('{{ asset('imagenes/'.$home_slider[$i]->path) }}');@if ($home_slider[$i]->path_mobile) --poster-mobile:url('{{ asset('imagenes/'.$home_slider[$i]->path_mobile) }}');@endif">
                                @if ($home_slider[$i]->posicion != null)
                                    @include('frontend.components.hotspot', ['imagen' => $home_slider[$i]])
                                @endif
                            </div>
                        @else
                            <div class='poster d-flex justify-content-center'>
                                <video class='main-video' autoplay muted loop
                                    src="imagenes/{{ $home_slider[$i]->path }}"></video>
                            </div>
                        @endif
                        <div class='container baner' data-aos="fade-left">
                            <div class='baner-interno'>
                                <div class='empresa baner-texto'>
                                    {!! $home_slider[$i]->baner_texto !!}
                                </div>

                                {{-- <div class='empresa2 baner-texto2'>
                    {!! $home_slider[$i]->baner_texto_2 !!}
                </div> --}}

                                <div class="empresa3">
                                    <a href="{{ route('nosotros') }}">

                                        <button class="btn btn-slider">Más información</button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor

            </div>
            <div class='degradado'></div>

        </div>

    </section>




    <section class="services">
        <div class="container">
            <div class='d-flex justify-content-between' style='padding-top: 20px; padding-bottom:17px;'>
                <h2 class='titulo-seccion'>Productos</h2>
                <div class='btn-container'>
                    <a href="{{ route('categorias') }}" style='align-content: center'>
                        <button class="btn seccion-btn">Ver todos</button>

                    </a>
                </div>
            </div>


            {{-- Misma grilla que /categorias: 6 por fila en pantallas grandes. --}}
            <div class='row categorias-grilla'>
                @foreach ($categorias as $categoria)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-4" data-aos="fade-up">
                        @include('components/categoria')
                    </div>
                @endforeach
            </div>

        </div>
    </section>

    <section style="padding-top: 20px">
        <div class="container">
            <div class="d-flex panelHome" style="height:422px;">
                <div class="d-flex justify-content-center panelImg"
                    style="background: #FFFCFC; width:49%; background-image: url('imagenes/{{ $nosotros_slider->path }}'); background-size: cover; background-repeat:no-repeat; background-position: center;">


                </div>
                <div class='about-info  d-flex flex-column justify-content-center' style="height:422px; width:51%">
                    <h2 class='about-titulo' style='margin:0; padding-bottom: 24px'>{{ $nosotros_slider->baner_texto }}
                    </h2>
                    <div class='about-text mb-0' style="color: white; padding-bottom: 60px;">{!! $nosotros_slider->baner_texto_2 !!}</div>
                    <div class="botonV">
                        <a href="{{ route('nosotros') }}">

                            <button class="btn btn-slider">Conocenos</button>
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </section>


    <section class="services" style='padding-top:63px; padding-bottom:40px;'>
        <div class="container">
            <div class='d-flex justify-content-between' style='padding-bottom:22px;'>
                <h2 class='titulo-seccion'>productos destacados</h2>
       
            </div>


            <div class='row'>
                @foreach ($productos as $producto)
                    <div class="col-lg-6" data-aos="fade-up">
                        @include('frontend/components/productoBmh')
                    </div>
                @endforeach
            </div>

        </div>
    </section>





    <section class="services" style='padding-bottom:90px;'>
        <div class="container">
            <div class='d-flex justify-content-between' style='padding-bottom:47px;'>
                <h2 class='titulo-seccion'>Novedades</h2>
                <div class='btn-container' style="padding-top: 28px;">
                    <a href="{{ route('novedades') }}" style='align-content: center'>
                        <button class="btn seccion-btn">Ver todos</button>

                    </a>
                </div>
            </div>
            <div class='row'>
                @foreach ($novedades as $novedad)
                    <div class='col-lg-4'>
                        @include('components/novedad')
                    </div>
                @endforeach
            </div>
        </div>
    </section>



    {{-- MODAL REGISTRO --}}
    @if ($registro)
        <?php
        $titulo = App\Models\Mail::find(1)->registro_titulo;
        $contenido = App\Models\Mail::find(1)->registro;
        ?>
        <div class="modal fade" id="registro" aria-hidden="true" aria-labelledby="exampleModalToggleLabel2"
            tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg" style='width:828px;'>
                <div class="modal-content"
                    style='padding: 67px 81px 60px 81px;
        border-radius: 25px;
        background: #FFF;
        box-shadow: 0px 0px 6.3px 0px rgba(0, 0, 0, 0.25);'>
                    <div class="modal-body" style='overflow:hidden;text-align:center;'>
                        <div
                            style='
            font-size: 32px;
            font-style: normal;
            font-weight: 500;
            line-height: normal;'>
                            {{ $titulo }}</div>
                        <div
                            style='
            font-size: 24px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
            margin-top:23px;margin-bottom:37px;'>
                            {!! $contenido !!}</div>
                        {{-- <a href="{{route('home')}}"><button class='green-btn inicio'>Ir al inicio</button></a> --}}
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('script')
    <script>

        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $('header').addClass('scrolled');
                $('.infoHeader').addClass('esconder');

            } else {
                $('header').removeClass('scrolled');
                $('.infoHeader').removeClass('esconder');

            }

        });








        const header = document.querySelector('header');
        const logo1 = document.getElementById('logo1')
        const logo2 = document.getElementById('logo2')
        const piramide1 = document.getElementById('piramide1')
        const piramide2 = document.getElementById('piramide2')


        function obtenerParametroDeURL(nombreParametro) {
            var url = window.location.href;
            var parametros = url.split("?")[1];
            if (parametros) {
                var pares = parametros.split("&");
                for (var i = 0; i < pares.length; i++) {
                    var par = pares[i].split("=");
                    if (par[0] === nombreParametro) {
                        return decodeURIComponent(par[1]);
                    }
                }
            }
            return null;
        }
        var aviso = obtenerParametroDeURL("aviso");
        if (aviso !== null) {
            toastr.warning('Un producto de tu carrito ha sido modificado por falta de stock')
        }

        $('#registro').modal('show');

        // POPOVER initialization
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

        // Selecciona todos los elementos que deseas observar (en este ejemplo, todos los elementos con la clase ".boton")
        var elementosObservados = document.querySelectorAll('.indicator');


        // Crea una función de devolución de llamada para el observador
        var callback = function(mutationsList, observer) {
            mutationsList.forEach(function(mutation) {
                if (mutation.target.classList.contains('active')) {
                    document.querySelectorAll('.btn-carousel').forEach((elemento) => {
                        if (elemento.classList.contains('active')) {
                            elemento.classList.remove('active')
                        }
                        if (elemento.dataset.bsSlideTo == mutation.target.dataset.bsSlideTo) {
                            elemento.classList.add('active')

                        }
                    })
                }
            });
        };

        // Itera sobre cada elemento observado y agrega un observador a cada uno
        elementosObservados.forEach(function(elemento) {
        var observer = new MutationObserver(callback);

        var config = {
            attributes: true,
            childList: true,
            subtree: true
        };

        observer.observe(elemento, config);
        });
    </script>


 
@endsection
