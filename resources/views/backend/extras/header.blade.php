@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
@endsection

@section('content')
@php
  use App\Models\Apariencia;

  $logos = [Apariencia::LOGO_TRANSPARENTE => $logoTransparente, Apariencia::LOGO_BLANCO => $logoBlanco];
  $vars = collect($apariencia->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
  $links = ['Home', 'Nosotros', 'Productos', 'Novedades', 'Contacto'];
  $hero = $heroUrl ? "background-image:url('{$heroUrl}')" : '';
  $tel = $contacto->tel ?? '';
  $mail = $contacto->mail ?? '';

  /** Los estados que muestra cada selector, en orden de edición. */
  $setsPorModo = [];
  foreach (Apariencia::SETS as $set => $config) {
      $setsPorModo[$config['modo']][] = $set;
  }

  /**
   * Cómo se dibuja la maqueta de cada estado de computadora.
   * - header: flotante (encima de la portada), normal (en el flujo) o fijo.
   * - info:   la barra de teléfono y mail, que se esconde al hacer scroll.
   */
  $maquetas = [
      'transparente_reposo' => ['header' => 'flotante', 'cuerpo' => 'hero', 'info' => true, 'url' => 'bmh.com.ar'],
      'transparente_scroll' => ['header' => 'fijo', 'cuerpo' => 'hero', 'info' => false, 'url' => 'bmh.com.ar'],
      'blanco_reposo' => ['header' => 'normal', 'cuerpo' => 'pagina', 'info' => true, 'url' => 'bmh.com.ar/nosotros'],
      'blanco_scroll' => ['header' => 'fijo', 'cuerpo' => 'pagina', 'info' => false, 'url' => 'bmh.com.ar/nosotros'],
  ];

  /** Las variables que usa la maqueta, apuntando al set que se está viendo. */
  $mkVars = function (string $set) {
      $pares = ['--mk-fondo:transparent'];
      foreach (Apariencia::coloresDe($set) as $color) {
          $pares[] = '--mk-'.Apariencia::sufijoCss($color).':var('.Apariencia::variable($set, $color).')';
      }

      return implode(';', $pares);
  };

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

  <div class="ext-encabezado">
    <div>
      <h1>Header</h1>
      <p>Los logos y los colores del encabezado del sitio. El header no es siempre igual: cambia según la página y según si el visitante hizo scroll. Elegí abajo cuál querés editar; a la derecha ves cómo queda mientras editás y nada cambia en el sitio hasta que guardás.</p>
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

    {{-- ============ Logos: los mismos dos archivos para los tres modos ============ --}}
    <section class="ext-card">
      <h2>Logos</h2>
      <p class="ext-ayuda">Son dos archivos y cada estado del header elige cuál de los dos usa. Arrastrá un archivo sobre la tarjeta o tocá «Cambiar logo». Recomendado: PNG transparente de 360 × 90 px.</p>

      <div class="ext-logos">
        <div class="ext-logo" data-parte="logo-transparente">
          <div class="ext-logo__escenario" style="{{ $hero }}">
            <img src="{{ $logoTransparente }}" alt="Logo actual para fondo transparente" data-logo-estado="transparente">
          </div>
          <div class="ext-logo__cuerpo">
            <p class="ext-logo__titulo">Para fondo transparente</p>
            <p class="ext-logo__uso">El que se lee sobre una foto o un fondo de color.</p>
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

        <div class="ext-logo" data-parte="logo-blanco">
          <div class="ext-logo__escenario ext-logo__escenario--blanco">
            <img src="{{ $logoBlanco }}" alt="Logo actual para fondo blanco" data-logo-estado="blanco">
          </div>
          <div class="ext-logo__cuerpo">
            <p class="ext-logo__titulo">Para fondo blanco</p>
            <p class="ext-logo__uso">El que se lee sobre un fondo claro.</p>
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
        <b>Fondo transparente</b> es la Home, donde el header se apoya sobre la foto de portada.
        <b>Fondo blanco</b> son Nosotros, Productos, Novedades, Contacto, la búsqueda y la Zona de Clientes.
        <b>Celular</b> es igual en todas las páginas. Cada uno guarda sus propios colores.
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
              <section class="ext-card">
                <h2>{{ $config['titulo'] }}</h2>
                <p class="ext-ayuda">{{ $config['ayuda'] }}</p>

                <div class="ext-fila" data-parte="{{ Apariencia::parteDe($set, 'logo') }}">
                  <span id="lbl-{{ $set }}-logo">Logo a mostrar</span>
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
                      'campo' => Apariencia::campo($set, $color),
                      'etiqueta' => Apariencia::etiqueta($set, $color),
                      'parte' => Apariencia::parteDe($set, $color),
                      'contraste' => Apariencia::contrasteDe($set, $color),
                      'hover' => str_ends_with($color, '_hover'),
                    ])
                  @endforeach
                </div>

                <p class="ext-subtitulo">Botón «Zona Clientes»</p>
                <div class="ext-campos">
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

      {{-- ============ Vista previa ============ --}}
      <aside class="ext-preview" aria-label="Vista previa del header">
        <div class="ext-card">
          <p class="ext-preview__titulo">Vista previa</p>

          @foreach (Apariencia::MODOS as $modo => $rotuloModo)
            <div id="ext-vista-{{ $modo }}" class="ext-vista" data-modo-panel="{{ $modo }}" @unless ($modo === $modoActivo) hidden @endunless>

              @if ($modo === 'celular')
                @php $set = 'celular'; @endphp
                <p class="ext-estado">Igual en todas las páginas</p>
                <div class="ext-telefonos">
                  <div class="ext-telefono">
                    <div class="ext-telefono__cuerpo"><div class="ext-lienzo">
                      <div class="mkm" style="{{ $mkVars($set) }}">
                        <div class="mkm-header" data-parte="{{ Apariencia::parteDe($set, 'fondo') }}">
                          <img alt="" data-logo-estado="{{ Apariencia::campo($set, 'logo') }}" data-parte="{{ Apariencia::parteDe($set, 'logo') }}">
                          <span class="mkm-toggler" data-parte="{{ Apariencia::parteDe($set, 'links') }}"></span>
                        </div>
                        <div class="mkm-hero" style="{{ $hero }}"><h3>Reacondicionamiento del inducido del automotor</h3></div>
                      </div>
                    </div></div>
                    <span class="ext-telefono__rotulo">Menú cerrado</span>
                  </div>

                  <div class="ext-telefono">
                    <div class="ext-telefono__cuerpo"><div class="ext-lienzo">
                      <div class="mkm" style="{{ $mkVars($set) }}">
                        <div class="mkm-header" data-parte="{{ Apariencia::parteDe($set, 'fondo') }}">
                          <img alt="" data-logo-estado="{{ Apariencia::campo($set, 'logo') }}" data-parte="{{ Apariencia::parteDe($set, 'logo') }}">
                          <span class="mkm-toggler" data-parte="{{ Apariencia::parteDe($set, 'links') }}"></span>
                        </div>
                        <div class="mkm-menu" data-parte="{{ Apariencia::parteDe($set, 'fondo') }}">
                          @foreach (array_slice($links, 1) as $link)
                            <a href="#" tabindex="-1" data-parte="{{ Apariencia::parteDe($set, 'links') }}">{{ $link }}</a>
                          @endforeach
                          <span class="mk-btn" data-parte="{{ Apariencia::parteDe($set, 'boton') }}">Zona Clientes</span>
                        </div>
                        <div class="mkm-hero" style="{{ $hero }}"></div>
                      </div>
                    </div></div>
                    <span class="ext-telefono__rotulo">Menú abierto</span>
                  </div>
                </div>

                <div class="ext-muestra" style="{{ $mkVars($set) }}">
                  <small>Botón normal</small><span class="mk-btn" data-parte="{{ Apariencia::parteDe($set, 'boton') }}">Zona Clientes</span>
                  <small>Al tocarlo</small><span class="mk-btn is-hover">Zona Clientes</span>
                </div>

              @else
                @foreach ($setsPorModo[$modo] as $set)
                  @php $maqueta = $maquetas[$set]; @endphp
                  <p class="ext-estado">{{ Apariencia::SETS[$set]['titulo'] }}</p>
                  <div class="ext-marco">
                    <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">{{ $maqueta['url'] }}</span></div>
                    <div class="ext-marco__ventana"><div class="ext-lienzo">
                      <div class="mk mk--{{ $maqueta['header'] }}" style="{{ $mkVars($set) }}">
                        @if ($maqueta['info'])
                          <div class="mk-info"><span><span>{{ $tel }}</span><span>{{ $mail }}</span></span></div>
                        @endif
                        <div class="mk-header" data-parte="{{ Apariencia::parteDe($set, 'fondo') }}">
                          <img alt="" data-logo-estado="{{ Apariencia::campo($set, 'logo') }}" data-parte="{{ Apariencia::parteDe($set, 'logo') }}">
                          <nav class="mk-nav">
                            @foreach ($links as $link)
                              <a href="#" tabindex="-1" data-parte="{{ Apariencia::parteDe($set, 'links') }}"
                                 class="{{ $maqueta['cuerpo'] === 'pagina' && $link === 'Nosotros' ? 'is-activo' : '' }}">{{ $link }}</a>
                            @endforeach
                            <span class="mk-btn" data-parte="{{ Apariencia::parteDe($set, 'boton') }}">Zona Clientes</span>
                          </nav>
                        </div>
                        @if ($maqueta['cuerpo'] === 'hero')
                          <div class="mk-hero" style="{{ $hero }}">
                            @if ($maqueta['info'])<h3>Reacondicionamiento del inducido del automotor</h3>@endif
                          </div>
                        @else
                          <div class="mk-pagina">
                            <div class="mk-pagina__foto"></div>
                            <div class="mk-pagina__texto"><b>SOBRE NOSOTROS</b><i></i><i></i><i style="width:80%"></i><i></i><i style="width:60%"></i></div>
                          </div>
                        @endif
                      </div>
                    </div></div>
                  </div>
                @endforeach
              @endif

            </div>
          @endforeach

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
