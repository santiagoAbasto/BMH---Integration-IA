@extends('layouts.plantilla-front')

@section('metadatos')
    <meta name='keyword' content='{{ App\Models\Metadatos::all()[0]->keyword }}'>
    <meta name='descripcion' content='{{ App\Models\Metadatos::all()[0]->descripcion }}'>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/catalogo.css') }}?v=1">
    <style>
    
        .carrito-btn {
            display: flex;
            width: auto;
            height: 39px;
            padding: 8px 20px;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            border-radius: 10px;
            border: 1px solid #0098DA;
            background: #FFF;
            color: #0098DA;

        }

        .carrito-btn:hover {
            background: var(--Verde, #0098DA) !important;
            border: 1px solid #0098DA !important;
            color: #FFF;


        }

        .carrito-btn:hover svg path {
            fill: #FFF;
        }

        .cantidad {
            display: flex;
            justify-content: space-between;
            width: 80px !important;
            height: 42px;
            padding: 0px 16px 0px 16px;
            border-radius: 10px;
            border-radius: 10px;
            border: 1px solid #EBEBEB;
            background: #FFF;
            align-items: center;
            margin-bottom: 15px
        }

        .cantidad div {
            height: auto !important;
        }

        .cantidad svg {
            cursor: pointer;
        }


        #consultar {
            border-radius: 10px;
            background: #0098DA;
            border: none;
            color: white;
            width: 100%;
            height: 39px;
            color: #FFF;
            font-family: 'Montserrat';
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: normal;

        }

        #consultar:hover {
            border-radius: 10px;
            background: white;
            border: 1px solid #0098DA;
            color: #0098DA !important;
            height: 39px;

        }

        .fotorama__stage {
            border: 1px solid #D8D8D8 !important;
            border-radius: 4px;

        }

        .fotorama__stage__frame img {
            padding: 0px !important;
            width: 100% !important;
            left: 0px !important;
            height: 274px;
        }


        .fotorama__thumb-border {
            margin-top: 16px !important;
            border-radius: 4px;
            border: 2px solid #0098DA !important;
        }

        .fotorama__nav__frame {
            padding-top: 16px !important;
            /* padding-right:16px !important; */
        }

        .fotorama__nav.fotorama__nav--thumbs {
            display: flex;
            justify-content: start;
        }

        .fotorama__thumb {
            background-color: transparent;
        }

        .fotorama__thumb {
            border-radius: 8px !important;
            border: 1px solid #DFDFDF;
        }

        .caracteristicas {
            column-count: 2;
            column-gap: 20px;
            /* Espacio entre columnas */
        }

        .caracteristicas ul {
            /* padding: 0;  */
            padding-left: 1.5rem !important;
        }

        .caracteristicas li {
            /* margin-bottom: 10px; */
        }

        .pbmh-card { background:#fff; border:1px solid #E7E9EC; border-radius:10px; margin-bottom:18px;
            overflow:hidden; font-family:'Roboto',sans-serif; }
        .pbmh-top { display:flex; gap:26px; padding:26px 28px 22px; }
        .pbmh-imgbox { flex:0 0 250px; align-self:flex-start; }
        .pbmh-imgbox img { width:250px; height:183px; object-fit:contain; display:block; }
        .pbmh-body { flex:1; min-width:0; display:flex; flex-direction:column; }
        .pbmh-headrow { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .pbmh-titulos { display:flex; flex-direction:column; gap:3px; min-width:0; }
        .pbmh-codigo { font-size:15px; font-weight:700; color:#1F2430; letter-spacing:.02em; }
        .pbmh-nombre { font-size:17px; font-weight:700; color:#1F2430; line-height:1.3;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .pbmh-ver { font-size:14px; color:#0098DA; text-decoration:none; white-space:nowrap; }
        .pbmh-ver:hover { text-decoration:underline; color:#007CB2; }
        .pbmh-cars { margin-top:12px; display:flex; flex-direction:column; gap:3px;
            max-height:177px; overflow-y:auto; padding-right:6px; }
        .pbmh-cars::-webkit-scrollbar { width:6px; }
        .pbmh-cars::-webkit-scrollbar-thumb { background:#D9DDE3; border-radius:3px; }
        .pbmh-cars::-webkit-scrollbar-track { background:transparent; }
        .pbmh-car { display:flex; gap:7px; font-size:13px; line-height:1.5; }
        .pbmh-car-label { color:#9AA0A8; letter-spacing:.03em; white-space:nowrap; }
        .pbmh-car-valor { color:#3A3F47; font-weight:500; }
        .pbmh-precios { margin-top:14px; display:flex; flex-direction:column; gap:5px; max-width:380px; }
        .pbmh-precio-fila { display:flex; justify-content:space-between; font-size:15px; color:#3A3F47; }
        .pbmh-precio-valor { font-weight:500; color:#1F2430; }
        .pbmh-actions { margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .pbmh-stepper { display:inline-flex; align-items:center; border:1px solid #D9DDE3; border-radius:8px;
            overflow:hidden; background:#fff; }
        .pbmh-step { width:34px; height:36px; border:none; background:none; font-size:18px; color:#3A3F47;
            cursor:pointer; line-height:1; }
        .pbmh-step:hover { background:#F3F5F7; }
        .pbmh-qty { min-width:34px; text-align:center; font-size:15px; font-weight:600; color:#1F2430; }
        .pbmh-cart-btn { display:inline-flex; align-items:center; gap:10px; border:1.5px solid #0098DA;
            color:#0098DA; background:#fff; border-radius:8px; padding:11px 26px; font-size:14px;
            font-weight:600; letter-spacing:.05em; cursor:pointer; transition:all .15s; }
        .pbmh-cart-btn:hover { background:#0098DA; color:#fff; }
        .pbmh-cart-btn:hover svg path { fill:#fff; }
        .pbmh-consultar { display:inline-flex; align-items:center; justify-content:center; border-radius:10px;
            background:#0098DA; color:#fff; border:1px solid #0098DA; font-family:'Montserrat',sans-serif;
            font-size:15px; font-weight:600; letter-spacing:.02em; padding:11px 26px; text-decoration:none;
            transition:all .15s; cursor:pointer; white-space:nowrap; }
        .pbmh-consultar:hover { background:#fff; color:#0098DA; }
        .pbmh-consultar-sm { padding:9px 17px; font-size:13px; }
        .pbmh-tabs { display:flex; gap:38px; padding:14px 28px; border-top:1px solid #EDEFF2; }
        .pbmh-tab { border:none; background:none; font-family:inherit; font-size:15px; font-weight:600;
            color:#1F2430; cursor:pointer; display:inline-flex; align-items:center; gap:8px; padding:5px 0; }
        .pbmh-tab:hover { color:#0098DA; }
        .pbmh-caret { font-size:12px; transition:transform .18s; }
        .pbmh-tab.activa .pbmh-caret { transform:rotate(180deg); }
        .pbmh-panel { padding:0 24px 20px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin; scrollbar-color:#E8EBEF transparent; }
        /* Si hay «Productos relacionados», la card horizontal trae su propio
           .pbmh-panel (cerrado con max-height: 0). Los paneles de la ficha se
           abren con d-none y no deben heredar eso. */
        [data-pbmh-detalle] .pbmh-panel { max-height:none; opacity:1; overflow-y:hidden; }
        .pbmh-panel::-webkit-scrollbar { height:3px; }
        .pbmh-panel::-webkit-scrollbar-track { background:transparent; }
        .pbmh-panel::-webkit-scrollbar-thumb { background:#E8EBEF; border-radius:10px; }
        .pbmh-panel::-webkit-scrollbar-thumb:hover { background:#D1D6DE; }
        .pbmh-tabla { width:100%; border-collapse:separate; border-spacing:0; }
        .pbmh-tabla thead tr { background:#111315; color:#fff; }
        .pbmh-tabla th { font-size:14px; font-weight:500; text-align:left; padding:13px 16px; }
        .pbmh-tabla thead th:first-child { border-top-left-radius:4px; }
        .pbmh-tabla thead th:last-child { border-top-right-radius:4px; }
        .pbmh-tabla td { padding:12px 16px; border-bottom:1px solid #F0F2F4; font-size:14px;
            color:#3A3F47; vertical-align:middle; }
        .pbmh-tabla tbody tr:hover { background:#FAFBFC; }
        .pbmh-col-img { width:78px; }
        .pbmh-thumb { width:58px; height:53px; object-fit:contain; display:block; }
        .pbmh-celda-cod { font-weight:600; color:#1F2430; white-space:nowrap; }
        .pbmh-celda-desc { min-width:210px; }
        .pbmh-celda-desc a { display:inline-flex; flex-direction:column; gap:2px; color:#9AA0A8; font-size:13px; text-decoration:none; justify-content:center; vertical-align:middle; }
        .pbmh-celda-desc a:hover { color:#0098DA; }
        .pbmh-num { white-space:nowrap; }
        .pbmh-stepper-sm .pbmh-qty { min-width:28px; padding:0 2px 0 11px; font-size:15px; text-align:left; }
        .pbmh-steps { display:flex; flex-direction:column; padding:0 8px 0 2px; }
        .pbmh-stepper-sm .pbmh-step { width:18px; height:16px; display:flex; align-items:center; justify-content:center;
            border:none; background:none; padding:0; color:#5A6169; cursor:pointer; }
        .pbmh-stepper-sm .pbmh-step:hover { background:none; color:#0098DA; }
        .pbmh-stepper-sm .pbmh-step svg { display:block; }
        .pbmh-total { font-weight:600; color:#1F2430; }
        .pbmh-mini-cart { width:44px; height:44px; border-radius:10px; border:1.5px solid #0098DA;
            background:#fff; color:#0098DA; cursor:pointer; display:inline-flex; align-items:center;
            justify-content:center; gap:3px; padding:0; transition:all .15s; }
        .pbmh-mini-cart svg { display:block; flex-shrink:0; }
        .pbmh-mini-cart:hover { background:#0098DA; color:#fff; }
        .pbmh-vacio { font-size:14px; color:#9AA0A8; padding:14px 0 2px; margin:0; }
        @media (max-width: 991px) {
            .pbmh-top { flex-direction:column; }
            .pbmh-imgbox { flex:none; }
            .pbmh-tabs { gap:20px; flex-wrap:wrap; }
            .pbmh-panel .pbmh-tabla { min-width:720px; }
        }
        /* Lupa zoom - fotorama del show y preview flotante */
        #pbmh-zoom { position:fixed; display:none; width:460px; height:460px; background:#fff; border:1px solid #E7E9EC;
            border-radius:10px; box-shadow:0 14px 36px rgba(16,24,40,.16); z-index:1060; pointer-events:none;
            background-repeat:no-repeat; background-position:center; overflow:hidden; }
        .pbmh-imgbox, .fotorama__stage, .pbmh-col-img { position:relative; }
        .pbmh-lens { position:absolute; display:none; border:1px solid rgba(0,152,218,.35);
            background:rgba(0,152,218,.08); pointer-events:none; border-radius:6px; z-index:2; }
        .fotorama__stage .pbmh-lens { border-radius:4px; }
        @media (max-width: 991px) { #pbmh-zoom, .pbmh-lens { display:none !important; } }
      
    </style>
@endsection

@section('content')
    @php $galeriaDetalle = $producto->galeriaUrls(); @endphp
    <section style="padding-top: 50px">
        <div class='container miga'>
            <div>
                <a href="{{ route('home') }}">
                    @if (!Auth::guard('web')->check())
                        Inicio
                    @else
                        Carrito
                    @endif
                </a>
                <span style="padding-left:8px; padding-right:8px">/ </span>
                <a href="{{ route('categorias') }}">Productos</a>
                <span style="padding-left:8px; padding-right:8px">/ </span>
                @if ($producto->categoria()->first() != null)
                    <a
                        href="{{ route('productos', ['categoria' => $producto->categoria()->first()->id]) }}">{{ ucfirst($producto->categoria()->first()->nombre) }}</a>
                    <span style="padding-left:8px; padding-right:8px">/ </span>
                @endif

                <span style='font-weight:600;'>{{ ucfirst($producto->nombre) }} {{ ucfirst($producto->codigo) }}</span>

            </div>
        </div>
    </section>
    
    
    <section class="catalogo" data-catalogo data-catalogo-detalle>
        <div class="container" data-catalogo-contenido>
            <div class="catalogo__grilla">
                <div class="catalogo__fondo" data-catalogo-cerrar-filtros aria-hidden="true"></div>
                <aside id="catalogo-filtros" class="catalogo__sidebar" aria-label="Filtros">
                    @include('frontend.catalogo._sidebar')
                </aside>
                <div class="catalogo__resultados">
                    <button type="button" class="catalogo__abrir-filtros mb-3" data-catalogo-abrir-filtros
                            aria-controls="catalogo-filtros" aria-expanded="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Filtros
                    </button>
                    <div class='row'>
                        <div class='col-lg-6' style='padding-bottom:48px;'>
                            <div class="fotorama" data-thumbfit='cover' data-thumbmargin="16" data-nav="thumbs"
                                data-thumbwidth="80px" data-thumbheight="78px" data-height='431px' data-width="100%"
                                data-fit='cover' data-ratio='800/600' data-click="false"
                                @include('frontend.components.galeria-datos', ['productoGaleria' => $producto, 'fotosGaleria' => $galeriaDetalle])>
                                @foreach ($galeriaDetalle as $foto)
                                    <img class="producto-img" src="{{ $foto }}" alt="{{ $producto->nombre }}">
                                @endforeach
                            </div>
                        </div>
                        <div class='col-lg-6 caja-producto' style="margin-bottom: 30px">
                            <div class='d-flex flex-column justify-content-between producto-informacion'>
                                <div>

                                    <div class='d-flex flex-column'>
                                        <div class="d-flex justify-content-between">
                                            <span class="producto-titulo"
                                                style="color: #000 !important">{{ ucfirst($producto->codigo) }}</span>
                                            <span class="producto-titulo" style="color: #0098DA !important">
                                                @if ($producto->estado == 1)
                                                    <span class="producto-titulo" style="color: #ABD430">Nuevo</span>
                                                @elseif ($producto->estado == 2)
                                                    <span class="producto-titulo" style="color: #0098DA">Reconstruido</span>
                                                @endif


                                            </span>

                                        </div>
                                        <span class="producto-titulo">{{ ucfirst($producto->nombre) }}</span>



                                    </div>

                                    <div class='d-flex flex-column mt-3'>

                                        <div class="infoR" style="font-size 14px !important">{!! ucfirst($producto->descripcion) !!}
                                        </div>



                                    </div>


                                    <div class="d-flex flex-column" style="overflow-y: auto; max-height: 200px; ">

                                        @for ($i = 1; $i <= 78; $i++)
                                            @php
                                                $columna = "columna_$i";
                                            @endphp

                                            @if ($producto->$columna)
                                            @if($producto->categoria()->first()->$columna)
                                                <div class="d-flex">
                                                    <span
                                                        class="infoR">{{ $producto->categoria()->first()->$columna }}:</span>
                                                    <span class="nR">{{ $producto->$columna }}</span>
                                                </div>
                                                @endif
                                            @endif
                                        @endfor
                                        
                                        @foreach($caracteristicas as $caracteristica)
                                              <div class="d-flex">
                                                    <span
                                                        class="infoR">{{ $caracteristica->nombre }}:</span>
                                                    <span class="nR">{{ $caracteristica->valor }}</span>
                                                </div>
                                        @endforeach
                                        
                                    

                                        @if ($producto->marca)
                                            <div class="d-flex">
                                                <span class="infoR">Marca:</span>
                                                <span class="nR">{{ $producto->marca }}</span>
                                            </div>
                                        @endif
                                        
                                        
                                              @if ($producto->modelo)
                                            <div class="d-flex">
                                                <span class="infoR">Modelo:</span>
                                                <span class="nR">{{ $producto->modelo }}</span>
                                            </div>
                                        @endif

                                        {{-- @if ($producto->equivalencias)
                                    <div class="d-flex">
                                        <span class="infoR">Equivalencias:</span>
                                        <span class="nR">{{ $producto->equivalencias }}</span>
                                    </div>
                                    @endif --}}



                                    </div>
                                </div>


                                <div class="row d-flex">
                                    @if (!Auth::guard('web')->check())
                                        <div class="col-lg-12">
                                            <a href="{{ route('contacto', ['producto' => $producto->nombre]) }}"><button
                                                    id='consultar' class='green-btn'>Consultar</button></a>
                                        </div>
                                    @else
                                    <div class="col-lg-12">


                                        <div class="d-flex" style="width: 250px;">
                                            <div class="col-lg-8">
                                                <span>
                                                    Precio Lista:
                                                </span>
                            
                                            </div>
                                            <div class="col-lg-6" style='text-align:end;'>
                                                <span>
                                                    ${{ number_format($producto->precio(), 2, ',', '.') }}
                                                </span>
                            
                                            </div>
                            
                                        </div>
                                        @if ($producto->descuento > 0)
                                            <div class="d-flex" style=" width: 250px;">
                                                <div class="col-lg-8">
                                                    <span>
                                                        Descuento producto:
                                                    </span>
                            
                                                </div>
                                                <div class="col-lg-6" style='text-align:end; '>
                                                    <span>
                                                        -{{ $producto->descuento }}%
                                                    </span>
                            
                                                </div>
                            
                                            </div>
                            
                            
                                            <div class="d-flex" style=" width: 250px;">
                                                <div class="col-lg-8">
                                                    <span>
                                                        Precio con descuento:
                                                    </span>
                            
                                                </div>
                                                <div class="col-lg-6" style='text-align:end; '>
                                                    <span>
                                                        ${{ number_format($producto->precio_final(), 2, ',', '.') }}
                                                    </span>
                            
                                                </div>
                            
                                            </div>
                                        @endif
                            
                                        @if (Auth::guard('web')->user()->descuento > 0)
                                            <div class="d-flex" style=" width: 250px;">
                                                <div class="col-lg-8">
                                                    <span>
                                                        Descuento cliente:
                                                    </span>
                            
                                                </div>
                                                <div class="col-lg-6" style='text-align:end; '>
                                                    <span>
                                                        -{{ Auth::guard('web')->user()->descuento }}%
                                                    </span>
                            
                                                </div>
                            
                                            </div>
                            
                            
                                            <div class="d-flex" style=" width: 250px;">
                                                <div class="col-lg-8">
                                                    <span>
                                                        Precio con descuento:
                                                    </span>
                            
                                                </div>
                                                <div class="col-lg-6" style='text-align:end; '>
                                                    <span>
                                                        ${{ number_format($producto->precio_unitario_descontado(), 2, ',', '.') }}
                                                    </span>
                            
                                                </div>
                            
                                            </div>
                                        @endif
                            
                                        <div class="d-flex" style="width: 250px;">
                                            <div class="col-lg-8">
                                                <span>
                                                    Precio reventa:
                                                </span>
                            
                                            </div>
                                            <div class="col-lg-6" style='text-align:end; '>
                                                <span>
                                                    ${{ $producto->precio_reventa() }}
                                                </span>
                            
                                            </div>
                            
                                        </div>
                            
                                        <div class="d-flex" style="padding-top: 15px; width: 250px;">
                                            <div class="col-lg-8">
                                                <span>
                                                    Subtotal
                            
                            
                            
                                                    :
                                                </span>
                            
                                            </div>
                                            <div class="col-lg-6">
                                                <div class='fila col-1 monitor subtotal{{ $producto->id }}' style='text-align:end; width: 100%;'>
                                                    <div>
                            
                                                        ${{ number_format($producto->precio_unitario_descontado(), 2, ',', '.') }}
                                                    </div>
                                                </div>
                            
                                            </div>
                            
                                        </div>
                            
                            
                                    </div>
                                        <div class="col-lg-12 d-flex">

                                            <div class='cantidad' style="width: 80px !important;">
                                                <div>
                                                    <span class="addC"
                                                        onclick="sumar_restar('restar', '{{ $producto->id }}')">-</span>
                                                </div>
                                                <div class='cantidad-contador{{ $producto->id }}' style='width:auto;'>1
                                                </div>
                                                <div>
                                                    <span class="addC"
                                                        onclick="sumar_restar('sumar', '{{ $producto->id }}')">+</span>

                                                </div>

                                            </div>
                                            
                                            
                                            <div>
                                                
                                                 <button class='carrito-btn' style="margin-left: 50px !important"
                                                onclick="agregar_carrito_publico('{{ $producto->id }}', {{ $producto->precio_unitario_descontado() }})">
                                                SUMAR AL CARRITO <svg xmlns="http://www.w3.org/2000/svg" width="15"
                                                    height="17" viewBox="0 0 15 17" fill="none">
                                                    <path
                                                        d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z"
                                                        fill="#0098DA" />
                                                </svg>
                                            </button>
                                            </div>

                                        </div>
                                        <!--<div class="col-lg-12">-->


                                        <!--    <button class='carrito-btn'-->
                                        <!--        onclick="agregar_carrito_publico('{{ $producto->id }}', {{ $producto->precio_unitario_descontado() }})">-->
                                        <!--        SUMAR AL CARRITO <svg xmlns="http://www.w3.org/2000/svg" width="15"-->
                                        <!--            height="17" viewBox="0 0 15 17" fill="none">-->
                                        <!--            <path-->
                                        <!--                d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z"-->
                                        <!--                fill="#0098DA" />-->
                                        <!--        </svg>-->
                                        <!--    </button>-->


                                        <!--</div>-->
                                    @endif
                                </div>

                            </div>
                        </div>

                        @php
                            $detalleTienePartes = $producto->partesRelacionadas->isNotEmpty();
                            $detalleTieneEquivalencias = $producto->equivalencias->isNotEmpty();
                            $detalleTieneAplicaciones = $producto->aplicaciones->isNotEmpty();
                            $detalleDescuentoCliente = Auth::guard('web')->check() ? (int) Auth::guard('web')->user()->descuento : 0;
                            $detalleEsCliente = Auth::guard('web')->check();
                        @endphp
                        <div class="pbmh-card" data-pbmh-detalle data-agg-url="{{ route('carrito.agregar') }}" data-csrf="{{ csrf_token() }}" style="margin-top:32px;">
                            @if ($detalleTienePartes || $detalleTieneEquivalencias || $detalleTieneAplicaciones)
                            <div class="pbmh-tabs">
                                @if ($detalleTienePartes)
                                    <button type="button" class="pbmh-tab" data-tab="partes">
                                        Partes relacionadas <span class="pbmh-caret">&#9662;</span>
                                    </button>
                                @endif
                                @if ($detalleTieneEquivalencias)
                                    <button type="button" class="pbmh-tab" data-tab="equivalencias">
                                        Equivalencias <span class="pbmh-caret">&#9662;</span>
                                    </button>
                                @endif
                                @if ($detalleTieneAplicaciones)
                                    <button type="button" class="pbmh-tab" data-tab="aplicaciones">
                                        Aplicaciones <span class="pbmh-caret">&#9662;</span>
                                    </button>
                                @endif
                            </div>
                            @if ($detalleTienePartes)
                            <div class="pbmh-panel d-none" data-panel="partes">
                                <table class="pbmh-tabla">
                                    <thead>
                                        <tr>
                                            <th class="pbmh-col-img"></th>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            @if ($detalleEsCliente)
                                                <th>Precio</th>
                                                <th>Descuento</th>
                                                <th>Cantidad</th>
                                                <th>Total</th>
                                                <th></th>
                                            @else
                                                <th></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($producto->partesRelacionadas as $parte)
                                            @php
                                                $parteListaDet = (float) $parte->precio;
                                                $parteDescDet = $parteListaDet * (1 - ((float) $parte->descuento / 100)) * (1 - ($detalleDescuentoCliente / 100));
                                            @endphp
                                            <tr data-precio="{{ number_format($parteDescDet, 2, '.', '') }}">
                                                <td class="pbmh-col-img">
                                                    <a href="{{ route('producto', ['id' => $parte->id]) }}" data-gallery-open aria-haspopup="dialog" aria-label="Ver fotos de {{ $parte->nombre }}"
                                                       @include('frontend.components.galeria-datos', ['productoGaleria' => $parte])>
                                                        <img class="pbmh-thumb" src="{{ $parte->portadaUrl() }}" alt="" loading="lazy">
                                                    </a>
                                                </td>
                                                <td class="pbmh-celda-cod">
                                                    <a href="{{ route('producto', ['id' => $parte->id]) }}">{{ $parte->codigo }}</a>
                                                </td>
                                                <td class="pbmh-celda-desc">
                                                    <a href="{{ route('producto', ['id' => $parte->id]) }}">
                                                        <span>{{ $parte->nombre }}</span>
                                                        <span>Medidas</span>
                                                    </a>
                                                </td>
                                                @if ($detalleEsCliente)
                                                    <td class="pbmh-num">${{ number_format($parteListaDet, 2, ',', '.') }}</td>
                                                    <td class="pbmh-num">${{ number_format($parteDescDet, 2, ',', '.') }}</td>
                                                    <td>
                                                        <div class="pbmh-stepper pbmh-stepper-sm">
                                                            <span class="pbmh-qty" data-qty>1</span>
                                                            <span class="pbmh-steps">
                                                                <button type="button" class="pbmh-step" data-step="1" aria-label="Sumar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 5l4-4 4 4"/></svg>
                                                                </button>
                                                                <button type="button" class="pbmh-step" data-step="-1" aria-label="Restar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"/></svg>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="pbmh-num pbmh-total" data-total>${{ number_format($parteDescDet, 2, ',', '.') }}</td>
                                                    <td>
                                                        <button type="button" class="pbmh-mini-cart" data-add data-producto-id="{{ $parte->id }}" data-precio="{{ number_format($parteDescDet, 2, '.', '') }}" title="Sumar al carrito" aria-label="Sumar al carrito">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 1.5v9M1.5 6h9"/></svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="17.5" cy="20" r="1.4"/><path d="M2.5 3.5h2.2l2.5 11.3a1.7 1.7 0 0 0 1.7 1.3h7.9a1.7 1.7 0 0 0 1.7-1.3l1.8-8.3H6"/></svg>
                                                        </button>
                                                    </td>
                                                @else
                                                    <td><a href="{{ route('contacto', ['producto' => $parte->nombre]) }}" class="pbmh-consultar pbmh-consultar-sm">CONSULTAR</a></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                            @if ($detalleTieneEquivalencias)
                            <div class="pbmh-panel d-none" data-panel="equivalencias">
                                @include('frontend.components.equivalencias-tabla', ['equivalencias' => $producto->equivalencias])
                            </div>
                            @endif
                            @if ($detalleTieneAplicaciones)
                            <div class="pbmh-panel d-none" data-panel="aplicaciones">
                                @include('frontend.components.aplicaciones-tabla', ['aplicaciones' => $producto->aplicaciones])
                            </div>
                            @endif
                            @endif
                        </div>

                        @if ($productos != null)
                            <h4
                                style='padding-bottom:10px; margin-top: 40px;
            font-size: 24px;
            font-style: normal;
            font-weight: 600;
            line-height: 130%; /* 31.2px */'>
                                Productos relacionados</h4>

                            @include('frontend/productos-listado')
                        @endif


                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection

@section('script')
    <script src="{{ asset('js/catalogo.js') }}?v=2"></script>
    <script>
        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";


        function sumar_restar(tipo, id) {
            var cantidad = document.querySelector('.cantidad-contador' + id)
            if (tipo == 'sumar') {
                var resultado = parseInt(cantidad.innerText) + 1
            } else {
                var resultado = parseInt(cantidad.innerText) - 1
            }
            if (resultado > 0) {
                cantidad.innerText = resultado
                $.ajax({
                    url: "{{ route('actualizar.subtotal') }}",
                    type: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        cantidad: resultado,
                    },
                    success: function(response) {
                        console.log(response, '?')
                        document.querySelector('.subtotal' + id).innerText =  response.total
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        // Delegación para la card del detalle del producto — tabs, stepper y carrito.
        // Flag y atributo propios: las cards de «Productos relacionados»
        // (frontend.components.productoBmh) registran su propia delegación con
        // __pbmhDelegado; si compartieran el flag, la que carga primero dejaba
        // a la otra sin registrar y los desplegables de la ficha no abrían.
        (function () {
            if (window.__pbmhDetalleDelegado) return;
            window.__pbmhDetalleDelegado = true;
            function formatear(n) { return '$' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
            function toastOk() {
                if (window.iziToast) {
                    iziToast.success({ title: 'Producto agregado al carrito', backgroundColor: '#DAF6D3', titleColor: '#479831', iconColor: '#479831', progressBar: false, position: 'bottomRight', timeout: 2500 });
                }
            }
            document.addEventListener('click', function (ev) {
                var card = ev.target.closest('[data-pbmh-detalle]');
                if (!card) return;
                var stepBtn = ev.target.closest('[data-step]');
                if (stepBtn) {
                    var qtyEl = stepBtn.closest('.pbmh-stepper').querySelector('[data-qty]');
                    var valor = Math.max(1, parseInt(qtyEl.textContent, 10) + parseInt(stepBtn.dataset.step, 10));
                    qtyEl.textContent = valor;
                    var fila = stepBtn.closest('tr');
                    if (fila && fila.dataset.precio) {
                        fila.querySelector('[data-total]').textContent = formatear(valor * parseFloat(fila.dataset.precio));
                    }
                    return;
                }
                var tab = ev.target.closest('.pbmh-tab');
                if (tab) {
                    var nombre = tab.dataset.tab;
                    var abrir = !tab.classList.contains('activa');
                    card.querySelectorAll('.pbmh-tab').forEach(function (t) { t.classList.remove('activa'); });
                    card.querySelectorAll('.pbmh-panel').forEach(function (p) { p.classList.add('d-none'); });
                    if (abrir) { tab.classList.add('activa'); card.querySelector('[data-panel="' + nombre + '"]').classList.remove('d-none'); }
                    return;
                }
                var addBtn = ev.target.closest('[data-add]');
                if (addBtn) {
                    var grupo = addBtn.closest('.pbmh-actions') || addBtn.closest('tr');
                    var qty = grupo ? parseInt(grupo.querySelector('[data-qty]').textContent, 10) || 1 : 1;
                    var body = new URLSearchParams();
                    body.append('producto_id', addBtn.dataset.productoId);
                    body.append('precio', addBtn.dataset.precio);
                    body.append('qty', qty);
                    fetch(card.dataset.aggUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': card.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: body,
                    }).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(function () { toastOk(); })
                    .catch(function (e) { console.error('carrito', e); });
                }
            });
        })();

        // Lupa zoom para el fotorama del show (mismo comportamiento que en las cards)
        (function () {
            if (window.__pbmhZoomDetalleInit) return;
            window.__pbmhZoomDetalleInit = true;
            var preview = document.getElementById('pbmh-zoom');
            if (!preview) { preview = document.createElement('div'); preview.id = 'pbmh-zoom'; document.body.appendChild(preview); }
            var lens = document.createElement('div');
            lens.className = 'pbmh-lens';
            var activeImg = null, activeBox = null, zoom = 2.4;
            var dragging = false, rafId = null, pendingEvent = null, hovering = false;
            // Igual que en las cards: priorizar la izquierda y ajustar al espacio disponible.
            var LADO_MAX = 520, LADO_MIN = 260, SEPARACION = 24, BORDE = 12;
            var aLaIzquierda = true;
            function ubicarPreview(box) {
                var rect = box.getBoundingClientRect();
                var alto = window.innerHeight - BORDE * 2;
                var izquierda = rect.left - SEPARACION - BORDE;
                var derecha = window.innerWidth - rect.right - SEPARACION - BORDE;
                aLaIzquierda = izquierda >= LADO_MIN || izquierda >= derecha;
                var lado = Math.max(LADO_MIN, Math.min(LADO_MAX, alto, aLaIzquierda ? izquierda : derecha));
                preview.style.width = lado + 'px';
                preview.style.height = lado + 'px';
            }
            function showFotorama(box, img) {
                if (window.innerWidth < 992 || document.body.classList.contains('product-gallery-open')) return;
                ubicarPreview(box);
                activeImg = img; activeBox = box;
                if (!box.contains(lens)) box.appendChild(lens);
                if (setSrc(img)) { lens.style.display = 'block'; preview.style.display = 'block'; }
            }
            function hideFotorama() {
                if (dragging) return;
                preview.style.display = 'none';
                lens.style.display = 'none';
                activeImg = null; activeBox = null;
                pendingEvent = null;
                if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
            }
            document.addEventListener('product-gallery:open', function () { dragging = false; hovering = false; hideFotorama(); });
            function moveFotorama(e) {
                if (!activeImg || !activeBox || dragging) return;
                pendingEvent = e;
                if (rafId) return;
                rafId = requestAnimationFrame(applyMove);
            }
            function applyMove() {
                rafId = null;
                var e = pendingEvent; if (!e || !activeImg || !activeBox || dragging) return;
                preview.style.display = 'block'; lens.style.display = 'block';
                var rect = activeBox.getBoundingClientRect();
                var x = e.clientX - rect.left, y = e.clientY - rect.top;
                var cw = rect.width, ch = rect.height;
                var pw = preview.offsetWidth || 460, ph = preview.offsetHeight || 460;
                var lensW = pw / zoom, lensH = ph / zoom;
                var frac = cw < 120 ? 0.42 : (cw < 300 ? 0.5 : 0.58);
                lensW = Math.min(lensW, cw * frac); lensH = Math.min(lensH, ch * frac);
                lensW = Math.max(lensW, 22); lensH = Math.max(lensH, 22);
                lens.style.width = lensW + 'px'; lens.style.height = lensH + 'px';
                var lx = x - lensW/2, ly = y - lensH/2;
                lx = Math.max(0, Math.min(lx, cw - lensW));
                ly = Math.max(0, Math.min(ly, ch - lensH));
                lens.style.left = lx + 'px'; lens.style.top = ly + 'px';
                var xPct = (cw - lensW) > 0 ? (lx / (cw - lensW)) * 100 : (x / cw) * 100;
                var yPct = (ch - lensH) > 0 ? (ly / (ch - lensH)) * 100 : (y / ch) * 100;
                preview.style.backgroundPosition = xPct + '% ' + yPct + '%';
                var left = aLaIzquierda ? rect.left - pw - SEPARACION : rect.right + SEPARACION;
                var top = rect.top + (ch/2) - (ph/2);
                left = Math.max(BORDE, Math.min(left, window.innerWidth - pw - BORDE));
                top = Math.max(12, Math.min(top, window.innerHeight - ph - 12));
                preview.style.left = left + 'px';
                preview.style.top = top + 'px';
            }
            function initZoomThumbsDetalle() {
                document.querySelectorAll('.pbmh-thumb').forEach(function (img) {
                    var cell = img.closest('.pbmh-col-img') || img.closest('td') || img.parentElement;
                    if (!cell || cell.dataset.pbmhZoomAttachedThumb) return;
                    cell.dataset.pbmhZoomAttachedThumb = '1';
                    cell.style.position = 'relative';
                    img.draggable = false;
                    cell.addEventListener('dragstart', function (e) { e.preventDefault(); });
                    cell.addEventListener('mouseenter', function () { if (!dragging) showFotorama(cell, img); });
                    cell.addEventListener('mousemove', moveFotorama);
                    cell.addEventListener('mouseleave', hideFotorama);
                });
            }
            function attachFotorama() {
                var stage = document.querySelector('.fotorama__stage');
                if (!stage) return;
                // Evitar doble attach
                if (stage.dataset.pbmhZoomAttached) return;
                stage.dataset.pbmhZoomAttached = '1';
                // Evitar que el navegador inicie un "drag" nativo de la imagen: eso congela el
                // mousemove y hace que la lupa zoom vaya entrecortada.
                stage.addEventListener('dragstart', function (e) { e.preventDefault(); });
                stage.querySelectorAll('img').forEach(function (im) { im.draggable = false; });
                // Mientras el usuario arrastra (swipe) para cambiar de imagen, ocultamos la lupa;
                // al soltar se reanuda sola en el próximo mousemove.
                stage.addEventListener('pointerdown', function () { dragging = true; preview.style.display = 'none'; lens.style.display = 'none'; });
                window.addEventListener('pointerup', function () { dragging = false; });
                window.addEventListener('pointercancel', function () { dragging = false; });
                function getActiveImg() {
                    return stage.querySelector('.fotorama__active img')
                        || stage.querySelector('img.fotorama__img')
                        || stage.querySelector('img');
                }
                stage.addEventListener('mouseenter', function () {
                    if (dragging) return;
                    hovering = true;
                    var img = getActiveImg();
                    if (img) showFotorama(stage, img);
                });
                stage.addEventListener('mousemove', function () {
                    // La imagen activa puede cambiar (fotorama crossfade) — refrescar antes de mover
                    var img = getActiveImg();
                    if (img) { activeImg = img; if (!dragging) setSrc(img); }
                    moveFotorama.apply(null, arguments);
                });
                stage.addEventListener('mouseleave', function () { hovering = false; hideFotorama(); });
                // Rastrear el frame activo para que el zoom siempre use la imagen seleccionada
                var root = document.querySelector('.fotorama');
                if (root) {
                    var onShow = function () { var i = getActiveImg(); if (!i) return; activeImg = i; if (!dragging && hovering) showFotorama(stage, i); else setSrc(i); };
                    root.addEventListener('fotorama:showend', onShow);
                    root.addEventListener('fotorama:show', onShow);
                }
            }
            function setSrc(img) {
                if (!img) return false;
                var src = img.currentSrc || img.src;
                if (!src || src.includes('WhatsApp-Image')) { preview.style.display = 'none'; lens.style.display = 'none'; return false; }
                preview.style.backgroundImage = 'url("' + src.replace(/"/g, '&quot;') + '")';
                var box = activeBox || img.parentElement;
                var cw0 = box.clientWidth || img.clientWidth || 48;
                if (img.naturalWidth) {
                    var base = img.naturalWidth / cw0 * 1.35;
                    var maxScale = cw0 < 120 ? 10 : (cw0 < 300 ? 6 : 4.2);
                    zoom = Math.min(maxScale, Math.max(3.0, base));
                    preview.style.backgroundSize = (cw0 * zoom) + 'px ' + (box.clientHeight * zoom) + 'px';
                }
                return true;
            }
            // Fotorama se inicializa asincrónicamente, reintentar
            function initDetalleZoom() { attachFotorama(); initZoomThumbsDetalle(); }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () { setTimeout(initDetalleZoom, 600); setTimeout(initDetalleZoom, 1800); });
            } else { setTimeout(initDetalleZoom, 600); setTimeout(initDetalleZoom, 1800); }
            // Reintentar al abrir el tab Partes relacionadas (panel estaba oculto al inicio)
            document.addEventListener('click', function (e) { if (e.target.closest('.pbmh-tab[data-tab="partes"]')) setTimeout(initZoomThumbsDetalle, 80); });
            // También observar cambios de imagen activa
            var obs = new MutationObserver(function () { /* si cambia la imagen, actualizar src en próximo hover */ });
            var fotoramaRoot = document.querySelector('.fotorama');
            if (fotoramaRoot) obs.observe(fotoramaRoot, { childList: true, subtree: true });
        })();
    </script>
@endsection
