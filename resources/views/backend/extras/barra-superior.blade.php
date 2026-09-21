@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
  <style>
    /* ------------------------------------------------------------------
       Barra superior. Reusa el sistema de Extras (_estilos + _script) y
       suma una vista previa a tamaño real, porque la barra es una franja
       ancha y baja que en una columna angosta no se entendería.
       ------------------------------------------------------------------ */
    .tb-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .tb-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;background:#EEF2F6;color:#475467;font-size:.76rem;font-weight:500}
    .tb-chip i{color:#98A2B3}

    /* ---------------------------- Vista previa ---------------------------- */
    .tb-previa .ext-marco{border-radius:10px}
    .tb-previa__lienzo{position:relative;background:#fff;overflow:hidden}
    .tb-previa__rotulo{position:absolute;right:12px;top:40px;font-size:.68rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--ext-azul);display:flex;align-items:center;gap:6px}
    .tb-previa__rotulo::before{content:"";width:28px;height:1px;background:currentColor;opacity:.6}

    /* La barra: mismas medidas que el sitio (31 px, Montserrat 14/500). */
    .mkb{height:31px;background:var(--ap-tb-fondo);color:var(--ap-tb-texto);font-family:Montserrat,system-ui,sans-serif;transition:background-color .2s}
    .mkb__fila{height:100%;max-width:1140px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:16px}
    .mkb__izq,.mkb__der{display:flex;align-items:center;min-width:0}
    .mkb__izq{gap:56px}
    .mkb__der{gap:15px}
    .mkb-item{display:inline-flex;align-items:center;gap:10px;color:inherit;text-decoration:none;font-size:14px;font-weight:500;white-space:nowrap;border-radius:5px;cursor:pointer;transition:color .2s}
    .mkb-item[hidden]{display:none!important}
    .mkb-item svg{flex:0 0 auto}
    .mkb-item span{overflow:hidden;text-overflow:ellipsis}
    .mkb-item svg [fill="white"]{fill:var(--ap-tb-texto);transition:fill .2s}
    .mkb-item svg [stroke="white"]{stroke:var(--ap-tb-texto);transition:stroke .2s}
    .mkb-item:hover,.mkb-item.is-hover{color:var(--ap-tb-hover)}
    .mkb-item:hover svg [fill="white"],.mkb-item.is-hover svg [fill="white"]{fill:var(--ap-tb-hover)}
    .mkb-item:hover svg [stroke="white"],.mkb-item.is-hover svg [stroke="white"]{stroke:var(--ap-tb-hover)}
    .mkb.is-resaltado{outline-offset:-3px;border-radius:0}
    .mkb-vacio{font-size:12px;opacity:.75;font-style:italic}

    /* Debajo, un header esbozado para dar contexto: no se edita acá. */
    .mkb-header{height:74px;display:flex;align-items:center;justify-content:space-between;max-width:1140px;margin:0 auto;padding:0 24px;opacity:.55}
    .mkb-header__logo{width:120px;height:26px;border-radius:5px;background:#D0D5DD}
    .mkb-header__nav{display:flex;gap:26px}
    .mkb-header__nav i{display:block;width:62px;height:9px;border-radius:5px;background:#E4E7EC}
    .mkb-hero{height:64px;background:linear-gradient(180deg,#F2F4F7,#fff)}

    .tb-leyenda{display:flex;gap:18px;flex-wrap:wrap;margin:12px 2px 0;font-size:.78rem;color:var(--ext-suave)}
    .tb-leyenda span{display:inline-flex;align-items:center;gap:6px}

    /* ------------------------------ Campos ------------------------------ */
    .tb-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;align-items:start;margin-top:20px}
    @media (max-width:1199px){.tb-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width:760px){.tb-grid{grid-template-columns:1fr}}
    .tb-grid .ext-card{margin-bottom:0;height:100%}
    .tb-grid .ext-campos{grid-template-columns:1fr}

    .tb-campo + .tb-campo{margin-top:14px}
    .tb-campo label{font-size:.84rem;font-weight:500;margin-bottom:6px;display:block}
    .tb-campo .input-group-text{background:#F4F7FA;border-color:var(--ext-borde);color:#667085;width:40px;justify-content:center}
    .tb-campo .form-control{font-size:.88rem;border-color:var(--ext-borde)}
    .tb-campo .form-control:focus{border-color:var(--ext-azul);box-shadow:0 0 0 3px rgba(0,152,218,.15)}
    .tb-campo.is-invalido .form-control,.tb-campo.is-invalido .input-group-text{border-color:#B42318}
    .tb-campo small{display:block;margin-top:5px;font-size:.74rem;color:var(--ext-suave)}
    .tb-campo small[data-tb-aviso]{color:var(--ext-alerta)}

    .tb-compartido{display:flex;gap:10px;align-items:flex-start;margin:0 0 16px;padding:10px 12px;border-radius:8px;background:#EAF6FC;color:#08607f;font-size:.78rem;line-height:1.45}
    .tb-compartido i{margin-top:2px}
    .tb-compartido a{color:#04506b;font-weight:600}
  </style>
@endsection

@section('content')
@php
  use App\Models\Contacto;

  $vars = collect($apariencia->variablesCss())->map(fn ($v, $k) => "$k:$v")->implode(';');
  $iconosAdmin = ['tel' => 'fa-solid fa-phone', 'mail' => 'fa-solid fa-envelope', 'tiktok' => 'fa-brands fa-tiktok', 'instagram' => 'fa-brands fa-instagram', 'facebook' => 'fa-brands fa-facebook-f'];
  $placeholders = ['tiktok' => 'https://www.tiktok.com/@usuario', 'instagram' => 'https://www.instagram.com/usuario', 'facebook' => 'https://www.facebook.com/pagina'];
  $valor = fn (string $campo) => old($campo, $contacto->{$campo});
@endphp

<div class="ext" data-ext data-errores="{{ $errors->any() ? 1 : 0 }}" style="{{ $vars }}">

  <div class="ext-encabezado">
    <div>
      <h1>Barra superior</h1>
      <p>La franja de arriba del header con el teléfono, el mail y las redes sociales. Lo que cambies se ve en la vista previa al instante; en el sitio, recién cuando guardás.</p>
      <div class="tb-chips">
        <span class="tb-chip"><i class="fa-solid fa-desktop" aria-hidden="true"></i> Sólo en computadora</span>
        <span class="tb-chip"><i class="fa-regular fa-clone" aria-hidden="true"></i> Igual en todas las páginas</span>
        <span class="tb-chip"><i class="fa-solid fa-arrows-up-to-line" aria-hidden="true"></i> Se oculta al hacer scroll</span>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: revisá los campos marcados.</div>
  @endif

  <form id="ext-form" method="POST" action="{{ route('extras.barra-superior.update') }}" novalidate>
    @csrf
    @method('PUT')

    {{-- ============================ Vista previa ============================ --}}
    <section class="ext-card tb-previa" aria-label="Vista previa de la barra superior">
      <p class="ext-preview__titulo">Vista previa <span class="text-muted fw-normal" style="font-size:.8rem">· a tamaño real</span></p>
      <div class="ext-vista mt-3">
        <div class="ext-marco">
          <div class="ext-marco__chrome"><i></i><i></i><i></i><span class="ext-marco__url">bmhbobinajes.com.ar</span></div>
          <div class="tb-previa__lienzo">
            <div class="mkb" data-parte="tb-fondo">
              <div class="mkb__fila">
                <div class="mkb__izq">
                  <a class="mkb-item" data-tb-item="tel" data-parte="tb-texto" tabindex="-1" @if (blank($valor('tel'))) hidden @endif>
                    @include('layouts.partials.iconos-barra', ['icono' => 'tel'])
                    <span data-tb-texto>{{ $valor('tel') }}</span>
                  </a>
                  <a class="mkb-item" data-tb-item="mail" data-parte="tb-texto" tabindex="-1" @if (blank($valor('mail'))) hidden @endif>
                    @include('layouts.partials.iconos-barra', ['icono' => 'mail'])
                    <span data-tb-texto>{{ $valor('mail') }}</span>
                  </a>
                  <span class="mkb-vacio" data-tb-vacio="izq" @unless (blank($valor('tel')) && blank($valor('mail'))) hidden @endunless>Sin teléfono ni mail</span>
                </div>
                <div class="mkb__der">
                  @foreach (array_keys(Contacto::REDES_BARRA) as $red)
                    <a class="mkb-item" data-tb-item="{{ $red }}" data-parte="tb-texto" tabindex="-1" title="{{ Contacto::REDES_BARRA[$red] }}" @if (blank($valor($red))) hidden @endif>
                      @include('layouts.partials.iconos-barra', ['icono' => $red])
                    </a>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="mkb-header" aria-hidden="true">
              <span class="mkb-header__logo"></span>
              <span class="mkb-header__nav"><i></i><i></i><i></i><i></i></span>
            </div>
            <div class="mkb-hero" aria-hidden="true"></div>
            <span class="tb-previa__rotulo" aria-hidden="true">Header</span>
          </div>
        </div>
      </div>
      <p class="tb-leyenda">
        <span><i class="fa-regular fa-hand-pointer" aria-hidden="true"></i> Pasá el mouse por los datos para ver el color al pasar el mouse.</span>
        <span><i class="fa-regular fa-eye-slash" aria-hidden="true"></i> Un dato vacío no aparece en la barra.</span>
      </p>
    </section>

    <div class="tb-grid">
      {{-- ============================== Contacto ============================== --}}
      <section class="ext-card">
        <h2>Teléfono y mail</h2>
        <p class="tb-compartido">
          <i class="fa-solid fa-link" aria-hidden="true"></i>
          <span>Son los mismos datos de la sección <a href="{{ route('dashboard.contacto') }}">Contacto</a>: si los cambiás acá, también cambian en el footer y en la página de Contacto.</span>
        </p>

        <div class="tb-campo {{ $errors->has('tel') ? 'is-invalido' : '' }}" data-tb-campo="tel">
          <label for="tb-tel">Teléfono</label>
          <div class="input-group">
            <span class="input-group-text"><i class="{{ $iconosAdmin['tel'] }}" aria-hidden="true"></i></span>
            <input type="text" id="tb-tel" name="tel" class="form-control" value="{{ $valor('tel') }}"
                   maxlength="40" inputmode="tel" autocomplete="off" placeholder="(011) 4482-2609" aria-describedby="tb-tel-ayuda">
          </div>
          @error('tel')
            <small class="text-danger">{{ $message }}</small>
          @else
            <small id="tb-tel-ayuda">Se muestra como lo escribas. Al tocarlo en el celular, llama.</small>
          @enderror
        </div>

        <div class="tb-campo {{ $errors->has('mail') ? 'is-invalido' : '' }}" data-tb-campo="mail">
          <label for="tb-mail">Mail</label>
          <div class="input-group">
            <span class="input-group-text"><i class="{{ $iconosAdmin['mail'] }}" aria-hidden="true"></i></span>
            <input type="email" id="tb-mail" name="mail" class="form-control" value="{{ $valor('mail') }}"
                   maxlength="255" autocomplete="off" placeholder="ventas@empresa.com" aria-describedby="tb-mail-ayuda">
          </div>
          @error('mail')
            <small class="text-danger">{{ $message }}</small>
          @else
            <small id="tb-mail-ayuda">Al hacer clic abre el correo del visitante.</small>
          @enderror
        </div>
      </section>

      {{-- ============================ Redes sociales ============================ --}}
      <section class="ext-card">
        <h2>Redes sociales</h2>
        <p class="ext-ayuda">Pegá el link del perfil. Si una red queda vacía, su ícono no aparece.</p>

        @foreach (Contacto::REDES_BARRA as $red => $nombre)
          <div class="tb-campo {{ $errors->has($red) ? 'is-invalido' : '' }}" data-tb-campo="{{ $red }}">
            <label for="tb-{{ $red }}">{{ $nombre }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="{{ $iconosAdmin[$red] }}" aria-hidden="true"></i></span>
              <input type="text" id="tb-{{ $red }}" name="{{ $red }}" class="form-control" value="{{ $valor($red) }}"
                     maxlength="255" inputmode="url" autocomplete="off" spellcheck="false" placeholder="{{ $placeholders[$red] }}">
            </div>
            @error($red)
              <small class="text-danger">{{ $message }}</small>
            @else
              <small data-tb-aviso hidden></small>
            @enderror
          </div>
        @endforeach
      </section>

      {{-- ============================== Colores ============================== --}}
      <section class="ext-card">
        <h2>Colores</h2>
        <p class="ext-ayuda">Al tocar un color se marca en naranja la parte de la barra que cambia.</p>
        <div class="ext-campos">
          @include('backend.extras._campo-color', ['campo' => 'barra_fondo', 'etiqueta' => 'Fondo', 'parte' => 'tb-fondo', 'contraste' => null, 'hover' => false])
          @include('backend.extras._campo-color', ['campo' => 'barra_texto', 'etiqueta' => 'Texto e íconos', 'parte' => 'tb-texto', 'contraste' => 'barra_fondo', 'hover' => false])
          @include('backend.extras._campo-color', ['campo' => 'barra_hover', 'etiqueta' => 'Texto e íconos al pasar el mouse', 'parte' => 'tb-texto', 'contraste' => 'barra_fondo', 'hover' => true])
        </div>
      </section>
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
  <script>
  (function () {
    'use strict';

    // Los datos de texto: la vista previa los sigue mientras se escribe.
    var raiz = document.querySelector('[data-ext]');
    var vacioIzq = raiz.querySelector('[data-tb-vacio="izq"]');

    function item(clave) { return raiz.querySelector('[data-tb-item="' + clave + '"]'); }

    function pintar(clave, valor) {
      var el = item(clave);
      if (!el) return;
      var texto = el.querySelector('[data-tb-texto]');
      if (texto) texto.textContent = valor;
      el.hidden = valor === '';
      vacioIzq.hidden = !(item('tel').hidden && item('mail').hidden);
    }

    // Un link sin protocolo se completa al guardar: se avisa para que no sorprenda.
    function avisarLink(campo, valor) {
      var aviso = campo.querySelector('[data-tb-aviso]');
      if (!aviso) return;
      var sinProtocolo = valor !== '' && !/^https?:\/\//i.test(valor);
      aviso.hidden = !sinProtocolo;
      if (sinProtocolo) aviso.textContent = 'Se va a guardar como https://' + valor.replace(/^\/+/, '');
    }

    raiz.querySelectorAll('[data-tb-campo]').forEach(function (campo) {
      var clave = campo.dataset.tbCampo;
      var input = campo.querySelector('input');

      input.addEventListener('input', function () {
        campo.classList.remove('is-invalido');
        pintar(clave, input.value.trim());
        avisarLink(campo, input.value.trim());
      });

      // Al editar un dato se marca dónde está en la barra.
      input.addEventListener('focus', function () { var el = item(clave); if (el) el.classList.add('is-resaltado'); });
      input.addEventListener('blur', function () { var el = item(clave); if (el) el.classList.remove('is-resaltado'); });

      avisarLink(campo, input.value.trim());
    });
  })();
  </script>
@endsection
