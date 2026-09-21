{{--
    Colores editables del header y el footer (admin → Extras).

    Los valores salen de App\Models\Apariencia como variables CSS; las reglas
    de abajo pisan los colores que estaban fijos en public/css/styles2.css.
    Se usa el id #site-header para ganarle a los `!important` de esa hoja sin
    depender del orden de carga.

    El header tiene cuatro estados en computadora —la Home en reposo sobre la
    portada y al hacer scroll, y el resto de las páginas en reposo y al hacer
    scroll— y uno solo en celular, donde no cambia con el scroll. Cada estado
    se edita por separado y se pinta acá con su propio juego de variables.

    Desktop y celular van en media queries que no se solapan (990/991, el
    mismo corte que usa styles2.css), así que ninguna regla le gana a la otra
    por especificidad: manda el ancho de la pantalla.
--}}
@php
    use App\Models\Apariencia;

    /** A qué header del sitio le aplica cada estado editable. */
    $estados = [
        'transparente_reposo' => '#site-header.home:not(.scrolled)',
        'transparente_scroll' => '#site-header.home.scrolled',
        'blanco_reposo' => '#site-header:not(.home):not(.scrolled)',
        'blanco_scroll' => '#site-header:not(.home).scrolled',
    ];

    $var = fn (string $set, string $color) => 'var('.Apariencia::variable($set, $color).')';
@endphp
<style>
  :root{
    @foreach ($apariencia->variablesCss() as $variable => $valor)
    {{ $variable }}: {{ $valor }};
    @endforeach
  }

  /* ---------- Barra de contacto de arriba (sólo computadora) ---------- */
  #site-topbar .barra-superior { background-color: var(--ap-tb-fondo); }
  #site-topbar .headerT { color: var(--ap-tb-texto); transition: color .2s; }
  /* Los íconos traen fill/stroke blanco fijo en el SVG. */
  #site-topbar svg [fill="white"] { fill: var(--ap-tb-texto); transition: fill .2s; }
  #site-topbar svg [stroke="white"] { stroke: var(--ap-tb-texto); transition: stroke .2s; }
  #site-topbar a:hover .headerT,
  #site-topbar a:focus-visible .headerT { color: var(--ap-tb-hover); }
  #site-topbar a:hover svg [fill="white"],
  #site-topbar a:focus-visible svg [fill="white"] { fill: var(--ap-tb-hover); }
  #site-topbar a:hover svg [stroke="white"],
  #site-topbar a:focus-visible svg [stroke="white"] { stroke: var(--ap-tb-hover); }
  #site-topbar a { text-decoration: none; }

  /* ---------- Logos: uno por estado ---------- */
  #site-header .logo-scroll,
  #site-header .logo-mobile { display: none; }
  #site-header.scrolled .logo-reposo { display: none; }
  #site-header.scrolled .logo-scroll { display: inline-block; }

  /* ---------- Computadora: cuatro estados ---------- */
  @media (min-width: 991px) {
    @foreach ($estados as $set => $sel)
    @if (Apariencia::SETS[$set]['fondo'])
    {{ $sel }},
    {{ $sel }} .navbar { background-color: {{ $var($set, 'fondo') }} !important; }
    @endif

    {{ $sel }} .navbar .nav-link,
    {{ $sel }} .navbar .nav-link.nav-activo { color: {{ $var($set, 'links') }} !important; }

    {{ $sel }} .navbar .nav-link:hover {
      color: {{ $var($set, 'links_hover') }} !important;
      box-shadow: 0 2px 0 {{ $var($set, 'links_hover') }} !important;
    }

    /* Barra inferior del link de la página actual; box-shadow en vez de
       border para no correr el layout un par de píxeles. */
    {{ $sel }} .navbar .nav-link.selectUrl,
    {{ $sel }} .navbar .nav-link.nav-activo {
      border-bottom-color: transparent !important;
      box-shadow: 0 2px 0 {{ $var($set, 'links') }} !important;
      font-weight: 600;
    }

    {{ $sel }} .zona-cliente-btn {
      color: {{ $var($set, 'boton') }} !important;
      border-color: {{ $var($set, 'boton') }} !important;
      background-image: linear-gradient({{ $var($set, 'boton_relleno') }}, {{ $var($set, 'boton_relleno') }}) !important;
    }
    {{ $sel }} .zona-cliente-btn:hover {
      color: {{ $var($set, 'boton_hover_texto') }} !important;
      border-color: {{ $var($set, 'boton_relleno') }} !important;
      background-size: 100% 100% !important;
    }
    @endforeach
  }

  /* ---------- Celular: un solo estado, no cambia con el scroll ---------- */
  @media (max-width: 990px) {
    #site-header .logo-reposo,
    #site-header .logo-scroll { display: none !important; }
    #site-header .logo-mobile { display: inline-block !important; }

    /* El fondo pinta el header y el menú desplegado, que vive dentro. */
    #site-header,
    #site-header .navbar { background-color: {{ $var('celular', 'fondo') }} !important; }

    #site-header .navbar .nav-link,
    #site-header .navbar .nav-link.active,
    #site-header .navbar .nav-link.nav-activo { color: {{ $var('celular', 'links') }} !important; }

    #site-header .navbar .nav-link:hover,
    #site-header .navbar .nav-link:active { color: {{ $var('celular', 'links_hover') }} !important; }

    /* El ícono de menú sigue el color de los links. Máscara con un SVG
       codificado: el data-URI original no se dibujaba y el botón no se veía. */
    #site-header .navbar-toggler-icon {
      background-image: none !important;
      background-color: {{ $var('celular', 'links') }};
      -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center / contain no-repeat;
              mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center / contain no-repeat;
    }

    #site-header .zona-cliente-btn {
      color: {{ $var('celular', 'boton') }} !important;
      border-color: {{ $var('celular', 'boton') }} !important;
      background-image: linear-gradient({{ $var('celular', 'boton_relleno') }}, {{ $var('celular', 'boton_relleno') }}) !important;
    }
    #site-header .zona-cliente-btn:hover,
    #site-header .zona-cliente-btn:active {
      color: {{ $var('celular', 'boton_hover_texto') }} !important;
      border-color: {{ $var('celular', 'boton_relleno') }} !important;
      background-size: 100% 100% !important;
    }
  }

  /* ---------- Footer: igual en todas las páginas ---------- */
  #site-footer { background-color: var(--ap-f-fondo); }

  #site-footer h4,
  #site-footer ul li,
  #site-footer ul li a { color: var(--ap-f-texto) !important; }
  #site-footer ul li a:hover { color: var(--ap-f-texto-hover) !important; }

  /* Los íconos traen fill/stroke blanco fijo; el trazo interno del sobre
     imita el fondo, así que sigue al color de fondo. */
  #site-footer .contact-list svg [fill="white"],
  #site-footer .contact-list svg [fill="#fff"] { fill: var(--ap-f-texto); }
  #site-footer .contact-list svg [stroke="white"] { stroke: var(--ap-f-texto); }
  #site-footer .contact-list svg [stroke="#0098DA"] { stroke: var(--ap-f-fondo); }

  /* Franja del copyright, al pie. */
  #site-footer .footer-derechos,
  #site-footer .footer-derechos .derechos { background-color: var(--ap-f-derechos-fondo) !important; }
  #site-footer .footer-derechos p,
  #site-footer .footer-derechos a,
  #site-footer .footer-derechos strong { color: var(--ap-f-derechos-texto) !important; }
</style>
