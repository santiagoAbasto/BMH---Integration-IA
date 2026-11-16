@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
@endsection

@section('content')
@php
  use App\Models\Apariencia;

  $logos = [Apariencia::LOGO_TRANSPARENTE => $logoTransparente, Apariencia::LOGO_BLANCO => $logoBlanco];
  $vars = collect($apariencia->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
  $hero = $heroUrl ? "background-image:url('{$heroUrl}')" : '';

  /** Los estados que muestra cada selector, en orden de edición. */
  $setsPorModo = [];
  foreach (Apariencia::SETS as $set => $config) {
      $setsPorModo[$config['modo']][] = $set;
  }

  /**
   * Lo que de verdad usa el header del rediseño (layouts/partials/sitio-header): el logo «para fondo transparente» en
   * todas las páginas y, de los colores, sólo los links de la Home al cargar la página. Lo demás se guarda igual, pero
   * hoy no cambia el sitio: se muestra marcado.
   */
  $setEnUso = 'transparente_reposo';
  $colorEnUso = fn (string $set, string $color) => $set === $setEnUso && in_array($color, ['links', 'links_hover'], true);

  /** Vistas previas de computadora de cada selector (el header real, en un iframe). */
  $vistas = [
      'transparente' => [
          ['titulo' => 'Home · al cargar la página', 'estado' => 'inicio', 'alto' => 400, 'url' => 'bmhbobinajes.com.ar', 'edita' => true],
          ['titulo' => 'Home · al hacer scroll', 'estado' => 'inicio-scroll', 'alto' => 230, 'url' => 'bmhbobinajes.com.ar', 'edita' => false],
      ],
      'blanco' => [
          ['titulo' => 'Páginas internas · al cargar', 'estado' => 'pagina', 'alto' => 300, 'url' => 'bmhbobinajes.com.ar/novedades', 'edita' => false],
          ['titulo' => 'Páginas internas · al hacer scroll', 'estado' => 'pagina-scroll', 'alto' => 230, 'url' => 'bmhbobinajes.com.ar/novedades', 'edita' => false],
      ],
  ];

  // Si volvió con errores, se abre el selector donde está el campo marcado.
  $modoActivo = array_key_first(Apariencia::MODOS);
  foreach (Apariencia::SETS as $set => $config) {
      foreach ([...Apariencia::coloresDe($set), 'logo'] as $color) {
          if ($errors->has(Apariencia::campo($set, $color))) {
              $modoActivo = $config['modo'];
              break 2;
          }
      }
  }
@endphp

<div class="ext" data-ext data-logos='@json($logos)' data-errores="{{ $errors->any() ? 1 : 0 }}" style="{{ $vars }}">

  <x-osole::admin-page-header.v1 eyebrow="Extras · Apariencia del sitio" title="Header" description="Los logos y los colores del encabezado del sitio. En el diseño actual, el header usa el logo «para fondo transparente» en todas las páginas y, de los colores, sólo cambian los links de la Home al cargar la página: lo demás queda marcado como «sin uso». A la derecha ves el header real con lo que elegís; nada cambia en el sitio hasta que guardás." />

  @if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: revisá los campos marcados.</div>
  @endif

  <form id="ext-form" method="POST" action="{{ route('extras.header.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- ============ Logos: los mismos dos archivos para los tres modos ============ --}}
    <section class="ext-card">
      <h2>Logos</h2>
      <p class="ext-ayuda">Arrastrá un archivo sobre la tarjeta o tocá «Cambiar logo». Recomendado: PNG transparente de 360 × 90 px.</p>

      <div class="ext-logos">
        <div class="ext-logo" data-parte="logo-transparente">
          <div class="ext-logo__escenario" style="{{ $hero }}">
            <img src="{{ $logoTransparente }}" alt="Logo actual para fondo transparente" data-logo-estado="transparente">
          </div>
          <div class="ext-logo__cuerpo">
            <p class="ext-logo__titulo">Para fondo transparente</p>
            <p class="ext-logo__uso">El del header en todas las páginas, en computadora y en el celular (va sobre la foto de la Home y sobre la barra oscura).</p>
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

        <div class="ext-logo ext-sin-uso" data-parte="logo-blanco">
          <div class="ext-logo__escenario ext-logo__escenario--blanco">
            <img src="{{ $logoBlanco }}" alt="Logo actual para fondo blanco" data-logo-estado="blanco">
          </div>
          <div class="ext-logo__cuerpo">
            <p class="ext-logo__titulo">Para fondo blanco <span class="ext-chip ext-chip--sin-uso">Sin uso</span></p>
            <p class="ext-logo__uso">El diseño actual no tiene header claro: este logo no se ve en el sitio. Queda guardado.</p>
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

    {{-- ============ Selector de modo: manda en los campos y en la vista previa ============ --}}
    <div class="ext-modos">
      <span id="lbl-modo" class="ext-modos__rotulo">¿Qué header estás editando?</span>
      <div class="ext-segmentos ext-segmentos--grande" role="radiogroup" aria-labelledby="lbl-modo">
        @foreach (Apariencia::MODOS as $modo => $rotulo)
          <input type="radio" id="modo-{{ $modo }}" name="ext-modo" value="{{ $modo }}" data-modo @checked($modo === $modoActivo)>
          <label for="modo-{{ $modo }}">{{ $rotulo }}</label>
        @endforeach
      </div>
      <p class="ext-modos__ayuda">
        <b>Fondo transparente</b> es la Home, donde el header se apoya sobre la foto de portada: ahí se eligen los
        colores de los links en computadora. <b>Fondo blanco</b> son las páginas internas y
        <b>Celular</b>, el header del teléfono: en el diseño actual tienen los colores fijos del diseño (barra oscura), así
        que sus campos quedan marcados como «sin uso».
      </p>
    </div>

    <div class="ext-grid">
      {{-- ============ Campos ============ --}}
      <div>
        @foreach (Apariencia::MODOS as $modo => $rotuloModo)
          <div data-modo-panel="{{ $modo }}" @unless ($modo === $modoActivo) hidden @endunless>
            @foreach ($setsPorModo[$modo] as $set)
              @php
                $config = Apariencia::SETS[$set];
                $campoLogo = Apariencia::campo($set, 'logo');
                $logoElegido = old($campoLogo, $apariencia->logoDe($set));
              @endphp
              <section @class(['ext-card', 'ext-card--sin-uso' => $set !== $setEnUso])>
                <h2>{{ $config['titulo'] }} @unless ($set === $setEnUso) <span class="ext-chip ext-chip--sin-uso">Sin uso</span> @endunless</h2>
                <p class="ext-ayuda">{{ $config['ayuda'] }}</p>
                @unless ($set === $setEnUso)
                  <p class="ext-aviso-sin-uso"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> En el diseño actual este header tiene los colores fijos del diseño: estos campos se guardan, pero no cambian el sitio.</p>
                @endunless

                <div @class(['ext-fila', 'ext-sin-uso' => $set === $setEnUso]) data-parte="{{ Apariencia::parteDe($set, 'logo') }}">
                  <span id="lbl-{{ $set }}-logo">Logo a mostrar @if ($set === $setEnUso) <span class="ext-chip ext-chip--sin-uso">Sin uso</span> @endif</span>
                  <div class="ext-segmentos" role="radiogroup" aria-labelledby="lbl-{{ $set }}-logo">
                    @foreach (Apariencia::LOGOS as $opcion)
                      <input type="radio" id="{{ $set }}-logo-{{ $opcion }}" name="{{ $campoLogo }}"
                             value="{{ $opcion }}" @checked($logoElegido === $opcion)>
                      <label for="{{ $set }}-logo-{{ $opcion }}">Fondo {{ $opcion }}</label>
                    @endforeach
                  </div>
                </div>
                @error($campoLogo) <p class="ext-color__error">{{ $message }}</p> @enderror

                <p class="ext-subtitulo">Header</p>
                @unless ($config['fondo'])
                  <p class="ext-nota">Este estado no tiene color de fondo: se ve la foto de portada detrás del header.</p>
                @endunless
                <div class="ext-campos">
                  @foreach (array_intersect(Apariencia::COLORES_HEADER, Apariencia::coloresDe($set)) as $color)
                    @include('backend.extras._campo-color', [
                      'sinUso' => $set === $setEnUso && ! $colorEnUso($set, $color),
                      'campo' => Apariencia::campo($set, $color),
                      'etiqueta' => Apariencia::etiqueta($set, $color),
                      'parte' => Apariencia::parteDe($set, $color),
                      'contraste' => Apariencia::contrasteDe($set, $color),
                      'hover' => str_ends_with($color, '_hover'),
                    ])
                  @endforeach
                </div>

                <p class="ext-subtitulo">Botón «Zona Clientes» @if ($set === $setEnUso) <span class="ext-chip ext-chip--sin-uso">Sin uso</span> @endif</p>
                <div @class(['ext-campos', 'ext-sin-uso' => $set === $setEnUso])>
                  @foreach (Apariencia::COLORES_BOTON as $color)
                    @include('backend.extras._campo-color', [
                      'campo' => Apariencia::campo($set, $color),
                      'etiqueta' => Apariencia::etiqueta($set, $color),
                      'parte' => Apariencia::parteDe($set, $color),
                      'contraste' => Apariencia::contrasteDe($set, $color),
                      'hover' => $color !== 'boton',
                    ])
                  @endforeach
                </div>
              </section>
            @endforeach
          </div>
        @endforeach
      </div>

      {{-- ============ Vista previa: el header real del sitio, en iframes (backend/extras/vista-previa) ============ --}}
      <aside class="ext-preview" aria-label="Vista previa del header">
        <div class="ext-card">
          <p class="ext-preview__titulo">Vista previa</p>
          <p class="ext-preview__ayuda">Es el header de verdad del sitio, con lo que estás eligiendo y todavía sin guardar.</p>

          @foreach (Apariencia::MODOS as $modo => $rotuloModo)
            <div id="ext-vista-{{ $modo }}" class="ext-vista" data-modo-panel="{{ $modo }}" @unless ($modo === $modoActivo) hidden @endunless>
              @if ($modo === 'celular')
                <p class="ext-estado">En el celular <span class="ext-chip">Colores fijos del diseño</span></p>
                <div class="ext-telefonos">
                  @foreach (['movil' => 'Menú cerrado', 'movil-menu' => 'Menú abierto'] as $estado => $rotulo)
                    <div class="ext-telefono">
                      <div class="ext-telefono__cuerpo">
                        @include('backend.extras._iframe', ['pieza' => 'header', 'estado' => $estado, 'ancho' => 375, 'alto' => 746, 'titulo' => 'Header en el celular: '.mb_strtolower($rotulo)])
                      </div>
                      <span class="ext-telefono__rotulo">{{ $rotulo }}</span>
                    </div>
                  @endforeach
                </div>
              @else
                @foreach ($vistas[$modo] as $vista)
                  <p class="ext-estado">
                    {{ $vista['titulo'] }}
                    @if ($vista['edita'])
                      <span class="ext-chip ext-chip--edita">Cambia con lo que elegís</span>
                    @else
                      <span class="ext-chip">Colores fijos del diseño</span>
                    @endif
                  </p>
                  <div class="ext-marco">
                    <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">{{ $vista['url'] }}</span></div>
                    @include('backend.extras._iframe', ['pieza' => 'header', 'estado' => $vista['estado'], 'ancho' => 1366, 'alto' => $vista['alto'], 'titulo' => 'Header: '.$vista['titulo']])
                  </div>
                @endforeach
              @endif
            </div>
          @endforeach

          <p class="ext-pie"><i class="fa-regular fa-hand-pointer" aria-hidden="true"></i> Pasá el mouse por los links para ver cómo reaccionan. Al tocar un campo, se marca en naranja la parte que cambia.</p>
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
