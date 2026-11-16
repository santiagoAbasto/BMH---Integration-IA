{{--
    Vista previa de admin → Extras › Header / Footer: el header o el footer reales del sitio (los mismos partials,
    layouts.partials.sitio-header / sitio-footer, con el mismo CSS), dentro de un iframe del editor. Así la vista previa
    no queda vieja cuando cambia el diseño.

    Sin el JS del sitio: el estado se arma acá (?estado=inicio|inicio-scroll|pagina|pagina-scroll|movil|movil-menu) y el
    editor (backend/extras/_script) le pasa los colores y los logos que se están eligiendo, antes de guardar.

    Lo único de Apariencia que usa el header del rediseño son los links de la Home al cargar la página
    (--ap-tr-links y --ap-tr-links-hover, que sitio-header pasa como --bmh-portada-links*); los logos salen de
    LogosSitio: el «para fondo transparente» en el header y el del footer abajo.
--}}
@php
    use App\Models\Apariencia;

    $esHome = in_array($estado, ['inicio', 'movil', 'movil-menu'], true);
    $conScroll = in_array($estado, ['inicio-scroll', 'pagina-scroll'], true);
    $vars = collect(Apariencia::actual()->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
    $varLinks = Apariencia::variable('transparente_reposo', 'links');
    $varHover = Apariencia::variable('transparente_reposo', 'links_hover');
    $parteLinks = Apariencia::parteDe('transparente_reposo', 'links');
@endphp
<!DOCTYPE html>
<html lang="es" class="ext-vp ext-vp--{{ $pieza }}" style="{{ $vars }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>Vista previa del {{ $pieza }}</title>
  {{-- Las mismas capas que el sitio (layouts/plantilla-front): el CSS viejo debajo de osole/ui y del tema. --}}
  <style>
    @layer legacy, osole;
    @import url("https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css") layer(legacy);
    @import url("{{ asset('css/styles2.css') }}?v=77") layer(legacy);
    @import url("{{ asset('css/layout.css') }}?v=3") layer(legacy);
    @import url("{{ asset('css/buscador-header.css') }}?v=2") layer(legacy);
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
  @vite(['resources/css/sitio.css'])
  <style>
    html.ext-vp { scrollbar-width: none; }
    html.ext-vp::-webkit-scrollbar { display: none; }
    .ext-vp body { margin: 0; overflow: hidden; background: var(--bmh-white); }

    /* Los links de la Home al cargar la página: lo que se está editando (el sitio los recibe de sitio-header). */
    .ext-vp .bmh-header--home {
      --bmh-portada-links: var({{ $varLinks }});
      --bmh-portada-links-hover: var({{ $varHover }});
    }

    /* Portada de la Home, de fondo. */
    .ext-vp-hero {
      display: flex;
      align-items: center;
      min-block-size: 100vh;
      padding-block: 9rem 4rem;
      background:
        linear-gradient(90deg, rgb(5 7 10 / 0.86) 0%, rgb(5 7 10 / 0.55) 45%, rgb(5 7 10 / 0.15) 100%),
        var(--ext-vp-foto, #1c2430) center / cover no-repeat;
      color: var(--bmh-white);
    }
    .ext-vp-hero h1 {
      max-inline-size: 16ch;
      margin: 0;
      font-family: var(--osole-font-heading);
      font-size: clamp(2rem, 1rem + 2.6vw, 3.25rem);
      font-weight: 800;
      line-height: 1.1;
    }

    /* Página interna o con scroll: contenido gris de relleno. */
    .ext-vp-pagina { padding-block: 1.5rem 3rem; }
    /* Con scroll: la página tiene largo y se baja de verdad, así el header queda como en el sitio (fijo, sin la barra de arriba). */
    .ext-vp--scroll .ext-vp-pagina { min-block-size: 250vh; }
    .ext-vp-pagina__titulo { inline-size: 16rem; block-size: 2.25rem; margin-block: 1rem 2rem; border-radius: 0.5rem; background: var(--bmh-surface-alt); }
    .ext-vp-tarjetas { display: grid; grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); gap: 1.5rem; }
    .ext-vp-tarjetas i { display: block; block-size: 15rem; border-radius: 0.75rem; background: var(--bmh-surface-alt); }

    /* El editor marca lo que cambia el campo enfocado (como en el resto de Extras). */
    .ext-vp .is-resaltado { outline: 3px solid #f79009 !important; outline-offset: 3px; border-radius: 6px; }
    .ext-vp #site-header .is-hover { color: var(--_link-hover-color) !important; }
    .ext-vp #site-header .is-hover::after { opacity: 1 !important; background-color: var(--_link-hover-color) !important; }
  </style>
</head>
<body class="{{ $conScroll ? 'ext-vp--scroll' : '' }}">
  @if ($pieza === 'header')
    @include('layouts.partials.sitio-header')

    <script>
      (function () {
        var header = document.getElementById('site-header');
        if (!header) return;
        @if ($esHome)
          header.classList.add('bmh-header--home', 'osole-header-catalog-v1--overlay');
        @else
          header.classList.remove('bmh-header--home', 'osole-header-catalog-v1--overlay');
        @endif
        @if ($conScroll)
          header.setAttribute('data-scrolled', '');
          addEventListener('load', function () { window.scrollTo(0, 420); });
        @endif
        // Lo que el editor reemplaza y marca: el logo y los links.
        header.querySelectorAll('.osole-header-catalog-v1__logo img').forEach(function (img) { img.setAttribute('data-logo-estado', 'transparente'); });
        header.querySelectorAll('.osole-header-catalog-v1__link, .osole-header-catalog-v1__trigger').forEach(function (a) { a.setAttribute('data-parte', '{{ $parteLinks }}'); });
        @if ($estado === 'movil-menu')
          var panel = header.querySelector('.osole-header-catalog-v1__panel');
          if (panel && panel.showPopover) panel.showPopover();
        @endif
      })();
    </script>

    @if ($esHome)
      <div class="ext-vp-hero" @if ($heroUrl) style="--ext-vp-foto: url('{{ $heroUrl }}')" @endif>
        <x-osole::container.v1><h1>Calidad que mantiene tu mundo en movimiento</h1></x-osole::container.v1>
      </div>
    @else
      @unless ($conScroll)
        @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Novedades']]])
      @endunless
      <div class="ext-vp-pagina">
        <x-osole::container.v1>
          @unless ($conScroll)<div class="ext-vp-pagina__titulo"></div>@endunless
          <div class="ext-vp-tarjetas">{!! str_repeat('<i></i>', $conScroll ? 15 : 5) !!}</div>
        </x-osole::container.v1>
      </div>
    @endif
  @else
    @include('layouts.partials.sitio-footer')
    <script>
      document.querySelectorAll('#site-footer .osole-footer-v1__logo img, #site-footer [class*="logo"] img').forEach(function (img) { img.setAttribute('data-logo-estado', 'footer'); img.setAttribute('data-parte', 'f-logo'); });
    </script>
  @endif

  {{-- Es una muestra: los links y los formularios no navegan. --}}
  <script>
    document.addEventListener('click', function (e) { if (e.target.closest('a, button[type="submit"]')) e.preventDefault(); }, true);
    document.addEventListener('submit', function (e) { e.preventDefault(); }, true);
  </script>
</body>
</html>
