@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
  <style>
    /* ------------------------------------------------------------------
       Imagen por defecto. Reusa el sistema de Extras (_estilos) y suma:
       mesa de luz con damero para ver transparencias, un visor que marca
       qué parte recorta la ficha del producto, y un checklist en vivo.
       ------------------------------------------------------------------ */
    .idef{--idef-tinta-2:#475467;--idef-ok:#12805C;--idef-ok-bg:#E7F6EF;--idef-error:#B42318;--idef-error-bg:#FDECEA;
      --idef-damero:linear-gradient(45deg,#E6EBF1 25%,transparent 25%),linear-gradient(-45deg,#E6EBF1 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#E6EBF1 75%),linear-gradient(-45deg,transparent 75%,#E6EBF1 75%)}
    .idef .ext-encabezado p{max-width:70ch}
    .idef-dato{display:inline-flex;align-items:baseline;gap:8px;margin-top:12px;padding:7px 12px;border-radius:999px;background:#EAF6FC;color:#08607f;font-size:.8rem}
    .idef-dato strong{font:600 1rem ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#04506b}

    .idef-cabecera{display:flex;align-items:flex-start;justify-content:space-between;gap:8px 12px;flex-wrap:wrap;margin-bottom:14px}
    .idef-cabecera > div{min-width:0;flex:1 1 220px}
    .idef-estado{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap}
    .idef-estado::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
    .idef-estado--propia{background:var(--idef-ok-bg);color:var(--idef-ok)}
    .idef-estado--bmh{background:#EEF2F6;color:var(--idef-tinta-2)}

    /* Zona para soltar: es el label del input, así funciona con teclado. */
    .idef-drop{position:relative;display:block;margin:0;border:2px dashed #C9D3DE;border-radius:12px;overflow:hidden;cursor:pointer;transition:border-color .2s,box-shadow .2s}
    .idef-drop:hover{border-color:var(--ext-azul)}
    .idef-archivo:focus-visible + .idef-drop{outline:3px solid var(--ext-azul);outline-offset:3px}
    .idef-drop.is-arrastrando{border-color:var(--ext-azul);box-shadow:0 0 0 6px rgba(0,152,218,.14)}
    .idef-drop.is-error{border-color:var(--idef-error)}
    .idef-mesa{height:260px;display:flex;align-items:center;justify-content:center;padding:22px;background-color:#F7F9FB;background-image:var(--idef-damero);background-size:22px 22px;background-position:0 0,0 11px,11px -11px,-11px 0}
    .idef-mesa img{max-width:100%;max-height:100%;object-fit:contain;filter:drop-shadow(0 6px 16px rgba(16,24,40,.12));transition:opacity .25s}
    .idef-drop__velo{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(10,37,56,.72);color:#fff;opacity:0;transition:opacity .2s;text-align:center;padding:16px}
    .idef-drop:hover .idef-drop__velo,.idef-drop.is-arrastrando .idef-drop__velo,.idef-archivo:focus-visible + .idef-drop .idef-drop__velo{opacity:1}
    .idef-drop__velo i{font-size:1.7rem}
    .idef-drop__velo b{font-size:.95rem;font-weight:600}
    .idef-drop__velo small{font-size:.76rem;opacity:.85}
    .idef-pie-drop{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:12px}
    .idef-nombre{font-size:.8rem;color:var(--ext-suave);overflow-wrap:anywhere;min-width:0}
    .idef-nombre.is-nueva{color:var(--ext-tinta);font-weight:500}
    .idef-ficha{display:inline-flex;gap:6px;flex-wrap:wrap}
    .idef-ficha span{font:500 .72rem ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;padding:3px 8px;border-radius:6px;background:#F1F4F8;color:var(--idef-tinta-2)}

    /* Checklist: arranca como recomendación y se vuelve verificación. */
    .idef-checks{list-style:none;margin:16px 0 0;padding:0;display:grid;gap:8px}
    .idef-check{display:grid;grid-template-columns:22px 1fr;gap:10px;align-items:start;font-size:.82rem;color:var(--idef-tinta-2)}
    .idef-check__icono{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;background:#EEF2F6;color:#98A2B3}
    .idef-check b{display:block;color:var(--ext-tinta);font-weight:600;font-size:.82rem}
    .idef-check small{display:block;font-size:.76rem;color:var(--ext-suave);margin-top:1px}
    .idef-check[data-estado="ok"] .idef-check__icono{background:var(--idef-ok-bg);color:var(--idef-ok)}
    .idef-check[data-estado="aviso"] .idef-check__icono{background:var(--ext-alerta-bg);color:var(--ext-alerta)}
    .idef-check[data-estado="error"] .idef-check__icono{background:var(--idef-error-bg);color:var(--idef-error)}
    .idef-check[data-estado="error"] small{color:var(--idef-error)}

    .idef-volver{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
    .idef-volver img{width:74px;height:74px;border-radius:9px;border:1px solid var(--ext-borde);object-fit:contain;background:#fff;flex:0 0 auto}
    .idef-volver div{flex:1 1 180px;min-width:0}
    .idef-volver p{margin:0;font-size:.82rem;color:var(--ext-suave)}

    /* ---------------------------- Vista previa ---------------------------- */
    .idef-previa-barra{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .idef-comparar button{border:0;background:none;padding:6px 13px;border-radius:6px;font-size:.8rem;color:var(--ext-suave);cursor:pointer}
    .idef-comparar button[aria-pressed="true"]{background:#fff;color:var(--ext-tinta);font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.1)}
    .idef-comparar button:focus-visible{outline:2px solid var(--ext-azul);outline-offset:1px}
    .idef-bloque{animation:idef-entra .5s cubic-bezier(.2,.8,.25,1) both}
    .idef-bloque + .idef-bloque{animation-delay:.08s}
    .idef-bloque + .idef-bloque + .idef-bloque{animation-delay:.16s}
    @keyframes idef-entra{from{opacity:0;transform:translateY(8px)}}

    /* 1. Card del catálogo (productoBmh): caja 420×365, contain, fondo blanco. */
    .idef-card{display:grid;grid-template-columns:minmax(0,42fr) minmax(0,58fr);gap:18px;padding:16px;border:1px solid #E7E9EC;border-radius:10px;background:#fff;font-family:Roboto,system-ui,sans-serif}
    .idef-card__img{aspect-ratio:420/365;display:flex;align-items:center;justify-content:center;background:#fff}
    .idef-card__img img{width:100%;height:100%;object-fit:contain}
    .idef-card__codigo{font-size:.86rem;font-weight:800;color:#0098DA}
    .idef-card__nombre{font-size:.92rem;font-weight:700;color:#1F2430;line-height:1.3;margin:2px 0 10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .idef-linea{display:block;height:8px;border-radius:5px;background:#EEF1F4;margin-bottom:8px}
    .idef-card__boton{display:inline-block;margin-top:6px;padding:7px 16px;border-radius:7px;background:#0098DA;color:#fff;font-size:.74rem;font-weight:600}

    /* 2. Ficha del producto (fotorama): 4:3 con cover, o sea recorta. */
    .idef-ficha-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;align-items:stretch}
    .idef-galeria{aspect-ratio:800/600;border-radius:8px;overflow:hidden;background:#EEF1F4}
    .idef-galeria img{width:100%;height:100%;object-fit:cover;display:block}
    .idef-encuadre{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:8px;background:#101828}
    /* El JS le da al marco el ancho y la proporción de la imagen medida: un
       SVG sin tamaño intrínseco colapsaría a 0 en un contenedor que se ajusta. */
    .idef-encuadre__marco{position:relative;display:block;width:150px;aspect-ratio:1;max-width:100%;overflow:hidden;border-radius:3px}
    .idef-encuadre__marco img{display:block;width:100%;height:100%;object-fit:fill}
    .idef-visor{position:absolute;box-shadow:0 0 0 999px rgba(16,24,40,.62);transition:all .35s cubic-bezier(.2,.8,.25,1)}
    .idef-visor i{position:absolute;width:12px;height:12px;border:2px solid #36C2F5}
    .idef-visor i:nth-child(1){top:-1px;left:-1px;border-right:0;border-bottom:0}
    .idef-visor i:nth-child(2){top:-1px;right:-1px;border-left:0;border-bottom:0}
    .idef-visor i:nth-child(3){bottom:-1px;left:-1px;border-right:0;border-top:0}
    .idef-visor i:nth-child(4){bottom:-1px;right:-1px;border-left:0;border-top:0}
    .idef-encuadre__nota{font-size:.72rem;color:#C9D6E3;text-align:center;line-height:1.4}
    .idef-encuadre__nota b{color:#fff;font-weight:600}

    /* 3. Miniaturas: partes relacionadas (196×178) y carrito (96×85). */
    .idef-minis{display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap}
    .idef-mini{display:flex;flex-direction:column;gap:6px;font-size:.72rem;color:var(--ext-suave)}
    .idef-mini__caja{border:1px solid #DFDFDF;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;padding:4px}
    .idef-mini__caja img{width:100%;height:100%;object-fit:contain}
    .idef-mini--partes .idef-mini__caja{width:147px;height:134px}
    .idef-mini--carrito .idef-mini__caja{width:96px;height:85px}

    @media (max-width:640px){
      .idef-card,.idef-ficha-grid{grid-template-columns:1fr}
      .idef-mesa{height:210px}
    }
    @media (prefers-reduced-motion:reduce){
      .idef-bloque{animation:none}
      .idef-visor,.idef-drop,.idef-drop__velo,.idef-mesa img{transition:none}
    }
  </style>
@endsection

@section('content')
@php
  $nombreEjemplo = $ejemplo?->nombre ?: 'Producto sin foto';
  $codigoEjemplo = $ejemplo?->codigo ?: 'COD-0000';
  $pesoMaximoMb = rtrim(rtrim(number_format($pesoMaximoKb / 1024, 1, ',', ''), '0'), ',');
@endphp

<div class="ext idef" data-idef
     data-actual="{{ $imagenActual }}"
     data-lado-minimo="{{ $ladoMinimo }}"
     data-peso-maximo="{{ $pesoMaximoKb * 1024 }}">

  <div class="ext-encabezado">
    <div>
      <h1>Imagen por defecto</h1>
      <p>Es la imagen que se muestra cuando un producto no tiene foto: en el catálogo, en la ficha del producto, en las partes relacionadas y en el carrito. A la derecha ves cómo queda antes de guardar.</p>
      <span class="idef-dato">
        <strong>{{ number_format($productosSinPortada, 0, ',', '.') }}</strong>
        {{ $productosSinPortada === 1 ? 'producto la usa hoy' : 'productos la usan hoy' }}
      </span>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: {{ $errors->first('imagen') }}</div>
  @endif

  <div class="ext-grid">
    <div>
      <form id="idef-form" method="POST" action="{{ route('extras.imagen-por-defecto.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="ext-card">
          <div class="idef-cabecera">
            <div>
              <h2>Imagen en uso</h2>
              <p class="ext-ayuda mb-0">Arrastrá una imagen sobre el recuadro o hacé clic para elegirla.</p>
            </div>
            @if ($usaPropia)
              <span class="idef-estado idef-estado--propia">Imagen propia</span>
            @else
              <span class="idef-estado idef-estado--bmh">Imagen de BMH</span>
            @endif
          </div>

          <input type="file" id="idef-archivo" name="imagen" class="ext-visually-hidden idef-archivo"
                 accept="image/jpeg,image/png,image/webp" aria-describedby="idef-checks" required>
          <label for="idef-archivo" class="idef-drop {{ $errors->has('imagen') ? 'is-error' : '' }}" data-idef-drop>
            <span class="idef-mesa">
              <img src="{{ $imagenActual }}" alt="Imagen por defecto actual" data-idef-img="principal">
            </span>
            <span class="idef-drop__velo" aria-hidden="true">
              <i class="fa-solid fa-image"></i>
              <b>Soltá la imagen o hacé clic para elegirla</b>
              <small>JPG, PNG o WEBP · hasta {{ $pesoMaximoMb }} MB</small>
            </span>
          </label>

          <div class="idef-pie-drop">
            <span class="idef-nombre" data-idef-nombre>{{ $usaPropia ? 'Imagen cargada desde el panel' : 'Logo de BMH sobre fondo gris claro' }}</span>
            <span class="idef-ficha" data-idef-ficha></span>
          </div>

          <ul class="idef-checks" id="idef-checks" aria-live="polite">
            <li class="idef-check" data-check="formato">
              <span class="idef-check__icono" aria-hidden="true"><i class="fa-solid fa-file-image"></i></span>
              <span><b>Formato</b><small data-texto>JPG, PNG o WEBP. PNG si necesitás fondo transparente.</small></span>
            </li>
            <li class="idef-check" data-check="peso">
              <span class="idef-check__icono" aria-hidden="true"><i class="fa-solid fa-weight-hanging"></i></span>
              <span><b>Peso</b><small data-texto>Hasta {{ $pesoMaximoMb }} MB. Menos de 300 KB carga más rápido en el catálogo.</small></span>
            </li>
            <li class="idef-check" data-check="tamano">
              <span class="idef-check__icono" aria-hidden="true"><i class="fa-solid fa-expand"></i></span>
              <span><b>Tamaño</b><small data-texto>Mínimo {{ $ladoMinimo }} × {{ $ladoMinimo }} px. Ideal 1200 × 900 px.</small></span>
            </li>
            <li class="idef-check" data-check="proporcion">
              <span class="idef-check__icono" aria-hidden="true"><i class="fa-solid fa-crop-simple"></i></span>
              <span><b>Proporción</b><small data-texto>4:3 horizontal: así la ficha del producto no la recorta.</small></span>
            </li>
          </ul>
        </section>
      </form>

      @if ($usaPropia)
        <section class="ext-card">
          <form method="POST" action="{{ route('extras.imagen-por-defecto.destroy') }}" class="idef-volver"
                data-idef-volver>
            @csrf
            @method('DELETE')
            <img src="{{ $imagenBmh }}" alt="Imagen por defecto de BMH">
            <div>
              <h2>Volver a la imagen de BMH</h2>
              <p>Borra la imagen propia y los productos sin foto vuelven a mostrar el logo del sitio.</p>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Restaurar
            </button>
          </form>
        </section>
      @endif
    </div>

    {{-- ============================ Vista previa ============================ --}}
    <aside class="ext-preview" aria-label="Vista previa de la imagen por defecto">
      <div class="ext-card">
        <div class="idef-previa-barra">
          <p class="ext-preview__titulo">Vista previa</p>
          <div class="ext-segmentos idef-comparar" data-idef-comparar hidden>
            <button type="button" data-ver="nueva" aria-pressed="true">Nueva</button>
            <button type="button" data-ver="actual" aria-pressed="false">Actual</button>
          </div>
        </div>

        <div class="idef-bloque">
          <p class="ext-estado">Catálogo · Zona de Clientes</p>
          <div class="idef-card">
            <div class="idef-card__img"><img src="{{ $imagenActual }}" alt="" data-idef-img></div>
            <div>
              <div class="idef-card__codigo">{{ $codigoEjemplo }}</div>
              <div class="idef-card__nombre">{{ $nombreEjemplo }}</div>
              <span class="idef-linea" style="width:78%"></span>
              <span class="idef-linea" style="width:62%"></span>
              <span class="idef-linea" style="width:70%"></span>
              <span class="idef-card__boton">Agregar al carrito</span>
            </div>
          </div>
        </div>

        <div class="idef-bloque">
          <p class="ext-estado">Ficha del producto · formato 4:3</p>
          <div class="idef-ficha-grid">
            <div class="idef-galeria"><img src="{{ $imagenActual }}" alt="" data-idef-img></div>
            <div class="idef-encuadre">
              <span class="idef-encuadre__marco">
                <img src="{{ $imagenActual }}" alt="" data-idef-img data-idef-encuadre>
                <span class="idef-visor" data-idef-visor style="inset:0"><i></i><i></i><i></i><i></i></span>
              </span>
              <span class="idef-encuadre__nota" data-idef-nota>Calculando el encuadre…</span>
            </div>
          </div>
        </div>

        <div class="idef-bloque">
          <p class="ext-estado">Miniaturas</p>
          <div class="idef-minis">
            <div class="idef-mini idef-mini--partes">
              <span class="idef-mini__caja"><img src="{{ $imagenActual }}" alt="" data-idef-img></span>
              Partes relacionadas
            </div>
            <div class="idef-mini idef-mini--carrito">
              <span class="idef-mini__caja"><img src="{{ $imagenActual }}" alt="" data-idef-img></span>
              Carrito
            </div>
          </div>
        </div>

        <p class="ext-pie"><i class="fa-regular fa-lightbulb" aria-hidden="true"></i> En la ficha, lo que queda fuera del recuadro celeste no se ve. Dejá el motivo principal centrado y con margen.</p>
      </div>
    </aside>
  </div>

  <div class="ext-guardar">
    <span class="ext-guardar__estado" role="status" data-idef-estado>Elegí una imagen para reemplazar la actual</span>
    <button type="button" class="btn btn-outline-secondary" data-idef-descartar hidden>Descartar</button>
    <button type="submit" form="idef-form" class="btn btn-primary" data-idef-guardar disabled>
      <i class="fa-solid fa-check" aria-hidden="true"></i> Guardar imagen
    </button>
  </div>
</div>
@endsection

@section('script')
<script>
(function () {
  'use strict';

  var raiz = document.querySelector('[data-idef]');
  if (!raiz) return;

  var TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
  var LADO_MINIMO = parseInt(raiz.dataset.ladoMinimo, 10);
  var PESO_MAXIMO = parseInt(raiz.dataset.pesoMaximo, 10);
  var PROPORCION = 4 / 3;

  var form = document.getElementById('idef-form');
  var input = document.getElementById('idef-archivo');
  var drop = raiz.querySelector('[data-idef-drop]');
  var imagenes = raiz.querySelectorAll('[data-idef-img]');
  var encuadre = raiz.querySelector('[data-idef-encuadre]');
  var visor = raiz.querySelector('[data-idef-visor]');
  var nota = raiz.querySelector('[data-idef-nota]');
  var nombre = raiz.querySelector('[data-idef-nombre]');
  var ficha = raiz.querySelector('[data-idef-ficha]');
  var comparar = raiz.querySelector('[data-idef-comparar]');
  var estado = raiz.querySelector('[data-idef-estado]');
  var guardar = raiz.querySelector('[data-idef-guardar]');
  var descartar = raiz.querySelector('[data-idef-descartar]');

  var actual = { url: raiz.dataset.actual, ancho: 0, alto: 0 };
  var nueva = null; // { url, ancho, alto, valida }
  var textoNombre = nombre.textContent;
  var textoEstado = estado.textContent;
  var enviando = false;

  // ----------------------------------------------------------- utilidades
  function kb(bytes) {
    return bytes < 1024 * 1024
      ? Math.round(bytes / 1024) + ' KB'
      : (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
  }
  function marcar(clave, estadoCheck, texto) {
    var li = raiz.querySelector('[data-check="' + clave + '"]');
    if (!li.dataset.original) li.dataset.original = li.querySelector('[data-texto]').textContent;
    if (estadoCheck) li.dataset.estado = estadoCheck; else delete li.dataset.estado;
    li.querySelector('[data-texto]').textContent = texto || li.dataset.original;
    li.querySelector('.idef-check__icono').innerHTML = '<i class="fa-solid ' + ({
      ok: 'fa-check', aviso: 'fa-triangle-exclamation', error: 'fa-xmark'
    }[estadoCheck] || li.dataset.icono || '') + '" aria-hidden="true"></i>';
  }
  raiz.querySelectorAll('[data-check]').forEach(function (li) {
    li.dataset.icono = li.querySelector('.idef-check__icono i').className.replace('fa-solid ', '');
  });
  function medir(url, listo) {
    var img = new Image();
    img.onload = function () { listo(img.naturalWidth, img.naturalHeight); };
    img.onerror = function () { listo(0, 0); };
    img.src = url;
  }

  // -------------------------------------------------------- encuadre 4:3
  // Qué parte de la imagen queda visible con object-fit: cover en 4:3.
  var marco = encuadre.parentElement;

  function pintarEncuadre(ancho, alto) {
    if (!ancho || !alto) { nota.textContent = ''; return; }
    var r = ancho / alto;
    var visible, perdido;

    // Hasta 240 px de ancho y 150 px de alto, respetando la proporción real.
    marco.style.aspectRatio = ancho + ' / ' + alto;
    marco.style.width = Math.round(Math.min(240, 150 * r)) + 'px';

    if (Math.abs(r - PROPORCION) < 0.02) {
      visor.style.cssText = 'left:0;top:0;width:100%;height:100%';
      nota.innerHTML = '<b>Se ve completa.</b> La proporción ya es 4:3.';
      return;
    }
    if (r > PROPORCION) {
      visible = PROPORCION / r;
      visor.style.cssText = 'top:0;height:100%;width:' + (visible * 100) + '%;left:' + ((1 - visible) / 2 * 100) + '%';
      perdido = Math.round((1 - visible) * 100);
      nota.innerHTML = '<b>Se recorta un ' + perdido + '%</b> de los costados.';
    } else {
      visible = r / PROPORCION;
      visor.style.cssText = 'left:0;width:100%;height:' + (visible * 100) + '%;top:' + ((1 - visible) / 2 * 100) + '%';
      perdido = Math.round((1 - visible) * 100);
      nota.innerHTML = '<b>Se recorta un ' + perdido + '%</b> de arriba y abajo.';
    }
  }

  // ------------------------------------------------------------- mostrar
  function mostrar(cual) {
    var fuente = cual === 'nueva' && nueva ? nueva : actual;
    imagenes.forEach(function (img) { img.src = fuente.url; });
    pintarEncuadre(fuente.ancho, fuente.alto);
    comparar.querySelectorAll('button').forEach(function (b) {
      b.setAttribute('aria-pressed', b.dataset.ver === cual ? 'true' : 'false');
    });
  }

  function ficharArchivo(archivo, ancho, alto) {
    ficha.innerHTML = '';
    [ancho && alto ? ancho + ' × ' + alto + ' px' : null, kb(archivo.size)].forEach(function (t) {
      if (!t) return;
      var s = document.createElement('span'); s.textContent = t; ficha.appendChild(s);
    });
  }

  function actualizarBarra() {
    var hayNueva = !!nueva;
    comparar.hidden = !hayNueva;
    descartar.hidden = !hayNueva;
    guardar.disabled = !(nueva && nueva.valida);
    drop.classList.toggle('is-error', !!nueva && !nueva.valida);
    raiz.querySelector('.ext-guardar').classList.toggle('is-sucio', hayNueva);
    if (!hayNueva) estado.textContent = textoEstado;
    else estado.textContent = nueva.valida
      ? 'Imagen nueva lista: guardá para aplicarla en todo el sitio'
      : 'Esta imagen no se puede usar: revisá los puntos marcados';
  }

  function limpiar() {
    if (nueva) URL.revokeObjectURL(nueva.url);
    nueva = null;
    input.value = '';
    nombre.textContent = textoNombre;
    nombre.classList.remove('is-nueva');
    ficha.innerHTML = '';
    ['formato', 'peso', 'tamano', 'proporcion'].forEach(function (c) { marcar(c, null); });
    mostrar('actual');
    actualizarBarra();
  }

  // ------------------------------------------------------ elegir archivo
  function elegir(archivo) {
    if (!archivo) { limpiar(); return; }
    if (nueva) URL.revokeObjectURL(nueva.url);

    nombre.textContent = archivo.name;
    nombre.classList.add('is-nueva');

    var formatoOk = TIPOS.indexOf(archivo.type) !== -1;
    var pesoOk = archivo.size <= PESO_MAXIMO;
    marcar('formato', formatoOk ? 'ok' : 'error', formatoOk ? null : 'Tiene que ser JPG, PNG o WEBP.');
    marcar('peso', pesoOk ? (archivo.size > 300 * 1024 ? 'aviso' : 'ok') : 'error',
      !pesoOk ? 'Pesa ' + kb(archivo.size) + ': el máximo es ' + kb(PESO_MAXIMO) + '.'
        : archivo.size > 300 * 1024 ? 'Pesa ' + kb(archivo.size) + ': se puede usar, pero conviene comprimirla.' : kb(archivo.size) + ', liviana.');

    if (!formatoOk) {
      nueva = { url: '', ancho: 0, alto: 0, valida: false };
      marcar('tamano', null); marcar('proporcion', null);
      ficharArchivo(archivo, 0, 0);
      actualizarBarra();
      return;
    }

    var url = URL.createObjectURL(archivo);
    medir(url, function (ancho, alto) {
      var tamanoOk = ancho >= LADO_MINIMO && alto >= LADO_MINIMO;
      var chica = tamanoOk && ancho < 800;
      marcar('tamano', !tamanoOk ? 'error' : chica ? 'aviso' : 'ok',
        !ancho ? 'No se pudo leer la imagen.'
          : !tamanoOk ? 'Mide ' + ancho + ' × ' + alto + ' px: el mínimo es ' + LADO_MINIMO + ' px por lado.'
          : chica ? ancho + ' × ' + alto + ' px: en pantallas grandes puede verse suave.'
          : ancho + ' × ' + alto + ' px, buena resolución.');

      var r = alto ? ancho / alto : 0;
      var desvio = r ? Math.abs(r - PROPORCION) / PROPORCION : 1;
      marcar('proporcion', !r ? null : desvio < 0.02 ? 'ok' : 'aviso',
        !r ? null : desvio < 0.02 ? '4:3, la ficha la muestra completa.'
          : 'No es 4:3: la ficha va a recortar parte. Mirá el encuadre en la vista previa.');

      nueva = { url: url, ancho: ancho, alto: alto, valida: pesoOk && tamanoOk };
      ficharArchivo(archivo, ancho, alto);
      mostrar('nueva');
      actualizarBarra();
    });
  }

  input.addEventListener('change', function () { elegir(input.files && input.files[0]); });

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

  comparar.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-ver]');
    if (b) mostrar(b.dataset.ver);
  });
  descartar.addEventListener('click', limpiar);

  form.addEventListener('submit', function (e) {
    if (!nueva || !nueva.valida) { e.preventDefault(); return; }
    enviando = true;
    guardar.disabled = true;
    guardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando…';
  });

  var volver = raiz.querySelector('[data-idef-volver]');
  if (volver) {
    volver.addEventListener('submit', function (e) {
      if (!confirm('¿Volver a la imagen de BMH? Se borra la imagen propia y los productos sin foto muestran el logo del sitio.')) {
        e.preventDefault();
        return;
      }
      enviando = true;
    });
  }

  window.addEventListener('beforeunload', function (e) {
    if (nueva && !enviando) { e.preventDefault(); e.returnValue = ''; }
  });

  // --------------------------------------------------------------- inicio
  medir(actual.url, function (ancho, alto) {
    actual.ancho = ancho; actual.alto = alto;
    if (!nueva) pintarEncuadre(ancho, alto);
  });
})();
</script>
@endsection
