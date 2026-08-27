<!DOCTYPE html>
<html lang="es">
<?php
use App\Models\Imagen;
use App\Models\Pedido;
use Carbon\Carbon;
$logo2 = Imagen::where('sector', 'logo2')->get()->first();
$user = Auth::guard('admin')->user();
$nuevosPedidos = Pedido::where('created_at', '>=', Carbon::now()->subDay())->count();

?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Administrador</title>
  <link rel="icon" href="{{asset('imagenes/'.$logo2->path)}}" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">

  {{-- FONTS --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  {{-- <link rel="stylesheet" href="dashboard.css"> --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  
  
  {{-- IZITOAST --}}
    <link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>



  {{-- SELECT2 --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  @yield('styles')
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fff;
      overflow-y: scroll
    }

    .wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .navbar {
      flex-shrink: 0;
      height: 60px;
      box-shadow: 0 4px 2px -2px rgba(0, 0, 0, .15);
    }

    .container{
      display: flex;
      margin: 0;
      padding: 0;
    }

    .collapse{
      visibility: inherit !important;
    }

    .sidebar {
      width: 250px;
      background-color: #000;
      color: #fff;
      padding: 0;
      height: 100vh;
      position:fixed;
    }

    .sidebar .nav-link {
      color: #fff;
      padding: 0.5rem 1rem;
      background-color: #000;
      transition: background-color 0.2s ease;
    }

    .sidebar .nav-link.active {
      background-color: #495057;
      
    }

    .sidebar .nav-link:hover {
      background-color: #254F70;
      transition: none;
    }

    .sidebar .nav-link i {
      width: 1.5rem;
      margin-right: 0.5rem;
      text-align: center;
    }

    hr{
      margin: 0;
      /* margin-left:16px;
      margin-right:16px; */
    }

    .content {
      flex-grow: 1;
      padding: 0;
      margin-left: 250px;
    }
    .content-container{
      padding: 2rem;
      font-size: 14.5px;
    }
    a {
      color: inherit; /* Hereda el color del texto del elemento padre */
      text-decoration: none; /* Quita la subrayado del enlace */
      cursor: pointer; /* Cambia el cursor al puntero al pasar sobre el enlace */
      outline: none; /* Quita el contorno al enfocar el enlace */
    }

    a:hover {
      color: inherit; /* Hereda el color del texto del elemento padre */
      /* Otros estilos específicos según tus necesidades */
    }

    .accordion-item{
      
    }
    .accordion-body{
      font-weight: 300;
      font-size:14px;
      margin-left:27px;
      border-left: 2px solid rgba(255, 255, 255, 0.5);

      /* border-left: 2px solid rgb(235, 56, 51); */
    }
    .accordion-body .nav-link{
      padding-top:10px;
      padding-bottom:10px;
    }
    .accordion-button{
      font-weight: 300;
      font-size:14px;
      padding-top:13px !important;
      padding-bottom:13px !important;
    }
    .selected{
      background-color: #254F70 !important;
    }
    .multiple .accordion-button::after {
      background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%23ffffff' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-size: 1rem;
      width: 1rem;
      height: 1rem;
      content: "";
      transition: transform .2s ease-in-out;
    }

    .multiple .accordion-button[aria-expanded="true"]::after {
      transform: rotate(180deg);
    }

    h1{
      text-transform: uppercase !important;
      font-size:30px !important;
      font-weight: 600 !important;
      border-bottom: 1px solid #eee !important;
      padding-bottom: 10px !important;
    }
    
    h2 {
        font-size: 2em !important; 
        font-weight: bold !important; 
    }

    
    h3 {
        font-size: 1.5em !important; 
        font-weight: bold !important; 
    }

    
    h4 {
        font-size: 1.17em !important; 
        font-weight: bold !important; 
    }

    
    h5 {
        font-size: 1em !important; 
        font-weight: bold !important; 
    }

    
    h6 {
        font-size: 0.83em !important; 
        font-weight: bold !important; 
    }


    .card{
      margin-left:50px;
      margin-right:50px;
    }

    .table{
      border: 1px solid #dddddd;
    }

    td{
      overflow-wrap: break-word;
    }
    td:last-child {
      /* Aquí puedes definir el ancho de la última columna */
      width: 140px; /* Por ejemplo, 200px */
    }

    label{
      font-size:16px;
      font-weight: 350;
    }

    .recomendada{
      /* font-size:13px; */
      opacity: 0.7;
    }

    .loading-spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid gray;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
      display: inline-block;
      vertical-align: middle;
    }
    
    .search-container {
      display: flex;
      align-items: center;
      border: 1px solid #ccc;
      border-radius: 10px;
      overflow: hidden;
      max-width: 400px;
      max-height: 40px;
    }

    .search-input {
      flex: 1;
      padding: 10px;
      border: none;
      outline: none;
      font-size: 14px;
    }

    .search-btn {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 10px 15px;
      border-radius: 0 10px 10px 0;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .search-btn:hover {
      background-color: #0056b3;
    }

    .search-btn i {
      font-size: 16px;
    }

    .text-sm{
      visibility: 
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 991.98px) {
      .sidebar {
        position: fixed;
        top: 60px;
        bottom: 0;
        left: -250px;
        width: 250px;
        padding: 1rem;
        background-color: #343a40;
        transition: left 0.3s ease-in-out;
        z-index: 1000;
        
      }

      .sidebar.show {
        left: 0;
      }


      .navbar-toggler {
        display: block;
      }
      .content{
        margin:0;
      }
    }
    .input-hidden{
        display: none;
    }
    .input-selected{
        background-color: #2B2E7B;
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class='container-fluid' style='max-width:100%;'>

      <div class='row d-flex'>
        
        <!-- Barra lateral izquierda -->
        <nav class="sidebar col-3 collapse show">
          <div style='padding:30px;height:150px;width:100%;'>
            <div style='background-image: url("{{asset('imagenes/'.$logo2->path)}}"); background-size: contain; background-position: center;background-repeat:no-repeat;
            width:100%;
            height:100%;'>

            </div>
          </div>
          <hr style='margin-top:0;'>
          <div class="accordion-flush" id="accordionExample">
            <div class="accordion-item multiple">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                  <i class="fas fa-home"></i> Home
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse {{isset($home_slider) || isset($anuncio) || isset($logo) ? 'show' : ''}}" aria-labelledby="headingOne">
                <div class="accordion-body">
                  <a class='nav-link menu-item' href="{{route('dashboard.home-slider')}}">Slider</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.logo')}}">Contenido</a>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="nosotros">
                <a class='menu-item' href="{{route('dashboard.nosotros')}}">
                <button class="accordion-button nav-link" type="button">
                  <i class="fas fa-users"></i> Nosotros
                </button>
                </a>
              </h2>
            </div>

            <div class="accordion-item multiple">
              <h2 class="accordion-header" id="catalogo">
                <button class="accordion-button collapsed nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseOne">
                  <i class="fa-solid fa-gear"></i> Catálogo
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse {{isset($categorias) || isset($productos) || isset($medidas) || isset($repuestos) ? 'show' : ''}}" aria-labelledby="headingOne">
                <div class="accordion-body">
                  <a class='nav-link menu-item' href="{{route('dashboard.categorias')}}">Categorías</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.productos')}}">Productos</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.caracteristicas')}}">Caracteristicas</a>

                  {{-- <a class='nav-link menu-item' href="{{route('dashboard.medidas')}}">Medidas</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.repuestos')}}">Repuestos</a> --}}

                  {{-- <a class='nav-link menu-item' href="{{route('dashboard.ofertas')}}">Ofertas</a> --}}
                </div>
              </div>
            </div>

            {{-- <div class="accordion-item">
              <h2 class="accordion-header" id="descargas">
                <a class='menu-item' href="{{route('dashboard.descargas')}}">
                  <button class="accordion-button nav-link" type="button">
                    <i class="fa-solid fa-file-arrow-down"></i> Descargas
                  </button>
                </a>
              </h2>
            </div> --}}

            <div class="accordion-item">
              <h2 class="accordion-header" id="novedades">
                <a class='menu-item' href="{{route('dashboard.novedades')}}">
                  <button class="accordion-button nav-link" type="button">
                    <i class="fa-regular fa-newspaper"></i> Novedades
                  </button>
                </a>
              </h2>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="contacto">
                <a class='menu-item' href="{{route('dashboard.contacto')}}">
                  <button class="accordion-button nav-link" type="button">
                    <i class="fas fa-phone"></i> Contacto
                  </button>
                </a>
              </h2>
            </div>

            <hr>

            <div class="accordion-item">
              <h2 class="accordion-header" id="ventas">
                <a class='menu-item' href="{{route('ventas')}}">
                  <button class="accordion-button nav-link" type="button">
                    <i class="fa-solid fa-bag-shopping"></i> Pedidos
                  </button>
                </a>
              </h2>
            </div>

            <div class="accordion-item multiple">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  <i class="fa-solid fa-cart-shopping"></i> Carrito
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse {{isset($informacion) || isset($lista) || isset($impuestos) || isset($bonificaciones) ? 'show' : ''}}" aria-labelledby="headingTwo">
                <div class="accordion-body">
                  {{-- <a class='nav-link menu-item' href="{{route('dashboard.bonificaciones')}}">Bonificaciones</a> --}}
                  <a class='nav-link menu-item' href="{{route('dashboard.informacion')}}">Información</a>
                  {{-- <a class='nav-link menu-item' href="{{route('zonas.postales')}}">Zonas postales</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.cp')}}">Códigos postales</a> --}}
                  {{-- <a class='nav-link menu-item' href="{{route('dashboard.impuestos')}}">Impuestos</a> --}}
                  <a class='nav-link menu-item' href="{{route('dashboard.lista')}}">Lista de precios</a>
                  <a class='nav-link menu-item' href="{{route('dashboard.anuncio')}}">Anuncio</a>

                </div>
              </div>
            </div>

            {{-- <div class="accordion-item multiple">
              <h2 class="accordion-header" id="mails">
                <button class="accordion-button collapsed nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#mailscontrols" aria-expanded="false" aria-controls="mailscontrols">
                  <i class="fas fa-envelope"></i> Mails
                </button>
              </h2>
              <div id="mailscontrols" class="accordion-collapse collapse {{isset($mails) || isset($newsletter) ? 'show' : ''}}" aria-labelledby="mails">
                <div class="accordion-body">
                  <a class='nav-link menu-item' href="{{route('dashboard.mails')}}">Editar contenido</a>
                  <a class='nav-link menu-item' href="{{route('newsletter')}}">Envío masivo</a>
                </div>
              </div>
            </div> --}}

            <div class="accordion-item">
              <h2 class="accordion-header" id="metadatos">
                <a class='menu-item' href="{{route('metadatos')}}">
                  <button class="accordion-button nav-link" type="button">
                    <i class="fas fa-table"></i> Metadatos
                  </button>
                </a>
              </h2>
            </div>
            
            {{-- <div class="accordion-item">
              <h2 class="accordion-header" id="vendedores">
                <a class='menu-item' href="{{route('dashboard.vendedores')}}">
                <button class="accordion-button nav-link" type="button">
                  <i class="fas fa-users"></i> Vendedores
                </button>
                </a>
              </h2>
            </div> --}}

            <div class="accordion-item">
              <h2 class="accordion-header" id="Clientes">
                <a class='menu-item' href="{{route('dashboard.clientes')}}">
                <button class="accordion-button nav-link" type="button">
                  <i class="fas fa-users"></i> Clientes
                </button>
                </a>
              </h2>
            </div>

            @if($user->rol == 'administrador')
            <div class="accordion-item multiple">
              <h2 class="accordion-header" id="headingUsuarios">
                <button class="accordion-button nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsuarios" aria-expanded="false" aria-controls="collapseUsuarios">
                  <i class="fas fa-user"></i> Usuarios
                </button>
              </h2>
              <div id="collapseUsuarios" class="accordion-collapse collapse {{isset($crear_usuario) || isset($editar_usuarios) ? 'show' : ''}}" aria-labelledby="headingUsuarios">
                <div class="accordion-body">
                  <a class='nav-link menu-item' href="{{route('nuevo.usuario')}}">Crear usuario</a>
                  <a class='nav-link menu-item' href="{{route('usuarios')}}">Editar usuarios</a>
                </div>
              </div>
            </div>
            @endif

            
            <div class="accordion-item pl-6">
              <h2 class="accordion-header" id="Clientes">
                  @if($nuevosPedidos > 0)
                  <div class="d-flex justify-content-center align-items-center ml-4 me-3">
                    <span class="badge bg-success" style="font-size: 14px; padding: 8px 15px; border-radius: 20px;">
                      {{ $nuevosPedidos }} pedidos recientes
                    </span>
                  </div>
                @endif
              </h2>
            </div>



  



          </div>
        </nav>

        
        
    
        <!-- Contenido principal -->
        <div class="content col-12 col-md-9">

          {{-- BARRA SUPERIOR --}}
          <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
              <div class='d-flex justify-content-between w-100'>
                <div class='d-flex'>
                  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                  </button>
                </div>
                
                <div class="dropdown">
                  <div class="bdropdown-toggle d-flex justify-content-end" type="button" data-bs-toggle="dropdown" aria-expanded="false" style='align-items:center;width:160px;height:30px;opacity:0.5;'>
                    {{Auth::guard('admin')->user()->username}}
                    <div style='background-image: url("{{asset('imagenes/iconos/user-svgrepo.png')}}"); background-size: contain; background-position: center;background-repeat:no-repeat;
                    width:40px;
                    height:100%;'></div>
                  </div>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{route('home')}}" target='_blank'>Ir al sitio</a></li>
                    <li>
                      <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <a class="dropdown-item" href="{{route('home')}}" onclick="event.preventDefault();
                        this.closest('form').submit();" style='color:red;'>Cerrar sesión</a>
                      </form>
                    </li>
                  </ul>
                </div>
                
                
              </div>
              
              
            </div>
            
          </nav>
          
          <div class='content-container'>
            @yield('content')
          </div>
          
        </div>

      </div>

        
    </div>

    
  </div>

  
  
<style>
    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 40px; }
    .btn-toggle-password {
        position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
        background: none; border: none; padding: 0; cursor: pointer; color: #6b7280;
        display: flex; align-items: center;
    }
    .btn-toggle-password svg { width: 18px; height: 18px; }
    .btn-toggle-password .eye-off { display: none; }
    .btn-toggle-password.showing .eye-open { display: none; }
    .btn-toggle-password.showing .eye-off { display: block; }
</style>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>

<script>

  function toggleClase(car){
    document.querySelector('#car' + car).classList.toggle('input-hidden')
    document.querySelector('#btn' + car).classList.toggle('input-selected')
  }

  $('#summernote').summernote({
    placeholder: '',
    tabsize: 2,
    height: 120,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      // ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      // ['table', ['table']],
      // ['insert', ['link', 'picture', 'video']],
      ['view', ['fullscreen', 'codeview', 'help']]
    ]
  });

  // Obtener una referencia al elemento que quieres modificar
  const myElement = document.querySelector('.sidebar');

  // Función para aplicar la clase adecuada según el tamaño de pantalla
  function adjustElementClass() {
    // Obtener el ancho de la ventana
    const windowWidth = window.innerWidth;

    // Verificar el tamaño de pantalla y aplicar la clase correspondiente
    if (windowWidth < 992) {
      myElement.classList.remove('show');
    } else {
      myElement.classList.add('show');
    }
  }

  // Llamar a la función cuando se carga la página
  window.addEventListener('load', adjustElementClass);

  // Llamar a la función cuando se redimensiona la ventana
  window.addEventListener('resize', adjustElementClass);

  // Botón submit formularios
  $(document).ready(function() {
    $(".js-example-tags").select2({
      tags: true
    });
      
    iziToast.settings({
      timeout: 5000,
      resetOnHover: true,
      position: 'topRight',
      animateInside: false,
      theme:'dark',
      progressBarColor:'#fff',
      pauseOnHover:true,
      resetOnHover:false
    });
    
    
    $('.submit').click(function(e) {
      e.preventDefault()
      var formId = $(this).data('form-id'); // Obtiene el ID del formulario desde el atributo de datos del botón
      var form = $('#' + formId)[0]; // Obtiene el formulario correspondiente
      // console.log(form)

      // Si tiene summernote
      var summernote = true;
      var summernoteFields = document.querySelectorAll('.summernote-text');
      if(summernoteFields){
        for (var i = 0; i < summernoteFields.length; i++) {
          var content = $(summernoteFields[i]).summernote('code').trim(); // Obtener el contenido y eliminar espacios en blanco al inicio y al final
          if (!content) {
              summernote = false
              iziToast.warning({
                title: 'Datos incompletos',
                message:'Por favor complete todos los campos',
                backgroundColor: '#9F0D12'
              });
              return;
          }
        }
      }

      // Verifica si el formulario es válido
      var check = true
      if (form.checkValidity()) {
        if(document.getElementById('table-dimensiones') != null){
          var precioInput = form.querySelectorAll("input[name='precio[]']");
          if(precioInput.length != 0){
            precioInput.forEach(element => {
              var precioValue = element.value.trim();
              // Verificar si el campo de precio contiene un valor numérico
              if (!isNaN(parseFloat(precioValue)) && isFinite(precioValue)) {
                console.log('')
              } else {
                check = false
              }
            });
            if(!check){
              iziToast.warning({
                title: 'Error campo numérico',
                message:'El campo de precio debe contener un valor numérico válido. Utiliza punto para los decimales y sin separador de mil.',
                backgroundColor: '#EB3833',
                timeout:10000,
                maxWidth:400,
              });
            }else{
              e.target.innerHTML = '<div class="loading-spinner"></div>';
            }
              
          } else {
            check = false
            iziToast.warning({
              title: 'Ninguna dimensión',
              message:'Por favor ingrese al menos una dimensión del producto',
              backgroundColor: '#2B2E7B'
            });
          }
        } else {
          e.target.innerHTML = '<div class="loading-spinner"></div>';
        }
        

        if(check && summernote){
          form.submit()
        }
        
      } else {
        // Si el formulario no es válido, realiza aquí alguna acción, como mostrar un mensaje de error
        iziToast.warning({
          title: 'Datos incompletos',
          message:'Por favor complete todos los campos',
          backgroundColor: '#9F0D12'
        });
      }
    });
    
    var currentPath = 'https://d1.osole.com.ar'+ window.location.pathname; // Obtiene la ruta actual
    $('.menu-item').each(function() {
        if ($(this).attr('href') == currentPath) {
            if ($(this).hasClass('nav-link')) {
                $(this).addClass('selected');
            } else {
                $(this).find('.nav-link').addClass('selected');
            }
        }
    });
    
    
  });

    function toggleClientePasswordVisibility(btn) {
        var input = btn.parentElement.querySelector('input');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.classList.add('showing');
        } else {
            input.type = 'password';
            btn.classList.remove('showing');
        }
    }
</script>



@yield('script')

</html>
