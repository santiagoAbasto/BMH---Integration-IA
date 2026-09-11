{{--
    Colores editables del header y el footer (admin → Extras).

    Los valores salen de App\Models\Apariencia como variables CSS; las reglas
    de abajo pisan los colores que estaban fijos en public/css/styles2.css.
    Se usan los ids #site-header y #site-footer para ganarle a los `!important`
    de esa hoja sin depender del orden de carga.

    El reposo desktop no se edita: en la home el header es transparente sobre
    el hero y en el resto de las páginas es blanco.
--}}
<style>
  :root{
    @foreach ($apariencia->variablesCss() as $variable => $valor)
    {{ $variable }}: {{ $valor }};
    @endforeach
  }

  /* ---------- Logos: uno por estado ---------- */
  #site-header .logo-scroll,
  #site-header .logo-mobile { display: none; }
  #site-header.scrolled .logo-reposo { display: none; }
  #site-header.scrolled .logo-scroll { display: inline-block; }

  /* Reposo fuera de la home: el link de la página actual (antes era inline). */
  #site-header:not(.home) .nav-activo { color: #000 !important; font-weight: 600; }

  /* ---------- Desktop · al hacer scroll ---------- */
  #site-header.scrolled { background-color: var(--ap-hs-fondo) !important; }

  #site-header.scrolled .navbar .nav-link,
  #site-header.scrolled .navbar .nav-link.nav-activo { color: var(--ap-hs-links) !important; }

  #site-header.scrolled .navbar .nav-link:hover {
    color: color-mix(in srgb, var(--ap-hs-links) 70%, transparent) !important;
    box-shadow: 0 2px 0 color-mix(in srgb, var(--ap-hs-links) 70%, transparent) !important;
  }
  /* Barra inferior del link activo; box-shadow para no mover el layout. */
  #site-header.scrolled .navbar .nav-link.selectUrl,
  #site-header.scrolled .navbar .nav-link.nav-activo {
    border-bottom-color: transparent !important;
    box-shadow: 0 2px 0 color-mix(in srgb, var(--ap-hs-links) 70%, transparent) !important;
  }

  #site-header.scrolled .zona-cliente-btn {
    color: var(--ap-hs-btn-texto) !important;
    border-color: var(--ap-hs-btn-borde) !important;
    background-image: linear-gradient(var(--ap-hs-btn-hover-fondo), var(--ap-hs-btn-hover-fondo)) !important;
  }
  #site-header.scrolled .zona-cliente-btn:hover {
    color: var(--ap-hs-btn-hover-texto) !important;
    border-color: var(--ap-hs-btn-hover-fondo) !important;
    background-size: 100% 100% !important;
  }

  /* ---------- Mobile: siempre igual, no cambia con el scroll ---------- */
  @media (max-width: 990px) {
    #site-header .logo-reposo,
    #site-header .logo-scroll { display: none !important; }
    #site-header .logo-mobile { display: inline-block !important; }

    #site-header,
    #site-header .navbar { background-color: var(--ap-hm-fondo) !important; }

    #site-header .navbar .nav-link,
    #site-header .navbar .nav-link:hover,
    #site-header .navbar .nav-link.active { color: var(--ap-hm-links) !important; }

    /* El ícono de menú sigue el color de los links. Máscara con un SVG
       codificado: el data-URI original no se dibujaba y el botón no se veía. */
    #site-header .navbar-toggler-icon {
      background-image: none !important;
      background-color: var(--ap-hm-links);
      -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center / contain no-repeat;
              mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center / contain no-repeat;
    }

    #site-header .zona-cliente-btn {
      color: var(--ap-hm-btn-texto) !important;
      border-color: var(--ap-hm-btn-borde) !important;
      background-image: linear-gradient(var(--ap-hm-btn-hover-fondo), var(--ap-hm-btn-hover-fondo)) !important;
    }
    #site-header .zona-cliente-btn:hover,
    #site-header .zona-cliente-btn:active {
      color: var(--ap-hm-btn-hover-texto) !important;
      border-color: var(--ap-hm-btn-hover-fondo) !important;
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
</style>
