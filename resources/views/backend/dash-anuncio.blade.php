@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
  <style>
    /* ------------------------------------------------------------------
       Anuncio. Reusa el sistema de Extras (_estilos). Lo propio: el
       interruptor de estado y el «escenario», una Zona de Clientes en
       miniatura con el anuncio encima, en la misma proporción y tamaño
       relativo que en el sitio (escritorio 1280 × 800 y celular 390 × 760).
       ------------------------------------------------------------------ */
    .anu{--anu-ok:#12805C;--anu-ok-bg:#E7F6EF;--anu-error:#B42318;--anu-error-bg:#FDECEA;--anu-navy:#0B2A4A}

    /* Estado: una fila con el interruptor y una frase que dice qué pasa hoy. */
    .anu-estado{display:grid;grid-template-columns:1fr auto;gap:6px 18px;align-items:center}
    .anu-estado h2{display:flex;align-items:center;gap:10px}
    .anu-pildora{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap;background:#EEF2F6;color:#475467}
    .anu-pildora::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
    .anu-pildora[data-visible="1"]{background:var(--anu-ok-bg);color:var(--anu-ok)}
    .anu-estado__frase{grid-column:1 / -1;margin:0;font-size:.86rem;color:var(--ext-suave);max-width:62ch}
    .anu-estado__frase[data-error]{color:var(--anu-error)}

    .anu-switch{position:relative;display:inline-block;width:52px;height:30px;flex:0 0 auto}
    .anu-switch input{position:absolute;inset:0;width:100%;height:100%;margin:0;opacity:0;cursor:pointer;z-index:1}
    .anu-switch input:disabled{cursor:not-allowed}
    .anu-switch span{position:absolute;inset:0;border-radius:999px;background:#CBD2DB;transition:background-color .2s}
    .anu-switch span::after{content:"";position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.25);transition:transform .2s cubic-bezier(.2,.8,.25,1)}
    .anu-switch input:checked + span{background:var(--anu-ok)}
    .anu-switch input:checked + span::after{transform:translateX(22px)}
    .anu-switch input:focus-visible + span{outline:3px solid var(--ext-azul);outline-offset:2px}
    .anu-switch input:disabled + span{opacity:.5}
    .anu-switch[aria-busy="true"] span::after{animation:anu-latido .8s ease-in-out infinite alternate}
    @keyframes anu-latido{to{opacity:.55}}

    /* Imagen: el recuadro es el label del input, así funciona con teclado. */
    .anu-drop{position:relative;display:block;margin:0;border:2px dashed #C9D3DE;border-radius:12px;overflow:hidden;cursor:pointer;background:#F7F9FB;transition:border-color .2s,box-shadow .2s}
    .anu-drop:hover{border-color:var(--ext-azul)}
    .anu-archivo:focus-visible + .anu-drop{outline:3px solid var(--ext-azul);outline-offset:3px}
    .anu-drop.is-arrastrando{border-color:var(--ext-azul);box-shadow:0 0 0 6px rgba(0,152,218,.14)}
    .anu-drop.is-error{border-color:var(--anu-error)}
    .anu-drop__mesa{display:flex;align-items:center;justify-content:center;height:230px;padding:18px}
    .anu-drop__mesa img{max-width:100%;max-height:100%;object-fit:contain;border-radius:6px;box-shadow:0 6px 18px rgba(16,24,40,.14)}
    .anu-drop__vacio{display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--ext-suave);text-align:center;font-size:.86rem}
    .anu-drop__vacio i{font-size:1.8rem;color:#98A2B3}
    .anu-drop__vacio b{color:var(--ext-tinta);font-weight:600}
    .anu-drop__velo{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(10,37,56,.74);color:#fff;opacity:0;transition:opacity .2s;text-align:center;padding:16px}
    .anu-drop:hover .anu-drop__velo,.anu-drop.is-arrastrando .anu-drop__velo,.anu-archivo:focus-visible + .anu-drop .anu-drop__velo{opacity:1}
    .anu-drop--vacia .anu-drop__velo{display:none}
    .anu-drop__velo i{font-size:1.6rem}
    .anu-drop__velo b{font-size:.95rem;font-weight:600}
    .anu-drop__velo small{font-size:.76rem;opacity:.85}
    .anu-pie-drop{display:flex;align-items:center;justify-content:space-between;gap:8px 12px;flex-wrap:wrap;margin-top:10px}
    .anu-nombre{font-size:.8rem;color:var(--ext-suave);overflow-wrap:anywhere;min-width:0}
    .anu-nombre.is-nueva{color:var(--ext-tinta);font-weight:500}
    .anu-ficha{display:inline-flex;gap:6px;flex-wrap:wrap}
    .anu-ficha span{font-size:.74rem;padding:3px 8px;border-radius:6px;background:#F1F4F8;color:#475467;font-variant-numeric:tabular-nums}

    .anu-checks{list-style:none;margin:14px 0 0;padding:0;display:grid;gap:7px}
    .anu-check{display:grid;grid-template-columns:20px 1fr;gap:10px;align-items:start;font-size:.8rem;color:#475467}
    .anu-check__icono{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.62rem;background:#EEF2F6;color:#98A2B3}
    .anu-check[data-estado="ok"] .anu-check__icono{background:var(--anu-ok-bg);color:var(--anu-ok)}
    .anu-check[data-estado="aviso"] .anu-check__icono{background:var(--ext-alerta-bg);color:var(--ext-alerta)}
    .anu-check[data-estado="error"] .anu-check__icono{background:var(--anu-error-bg);color:var(--anu-error)}
    .anu-check[data-estado="error"]{color:var(--anu-error)}

    .anu-campo{margin-top:18px}
    .anu-campo label{display:block;font-weight:600;font-size:.86rem;margin-bottom:4px}
    .anu-campo small{display:block;font-size:.76rem;color:var(--ext-suave);margin-top:5px}

    /* Cómo funciona: tres hechos, en el orden en que le pasan al cliente. */
    .anu-pasos{list-style:none;margin:0;padding:0;display:grid;gap:12px;counter-reset:anu}
    .anu-pasos li{display:grid;grid-template-columns:26px 1fr;gap:10px;font-size:.84rem;color:#475467;counter-increment:anu}
    .anu-pasos li::before{content:counter(anu);width:26px;height:26px;border-radius:50%;background:#E5F5FC;color:#0369A1;font-weight:600;font-size:.78rem;display:flex;align-items:center;justify-content:center}
    .anu-pasos b{display:block;color:var(--ext-tinta);font-weight:600}
    .anu-ver{display:inline-flex;align-items:center;gap:6px;margin-top:14px;font-size:.84rem;font-weight:500}

    /* ---------------------------- Escenario ---------------------------- */
    .anu-previa-barra{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:12px}
    .anu-escenario-wrap{display:flex;justify-content:center}
    .anu-escenario{position:relative;container-type:size;width:100%;aspect-ratio:1280/800;border-radius:0 0 8px 8px;overflow:hidden;background:#F3F5F8}
    .anu-escenario-wrap[data-modo="celular"] .ext-marco{width:min(100%,260px);border:9px solid #1F2A37;border-radius:30px}
    .anu-escenario-wrap[data-modo="celular"] .ext-marco__chrome{display:none}
    .anu-escenario-wrap[data-modo="celular"] .anu-escenario{aspect-ratio:390/760;border-radius:0}
    .anu-escenario-wrap .ext-marco{width:100%;transition:width .3s cubic-bezier(.2,.8,.25,1)}

    /* La Zona de Clientes, apenas sugerida: header, títulos y una grilla de productos. */
    .anu-sitio{position:absolute;inset:0;display:flex;flex-direction:column}
    .anu-sitio__header{height:9cqh;min-height:14px;background:var(--anu-navy);display:flex;align-items:center;gap:2cqw;padding:0 4cqw}
    .anu-sitio__header i{display:block;height:1.6cqh;border-radius:2px;background:rgba(255,255,255,.35)}
    .anu-sitio__header i:first-child{width:9cqw;height:3cqh;background:#36C2F5;opacity:.85;margin-right:auto}
    .anu-sitio__cuerpo{flex:1;padding:4cqh 4cqw;display:grid;grid-template-columns:repeat(4,1fr);gap:2.4cqw;align-content:start}
    .anu-sitio__titulo{grid-column:1 / -1;height:3.4cqh;width:30%;border-radius:3px;background:#D5DCE4}
    .anu-sitio__card{aspect-ratio:3/4;border-radius:6px;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.06)}
    .anu-escenario-wrap[data-modo="celular"] .anu-sitio__cuerpo{grid-template-columns:repeat(2,1fr)}
    .anu-sitio::after{content:"";position:absolute;inset:0;background:rgba(0,0,0,.5)}

    /* La ventana del anuncio: misma regla que resources/css/sitio/anuncio.css, en unidades del escenario. */
    .anu-ventana{--r:1.4;position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
      width:min(54.69cqw,calc(100cqw - 2.5cqw * 2),calc((100cqh - 8cqh) * var(--r)));
      border-radius:min(1.25cqw,10px);overflow:hidden;background:#fff;box-shadow:0 2cqh 6cqh rgba(8,28,54,.4)}
    .anu-escenario-wrap[data-modo="celular"] .anu-ventana{width:min(91.8cqw,calc((100cqh - 8.4cqh) * var(--r)));border-radius:2.6cqw}
    .anu-ventana img{display:block;width:100%;aspect-ratio:var(--r);object-fit:contain}
    .anu-ventana__cerrar{position:absolute;top:1.6cqw;right:1.6cqw;width:max(14px,5.4cqw);max-width:22px;aspect-ratio:1;border-radius:50%;background:rgba(255,255,255,.94);box-shadow:0 1px 4px rgba(0,0,0,.2);display:flex;align-items:center;justify-content:center;color:var(--anu-navy);font-size:clamp(8px,2cqw,12px)}
    .anu-escenario-wrap[data-modo="celular"] .anu-ventana__cerrar{width:9cqw;max-width:none;top:3cqw;right:3cqw}
    .anu-ventana--vacia{display:flex;align-items:center;justify-content:center;aspect-ratio:1.4;color:var(--ext-suave);font-size:.8rem;padding:12px;text-align:center}
    .anu-escenario[data-oculto] .anu-ventana{opacity:.35;filter:grayscale(1)}
    .anu-escenario[data-oculto] .anu-sitio::after{background:rgba(0,0,0,.18)}
    .anu-sello{position:absolute;left:50%;bottom:4cqh;transform:translateX(-50%);padding:4px 12px;border-radius:999px;background:#fff;color:#475467;font-size:.74rem;font-weight:600;box-shadow:0 2px 8px rgba(16,24,40,.18);white-space:nowrap}

    @media (max-width:640px){ .anu-drop__mesa{height:190px} }
    @media (prefers-reduced-motion:reduce){
      .anu-switch span,.anu-switch span::after,.anu-drop,.anu-drop__velo,.anu-escenario-wrap .ext-marco{transition:none}
      .anu-switch[aria-busy="true"] span::after{animation:none}
    }
  </style>
@endsection

@section('content')
@php
  $pesoMaximoMb = rtrim(rtrim(number_format($pesoMaximoKb / 1024, 1, ',', ''), '0'), ',');
  $tieneImagen = filled($anuncio->imagen);
@endphp

<div class="ext anu" data-anu
     data-actual="{{ $anuncio->url() }}"
     data-proporcion="{{ $anuncio->proporcion() }}"
     data-ancho-minimo="{{ $anchoMinimo }}"
     data-peso-maximo="{{ $pesoMaximoKb * 1024 }}"
     data-url-mostrar="{{ route('anuncio.mostrar') }}">

  <x-osole::admin-page-header.v1 eyebrow="Carrito" title="Anuncio" description="Una imagen que aparece en una ventana cuando un cliente ingresa a la Zona de Clientes. Sirve para avisar una oferta, un cambio de horario o una novedad." />

  @if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: {{ $errors->first() }}</div>
  @endif

  <div class="ext-grid">
    <div>
      {{-- 1. Estado: se guarda al tocar el interruptor. --}}
      <section class="ext-card anu-estado" aria-labelledby="anu-estado-titulo">
        <h2 id="anu-estado-titulo">
          Mostrar a los clientes
          <span class="anu-pildora" data-anu-pildora data-visible="{{ $anuncio->visible() ? 1 : 0 }}">{{ $anuncio->visible() ? 'Visible' : 'Oculto' }}</span>
        </h2>
        <label class="anu-switch" title="{{ $tieneImagen ? '' : 'Primero cargá una imagen' }}">
          <input type="checkbox" role="switch" data-anu-switch aria-labelledby="anu-estado-titulo" aria-describedby="anu-estado-frase"
                 @checked($anuncio->visible()) @disabled(! $tieneImagen)>
          <span aria-hidden="true"></span>
        </label>
        <p class="anu-estado__frase" id="anu-estado-frase" data-anu-frase aria-live="polite">
          @if (! $tieneImagen)
            Cargá una imagen para poder mostrar el anuncio.
          @elseif ($anuncio->visible())
            Cada cliente lo ve una vez, al ingresar a la Zona de Clientes.
          @else
            Nadie lo ve. La imagen queda guardada para cuando lo vuelvas a activar.
          @endif
        </p>
      </section>

      {{-- 2. Imagen y descripción. --}}
      <form id="anu-form" method="POST" action="{{ route('anuncio.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="ext-card" aria-labelledby="anu-imagen-titulo">
          <h2 id="anu-imagen-titulo">Imagen</h2>
          <p class="ext-ayuda">Arrastrá una imagen sobre el recuadro o hacé clic para elegirla. Se muestra entera, sin recortes.</p>

          <input type="file" id="anu-archivo" name="imagen" class="ext-visually-hidden anu-archivo"
                 accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="anu-checks">
          <label for="anu-archivo" class="anu-drop {{ $tieneImagen ? '' : 'anu-drop--vacia' }} {{ $errors->has('imagen') ? 'is-error' : '' }}" data-anu-drop>
            <span class="anu-drop__mesa">
              <img src="{{ $anuncio->url() }}" alt="Imagen actual del anuncio" data-anu-miniatura @if(! $tieneImagen) hidden @endif>
              <span class="anu-drop__vacio" data-anu-vacio @if($tieneImagen) hidden @endif>
                <i class="fa-regular fa-image" aria-hidden="true"></i>
                <b>Elegí o soltá la imagen del anuncio</b>
                JPG, PNG, WEBP o GIF · hasta {{ $pesoMaximoMb }} MB
              </span>
            </span>
            <span class="anu-drop__velo" aria-hidden="true">
              <i class="fa-solid fa-arrow-up-from-bracket"></i>
              <b>Reemplazar la imagen</b>
              <small>Soltala acá o hacé clic para elegirla</small>
            </span>
          </label>

          <div class="anu-pie-drop">
            <span class="anu-nombre" data-anu-nombre>{{ $tieneImagen ? 'Imagen en uso' : 'Sin imagen' }}</span>
            <span class="anu-ficha" data-anu-ficha>
              @if ($anuncio->ancho && $anuncio->alto)<span>{{ $anuncio->ancho }} × {{ $anuncio->alto }} px</span>@endif
            </span>
          </div>

          <ul class="anu-checks" id="anu-checks" aria-live="polite">
            <li class="anu-check" data-check="formato"><span class="anu-check__icono" aria-hidden="true"><i class="fa-solid fa-file-image"></i></span><span data-texto>JPG, PNG, WEBP o GIF.</span></li>
            <li class="anu-check" data-check="peso"><span class="anu-check__icono" aria-hidden="true"><i class="fa-solid fa-weight-hanging"></i></span><span data-texto>Hasta {{ $pesoMaximoMb }} MB. Menos de 500 KB abre al instante.</span></li>
            <li class="anu-check" data-check="tamano"><span class="anu-check__icono" aria-hidden="true"><i class="fa-solid fa-expand"></i></span><span data-texto>Al menos {{ $anchoMinimo }} px de ancho. Ideal 1400 × 1000 px (se ve nítida en pantallas grandes).</span></li>
            <li class="anu-check" data-check="proporcion"><span class="anu-check__icono" aria-hidden="true"><i class="fa-solid fa-crop-simple"></i></span><span data-texto>Horizontal 7:5, como el cartel de siempre. Cualquier otra también entra: la ventana se adapta.</span></li>
          </ul>

          <div class="anu-campo">
            <label for="anu-alt">Qué dice la imagen</label>
            <input type="text" id="anu-alt" name="texto_alternativo" class="form-control" maxlength="200"
                   value="{{ old('texto_alternativo', $anuncio->texto_alternativo) }}" placeholder="Por ejemplo: 20% de descuento en compresores hasta el 30/11" data-anu-alt>
            <small>Lo escuchan quienes navegan con lector de pantalla y aparece si la imagen no carga.</small>
          </div>
        </section>
      </form>

      {{-- 3. Cómo funciona. --}}
      <section class="ext-card" aria-labelledby="anu-como-titulo">
        <h2 id="anu-como-titulo">Cómo lo ve el cliente</h2>
        <ol class="anu-pasos">
          <li><span><b>Ingresa con su usuario</b>El anuncio se abre sobre la primera página de la Zona de Clientes.</span></li>
          <li><span><b>Lo ve una sola vez</b>No vuelve a aparecer mientras navega. Si cierra sesión y vuelve a ingresar, lo ve otra vez.</span></li>
          <li><span><b>Lo cierra cuando quiere</b>Con la cruz, con la tecla Esc o tocando afuera de la imagen.</span></li>
        </ol>
        @if ($tieneImagen)
          <a class="anu-ver" href="{{ route('home', ['anuncio' => 1]) }}" target="_blank" rel="noopener">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrirlo en el sitio
          </a>
        @endif
      </section>
    </div>

    {{-- ============================ Vista previa ============================ --}}
    <aside class="ext-preview" aria-label="Vista previa del anuncio">
      <div class="ext-card">
        <div class="anu-previa-barra">
          <div>
            <p class="ext-preview__titulo">Así lo ve el cliente</p>
            <p class="ext-preview__ayuda" data-anu-previa-ayuda>Con la imagen {{ $tieneImagen ? 'guardada' : 'que elijas' }}.</p>
          </div>
          <div class="ext-segmentos" role="radiogroup" aria-label="Pantalla">
            <input type="radio" name="anu-modo" id="anu-modo-escritorio" value="escritorio" checked data-anu-modo>
            <label for="anu-modo-escritorio">Computadora</label>
            <input type="radio" name="anu-modo" id="anu-modo-celular" value="celular" data-anu-modo>
            <label for="anu-modo-celular">Celular</label>
          </div>
        </div>

        <div class="anu-escenario-wrap" data-modo="escritorio" data-anu-wrap>
          <div class="ext-marco">
            <div class="ext-marco__chrome" aria-hidden="true"><i></i><i></i><i></i><span class="ext-marco__url">bmhbobinajes.com.ar/productos-zona-home</span></div>
            <div class="anu-escenario" data-anu-escenario @if(! $anuncio->visible()) data-oculto @endif>
              <div class="anu-sitio" aria-hidden="true">
                <div class="anu-sitio__header"><i></i><i style="width:7cqw"></i><i style="width:6cqw"></i><i style="width:8cqw"></i></div>
                <div class="anu-sitio__cuerpo">
                  <span class="anu-sitio__titulo"></span>
                  @for ($i = 0; $i < 8; $i++)<span class="anu-sitio__card"></span>@endfor
                </div>
              </div>
              <div class="anu-ventana" style="--r: {{ $anuncio->proporcion() }}" data-anu-ventana>
                <img src="{{ $anuncio->url() }}" alt="" data-anu-previa @if(! $tieneImagen) hidden @endif>
                <span class="anu-ventana--vacia" data-anu-previa-vacia @if($tieneImagen) hidden @endif>Acá va la imagen del anuncio</span>
                <span class="anu-ventana__cerrar" aria-hidden="true">✕</span>
              </div>
              <span class="anu-sello" data-anu-sello @if($anuncio->visible()) hidden @endif>Oculto: los clientes no lo ven</span>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <div class="ext-guardar">
    <span class="ext-guardar__estado" role="status" data-anu-estado>Sin cambios</span>
    <button type="button" class="btn btn-outline-secondary" data-anu-descartar hidden>Descartar</button>
    <button type="submit" form="anu-form" class="btn btn-primary" data-anu-guardar disabled>
      <i class="fa-solid fa-check" aria-hidden="true"></i> Guardar cambios
    </button>
  </div>
</div>
@endsection

@section('script')
<script>
(function () {
  'use strict';

  var raiz = document.querySelector('[data-anu]');
  if (!raiz) return;

  var TIPOS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  var ANCHO_MINIMO = parseInt(raiz.dataset.anchoMinimo, 10);
  var PESO_MAXIMO = parseInt(raiz.dataset.pesoMaximo, 10);
  var PROPORCION_IDEAL = 7 / 5;
  var csrf = document.querySelector('#anu-form [name=_token]').value;

  var $ = function (sel) { return raiz.querySelector(sel); };
  var form = document.getElementById('anu-form');
  var input = document.getElementById('anu-archivo');
  var drop = $('[data-anu-drop]');
  var miniatura = $('[data-anu-miniatura]');
  var vacio = $('[data-anu-vacio]');
  var nombre = $('[data-anu-nombre]');
  var ficha = $('[data-anu-ficha]');
  var alt = $('[data-anu-alt]');
  var ventana = $('[data-anu-ventana]');
  var previa = $('[data-anu-previa]');
  var previaVacia = $('[data-anu-previa-vacia]');
  var previaAyuda = $('[data-anu-previa-ayuda]');
  var escenario = $('[data-anu-escenario]');
  var sello = $('[data-anu-sello]');
  var estado = $('[data-anu-estado]');
  var guardar = $('[data-anu-guardar]');
  var descartar = $('[data-anu-descartar]');
  var barra = $('.ext-guardar');
  var interruptor = $('[data-anu-switch]');
  var pildora = $('[data-anu-pildora]');
  var frase = $('[data-anu-frase]');

  var actual = { url: raiz.dataset.actual, r: parseFloat(raiz.dataset.proporcion) || PROPORCION_IDEAL };
  var altOriginal = alt.value;
  var textoNombre = nombre.textContent;
  var fichaOriginal = ficha.innerHTML;
  var nueva = null; // { url, r, valida }
  var enviando = false;

  function kb(bytes) {
    return bytes < 1024 * 1024 ? Math.round(bytes / 1024) + ' KB' : (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
  }
  raiz.querySelectorAll('[data-check]').forEach(function (li) {
    li.dataset.icono = li.querySelector('.anu-check__icono i').className;
    li.dataset.original = li.querySelector('[data-texto]').textContent;
  });
  function marcar(clave, estadoCheck, texto) {
    var li = raiz.querySelector('[data-check="' + clave + '"]');
    if (estadoCheck) li.dataset.estado = estadoCheck; else delete li.dataset.estado;
    li.querySelector('[data-texto]').textContent = texto || li.dataset.original;
    li.querySelector('.anu-check__icono').innerHTML = '<i class="' + ({
      ok: 'fa-solid fa-check', aviso: 'fa-solid fa-circle-info', error: 'fa-solid fa-xmark'
    }[estadoCheck] || li.dataset.icono) + '"></i>';
  }

  // ------------------------------------------------------------ vista previa
  function pintar(fuente) {
    var hay = !!(fuente && fuente.url);
    ventana.style.setProperty('--r', hay ? fuente.r : PROPORCION_IDEAL);
    previa.hidden = !hay;
    previaVacia.hidden = hay;
    miniatura.hidden = !hay;
    vacio.hidden = hay;
    drop.classList.toggle('anu-drop--vacia', !hay);
    if (hay) { previa.src = fuente.url; miniatura.src = fuente.url; }
  }

  raiz.querySelectorAll('[data-anu-modo]').forEach(function (radio) {
    radio.addEventListener('change', function () { $('[data-anu-wrap]').dataset.modo = radio.value; });
  });

  // ------------------------------------------------------------ cambios
  function sucio() { return !!nueva || alt.value !== altOriginal; }

  function actualizarBarra() {
    var hay = sucio();
    barra.classList.toggle('is-sucio', hay);
    descartar.hidden = !hay;
    guardar.disabled = !hay || (nueva && !nueva.valida);
    drop.classList.toggle('is-error', !!nueva && !nueva.valida);
    previaAyuda.textContent = nueva && nueva.valida ? 'Con la imagen nueva, todavía sin guardar.' : (actual.url ? 'Con la imagen guardada.' : 'Con la imagen que elijas.');
    estado.textContent = !hay ? 'Sin cambios'
      : nueva && !nueva.valida ? 'Esta imagen no se puede usar: revisá los puntos marcados'
      : nueva ? 'Imagen nueva lista: guardá para que la vean los clientes'
      : 'Cambiaste la descripción';
  }

  function limpiar() {
    if (nueva && nueva.url) URL.revokeObjectURL(nueva.url);
    nueva = null;
    input.value = '';
    alt.value = altOriginal;
    nombre.textContent = textoNombre;
    nombre.classList.remove('is-nueva');
    ficha.innerHTML = fichaOriginal;
    ['formato', 'peso', 'tamano', 'proporcion'].forEach(function (c) { marcar(c, null); });
    pintar(actual.url ? actual : null);
    actualizarBarra();
  }

  function elegir(archivo) {
    if (!archivo) { limpiar(); return; }
    if (nueva && nueva.url) URL.revokeObjectURL(nueva.url);

    nombre.textContent = archivo.name;
    nombre.classList.add('is-nueva');

    var formatoOk = TIPOS.indexOf(archivo.type) !== -1;
    var pesoOk = archivo.size <= PESO_MAXIMO;
    marcar('formato', formatoOk ? 'ok' : 'error', formatoOk ? null : 'Tiene que ser JPG, PNG, WEBP o GIF.');
    marcar('peso', !pesoOk ? 'error' : archivo.size > 500 * 1024 ? 'aviso' : 'ok',
      !pesoOk ? 'Pesa ' + kb(archivo.size) + ': el máximo es ' + kb(PESO_MAXIMO) + '.'
        : archivo.size > 500 * 1024 ? 'Pesa ' + kb(archivo.size) + ': se puede usar, pero comprimida abre más rápido.'
        : kb(archivo.size) + ', liviana.');

    if (!formatoOk) {
      nueva = { url: '', r: PROPORCION_IDEAL, valida: false };
      marcar('tamano', null); marcar('proporcion', null);
      ficha.innerHTML = '';
      actualizarBarra();
      return;
    }

    var url = URL.createObjectURL(archivo);
    var img = new Image();
    img.onload = img.onerror = function () {
      var ancho = img.naturalWidth || 0, alto = img.naturalHeight || 0;
      var tamanoOk = ancho >= ANCHO_MINIMO;
      marcar('tamano', !ancho ? 'error' : !tamanoOk ? 'error' : ancho < 1000 ? 'aviso' : 'ok',
        !ancho ? 'No se pudo leer la imagen.'
          : !tamanoOk ? 'Mide ' + ancho + ' px de ancho: el mínimo es ' + ANCHO_MINIMO + ' px.'
          : ancho < 1000 ? ancho + ' × ' + alto + ' px: en pantallas grandes puede verse un poco suave.'
          : ancho + ' × ' + alto + ' px, buena resolución.');

      var r = alto ? ancho / alto : PROPORCION_IDEAL;
      var desvio = Math.abs(r - PROPORCION_IDEAL) / PROPORCION_IDEAL;
      marcar('proporcion', !alto ? null : desvio < 0.03 ? 'ok' : 'aviso',
        !alto ? null : desvio < 0.03 ? '7:5, igual que la ventana de siempre.'
          : r < 1 ? 'Vertical: en computadora se ve más angosta. Mirá la vista previa.'
          : 'No es 7:5, pero entra entera: la ventana toma la forma de la imagen.');

      nueva = { url: url, r: r, valida: pesoOk && tamanoOk && ancho > 0 };
      ficha.innerHTML = '';
      [ancho ? ancho + ' × ' + alto + ' px' : null, kb(archivo.size)].forEach(function (t) {
        if (!t) return;
        var s = document.createElement('span'); s.textContent = t; ficha.appendChild(s);
      });
      pintar(nueva);
      actualizarBarra();
    };
    img.src = url;
  }

  input.addEventListener('change', function () { elegir(input.files && input.files[0]); });
  alt.addEventListener('input', actualizarBarra);
  ['dragenter', 'dragover'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-arrastrando'); });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-arrastrando'); });
  });
  drop.addEventListener('drop', function (e) {
    if (!e.dataTransfer || !e.dataTransfer.files.length) return;
    input.files = e.dataTransfer.files;
    input.dispatchEvent(new Event('change'));
  });
  descartar.addEventListener('click', limpiar);

  form.addEventListener('submit', function (e) {
    if (!sucio() || (nueva && !nueva.valida)) { e.preventDefault(); return; }
    enviando = true;
    guardar.disabled = true;
    guardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando…';
  });

  // ------------------------------------------------------------ mostrar / ocultar
  function pintarEstado(visible, texto, esError) {
    interruptor.checked = visible;
    pildora.dataset.visible = visible ? '1' : '0';
    pildora.textContent = visible ? 'Visible' : 'Oculto';
    sello.hidden = visible;
    if (visible) escenario.removeAttribute('data-oculto'); else escenario.setAttribute('data-oculto', '');
    frase.textContent = texto;
    if (esError) frase.setAttribute('data-error', ''); else frase.removeAttribute('data-error');
  }

  interruptor.addEventListener('change', function () {
    var quiere = interruptor.checked;
    var caja = interruptor.parentElement;
    interruptor.disabled = true;
    caja.setAttribute('aria-busy', 'true');

    fetch(raiz.dataset.urlMostrar, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ mostrar: quiere })
    })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        if (res.ok) pintarEstado(res.d.mostrar, res.d.mostrar
          ? 'Cada cliente lo ve una vez, al ingresar a la Zona de Clientes.'
          : 'Nadie lo ve. La imagen queda guardada para cuando lo vuelvas a activar.');
        else pintarEstado(!quiere, res.d.mensaje || 'No se pudo cambiar. Probá de nuevo.', true);
      })
      .catch(function () { pintarEstado(!quiere, 'No se pudo cambiar: revisá la conexión y probá de nuevo.', true); })
      .finally(function () { interruptor.disabled = false; caja.removeAttribute('aria-busy'); });
  });

  // Al cerrar la pestaña o al ir a otra pantalla del panel (Turbo).
  window.bmhAvisarSinGuardar(function () { return sucio() && !enviando; });
})();
</script>
@endsection
