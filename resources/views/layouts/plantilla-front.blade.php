<!DOCTYPE html>
<html lang="es">
<?php
use App\Models\Contacto;
use App\Models\Imagen;
use App\Models\Carrito;
$contacto = Contacto::find(1) ?? new Contacto();
// Logos y colores del header/footer: se editan en admin → Extras.
$logosSitio = app(\App\Services\LogosSitio::class);
$apariencia = \App\Models\Apariencia::actual();
// Cart::destroy();
use CodersFree\Shoppingcart\Facades\Cart;
$cart = Cart::content();
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @include('layouts.partials.seo')
  @include('layouts.partials.favicon')

  {{-- jquery --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

  {{-- CSS viejo (Bootstrap y las hojas del sitio) en la capa «legacy», por debajo de los
       componentes de osole/ui (capa «osole») y del tema (sin capa): resources/css/sitio.css.
       Así conviven mientras cada página pasa al rediseño. --}}
  <style>
    @layer legacy, osole;
    @import url("https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css") layer(legacy);
    @import url("{{ asset('css/styles2.css') }}?v=77") layer(legacy);
    @import url("{{ asset('css/layout.css') }}?v=3") layer(legacy);
    @import url("{{ asset('css/buscador-header.css') }}?v=2") layer(legacy);
    @import url("{{ asset('css/galeria-producto.css') }}?v=1") layer(legacy);
  </style>

  {{-- FONTS --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Quicksand:wght@300..700&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

  @vite(['resources/css/sitio.css', 'resources/js/sitio.js'])
  
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
  
  {{-- Bootstrap y los scripts globales del sitio van en el head (defer): con Turbo el head queda y el
       body se reemplaza en cada visita, así no se vuelven a ejecutar. --}}
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
  <script defer src="{{ asset('js/galeria-producto.js') }}?v=1"></script>
  <script defer src="{{ asset('js/carrito.js') }}?v=4"></script>
  <script defer src="{{ asset('js/buscador-header.js') }}?v=4"></script>
  <script>
    // Shim: Bootstrap 5 quitó $.fn.modal (jQuery). El sitio viejo todavía usa $('#aviso').modal('show').
    (function () {
      if (window.jQuery && !window.jQuery.fn.modal && window.bootstrap) {
        window.jQuery.fn.modal = function (action) {
          return this.each(function () {
            var instance = bootstrap.Modal.getOrCreateInstance(this);
            if (action === 'hide') instance.hide();
            else if (action === 'toggle') instance.toggle();
            else instance.show();
          });
        };
      }
    })();
    // Corre fn cuando el DOM está listo: en la carga completa y en cada visita de Turbo (el script del body se
    // vuelve a ejecutar con la página nueva ya en el documento).
    window.bmhAlListo = function (fn) {
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, { once: true });
      else fn();
    };
  </script>

  {{-- Turbo (navegación sin recarga): cada página lo activa con @section('turbo', true) cuando su JS ya es
       compatible; las demás se cargan completas, como siempre. --}}
  @hasSection('turbo')
  @else
    <meta name="turbo-visit-control" content="reload">
  @endif

  @yield('styles')
  <script>
    // Abre o cierra un modal de Bootstrap por id (carrito, home, listados).
    // Bootstrap se carga al final de la página: se busca recién al llamarla.
    window.bmhAbrirModal = function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
      else if (window.jQuery && jQuery.fn.modal) jQuery(el).modal('show');
    };
    window.bmhCerrarModal = function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).hide();
      else if (window.jQuery && jQuery.fn.modal) jQuery(el).modal('hide');
    };
  </script>
  <style>
    .bmh-advisor-nav { font-weight: 700; }
    .bmh-advisor-nav:hover { opacity: .82; }
    .bmh-advisor-dot {
      display: inline-block;
      width: 7px;
      height: 7px;
      margin-right: 6px;
      border-radius: 999px;
      background: #ABD430;
      vertical-align: middle;
      box-shadow: 0 0 0 0 rgba(171, 212, 48, .7);
      animation: bmhDot 2.6s infinite;
    }
  </style>
</head>

<body>
  @include('layouts.partials.sitio-header')

  <main>
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

  @include('layouts.partials.sitio-footer')

    {{--
        Los dos flotantes se excluyen entre sí: la esquina inferior derecha tiene
        un solo dueño según la zona.

        · Zona pública  → WhatsApp (el visitante todavía no es cliente).
        · Zona Clientes → Asesor IA (ya está autenticado, así que el asesor puede
          cotizarle con su condición comercial).

        La zona la define la página con `$zonaclientes`; `shouldRender()` agrega
        el requisito de una sesión de cliente o vendedor autenticada.
    --}}
    @php($bmhEnZonaClientes = isset($zonaclientes) && app(\App\Services\Ai\AdvisorBootstrap::class)->shouldRender())

    @if ($bmhEnZonaClientes)
      @include('components.bmh-advisor')
    @else
      @include('components.whatsapp-float')
    @endif
  
  
  
  @include('frontend.components.galeria-modal')
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
if (_togglePwd) _togglePwd.addEventListener("click", function () {
        var passwordField = document.getElementById("password");
        var mostrar = passwordField.type === "password";
        passwordField.type = mostrar ? "text" : "password";
        this.setAttribute("aria-pressed", mostrar ? "true" : "false");
        this.setAttribute("aria-label", mostrar ? "Ocultar la contraseña" : "Mostrar la contraseña");
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




    // Anuncio y animaciones de entrada: en la carga completa y en cada visita de Turbo.
    document.addEventListener('turbo:load', function () {
      if (window.jQuery && jQuery('#anuncio').length) jQuery('#anuncio').modal('show');
      if (window.AOS) AOS.init({ once: true, duration: 650, offset: 100 });
    }, { once: true });



    // Abre el ingreso a la Zona de Clientes (popover #bmh-ingreso del header).
    function toggleCarrito(){
      var ingreso = document.getElementById('bmh-ingreso');
      if (ingreso && ingreso.togglePopover) ingreso.togglePopover();
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
    
      bmhAlListo(function () {
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
bmhAlListo(function () {
    const categoriaSelect = document.getElementById('selectCategoria');
    if (categoriaSelect && categoriaSelect.value) {
        cargarAtributosCategoria(categoriaSelect.value);
    }
});




        bmhAlListo(function() {
        // Si hay datos guardados en localStorage, completamos el formulario (con guards)
        var _inpType = document.getElementById('input_type');
        var _pwd = document.getElementById('password');
        var _remember = document.getElementById('remember_me');
        if (_inpType && _pwd && localStorage.getItem('username') && localStorage.getItem('password')) {
            _inpType.value = localStorage.getItem('username');
            _pwd.value = localStorage.getItem('password');
            if (_remember) _remember.checked = true;
        }

        // El form de login, no el primero de la página: antes está el buscador del header.
        var _form = document.querySelector('form.ingreso');
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
      var element = document.querySelector('.{{ $ventana }}');
      if (element) element.classList.add('highlight');
    </script>
  @endif
</body>
</html>
