@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
@endsection

@section('content')
@php
  use App\Models\Apariencia;

  $logos = [Apariencia::LOGO_TRANSPARENTE => $logoTransparente, Apariencia::LOGO_BLANCO => $logoBlanco];
  $vars = collect($apariencia->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
  $scrollLogo = old('header_scroll_logo', $apariencia->header_scroll_logo);
  $mobileLogo = old('header_mobile_logo', $apariencia->header_mobile_logo);
  $links = ['Home', 'Nosotros', 'Productos', 'Novedades', 'Contacto'];
  $hero = $heroUrl ? "background-image:url('{$heroUrl}')" : '';
  $tel = $contacto->tel ?? '';
  $mail = $contacto->mail ?? '';
@endphp

<div class="ext" data-ext data-logos='@json($logos)' data-errores="{{ $errors->any() ? 1 : 0 }}" style="{{ $vars }}">

  <div class="ext-encabezado">
    <div>
      <h1>Header</h1>
      <p>Los logos y los colores del encabezado del sitio. A la derecha ves cómo queda en cada página y en el celular mientras editás; nada cambia en el sitio hasta que guardás.</p>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: revisá los campos marcados.</div>
  @endif

  <form id="ext-form" method="POST" action="{{ route('extras.header.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="ext-grid">
      <div>

        {{-- ============ Logos ============ --}}
        <section class="ext-card">
          <h2>Logos</h2>
          <p class="ext-ayuda">El header usa un logo distinto según lo que tenga detrás. Arrastrá un archivo sobre la tarjeta o tocá «Cambiar logo». Recomendado: PNG transparente de 360 × 90 px.</p>

          <div class="ext-logos">
            <div class="ext-logo" data-parte="logo-transparente" data-vista-tab="home">
              <div class="ext-logo__escenario" style="{{ $hero }}">
                <img src="{{ $logoTransparente }}" alt="Logo actual para fondo transparente" data-logo-estado="transparente">
              </div>
              <div class="ext-logo__cuerpo">
                <p class="ext-logo__titulo">Para fondo transparente</p>
                <p class="ext-logo__uso">Se ve sobre la imagen de portada de la Home.</p>
                <div class="ext-logo__acciones">
                  <input type="file" id="logo_transparente" name="logo_transparente" class="ext-visually-hidden"
                         accept=".png,.jpg,.jpeg,.webp,.svg" data-logo-input="transparente">
                  <label class="btn btn-sm btn-outline-primary mb-0" for="logo_transparente">
                    <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar logo
                  </label>
                  <span class="ext-archivo" data-archivo="transparente">PNG, JPG, WEBP o SVG · hasta 2 MB</span>
                </div>
                @error('logo_transparente') <p class="ext-color__error">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="ext-logo" data-parte="logo-blanco" data-vista-tab="nosotros">
              <div class="ext-logo__escenario ext-logo__escenario--blanco">
                <img src="{{ $logoBlanco }}" alt="Logo actual para fondo blanco" data-logo-estado="blanco">
              </div>
              <div class="ext-logo__cuerpo">
                <p class="ext-logo__titulo">Para fondo blanco</p>
                <p class="ext-logo__uso">Nosotros, Productos, Novedades, Contacto, la búsqueda y la Zona de Clientes.</p>
                <div class="ext-logo__acciones">
                  <input type="file" id="logo_blanco" name="logo_blanco" class="ext-visually-hidden"
                         accept=".png,.jpg,.jpeg,.webp,.svg" data-logo-input="blanco">
                  <label class="btn btn-sm btn-outline-primary mb-0" for="logo_blanco">
                    <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar logo
                  </label>
                  <span class="ext-archivo" data-archivo="blanco">PNG, JPG, WEBP o SVG · hasta 2 MB</span>
                </div>
                @unless ($logoBlancoPropio)
                  <p class="ext-nota">Todavía usa el mismo logo que el de fondo transparente.</p>
                @endunless
                @error('logo_blanco') <p class="ext-color__error">{{ $message }}</p> @enderror
              </div>
            </div>
          </div>
        </section>

        {{-- ============ Scroll (desktop) ============ --}}
        <section class="ext-card" data-vista-tab="escritorio">
          <h2>Al hacer scroll · computadora</h2>
          <p class="ext-ayuda">Cuando el visitante baja un poco la página, el header queda fijo arriba con estos colores, en todas las páginas.</p>

          <div class="ext-fila" data-parte="hs-logo">
            <span id="lbl-hs-logo">Logo a mostrar</span>
            <div class="ext-segmentos" role="radiogroup" aria-labelledby="lbl-hs-logo">
              <input type="radio" id="hs-logo-t" name="header_scroll_logo" value="{{ Apariencia::LOGO_TRANSPARENTE }}" @checked($scrollLogo === Apariencia::LOGO_TRANSPARENTE)>
              <label for="hs-logo-t">Fondo transparente</label>
              <input type="radio" id="hs-logo-b" name="header_scroll_logo" value="{{ Apariencia::LOGO_BLANCO }}" @checked($scrollLogo === Apariencia::LOGO_BLANCO)>
              <label for="hs-logo-b">Fondo blanco</label>
            </div>
          </div>

          <p class="ext-subtitulo">Header</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_fondo', 'etiqueta' => 'Fondo', 'parte' => 'hs-fondo'])
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_links', 'etiqueta' => 'Links', 'parte' => 'hs-links', 'contraste' => 'header_scroll_fondo'])
          </div>

          <p class="ext-subtitulo">Botón «Zona Clientes»</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_boton_texto', 'etiqueta' => 'Texto', 'parte' => 'hs-btn', 'contraste' => 'header_scroll_fondo'])
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_boton_borde', 'etiqueta' => 'Borde', 'parte' => 'hs-btn'])
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_boton_hover_fondo', 'etiqueta' => 'Relleno al pasar el mouse', 'parte' => 'hs-btn', 'hover' => true])
            @include('backend.extras._campo-color', ['campo' => 'header_scroll_boton_hover_texto', 'etiqueta' => 'Texto al pasar el mouse', 'parte' => 'hs-btn', 'hover' => true, 'contraste' => 'header_scroll_boton_hover_fondo'])
          </div>
        </section>

        {{-- ============ Celular ============ --}}
        <section class="ext-card" data-vista-tab="celular">
          <h2>Celular</h2>
          <p class="ext-ayuda">En el celular el header es siempre el mismo: no cambia al hacer scroll. Los links y el botón aparecen al abrir el menú.</p>

          <div class="ext-fila" data-parte="hm-logo">
            <span id="lbl-hm-logo">Logo a mostrar</span>
            <div class="ext-segmentos" role="radiogroup" aria-labelledby="lbl-hm-logo">
              <input type="radio" id="hm-logo-t" name="header_mobile_logo" value="{{ Apariencia::LOGO_TRANSPARENTE }}" @checked($mobileLogo === Apariencia::LOGO_TRANSPARENTE)>
              <label for="hm-logo-t">Fondo transparente</label>
              <input type="radio" id="hm-logo-b" name="header_mobile_logo" value="{{ Apariencia::LOGO_BLANCO }}" @checked($mobileLogo === Apariencia::LOGO_BLANCO)>
              <label for="hm-logo-b">Fondo blanco</label>
            </div>
          </div>

          <p class="ext-subtitulo">Header</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_fondo', 'etiqueta' => 'Fondo', 'parte' => 'hm-fondo'])
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_links', 'etiqueta' => 'Links e ícono del menú', 'parte' => 'hm-links', 'contraste' => 'header_mobile_fondo'])
          </div>

          <p class="ext-subtitulo">Botón «Zona Clientes»</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_boton_texto', 'etiqueta' => 'Texto', 'parte' => 'hm-btn', 'contraste' => 'header_mobile_fondo'])
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_boton_borde', 'etiqueta' => 'Borde', 'parte' => 'hm-btn'])
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_boton_hover_fondo', 'etiqueta' => 'Relleno al tocarlo', 'parte' => 'hm-btn', 'hover' => true])
            @include('backend.extras._campo-color', ['campo' => 'header_mobile_boton_hover_texto', 'etiqueta' => 'Texto al tocarlo', 'parte' => 'hm-btn', 'hover' => true, 'contraste' => 'header_mobile_boton_hover_fondo'])
          </div>
        </section>
      </div>

      {{-- ============ Vista previa ============ --}}
      <aside class="ext-preview" aria-label="Vista previa del header">
        <div class="ext-card">
          <div class="ext-preview__barra">
            <p class="ext-preview__titulo">Vista previa</p>
            <div class="ext-segmentos" role="tablist" aria-label="Página a previsualizar">
              <button type="button" role="tab" data-tab="home" aria-selected="true">Home</button>
              <button type="button" role="tab" data-tab="nosotros" aria-selected="false">Nosotros</button>
              <button type="button" role="tab" data-tab="celular" aria-selected="false">Celular</button>
            </div>
          </div>

          <div id="ext-vista">
            {{-- ---------- Home ---------- --}}
            <div data-panel="home">
              <p class="ext-estado">Al cargar la página</p>
              <div class="ext-marco">
                <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmh.com.ar</span></div>
                <div class="ext-marco__ventana"><div class="ext-lienzo">
                  <div class="mk mk--home">
                    <div class="mk-info"><span><span>{{ $tel }}</span><span>{{ $mail }}</span></span></div>
                    <div class="mk-header">
                      <img alt="" data-logo-estado="transparente">
                      <nav class="mk-nav">
                        @foreach ($links as $link) <a href="#" tabindex="-1">{{ $link }}</a> @endforeach
                        <span class="mk-btn">Zona Clientes</span>
                      </nav>
                    </div>
                    <div class="mk-hero" style="{{ $hero }}"><h3>Reacondicionamiento del inducido del automotor</h3></div>
                  </div>
                </div></div>
              </div>

              <p class="ext-estado">Al hacer scroll</p>
              <div class="ext-marco">
                <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmh.com.ar</span></div>
                <div class="ext-marco__ventana"><div class="ext-lienzo">
                  <div class="mk mk--scroll">
                    <div class="mk-header" data-parte="hs-fondo">
                      <img alt="" data-logo-estado="header_scroll_logo" data-parte="hs-logo">
                      <nav class="mk-nav">
                        @foreach ($links as $link) <a href="#" tabindex="-1" data-parte="hs-links">{{ $link }}</a> @endforeach
                        <span class="mk-btn" data-parte="hs-btn">Zona Clientes</span>
                      </nav>
                    </div>
                    <div class="mk-hero" style="{{ $hero }}"></div>
                  </div>
                </div></div>
              </div>
            </div>

            {{-- ---------- Nosotros (fondo blanco) ---------- --}}
            <div data-panel="nosotros" hidden>
              <p class="ext-estado">Al cargar la página</p>
              <div class="ext-marco">
                <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmh.com.ar/nosotros</span></div>
                <div class="ext-marco__ventana"><div class="ext-lienzo">
                  <div class="mk mk--blanco">
                    <div class="mk-info"><span><span>{{ $tel }}</span><span>{{ $mail }}</span></span></div>
                    <div class="mk-header">
                      <img alt="" data-logo-estado="blanco">
                      <nav class="mk-nav">
                        @foreach ($links as $link) <a href="#" tabindex="-1" class="{{ $link === 'Nosotros' ? 'is-activo' : '' }}">{{ $link }}</a> @endforeach
                        <span class="mk-btn">Zona Clientes</span>
                      </nav>
                    </div>
                    <div class="mk-pagina"><div class="mk-pagina__foto"></div><div class="mk-pagina__texto"><b>SOBRE NOSOTROS</b><i></i><i></i><i style="width:80%"></i><i></i><i style="width:60%"></i></div></div>
                  </div>
                </div></div>
              </div>

              <p class="ext-estado">Al hacer scroll</p>
              <div class="ext-marco">
                <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmh.com.ar/nosotros</span></div>
                <div class="ext-marco__ventana"><div class="ext-lienzo">
                  <div class="mk mk--scroll">
                    <div class="mk-header" data-parte="hs-fondo">
                      <img alt="" data-logo-estado="header_scroll_logo" data-parte="hs-logo">
                      <nav class="mk-nav">
                        @foreach ($links as $link) <a href="#" tabindex="-1" data-parte="hs-links" class="{{ $link === 'Nosotros' ? 'is-activo' : '' }}">{{ $link }}</a> @endforeach
                        <span class="mk-btn" data-parte="hs-btn">Zona Clientes</span>
                      </nav>
                    </div>
                    <div class="mk-pagina" style="padding-top:117px;height:360px"><div class="mk-pagina__foto"></div><div class="mk-pagina__texto"><i></i><i></i><i style="width:80%"></i><i></i><i style="width:70%"></i><i></i></div></div>
                  </div>
                </div></div>
              </div>
            </div>

            {{-- ---------- Celular ---------- --}}
            <div data-panel="celular" hidden>
              <p class="ext-estado">Igual en todas las páginas</p>
              <div class="ext-telefonos">
                <div class="ext-telefono">
                  <div class="ext-telefono__cuerpo"><div class="ext-lienzo">
                    <div class="mkm">
                      <div class="mkm-header" data-parte="hm-fondo">
                        <img alt="" data-logo-estado="header_mobile_logo" data-parte="hm-logo">
                        <span class="mkm-toggler" data-parte="hm-links"></span>
                      </div>
                      <div class="mkm-hero" style="{{ $hero }}"><h3>Reacondicionamiento del inducido del automotor</h3></div>
                    </div>
                  </div></div>
                  <span class="ext-telefono__rotulo">Menú cerrado</span>
                </div>

                <div class="ext-telefono">
                  <div class="ext-telefono__cuerpo"><div class="ext-lienzo">
                    <div class="mkm">
                      <div class="mkm-header" data-parte="hm-fondo">
                        <img alt="" data-logo-estado="header_mobile_logo" data-parte="hm-logo">
                        <span class="mkm-toggler" data-parte="hm-links"></span>
                      </div>
                      <div class="mkm-menu" data-parte="hm-fondo">
                        @foreach (array_slice($links, 1) as $link) <a href="#" tabindex="-1" data-parte="hm-links">{{ $link }}</a> @endforeach
                        <span class="mk-btn mkm-btn" data-parte="hm-btn">Zona Clientes</span>
                      </div>
                      <div class="mkm-hero" style="{{ $hero }}"></div>
                    </div>
                  </div></div>
                  <span class="ext-telefono__rotulo">Menú abierto</span>
                </div>
              </div>

              <div class="ext-muestra">
                <small>Botón normal</small><span class="mk-btn mkm-btn" data-parte="hm-btn">Zona Clientes</span>
                <small>Al tocarlo</small><span class="mk-btn mkm-btn is-hover">Zona Clientes</span>
              </div>
            </div>
          </div>

          <p class="ext-pie"><i class="fa-regular fa-hand-pointer" aria-hidden="true"></i> Pasá el mouse por los links y el botón para ver cómo reaccionan. Al tocar un campo, se marca en naranja la parte que cambia.</p>
        </div>
      </aside>
    </div>

    <div class="ext-guardar">
      <span class="ext-guardar__estado" role="status">Sin cambios</span>
      <button type="button" class="btn btn-outline-secondary" data-descartar>Descartar</button>
      <button type="submit" class="btn btn-primary" data-guardar><i class="fa-solid fa-check" aria-hidden="true"></i> Guardar cambios</button>
    </div>
  </form>
</div>
@endsection

@section('script')
  @include('backend.extras._script')
@endsection
