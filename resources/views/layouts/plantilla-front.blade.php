<!DOCTYPE html>
<html lang="es">
<?php
use App\Models\Contacto;
use App\Models\Imagen;
use App\Models\Carrito;
$contacto = Contacto::find(1);
$logo = Imagen::where('sector', 'logo')->get();
$logo = $logo [0];
$logo2 = Imagen::where('sector', 'logo2')->get()->first();
// Cart::destroy();
use CodersFree\Shoppingcart\Facades\Cart;
$cart = Cart::content();
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @yield('metadatos')
  <title>BMH</title>
  <link rel="icon" href="{{asset('imagenes/'.$logo->path)}}" type="image/x-icon">

  {{-- jquery --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

  {{-- BOOTSTRAP --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

  {{-- FONTS --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Quicksand:wght@300..700&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/styles2.css?v=76">
  
  {{-- FONTAWESOME --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">

   
     {{-- AOS --}}

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    
  {{-- FOTORAMA --}}
  <link href="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.js"></script>
  
  

  {{-- CAPTCHA --}}
<script src="https://www.google.com/recaptcha/api.js?render=6LfjNywqAAAAAKlfuav604AgIuv_qsZLXYtLqlPw"></script>

  {{-- MERCADOPAGO --}}
  <script src="https://sdk.mercadopago.com/js/v2"></script>
  
  {{-- IZITOAST --}}
    <link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>

    {{-- TOASTR --}}
   <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

  {{-- LOADING.IO --}}
  <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/gh/loadingio/ldbutton@latest/dist/index.min.css"/>

  {{-- SELECT2 --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  @yield('styles')
  <style>
    header.scrolled .nav-link:hover { color: rgba(255,255,255,.7) !important; }

    /* La barra inferior (link activo y hover) también debe ser blanca en
       estado scrolled. Se usa box-shadow para no desplazar el layout. */
    header.scrolled .nav-link.selectUrl {
        border-bottom-color: transparent !important;
        box-shadow: 0 2px 0 rgba(255,255,255,.7) !important;
    }
    header.scrolled .nav-link:hover {
        box-shadow: 0 2px 0 rgba(255,255,255,.7) !important;
    }
  </style>
</head>

<body>
  <div id='cortina' class='hidden'>
  </div>

  <div class="infoHeader">
    <div class="container-fluid" style="background: var(--Azul, #0098DA);height: 31px;">
        <div class="container">

            <div class="row justify-content-beetween">

                <div class="col-lg-6 pt-1 d-flex">
                  <div>
                    @if (isset($contacto->tel))
                        <a href="">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="none">
                            <path d="M14.563 11.971C14.0286 11.4326 12.7342 10.6467 12.1062 10.33C11.2884 9.91809 11.2211 9.88444 10.5783 10.362C10.1496 10.6807 9.8645 10.9654 9.3627 10.8584C8.86091 10.7514 7.7705 10.148 6.81572 9.1962C5.86094 8.24445 5.22251 7.1224 5.11515 6.62229C5.00779 6.12218 5.29722 5.84049 5.6129 5.41072C6.05782 4.80494 6.02416 4.70398 5.64386 3.88617C5.34737 3.25009 4.53864 1.96785 3.99815 1.43611C3.41996 0.864988 3.41996 0.965952 3.04741 1.12076C2.7441 1.24838 2.45312 1.4035 2.17811 1.58419C1.63963 1.94194 1.34078 2.23911 1.13178 2.6857C0.922787 3.1323 0.828891 4.1793 1.9082 6.14002C2.9875 8.10074 3.74473 9.10331 5.31203 10.6662C6.87933 12.2292 8.0845 13.0695 9.84632 14.0576C12.0258 15.2783 12.8618 15.0403 13.3097 14.8317C13.7577 14.623 14.0562 14.3269 14.4146 13.7884C14.5958 13.5138 14.7512 13.2232 14.879 12.9201C15.0342 12.5489 15.1352 12.5489 14.563 11.971Z" stroke="white" stroke-miterlimit="10"/>
                          </svg>
                            <span class="headerT">{{ $contacto->tel }} </span></a>
                    @endif
                  </div>

                  <div class="emailH">
                    @if (isset($contacto->mail))
                    <a  class='d-flex mailM' href="mailto:{{ $contacto->mail }}" target="_blank">
                      <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25" fill="none">
                        <path d="M20.0888 4.54846H4.16934C3.12267 4.54846 2.27417 5.39696 2.27417 6.44363V17.8147C2.27417 18.8613 3.12267 19.7098 4.16934 19.7098H20.0888C21.1354 19.7098 21.9839 18.8613 21.9839 17.8147V6.44363C21.9839 5.39696 21.1354 4.54846 20.0888 4.54846Z" stroke="white" stroke-width="1.51614" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5.3064 7.58069L12.129 12.8872L18.9516 7.58069" stroke="white" stroke-width="1.51614" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                        <span class="headerT mailMs">{{ $contacto->mail }} </span></a>
                @endif

                
                  </div>


                </div>
           

                <div class="col-lg-6 d-flex justify-content-end presupuestoM">
                  <a href="{{$contacto->tiktok}}" target="_blank">
                    <div><svg class="tik" xmlns="http://www.w3.org/2000/svg" width="14" height="16" viewBox="0 0 14 16" fill="none">
                      <path d="M12.0813 3.40515C11.9738 3.34961 11.8692 3.28872 11.7678 3.22272C11.473 3.02783 11.2027 2.7982 10.9628 2.53877C10.3624 1.85184 10.1382 1.15495 10.0556 0.667032H10.0589C9.98993 0.262037 10.0185 0 10.0228 0H7.2883V10.5737C7.2883 10.7156 7.2883 10.8559 7.28233 10.9946C7.28233 11.0118 7.28067 11.0278 7.27968 11.0463C7.27968 11.054 7.27968 11.0619 7.27802 11.0699V11.0759C7.2492 11.4552 7.12758 11.8217 6.92388 12.1431C6.72017 12.4644 6.44061 12.7308 6.1098 12.9187C5.76502 13.1149 5.37507 13.2178 4.9784 13.2173C3.70437 13.2173 2.67181 12.1784 2.67181 10.8954C2.67181 9.61243 3.70437 8.57357 4.9784 8.57357C5.21957 8.57335 5.45925 8.6113 5.68855 8.68602L5.69187 5.90179C4.99577 5.81188 4.28859 5.8672 3.61494 6.06427C2.94129 6.26134 2.31579 6.59588 1.77791 7.04679C1.30659 7.4563 0.910359 7.94492 0.607033 8.49065C0.491604 8.68966 0.056093 9.48937 0.00335402 10.7873C-0.0298152 11.524 0.191423 12.2872 0.296901 12.6026V12.6093C0.36324 12.795 0.620301 13.4289 1.03923 13.9632C1.37704 14.3919 1.77614 14.7684 2.2237 15.0807V15.0741L2.23033 15.0807C3.55412 15.9803 5.02185 15.9212 5.02185 15.9212C5.27593 15.9109 6.12705 15.9212 7.0936 15.4631C8.16563 14.9553 8.77594 14.1987 8.77594 14.1987C9.16584 13.7467 9.47587 13.2315 9.69274 12.6753C9.94018 12.0248 10.0228 11.2447 10.0228 10.9329V5.32332C10.0559 5.34322 10.4978 5.63544 10.4978 5.63544C10.4978 5.63544 11.1343 6.04343 12.1274 6.30911C12.8398 6.49818 13.7997 6.53798 13.7997 6.53798V3.82341C13.4634 3.8599 12.7805 3.75376 12.0813 3.40515Z" fill="white"/>
                    </svg></div>
                  </a>
                  <a href="{{$contacto->instagram}}" target="_blank">


                  <div><svg class="ig" xmlns="http://www.w3.org/2000/svg" width="23" height="19" viewBox="0 0 23 19" fill="none">
                    <path d="M15.9634 2.57279C16.8812 2.57553 17.7607 2.94135 18.4097 3.59034C19.0587 4.23933 19.4245 5.11877 19.4272 6.03658V12.9634C19.4245 13.8812 19.0587 14.7607 18.4097 15.4097C17.7607 16.0587 16.8812 16.4245 15.9634 16.4272H9.03658C8.11877 16.4245 7.23933 16.0587 6.59034 15.4097C5.94135 14.7607 5.57553 13.8812 5.57279 12.9634V6.03658C5.57553 5.11877 5.94135 4.23933 6.59034 3.59034C7.23933 2.94135 8.11877 2.57553 9.03658 2.57279H15.9634ZM15.9634 1.1875H9.03658C6.36953 1.1875 4.1875 3.36953 4.1875 6.03658V12.9634C4.1875 15.6305 6.36953 17.8125 9.03658 17.8125H15.9634C18.6305 17.8125 20.8125 15.6305 20.8125 12.9634V6.03658C20.8125 3.36953 18.6305 1.1875 15.9634 1.1875Z" fill="white"/>
                    <path d="M17.0025 6.03687C16.797 6.03687 16.5961 5.97593 16.4252 5.86175C16.2543 5.74758 16.1212 5.5853 16.0425 5.39543C15.9639 5.20557 15.9433 4.99665 15.9834 4.79509C16.0235 4.59353 16.1224 4.40839 16.2678 4.26307C16.4131 4.11776 16.5982 4.0188 16.7998 3.97871C17.0013 3.93861 17.2103 3.95919 17.4001 4.03783C17.59 4.11648 17.7523 4.24966 17.8664 4.42053C17.9806 4.5914 18.0415 4.7923 18.0415 4.9978C18.0418 5.13434 18.0152 5.26958 17.963 5.39578C17.9109 5.52198 17.8344 5.63664 17.7379 5.73319C17.6413 5.82973 17.5267 5.90626 17.4005 5.95837C17.2743 6.01048 17.139 6.03716 17.0025 6.03687ZM12.5 6.72933C13.048 6.72933 13.5838 6.89184 14.0395 7.19632C14.4951 7.50079 14.8503 7.93356 15.06 8.43988C15.2698 8.94621 15.3246 9.50336 15.2177 10.0409C15.1108 10.5784 14.8469 11.0721 14.4594 11.4596C14.0718 11.8472 13.5781 12.1111 13.0406 12.218C12.5031 12.3249 11.9459 12.27 11.4396 12.0603C10.9333 11.8506 10.5005 11.4954 10.196 11.0397C9.89156 10.5841 9.72904 10.0483 9.72904 9.50028C9.72983 8.76562 10.022 8.06127 10.5415 7.54179C11.061 7.0223 11.7653 6.73011 12.5 6.72933ZM12.5 5.34403C11.678 5.34403 10.8744 5.58779 10.1909 6.04449C9.50742 6.50118 8.9747 7.1503 8.66013 7.90976C8.34555 8.66921 8.26324 9.50489 8.42361 10.3111C8.58398 11.1174 8.97983 11.8579 9.56109 12.4392C10.1424 13.0205 10.8829 13.4163 11.6892 13.5767C12.4954 13.737 13.3311 13.6547 14.0905 13.3402C14.85 13.0256 15.4991 12.4929 15.9558 11.8094C16.4125 11.1259 16.6563 10.3223 16.6563 9.50028C16.6563 8.39798 16.2184 7.34082 15.4389 6.56137C14.6595 5.78192 13.6023 5.34403 12.5 5.34403Z" fill="white"/>
                  </svg></div>
                </a>
                <a href="{{$contacto->facebook}}" target="_blank">

                  <div><svg class="fb" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M17 8.5C17 3.80603 13.194 0 8.5 0C3.80603 0 0 3.80603 0 8.5C0 12.7424 3.10781 16.2589 7.17188 16.8972V10.9578H5.0131V8.5H7.17188V6.62734C7.17188 4.49741 8.44118 3.31993 10.3825 3.31993C11.3126 3.31993 12.2855 3.48614 12.2855 3.48614V5.57812H11.2132C10.1579 5.57812 9.82775 6.23308 9.82775 6.90625V8.5H12.185L11.8085 10.9578H9.82812V16.8979C13.8922 16.26 17 12.7435 17 8.5Z" fill="white"/>
                  </svg></div>
                </a>


                </div>

            </div>
        </div>

    </div>
</div>
  
<header class="{{ Route::is('home') ? 'home' : '' }}">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container h-100" style='position:relative;align-items:center !important;'>
    
        @if(Auth::guard('web')->check() && !isset($zonaclientes))
        <a class="navbar-brand" href="{{route('home')}}">
          <img id='logo1' src="{{asset('imagenes/'.$logo->path)}}" alt="Logo" >
        </a>
        @else
        <a class="navbar-brand" href="{{route('productos.home')}}">
          <img id='logo1' src="{{asset('imagenes/'.$logo->path)}}" alt="Logo" >
        </a>
        @endif
        <div class='mobile-flex'>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          {{-- <span class="search-icon ps-2" onclick="toggleBuscador()"><img src="imagenes/iconos/search.png" alt="" style='margin-right:5px;'></span> --}}
          
        </div>
        {{-- @if(Auth::guard('web')->check() && !isset($zonaclientes))<a href="{{route('productos.clientes')}}" style=''>@endif
        <div class='mobile user-mobile' onclick="{{Auth::guard('web')->check() && !isset($zonaclientes) ? '' : 'toggleCarrito()'}}">
          <svg xmlns="http://www.w3.org/2000/svg" height="25" width="25" viewBox="0 0 448 512">
            <path fill="#000000" d="M304 128a80 80 0 1 0 -160 0 80 80 0 1 0 160 0zM96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM49.3 464l349.5 0c-8.9-63.3-63.3-112-129-112l-91.4 0c-65.7 0-120.1 48.7-129 112zM0 482.3C0 383.8 79.8 304 178.3 304l91.4 0C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7L29.7 512C13.3 512 0 498.7 0 482.3z"/></svg>          
        </div> --}}
        @if(Auth::guard('web')->check() && !isset($zonaclientes))</a>@endif
        
        <div class=' barrasuperior1 container-nav d-flex flex-column h-100'>

          <div class=' d-flex flex-column justify-content-between h-100'>
            
            <div>
              <ul class="navbar-nav ml-auto {{ Auth::guard('web')->check() ? 'linkZona' : '' }}">
                @if(isset($zonaclientes) && Auth::guard('web')->check())
                  <li class="nav-item">
                    <a class="nav-link cartNav under active {{ Route::currentRouteName() == 'productos.clientes' || Route::currentRouteName() == 'productos.home'  ? 'selectUrl' : '' }}" href="{{route('productos.clientes')}}">Productos</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link under cartNav active {{ Route::currentRouteName() == 'carrito'  ? 'selectUrl' : '' }}" href="{{route('carrito')}}">Carrito</a>
                  </li>
                  @if(Auth::guard('web')->user()->rol == 'cliente')
                  <li class="nav-item">
                    <a class="nav-link under active cartNav historial-nav" href="{{route('cliente.historial', ['id' => Auth::guard('web')->user()->id ])}}">Historial</a>
                  </li>
                  @endif
                  <li class="nav-item">
                    <a class="nav-link under cartNav active {{ Route::currentRouteName() == 'lista' ? 'selectUrl' : '' }}" href="{{route('lista')}}">Lista de precios</a>
                  </li>

                  <li class="nav-item">
                    <a class="nav-link under cartNav active {{ Route::currentRouteName() == 'cliente.datos' ? 'selectUrl' : '' }}" href="{{route('cliente.datos', ['id' => Auth::guard('web')->user()->id])}}">Mis datos</a>
                  </li>

                  @if(Auth::guard('web')->user()->rol == 'vendedor')
                  <li class="nav-item">
                    <a class="nav-link under active clientes-nav" href="{{route('vendedor.clientes')}}">Clientes</a>
                  </li>
                  @endif

                @else
                @include('frontend.components/nav-item', ['titulo' => 'Home', 'ruta' => 'home'])
                @include('frontend.components/nav-item', ['titulo' => 'Nosotros', 'ruta' => 'nosotros'])
                @include('frontend.components.nav-item', ['titulo' => 'Productos', 'ruta' => ['categorias', 'productos', 'producto']])
                {{-- @include('frontend.components/nav-item', ['titulo' => 'Carrito', 'ruta' => Auth::guard('web')->check() ? 'carrito' : 'carrito.publico']) --}}
                  @include('frontend.components/nav-item', ['titulo' => 'Novedades', 'ruta' => ['novedades', 'novedad']])
                  @include('frontend.components/nav-item', ['titulo' => 'Contacto', 'ruta' => 'contacto'])
                @endif 
                
                <li class="nav-item" style='align-content:center;'>
                  @if(Auth::guard('web')->check())
                  <form method="POST" action="{{ route('logout', ['ventana' => 'home']) }}">
                    @csrf


                    <button class='green-btn-inverse zona-cliente-btn boton-home {{ Route::is('home') ? '' : 'boton-home isHome' }}'
                        onclick="event.preventDefault();this.closest('form').submit();">
                        <span style='margin-right:5px;'>Cerrar sesión</span>
                    </button>
                  </form>
                  @else
                    <button class='green-btn-inverse zona-cliente-btn {{ Route::is('home') ? '' : 'boton-home isHome' }}' onclick=toggleCarrito()>Zona Clientes</button>
                  @endif
                </li>


              </ul>
            </div>
          </div>
          

        </div>

 
        
        
        <div class="collapse navbar-collapse mt-2" id="navbarNav">
          <ul class="navbar-nav ml-auto">
            @if(isset($zonaclientes) && Auth::guard('web')->check())
            <li class="nav-item nav-mobile productos-nav px-2">
              <a class="nav-link active" href="{{route('productos.clientes')}}">Productos</a>
            </li>
            <li class="nav-item nav-mobile carrito-nav px-2">
              <a class="nav-link active" href="{{route('carrito')}}">Carrito</a>
            </li>
            
            {{-- <li class="nav-item nav-mobile historial-nav px-2">
              <a class="nav-link active" href="{{route('cliente.historial', ['id' => Auth::guard('web')->user()->id ])}}">Historial</a>
            </li> --}}
            <li class="nav-item nav-mobile novedades-nav px-2">
              <a class="nav-link active" href="{{route('lista')}}">Lista de precios</a>
            </li>
            <li class="nav-item nav-mobile datos-nav px-2">
              <a class="nav-link active" href="{{route('cliente.datos', ['id' => Auth::guard('web')->user()->id])}}">Mis datos</a>
            </li>

            <li class="mb-3">
              @if(isset($zonaclientes) && Auth::guard('web')->check())
              <form method="POST" action="{{ route('logout', ['ventana' => 'home']) }}">
                @csrf


                <button class='green-btn-inverse zona-cliente-btn boton-home'
                    onclick="event.preventDefault();this.closest('form').submit();">
                    <span style='margin-right:5px;'>Cerrar sesión</span>
                </button>
            </form>
              @else

                @if(Auth::guard('web')->check())<a href="{{route('productos.clientes')}}" style='margin-right:0;'>@endif
                  <button class='green-btn-inverse zona-cliente-btn {{ Route::is('home') ? '' : 'boton-home' }}'  {{Auth::guard('web')->check() ? '' : 'onclick=toggleCarrito()'}}>Zona Clientes</button>
                @if(Auth::guard('web')->check())</a>@endif
              
              @endif
            </li>

          
            @else
            <li class="nav-item nav-mobile nosotros-nav px-2">
              <a class="nav-link active" href="{{route('nosotros')}}">Nosotros</a>
            </li>
            <li class="nav-item nav-mobile categorias-nav px-2">
              <a class="nav-link active" href="{{route('categorias')}}">Productos</a>
            </li>
            {{-- <li class="nav-item nav-mobile descargas-nav px-2">
              <a class="nav-link active" href="{{route('descargas')}}">Descargas</a>
            </li> --}}
            <li class="nav-item nav-mobile novedades-nav px-2">
              <a class="nav-link active" href="{{route('novedades')}}">Novedades</a>
            </li>
            <li class="nav-item nav-mobile contacto-nav px-2">
              <a class="nav-link active " href="{{route('contacto')}}">Contacto</a>
            </li>

            <li class="mb-3">
              @if(Auth::guard('web')->check())
              <form method="POST" action="{{ route('logout', ['ventana' => 'home']) }}">
                @csrf
                <button class='green-btn-inverse zona-cliente-btn boton-home' onclick="event.preventDefault();this.closest('form').submit();">
                  <span style='margin-right:5px;'>Cerrar sesión</span>
                </button>
              </form>
              @else
              <div class='mobile user-mobile' onclick="toggleCarrito()">
                <button class='green-btn-inverse zona-cliente-btn boton-home'>Zona Clientes</button>
              </div>
              @endif
            </li>
            @endif
          </ul>
        </div>


        <div id='carrito' class="cuenta-hidden {{Auth::guard('web')->check() ? 'logueado' : ''}}">
          
            <div class='d-flex flex-column justify-content-between'>
              {{-- @if(Auth::guard('web')->check())
                <a class="dropdown-item cuenta-item" href="{{route('home')}}">Ir a zona pública</a>
                <a class="dropdown-item" href="{{route('cliente.datos', ['id' => Auth::guard('web')->user()->id])}}">Mis datos</a>
                <form method="POST" action="{{ route('logout',['ventana' => 'home']) }}">
                  @csrf
                  <a class="dropdown-item cuenta-item" href="" onclick="event.preventDefault();
                  this.closest('form').submit();" style='color:red;'>Cerrar sesión</a>
                </form>
              @else --}}
                @include('frontend.form-login')
              {{-- @endif --}}
              
            </div>
            
        </div> 
        
      </div>
    </nav>
  </header>

  <div class='loading-screen'>
    <div class='d-flex justify-content-center'>
      <img id='loading-img' src="{{asset('imagenes/ISO-1.png')}}" alt="" style='height:100%;width:40px;'>
    </div>
  </div>

  <main>
    <form id='buscador-mobile' action='{{route('search')}}' method='GET' style='background-color: #F3F3F3!important;'>
      @csrf
      <div class="form-group d-flex searchBar" >
        <button type="submit" class="btn btn-success rounded-0 d-flex flex-column justify-content-center" style='background-color:#F3F3F3;border:none;'><img style='max-height:20px;' class='' src="imagenes/iconos/search.png" alt="" ></button>
        <input required id="searchInput2"  type="text"  name='search' placeholder="Buscar" style='color:#000 !important; border-radius: 0 !important; background-color: #F3F3F3!important; border:none;
        font-weight: 400;
        line-height: normal;
        width:100%;'>
      </div>
    </form>
    @yield('content')
  </main>

  @php
    $anuncio = \App\Models\Anuncio::find(1);
  @endphp
  @if($anuncio && $anuncio->mostrar && (session('anuncio_pendiente') || request()->query('anuncio')))
    <div class="modal fade" id="anuncio" aria-hidden="true" aria-labelledby="anuncioLabel" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered anuncio-dialog">
        <div class="modal-content anuncio-card">
          <button type="button" class="anuncio-close" data-bs-dismiss="modal" aria-label="Cerrar">&times;</button>
          <div class="anuncio-media">
            <div class="anuncio-content">
              {!! $anuncio->contenido !!}
            </div>
          </div>
        </div>
      </div>
    </div>
    @php session()->forget('anuncio_pendiente'); @endphp
  @endif

  <footer>
    <div class='container d-flex flex-column' style='margin-bottom:56px;'>
        <div>
            <div class="container">
                <div class="row">
                  <div class='col-lg-3' style="padding-left: 0px;">
                      <div class='footer-logo'>
                        <a href="{{route('home')}}"><img style='max-width:100%;'class=' ' src="{{asset('imagenes/'.$logo2->path)}}" alt=""></a>
                        {{-- <div class='d-flex justify-content-center pb-5' style='padding-top:28px;'>
                          @if(isset($contacto->instagram))
                            <div style='padding-right:5px;'>
                              <a class="" href="{{$contacto->instagram}}" target="_blank"><img src="imagenes/iconos/instagram-w.png" alt=""></a>
                            </div>
                          @endif
                          @if(isset($contacto->facebook))
                            <div style='padding-right:5px;'>
                              <a class="" href="{{$contacto->facebook}}" target="_blank"><img src="imagenes/iconos/facebook-w.png" alt=""></a>
                            </div>
                          @endif
                        </div> --}}
                      </div>
                  </div>
                  
                  <div class="col-lg-4 secf" style='margin-bottom:35px; margin-top: 20px'>
                    <h4>Secciones</h4>
                    <div class='row'>
                      <div class='col-6'>
                          <ul>
                            <li><a href="{{route('home')}}">Nosotros</a></li>
                            <li><a href="{{route('categorias')}}">Productos</a></li>
                          </ul>
                      </div>
                      <div class='col-6'>
                          <ul>
                            <li><a href="{{route('novedades')}}">Novedades</a></li>
                            <li><a href="{{route('contacto')}}">Contacto</a></li>
                          </ul>
                      </div>
                    </div>
                  </div>

                  {{-- <div class="col-lg-3" style="margin-top: 20px">
                    <h4 class='mb-4'>Newsletter</h4>
                    <form id='newsletter' action='{{route('newsletter.crear')}}' method='POST'>
                      @csrf
                      <div class="form-group d-flex" style="height: 45px">
                        <input required type="email" class="form-control input-suscribe" name='mail' placeholder="Email" style='color: var(--Gris, #262123) !important; font-family: Roboto; font-size: 16px; font-style: normal; font-weight: 300; line-height: normal; letter-spacing: 0.96px; border-radius: 0 !important; background-color: #fff!important; border:none;'>
                        <button type="submit" class="btn btn-success rounded-0" style='background-color: #fff;border:none; padding-right: 20px'><svg xmlns="http://www.w3.org/2000/svg" width="18" height="16" viewBox="0 0 18 16" fill="none">
                          <path d="M16.3867 7.72949H1" stroke="#236644" stroke-width="2" stroke-linecap="round"/>
                          <path d="M9.65381 1L16.3872 7.89087L9.65381 14.0911" stroke="#236644" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg></button>
                      </div>
                    </form>

                    <h4 style="margin-top: 32px; margin-bottom:20px !important">REDES SOCIALES</h4>
                    <div class="d-flex">
                      <a href="{{$contacto->instagram}}"  target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                          <g clip-path="url(#clip0_2325_195)">
                            <path d="M14.1667 1.6665H5.83332C3.53214 1.6665 1.66666 3.53198 1.66666 5.83317V14.1665C1.66666 16.4677 3.53214 18.3332 5.83332 18.3332H14.1667C16.4678 18.3332 18.3333 16.4677 18.3333 14.1665V5.83317C18.3333 3.53198 16.4678 1.6665 14.1667 1.6665Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.3333 9.47476C13.4362 10.1683 13.3177 10.8766 12.9948 11.4989C12.6719 12.1213 12.161 12.6259 11.5347 12.9412C10.9084 13.2564 10.1987 13.3661 9.5065 13.2547C8.81428 13.1433 8.17481 12.8165 7.67904 12.3207C7.18327 11.825 6.85645 11.1855 6.74507 10.4933C6.63368 9.80106 6.7434 9.09134 7.05862 8.46507C7.37383 7.83881 7.8785 7.32788 8.50083 7.00496C9.12316 6.68205 9.83147 6.56359 10.525 6.66643C11.2324 6.77133 11.8874 7.10098 12.3931 7.60669C12.8988 8.11239 13.2284 8.76733 13.3333 9.47476Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.5833 5.4165H14.5917" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </g>
                          <defs>
                            <clipPath id="clip0_2325_195">
                              <rect width="20" height="20" fill="white"/>
                            </clipPath>
                          </defs>
                        </svg>
                      </a>

                      <a href="{{$contacto->facebook}}"  target="_blank">

                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                          <path d="M13.1885 10.125L13.6884 6.86742H10.5627V4.75348C10.5627 3.86227 10.9993 2.99355 12.3992 2.99355H13.8203V0.220078C13.8203 0.220078 12.5307 0 11.2978 0C8.72366 0 7.04109 1.56023 7.04109 4.38469V6.86742H4.17972V10.125H7.04109V18H10.5627V10.125H13.1885Z" fill="white"/>
                        </svg>
                      </a>

                    </div>

                  </div> --}}

                  <div class="col-lg-5 footerLeft" >
                    <h4>CONTACTO</h4>
                    <ul class="contact-list">
                      @if(isset($contacto->direccion))
                      <li class="ubiF" ><a class='d-flex' href="https://maps.app.goo.gl/bvt6zSHzT8txo6uV7" target="_blank">
                        <svg style="height: 16px; width: 23px" xmlns="http://www.w3.org/2000/svg" width="16" height="23" viewBox="0 0 16 23" fill="none">
                          <path d="M8 0C3.5835 0 0 3.2255 0 7.2C0 13.6 8 22.4 8 22.4C8 22.4 16 13.6 16 7.2C16 3.2255 12.4165 0 8 0ZM8 11.2C7.3671 11.2 6.74841 11.0123 6.22218 10.6607C5.69594 10.3091 5.28579 9.80931 5.04359 9.22459C4.80138 8.63986 4.73801 7.99645 4.86149 7.37571C4.98496 6.75497 5.28973 6.18479 5.73726 5.73726C6.18479 5.28973 6.75497 4.98496 7.37571 4.86149C7.99645 4.73801 8.63986 4.80138 9.22459 5.04359C9.80931 5.28579 10.3091 5.69594 10.6607 6.22218C11.0123 6.74841 11.2 7.3671 11.2 8C11.1991 8.84841 10.8616 9.6618 10.2617 10.2617C9.6618 10.8616 8.84841 11.1991 8 11.2Z" fill="white"/>
                        </svg>{{$contacto->direccion}}</a></li>
                      @endif
                
               
                      @if(isset($contacto->tel))
                      <li class="ubiF">
                        <a href="">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                            <path d="M16.5006 13.5383C15.8898 12.9229 14.4105 12.0248 13.6928 11.6629C12.7582 11.1921 12.6813 11.1536 11.9466 11.6994C11.4566 12.0637 11.1309 12.3891 10.5574 12.2667C9.9839 12.1444 8.73772 11.4548 7.64654 10.3671C6.55536 9.27937 5.82572 7.99703 5.70303 7.42548C5.58033 6.85392 5.91111 6.53199 6.27189 6.04083C6.78036 5.3485 6.7419 5.23311 6.30727 4.29848C5.96842 3.57154 5.04416 2.10612 4.42646 1.49841C3.76567 0.8457 3.76567 0.961088 3.33989 1.13801C2.99326 1.28386 2.66071 1.46114 2.34641 1.66764C1.73101 2.0765 1.38946 2.41612 1.15061 2.92652C0.911756 3.43692 0.804446 4.63348 2.03794 6.87431C3.27143 9.11513 4.13683 10.2609 5.92803 12.0471C7.71923 13.8333 9.09657 14.7937 11.1101 15.923C13.6009 17.318 14.5563 17.0461 15.0683 16.8076C15.5802 16.5692 15.9214 16.2307 16.331 15.6153C16.538 15.3015 16.7157 14.9693 16.8618 14.623C17.0391 14.1987 17.1545 14.1987 16.5006 13.5383Z" fill="white" stroke="white" stroke-miterlimit="10"/>
                          </svg>
                       {{$contacto->tel}} </a>
                      </li>
                      @endif
                      @if(isset($contacto->mail))
                      <li class="ubiF"><a class='d-flex' href="mailto:{{$contacto->mail}}" target="_blank">
                     

                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                          <path d="M17.6044 3.98584H3.65372C2.73649 3.98584 1.99292 4.7294 1.99292 5.64664V15.6114C1.99292 16.5286 2.73649 17.2722 3.65372 17.2722H17.6044C18.5216 17.2722 19.2652 16.5286 19.2652 15.6114V5.64664C19.2652 4.7294 18.5216 3.98584 17.6044 3.98584Z" fill="white" stroke="white" stroke-width="1.32864" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M4.65016 6.64307L10.629 11.2933L16.6079 6.64307" stroke="#0098DA" stroke-width="1.32864" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{$contacto->mail}}</a></li>
                      @endif
                      @if(isset($contacto->whatsapp))
                      <li class="ubiF"><a class='d-flex' href="https://wa.me/{{preg_replace("/[^0-9]/", "", $contacto->whatsapp)}}" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                          <path d="M7.25399 18.494L7.97799 18.917C9.19893 19.6291 10.5876 20.0029 12.001 20C13.5832 20 15.13 19.5308 16.4456 18.6518C17.7611 17.7727 18.7865 16.5233 19.392 15.0615C19.9975 13.5997 20.156 11.9911 19.8473 10.4393C19.5386 8.88743 18.7767 7.46197 17.6578 6.34315C16.539 5.22433 15.1136 4.4624 13.5617 4.15372C12.0099 3.84504 10.4013 4.00346 8.93952 4.60896C7.47771 5.21447 6.22828 6.23984 5.34923 7.55544C4.47018 8.87103 4.00099 10.4177 4.00099 12C3.99807 13.4139 4.37226 14.8029 5.08499 16.024L5.50699 16.748L4.85399 19.149L7.25399 18.494ZM2.00499 22L3.35699 17.032C2.46608 15.5049 1.99804 13.768 2.00099 12C2.00099 6.477 6.47799 2 12.001 2C17.524 2 22.001 6.477 22.001 12C22.001 17.523 17.524 22 12.001 22C10.2338 22.0029 8.49765 21.5352 6.97099 20.645L2.00499 22ZM8.39199 7.308C8.52599 7.298 8.66099 7.298 8.79499 7.304C8.84899 7.308 8.90299 7.314 8.95699 7.32C9.11599 7.338 9.29099 7.435 9.34999 7.569C9.64799 8.245 9.93799 8.926 10.218 9.609C10.28 9.761 10.243 9.956 10.125 10.146C10.065 10.243 9.97099 10.379 9.86199 10.518C9.74899 10.663 9.50599 10.929 9.50599 10.929C9.50599 10.929 9.40699 11.047 9.44499 11.194C9.45899 11.25 9.50499 11.331 9.54699 11.399L9.60599 11.494C9.86199 11.921 10.206 12.354 10.626 12.762C10.746 12.878 10.863 12.997 10.989 13.108C11.457 13.521 11.987 13.858 12.559 14.108L12.564 14.11C12.649 14.147 12.692 14.167 12.816 14.22C12.878 14.246 12.942 14.268 13.007 14.286C13.0742 14.3031 13.1449 14.2999 13.2102 14.2767C13.2756 14.2536 13.3326 14.2116 13.374 14.156C14.098 13.279 14.164 13.222 14.17 13.222V13.224C14.2203 13.1771 14.28 13.1415 14.3452 13.1196C14.4104 13.0977 14.4796 13.09 14.548 13.097C14.608 13.101 14.669 13.112 14.725 13.137C15.256 13.38 16.125 13.759 16.125 13.759L16.707 14.02C16.805 14.067 16.894 14.178 16.897 14.285C16.901 14.352 16.907 14.46 16.884 14.658C16.852 14.917 16.774 15.228 16.696 15.391C16.6425 15.5022 16.5716 15.6042 16.486 15.693C16.3851 15.7989 16.2746 15.8953 16.156 15.981C16.074 16.043 16.031 16.071 16.031 16.071C15.9066 16.1499 15.7788 16.2233 15.648 16.291C15.3905 16.4278 15.1062 16.5063 14.815 16.521C14.63 16.531 14.445 16.545 14.259 16.535C14.251 16.535 13.691 16.448 13.691 16.448C12.2692 16.074 10.9544 15.3735 9.85099 14.402C9.62499 14.203 9.41499 13.989 9.20099 13.776C8.31299 12.891 7.63999 11.936 7.23099 11.034C7.0227 10.5915 6.91024 10.11 6.90099 9.621C6.89723 9.01375 7.09605 8.42257 7.46599 7.941C7.53899 7.847 7.60799 7.749 7.72699 7.636C7.85299 7.516 7.93399 7.452 8.02099 7.408C8.13666 7.35003 8.26285 7.31602 8.39199 7.308Z" fill="#fff"/>
                        </svg>{{$contacto->whatsapp}}</a></li>
                      @endif
                    </ul>
                  </div>

             

                </div>
            
                
              </div>
        </div>
        
    </div>

    <div class='container-fluid' style='background-color:#241F21;height: 81px;'>
      <div class='container d-flex justify-content-between derechos p-0' style='background-color:#241F21;'>
        <div class='d-flex p-0 w-100 justify-content-between' style='margin-top:35px;'>
          <p class="text-center" style='font-weight:300;'>&copy; Copyright 2024 <span style='font-weight:700;'>BMH</span> Todos los derechos reservados</p>
          <style>
            #osole {
              cursor: pointer;
              transition: all 0.3s; /* Transición suave de colores */
            }

            #osole:hover {
              /* Agrega un efecto de brillo simulando iluminación */
              text-shadow: 0 0 10px #fff,
              0 0 20px #fff,
              0 0 30px #fff,
              0 0 40px #fff;
              opacity: 1;
            }

          </style>
          <p style='font-weight:200;'><a id='osole' href="https://osole.com.ar/" target="_blank">By <strong>Osole</strong></a></p>
        </div>
      </div>

    </div>
    
    {{--
        Los dos flotantes se excluyen entre sí: la esquina inferior derecha tiene
        un solo dueño según la zona.

        · Zona pública  → WhatsApp (el visitante todavía no es cliente).
        · Zona Clientes → Asesor IA (ya está autenticado, así que el asesor puede
          cotizarle con su condición comercial).

        `shouldRender()` es true sólo para clientes/vendedores autenticados, así
        que alcanza con negarlo para elegir cuál se muestra.
    --}}
    @php($bmhEnZonaClientes = app(\App\Services\Ai\AdvisorBootstrap::class)->shouldRender())

    @if ($bmhEnZonaClientes)
    @elseif (isset($contacto->whatsapp))
      <div class="whatsapp-container">
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contacto->whatsapp) }}" class="whatsapp-btn" target="_blank" rel="noopener">
          <img class='img-fluid' src="{{ asset('imagenes/wp-logo.png') }}" alt="WhatsApp">
        </a>
      </div>
    @endif

  </footer>
  
  
  
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
  <script>
  // Shim: Bootstrap 5 quitó $.fn.modal (jQuery). El layout legacy aún usa $('#aviso').modal('show')
  // Este shim lo polyfillea con la API nativa de Bootstrap 5 para no romper el sitio.
  (function() {
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.modal === 'undefined') {
      window.jQuery.fn.modal = function(action) {
        return this.each(function() {
          try {
            var instance = bootstrap.Modal.getOrCreateInstance(this);
            if (action === 'show') instance.show();
            else if (action === 'hide') instance.hide();
            else if (action === 'toggle') instance.toggle();
            else if (!action) instance.show();
          } catch(e) { console.warn('modal shim', e); }
        });
      };
      // También para eventos jQuery type `shown.bs.modal` ya funciona nativo, no hace falta más.
    }
  })();
  </script>
  <script src="js/carrito.js?v=4"></script>
  @yield('script')
  {{-- TAILWIND --}}
  {{-- <script src="https://cdn.tailwindcss.com"></script>  --}}

  {{-- Las notificaciones (toastr / iziToast) viven abajo a la derecha, en la
       misma esquina que el botón flotante del Asesor IA (z-index
       2147483000). Sin esto quedaban por detrás. Se sube su z-index por
       encima del asesor para que nunca se solapen. --}}
  <style>
      #toast-container,
      .iziToast-wrapper { z-index: 2147483100 !important; }
  </style>

  <script>
  
      $(document).ready(function() {
            if ($('#aviso').length) $('#aviso').modal('show');
        })

var _togglePwd = document.getElementById("toggle-password");
if (_togglePwd) _togglePwd.addEventListener("click", function() {
        var passwordField = document.getElementById("password");
        var icon = this.querySelector("svg");

        if (passwordField.type === "password") {
            // Cambiar a texto (mostrar la contraseña)
            passwordField.type = "text";
            icon.innerHTML = `<path d="M12.9833 10.0001C12.9833 11.6501 11.65 12.9834 10 12.9834C8.35 12.9834 7.01666 11.6501 7.01666 10.0001C7.01666 8.35006 8.35 7.01672 10 7.01672C11.65 7.01672 12.9833 8.35006 12.9833 10.0001Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path d="M9.99999 16.8916C12.9417 16.8916 15.6833 15.1583 17.5917 12.1583C18.3417 10.9833 18.3417 9.00831 17.5917 7.83331C15.6833 4.83331 12.9417 3.09998 9.99999 3.09998C7.05833 3.09998 4.31666 4.83331 2.40833 7.83331C1.65833 9.00831 1.65833 10.9833 2.40833 12.1583C4.31666 15.1583 7.05833 16.8916 9.99999 16.8916Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>`;
        } else {
            // Volver a ocultar la contraseña
            passwordField.type = "password";
            icon.innerHTML = `<path d="M12.9833 10.0001C12.9833 11.6501 11.65 12.9834 10 12.9834C8.35 12.9834 7.01666 11.6501 7.01666 10.0001C7.01666 8.35006 8.35 7.01672 10 7.01672C11.65 7.01672 12.9833 8.35006 12.9833 10.0001Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path d="M9.99999 16.8916C12.9417 16.8916 15.6833 15.1583 17.5917 12.1583C18.3417 10.9833 18.3417 9.00831 17.5917 7.83331C15.6833 4.83331 12.9417 3.09998 9.99999 3.09998C7.05833 3.09998 4.31666 4.83331 2.40833 7.83331C1.65833 9.00831 1.65833 10.9833 2.40833 12.1583C4.31666 15.1583 7.05833 16.8916 9.99999 16.8916Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>`;
        }
    });
  
     function enviar_pedido(e) {

            e.target.innerHTML = '<div class="loading-spinner"></div>  Procensando';
            document.getElementById('pedido').submit();
               iziToast.success({
                    title: 'Pedido realizado con éxito',
                    backgroundColor: '#DAF6D3',
                    titleColor: '#479831',
                    iconColor: '#479831',
                    progressBar: false,
                    icon: 'fa-solid fa-square-check',
                    position: 'bottomRight',
                });
       

        }


$(window).scroll(function() {
            if (window.innerWidth >= 992) {
                if ($(this).scrollTop() > 50) {
                    $('header').addClass('scrolled');
                    $('.infoHeader').addClass('esconder');
                    $('.nav-link').addClass('itemScroll');
                    $('.nav-link').removeClass('itemNavb');
                    $('.isHome').removeClass('boton-home');
                } else {
                    $('header').removeClass('scrolled');
                    $('.infoHeader').removeClass('esconder');
                    $('.nav-link').removeClass('itemScroll');
                    $('.nav-link').addClass('itemNavb');
                    $('.isHome').addClass('boton-home');
                }
            } else {
                // Mobile: el header no cambia de color al hacer scroll.
                $('header').removeClass('scrolled');
                $('.infoHeader').removeClass('esconder');
                $('.nav-link').removeClass('itemScroll');
                $('.nav-link').addClass('itemNavb');
                $('.isHome').addClass('boton-home');
            }
        });


    window.addEventListener('load', function() {
      $('#anuncio').modal('show');
      

      
      // Mostrar la página después de cargar todos los elementos
      document.querySelector('.loading-screen').style.opacity = '0'
      document.querySelector('main').style.display = 'block'
      document.querySelector('footer').style.display = 'block'
      document.querySelector('.loading-screen').style.zIndex = '-1'
      AOS.init({
        once:true,
        duration: 650,
        offset: 100 
        
      });
        
      var cortina = document.getElementById('cortina')
      var cuenta = document.getElementById('carrito')
      var zonaBtn = document.querySelector('.zona-cliente-btn')
      var userMobile = document.querySelector('.user-mobile')
      document.addEventListener('click', (event) => {
        if(!cortina.classList.contains('hidden') && !cuenta.contains(event.target) && !zonaBtn.contains(event.target) && !userMobile.contains(event.target) ){
          toggleCarrito()
        }
      })
    });



    function toggleCarrito(){
      var elemento = document.getElementById("carrito");
      elemento.classList.toggle("cuenta-hidden");
      elemento.classList.toggle("cuenta-visible");
      document.getElementById('cortina').classList.toggle('hidden')
      document.querySelector('body').classList.toggle('unscrollable')
    }

    toastr.options = {
      "closeButton": true,
      "debug": false,
      "newestOnTop": false,
      "progressBar": false,
      "positionClass": "toast-top-right",
      "preventDuplicates": false,
      "onclick": null,
      "showDuration": "300",
      "hideDuration": "1000",
      "timeOut": "5000",
      "extendedTimeOut": "1000",
      "showEasing": "swing",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut"
    }

    // Notificaciones por encima del Asesor IA (z-index 2147483000).
    if (window.iziToast) {
        iziToast.settings({ zindex: 2147483100 });
    }

    $(document).ready(function() {

 
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      $(".js-example-tags").select2({
        tags: true
      });

      $('.ingreso').submit(function(){
        event.preventDefault()
        const btn = document.getElementById('nosotros-mas');
        btn.innerHTML = '<div class="loading-spinner"></div>  Iniciando';
        var form = $(this)
        var formData = $(this).serialize();
        $.ajax({
          url: "{{ route('user.login', ['ventana' => 'home']) }}",
          type: 'POST',
          data: formData,
          success: function(response) {
              // Manejo de respuesta exitosa
              console.log(response);
              window.location.href = response;
          },
          error: function(xhr) {
              // Manejo de error
              console.error(xhr.responseText);
              var response = JSON.parse(xhr.responseText);

              if (response.errors) {
                  btn.innerHTML = 'Iniciar sesión';
                  // Muestra los nuevos mensajes de error
                  if (response.errors.username || response.errors.email) {
                    
                    var mensaje = ''
                    if(response.errors.username){
                      mensaje = response.errors.username[0]
                    } else {
                      mensaje = response.errors.email[0]
                    }
                    form.find('.username-error').text(mensaje).slideDown();
                  } else {
                    form.find('.username-error').slideUp();
                  }

                  if (response.errors.password) {
                      form.find('.password-error').text(response.errors.password[0]).slideDown();
                  } else {
                    form.find('.password-error').slideUp();
                  }
              }
          }
        });
      })

      // $('#newsletter').submit(function(event) {
      //     event.preventDefault(); // Evita que el formulario se envíe de forma predeterminada
      //     var formData = $(this).serialize(); // Serializa los datos del formulario
          
      //     $.ajax({
      //         url: "{{ route('newsletter.crear') }}",
      //         type: 'POST',
      //         data: formData,
      //         success: function(response) {
      //             // Manejo de respuesta exitosa
      //             // console.log(response.mensaje);
      //             document.querySelector('.input-suscribe').value= '¡Gracias por suscribirte!'
      //             document.querySelector('.suscribe').src = 'imagenes/iconos/done.png'
      //             iziToast.success({
      //               title: '¡Gracias por suscribirte!',
      //               backgroundColor: '#2B2E7B'
      //             });
      //         },
      //         error: function(xhr) {
      //             // Manejo de error
      //             console.error(xhr.responseText);
      //         }
      //     });
      // });
    });
    
      document.addEventListener("DOMContentLoaded", function () {
    const buscador = document.querySelector("input[name='buscadorPrincipal']");
    const checkboxNuevo = document.querySelector("input[name='nuevo']");
    const checkboxReconstruido = document.querySelector("input[name='reconstruido']");
    const codigoBMH = document.querySelector("input[name='codigoBMH']");
    const categoriaFiltro = document.querySelector("select[name='categoriaFiltro']");
    const marca = document.querySelector("select[name='marca']");
    const equivalenciaFiltro = document.querySelector("input[name='equivalenciaFiltro']");

    // Cargar la última búsqueda al cargar la página (con guards para páginas sin buscador)
    if (buscador && localStorage.getItem("ultimaBusqueda")) {
        buscador.value = localStorage.getItem("ultimaBusqueda");
    }
    if (codigoBMH && localStorage.getItem("codigoBMH")) {
        codigoBMH.value = localStorage.getItem("codigoBMH");
    }
    if (categoriaFiltro && localStorage.getItem("categoriaFiltro")) {
        categoriaFiltro.value = localStorage.getItem("categoriaFiltro");
    }
    if (marca && localStorage.getItem("marca")) {
        marca.value = localStorage.getItem("marca");
    }
    if (equivalenciaFiltro && localStorage.getItem("equivalenciaFiltro")) {
        equivalenciaFiltro.value = localStorage.getItem("equivalenciaFiltro");
    }

    // Cargar el estado de los checkboxes
    if (checkboxNuevo && localStorage.getItem("checkboxNuevo") === "true") {
        checkboxNuevo.checked = true;
    }
    if (checkboxReconstruido && localStorage.getItem("checkboxReconstruido") === "true") {
        checkboxReconstruido.checked = true;
    }

    if (buscador) buscador.addEventListener("input", function () {
        localStorage.setItem("ultimaBusqueda", buscador.value);
    });
    if (codigoBMH) codigoBMH.addEventListener("input", function () {
        localStorage.setItem("codigoBMH", codigoBMH.value);
    });
    if (categoriaFiltro) categoriaFiltro.addEventListener("change", function () {
        localStorage.setItem("categoriaFiltro", categoriaFiltro.value);
    });
    if (marca) marca.addEventListener("change", function () {
        localStorage.setItem("marca", marca.value);
    });
    if (equivalenciaFiltro) equivalenciaFiltro.addEventListener("input", function () {
        localStorage.setItem("equivalenciaFiltro", equivalenciaFiltro.value);
    });

    if (checkboxNuevo) checkboxNuevo.addEventListener("change", function () {
        localStorage.setItem("checkboxNuevo", checkboxNuevo.checked);
    });
    if (checkboxReconstruido) checkboxReconstruido.addEventListener("change", function () {
        localStorage.setItem("checkboxReconstruido", checkboxReconstruido.checked);
    });
});




    function toggleBuscador(){
      var buscador = document.getElementById('buscador-mobile')
      if($(buscador).is(':visible')){
        $(buscador).slideUp(300)
      } else {
        $(buscador).slideDown(300)
      }
    }


    function cargarAtributosCategoria(categoriaId) {
    const url = '{{ route('categoria.atributos', ['id' => ':id']) }}'.replace(':id', categoriaId);

    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        const atributosContainer = document.getElementById('atributosCategoria');
        atributosContainer.innerHTML = '';

        if (data.length > 0) {
            // Título 1
            const titulo = document.createElement('p');
            titulo.textContent = 'Caracteristica 1';
            titulo.classList.add('textCodigo');
            atributosContainer.appendChild(titulo);

            // Select 1
            const select = document.createElement('select');
            select.name = 'atributo';
            select.classList.add('selectFiltroProducto');

            const defaultOption = document.createElement('option');
            defaultOption.disabled = true;
            defaultOption.selected = true;
            defaultOption.textContent = 'Seleccionar';
            select.appendChild(defaultOption);

            data.forEach(atributo => {
                const option = document.createElement('option');
                option.value = atributo.value;
                option.textContent = atributo.nombre;
                select.appendChild(option);
            });

            atributosContainer.appendChild(select);

            // Input 1
            const inputTexto = document.createElement('input');
            inputTexto.type = 'text';
            inputTexto.name = 'valorAttr';
            inputTexto.classList.add('inputFiltroCodigo');
            atributosContainer.appendChild(inputTexto);

            // Título 2
            const tituloTwo = document.createElement('p');
            tituloTwo.textContent = 'Caracteristica 2';
            tituloTwo.classList.add('textCodigo');
            atributosContainer.appendChild(tituloTwo);

            // Select 2
            const selectTwo = document.createElement('select');
            selectTwo.name = 'atributoTwo';
            selectTwo.classList.add('selectFiltroProducto');

            const defaultOptionTwo = document.createElement('option');
            defaultOptionTwo.disabled = true;
            defaultOptionTwo.selected = true;
            defaultOptionTwo.textContent = 'Seleccionar';
            selectTwo.appendChild(defaultOptionTwo);

            data.forEach(atributo => {
                const optionTwo = document.createElement('option');
                optionTwo.value = atributo.value;
                optionTwo.textContent = atributo.nombre;
                selectTwo.appendChild(optionTwo);
            });

            atributosContainer.appendChild(selectTwo);

            // Input 2
            const inputTextoTwo = document.createElement('input');
            inputTextoTwo.type = 'text';
            inputTextoTwo.name = 'valorAttrTwo';
            inputTextoTwo.classList.add('inputFiltroCodigo');
            atributosContainer.appendChild(inputTextoTwo);

            // Ajustes de estilo
            $('.filtro, .filtro2').css({
                'height': 'auto',
                'padding-bottom': '25px'
            });

            // 🧠 Restaurar valores guardados
            const datosGuardados = JSON.parse(localStorage.getItem("atributosCategoria"));
            if (datosGuardados) {
                Object.keys(datosGuardados).forEach(name => {
                    const input = document.querySelector(`#atributosCategoria [name="${name}"]`);
                    if (input) input.value = datosGuardados[name];
                });
            }

            // 💾 Guardar al cambiar
            const inputs = document.querySelectorAll('#atributosCategoria select, #atributosCategoria input');
            inputs.forEach(input => {
                input.addEventListener("input", () => {
                    const datos = {};
                    document.querySelectorAll('#atributosCategoria select, #atributosCategoria input').forEach(i => {
                        datos[i.name] = i.value;
                    });
                    localStorage.setItem("atributosCategoria", JSON.stringify(datos));
                });
            });

        } else {
            atributosContainer.textContent = 'No hay atributos disponibles para esta categoría.';
        }
    })
    .catch(error => console.error('Error:', error));
}

// Ejecutar al cambiar select de categoría
var _selectCat = document.getElementById('selectCategoria');
if (_selectCat) _selectCat.addEventListener('change', function () {
    const categoriaId = this.value;
    if (categoriaId) {
        cargarAtributosCategoria(categoriaId);
    }
});

// Ejecutar al cargar la página si ya había categoría seleccionada
document.addEventListener("DOMContentLoaded", function () {
    const categoriaSelect = document.getElementById('selectCategoria');
    if (categoriaSelect && categoriaSelect.value) {
        cargarAtributosCategoria(categoriaSelect.value);
    }
});




        document.addEventListener('DOMContentLoaded', function() {
        // Si hay datos guardados en localStorage, completamos el formulario (con guards)
        var _inpType = document.getElementById('input_type');
        var _pwd = document.getElementById('password');
        var _remember = document.getElementById('remember_me');
        if (_inpType && _pwd && localStorage.getItem('username') && localStorage.getItem('password')) {
            _inpType.value = localStorage.getItem('username');
            _pwd.value = localStorage.getItem('password');
            if (_remember) _remember.checked = true;
        }

        var _form = document.querySelector('form');
        if (_form && _inpType && _pwd && _remember) _form.addEventListener('submit', function(e) {
            if (_remember.checked) {
                localStorage.setItem('username', _inpType.value);
                localStorage.setItem('password', _pwd.value);
            } else {
                localStorage.removeItem('username');
                localStorage.removeItem('password');
            }
        });
    });

  </script>
  
  @if(isset($ventana))
    <script>
      element = document.querySelector('.{{$ventana}}')
      element.classList.add('highlight');
    </script>
  @endif
</body>
</html>
