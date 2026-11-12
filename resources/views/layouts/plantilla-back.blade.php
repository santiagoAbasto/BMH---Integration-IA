<!DOCTYPE html>
<html lang="es" class="adm">
<?php
use App\Models\Imagen;
use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$logo2 = Imagen::where('sector', 'logo2')->first();
$user = Auth::guard('admin')->user();
$nuevosPedidos = Pedido::where('created_at', '>=', Carbon::now()->subDay())->count();
$esAdministrador = $user?->rol == 'administrador';

// Íconos de la sidebar que no trae osole/ui (trazos de 24×24, como los del paquete).
$icono = fn (string $trazos) => new HtmlString('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$trazos.'</svg>');

// Menú del panel: los mismos links que antes, agrupados por tarea.
$secciones = [
    ['items' => [
        ['label' => 'Inicio', 'href' => route('dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
    ]],
    ['title' => 'Catálogo', 'items' => [
        ['label' => 'Productos', 'href' => route('dashboard.productos'), 'icon' => $icono('<path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5z"/><path d="m3 7.5 9 4.5 9-4.5M12 12v9"/>'), 'active' => request()->routeIs('dashboard.productos*', 'producto.create', 'producto.edit') ? 'page' : null],
        ['label' => 'Categorías', 'href' => route('dashboard.categorias'), 'icon' => 'layers', 'active' => request()->routeIs('dashboard.categorias', 'categoria.create', 'categoria.show') ? 'page' : null],
        ['label' => 'Imágenes', 'href' => route('dashboard.imagenes'), 'icon' => 'image'],
        ['label' => 'Características', 'href' => route('dashboard.caracteristicas'), 'icon' => $icono('<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>')],
    ]],
    ['title' => 'Ventas', 'items' => [
        ['label' => 'Pedidos', 'href' => route('ventas'), 'icon' => $icono('<path d="M5.5 7h13l1 14h-15z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>'), 'active' => request()->routeIs('ventas', 'pedido.datos') ? 'page' : null],
        ['label' => 'Clientes', 'href' => route('dashboard.clientes'), 'icon' => 'users'],
        ['label' => 'Carrito', 'icon' => $icono('<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2.5 3.5h3l2.4 11.2a1 1 0 0 0 1 .8h9.3a1 1 0 0 0 1-.8L21 7H6.4"/>'), 'items' => [
            ['label' => 'Información', 'href' => route('dashboard.informacion')],
            ['label' => 'Lista de precios', 'href' => route('dashboard.lista')],
            ['label' => 'Anuncio', 'href' => route('dashboard.anuncio')],
        ]],
    ]],
    ['title' => 'Contenido del sitio', 'items' => [
        ['label' => 'Home', 'icon' => 'home', 'items' => [
            ['label' => 'Slider', 'href' => route('dashboard.home-slider')],
            ['label' => 'Contenido', 'href' => route('dashboard.logo')],
        ]],
        ['label' => 'Nosotros', 'href' => route('dashboard.nosotros'), 'icon' => $icono('<path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16M15 9h4a1 1 0 0 1 1 1v11M3 21h18M8 8h3M8 12h3M8 16h3"/>')],
        ['label' => 'Novedades', 'href' => route('dashboard.novedades'), 'icon' => $icono('<path d="M4 5h13v14a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2z"/><path d="M17 9h3v10a2 2 0 0 1-2 2M8 9h5M8 13h5M8 17h3"/>'), 'active' => request()->routeIs('dashboard.novedades', 'novedad.crear', 'novedad.editar') ? 'page' : null],
        ['label' => 'Contacto', 'href' => route('dashboard.contacto'), 'icon' => $icono('<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>')],
        ['label' => 'Extras', 'icon' => $icono('<path d="M12 3a9 9 0 1 0 0 18c1 0 1.5-.8 1.5-1.6 0-.9-.6-1.4-.6-2.2 0-.9.7-1.6 1.6-1.6H17a4 4 0 0 0 4-4c0-4.7-4-8.6-9-8.6z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10.5" cy="7" r="1"/><circle cx="15.5" cy="7.5" r="1"/>'), 'items' => [
            ['label' => 'Favicon', 'href' => route('dashboard.extras.favicon')],
            ['label' => 'Barra superior', 'href' => route('dashboard.extras.barra-superior')],
            ['label' => 'Header', 'href' => route('dashboard.extras.header')],
            ['label' => 'Footer', 'href' => route('dashboard.extras.footer')],
            ['label' => 'Imagen por defecto', 'href' => route('dashboard.extras.imagen-por-defecto')],
        ]],
        ['label' => 'Metadatos', 'href' => route('metadatos'), 'icon' => 'search'],
    ]],
];

if ($esAdministrador) {
    $secciones[] = ['title' => 'Administración', 'items' => [
        ['label' => 'Pruebas IA', 'href' => route('admin.ai-tests.index'), 'icon' => $icono('<path d="M9 3h6M10 3v6L4.5 18.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>')],
        ['label' => 'Usuarios', 'icon' => $icono('<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/>'), 'items' => [
            ['label' => 'Crear usuario', 'href' => route('nuevo.usuario')],
            ['label' => 'Editar usuarios', 'href' => route('usuarios')],
        ]],
    ]];
}

// Miga de la barra superior y <title>: la sección y el link del menú que corresponden a esta página.
$rutaActual = '/'.trim(request()->path(), '/');
$miga = null;
foreach ($secciones as $seccion) {
    foreach ($seccion['items'] as $item) {
        foreach (isset($item['items']) ? $item['items'] : [$item] as $link) {
            $ruta = '/'.trim((string) parse_url($link['href'], PHP_URL_PATH), '/');
            if (! empty($link['active']) || $ruta === $rutaActual) {
                $miga = ['seccion' => isset($item['items']) ? $item['label'] : ($seccion['title'] ?? null), 'pagina' => $link['label']];
                break 3;
            }
        }
    }
}
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ $miga ? $miga['pagina'].' · ' : '' }}Administrador BMH</title>
  @include('layouts.partials.favicon')

  {{-- CSS de terceros (Bootstrap, Summernote, Select2, iziToast) en la capa «legacy», por debajo
       de los componentes de osole/ui (capa «osole») y del tema del panel (sin capa):
       resources/css/admin.css. Mismo esquema que el sitio público. --}}
  <style>
    @layer legacy, osole;
    @import url("https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css") layer(legacy);
    @import url("https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css") layer(legacy);
    @import url("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css") layer(legacy);
    @import url("https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css") layer(legacy);
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">

  {{-- FONTS: las del sitio (Montserrat para títulos, Inter para texto) --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&family=Montserrat:wght@500..800&display=swap" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  @vite(['resources/css/admin.css', 'resources/js/admin.js'])

  {{-- Estilos propios de cada pantalla: después del tema, así le ganan en un empate. --}}
  @yield('styles')
</head>
<body class="@yield('body-class')">
  <a class="adm-skip" href="#adm-contenido">Saltar al contenido</a>

  <div class="adm-shell">
    <x-osole::admin-sidebar.v1
      class="adm-sidebar"
      :sections="$secciones"
      :home-href="route('home')"
      home-label="Ir al sitio de BMH"
      nav-label="Menú de administración"
      :user="$user ? ['name' => $user->name ?: $user->username, 'role' => $esAdministrador ? 'Administrador' : 'Usuario'] : null"
      :logout-action="route('admin.logout')"
    >
      <x-slot:logo>
        @if ($logo2?->path)
          <img src="{{ asset('imagenes/'.$logo2->path) }}" alt="" width="160" height="44">
        @else
          <span class="adm-sidebar__marca">BMH</span>
        @endif
      </x-slot:logo>
    </x-osole::admin-sidebar.v1>

    <div class="adm-main">
      <header class="adm-topbar">
        <button type="button" class="adm-topbar__menu" popovertarget="osole-admin-sidebar" aria-label="Abrir menú">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <nav class="adm-topbar__miga" aria-label="Estás en">
          <ol>
            <li><a href="{{ route('dashboard') }}">Panel</a></li>
            @if ($miga && $miga['seccion'])
              <li><span>{{ $miga['seccion'] }}</span></li>
            @endif
            @if ($miga && $miga['pagina'] !== 'Inicio')
              <li><span aria-current="page">{{ $miga['pagina'] }}</span></li>
            @endif
          </ol>
        </nav>

        <div class="adm-topbar__acciones">
          @if ($nuevosPedidos > 0)
            <a class="adm-topbar__pedidos" href="{{ route('ventas') }}">
              <span class="adm-topbar__pulso" aria-hidden="true"></span>
              {{ $nuevosPedidos }} {{ $nuevosPedidos === 1 ? 'pedido reciente' : 'pedidos recientes' }}
              <span class="visually-hidden">(últimas 24 horas)</span>
            </a>
          @endif
          <a class="adm-topbar__sitio" href="{{ route('home') }}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
            <span>Ver sitio</span>
            <span class="visually-hidden">(se abre en otra pestaña)</span>
          </a>
        </div>
      </header>

      <main id="adm-contenido" class="adm-content" tabindex="-1">
        @yield('content')
      </main>
    </div>
  </div>

<style>
    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 40px; }
    .btn-toggle-password {
        position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
        background: none; border: none; padding: 0; cursor: pointer; color: #5b6575;
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
