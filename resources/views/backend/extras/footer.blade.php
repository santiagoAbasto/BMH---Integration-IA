@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
@endsection

@section('content')
@php
  $vars = collect($apariencia->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
@endphp

<div class="ext" data-ext data-logos='@json(['footer' => $logoFooter])' data-errores="{{ $errors->any() ? 1 : 0 }}" style="{{ $vars }}">

  <div class="ext-encabezado">
    <div>
      <h1>Footer</h1>
      <p>El pie del sitio es igual en todas las páginas: se puede cambiar el logo, el fondo y el color de los textos, links e íconos. Nada cambia en el sitio hasta que guardás.</p>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: revisá los campos marcados.</div>
  @endif

  <form id="ext-form" method="POST" action="{{ route('extras.footer.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="ext-grid">
      <div>
        <section class="ext-card">
          <h2>Logo</h2>
          <p class="ext-ayuda">Se ve sobre el fondo del footer. Arrastrá un archivo sobre la tarjeta o tocá «Cambiar logo».</p>
          <div class="ext-logos ext-logos--uno">
            <div class="ext-logo" data-parte="f-logo">
              <div class="ext-logo__escenario" style="background:var(--ap-f-fondo)">
                <img src="{{ $logoFooter }}" alt="Logo actual del footer" data-logo-estado="footer">
              </div>
              <div class="ext-logo__cuerpo">
                <div class="ext-logo__acciones">
                  <input type="file" id="logo" name="logo" class="ext-visually-hidden" accept=".png,.jpg,.jpeg,.webp,.svg" data-logo-input="footer">
                  <label class="btn btn-sm btn-outline-primary mb-0" for="logo">
                    <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar logo
                  </label>
                  <span class="ext-archivo" data-archivo="footer">PNG, JPG, WEBP o SVG · hasta 2 MB</span>
                </div>
                <p class="ext-nota">Este logo también es el que aparece arriba a la izquierda en este panel.</p>
                @error('logo') <p class="ext-color__error">{{ $message }}</p> @enderror
              </div>
            </div>
          </div>
        </section>

        <section class="ext-card">
          <h2>Colores</h2>
          <p class="ext-ayuda">El bloque principal, con el logo, las secciones y los datos de contacto.</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'footer_fondo', 'etiqueta' => 'Fondo', 'parte' => 'f-fondo', 'contraste' => null, 'hover' => false])
            @include('backend.extras._campo-color', ['campo' => 'footer_texto', 'etiqueta' => 'Textos, links e íconos', 'parte' => 'f-texto', 'contraste' => 'footer_fondo', 'hover' => false])
            @include('backend.extras._campo-color', ['campo' => 'footer_texto_hover', 'etiqueta' => 'Links al pasar el mouse', 'parte' => 'f-hover', 'hover' => true, 'contraste' => 'footer_fondo'])
          </div>
        </section>

        <section class="ext-card">
          <h2>Franja del copyright</h2>
          <p class="ext-ayuda">La franja de abajo del todo, con «© Copyright BMH» y «By Osole».</p>
          <div class="ext-campos">
            @include('backend.extras._campo-color', ['campo' => 'footer_derechos_fondo', 'etiqueta' => 'Fondo', 'parte' => 'f-derechos', 'contraste' => null, 'hover' => false])
            @include('backend.extras._campo-color', ['campo' => 'footer_derechos_texto', 'etiqueta' => 'Texto', 'parte' => 'f-derechos', 'contraste' => 'footer_derechos_fondo', 'hover' => false])
          </div>
        </section>
      </div>

      <aside class="ext-preview" aria-label="Vista previa del footer">
        <div class="ext-card">
          <div class="ext-preview__barra">
            <p class="ext-preview__titulo">Vista previa</p>
          </div>
          <div id="ext-vista" class="ext-vista">
            <p class="ext-estado">Igual en todas las páginas</p>
            <div class="ext-marco">
              <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmh.com.ar</span></div>
              <div class="ext-marco__ventana"><div class="ext-lienzo">
                <div class="mkf" data-parte="f-fondo">
                  <div class="mkf-fila">
                    <div class="mkf-logo"><img alt="" data-logo-estado="footer" data-parte="f-logo"></div>
                    <div>
                      <h4 data-parte="f-texto">Secciones</h4>
                      <ul class="mkf-secciones">
                        @foreach (['Nosotros', 'Productos', 'Novedades', 'Contacto'] as $i => $seccion)
                          <li><a href="#" tabindex="-1" data-parte="f-texto {{ $i === 0 ? 'f-hover' : '' }}">{{ $seccion }}</a></li>
                        @endforeach
                      </ul>
                    </div>
                    <div>
                      <h4 data-parte="f-texto">Contacto</h4>
                      <ul>
                        @foreach ([
                          ['fa-solid fa-location-dot', $contacto->direccion ?? null],
                          ['fa-solid fa-phone', $contacto->tel ?? null],
                          ['fa-solid fa-envelope', $contacto->mail ?? null],
                          ['fa-brands fa-whatsapp', $contacto->whatsapp ?? null],
                        ] as [$icono, $dato])
                          @if ($dato)
                            <li><a href="#" tabindex="-1" data-parte="f-texto"><i class="{{ $icono }}" aria-hidden="true" style="width:22px;text-align:center"></i>{{ $dato }}</a></li>
                          @endif
                        @endforeach
                      </ul>
                    </div>
                  </div>
                  <div class="mkf-derechos" data-parte="f-derechos"><span>© Copyright 2024 <b>BMH</b> Todos los derechos reservados</span><span>By <b>Osole</b></span></div>
                </div>
              </div></div>
            </div>
          </div>
          <p class="ext-pie"><i class="fa-regular fa-hand-pointer" aria-hidden="true"></i> Pasá el mouse por los links para ver el color al pasar el mouse. Al tocar un campo, se marca en naranja la parte que cambia.</p>
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
