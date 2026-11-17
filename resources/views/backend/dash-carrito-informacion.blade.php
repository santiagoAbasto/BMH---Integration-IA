@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
  <style>
    /* ------------------------------------------------------------------
       Carrito › Información: el mensaje de «pedido enviado». Reusa el
       sistema de Extras (_estilos). Lo propio: el recorrido del pedido
       (tres pasos, el último es esta ventana) y la ventana en miniatura.
       ------------------------------------------------------------------ */
    .cinf{--cinf-verde:#479831;--cinf-navy:#0B2A4A}

    .cinf-campo + .cinf-campo{margin-top:18px}
    .cinf-campo label{display:flex;justify-content:space-between;align-items:baseline;gap:10px;font-weight:600;font-size:.88rem;margin-bottom:5px}
    .cinf-contador{font-weight:400;font-size:.74rem;color:var(--ext-suave);font-variant-numeric:tabular-nums}
    .cinf-contador.is-cerca{color:var(--ext-alerta)}
    .cinf-campo textarea{min-height:128px;resize:vertical;line-height:1.5}
    .cinf-campo small{display:block;font-size:.76rem;color:var(--ext-suave);margin-top:5px}
    .cinf-campo .is-invalid ~ .invalid-feedback{display:block}

    /* El recorrido: dónde aparece la ventana, en el orden en que le pasa al cliente. */
    .cinf-recorrido{list-style:none;margin:4px 0 0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;counter-reset:cinf}
    .cinf-recorrido li{position:relative;padding:12px 12px 12px 14px;border:1px solid var(--ext-borde);border-radius:9px;background:#fff;font-size:.8rem;color:var(--ext-suave);counter-increment:cinf}
    .cinf-recorrido li::before{content:counter(cinf);display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#EEF2F6;color:#475467;font-weight:600;font-size:.74rem;margin-bottom:8px}
    .cinf-recorrido b{display:block;color:var(--ext-tinta);font-weight:600;font-size:.84rem;margin-bottom:2px}
    .cinf-recorrido li.is-aca{border-color:var(--ext-azul);background:#F2FAFE;box-shadow:0 0 0 3px rgba(0,152,218,.12)}
    .cinf-recorrido li.is-aca::before{background:var(--ext-azul);color:#fff}
    .cinf-recorrido li:not(:last-child)::after{content:"";position:absolute;top:50%;right:-8px;width:6px;height:6px;border-top:2px solid #C9D3DE;border-right:2px solid #C9D3DE;transform:translateY(-50%) rotate(45deg)}
    .cinf-notas{margin:14px 0 0;padding:0;list-style:none;display:grid;gap:6px;font-size:.8rem;color:var(--ext-suave)}
    .cinf-notas li{display:grid;grid-template-columns:16px 1fr;gap:8px}
    .cinf-notas i{color:#98A2B3;margin-top:3px}

    /* ---------------------------- Vista previa ---------------------------- */
    .cinf-escenario{position:relative;container-type:inline-size;border-radius:0 0 8px 8px;overflow:hidden;background:#F3F5F8;padding:7cqw 5cqw}
    .cinf-fondo{position:absolute;inset:0;display:flex;flex-direction:column}
    .cinf-fondo__header{height:42px;background:var(--cinf-navy)}
    .cinf-fondo__cuerpo{flex:1;display:grid;grid-template-columns:3fr 2fr;gap:4cqw;padding:5cqw}
    .cinf-fondo__cuerpo span{border-radius:8px;background:#fff}
    .cinf-fondo::after{content:"";position:absolute;inset:0;background:rgba(0,0,0,.5)}
    /* La ventana: como el modal del carrito (título 32 px, texto 24 px light, botón verde), a escala. */
    .cinf-ventana{position:relative;margin:0 auto;max-width:520px;padding:clamp(20px,5cqw,34px) clamp(16px,4cqw,30px);border-radius:8px;background:#fff;text-align:center;box-shadow:0 18px 50px rgba(8,28,54,.35)}
    .cinf-ventana__titulo{margin:0;font-size:clamp(1.15rem,3.6cqw,1.6rem);font-weight:500;line-height:1.2;color:#000;overflow-wrap:anywhere}
    .cinf-ventana__texto{margin:14px 0 22px;font-size:clamp(.9rem,2.6cqw,1.15rem);font-weight:300;line-height:1.35;color:#000;overflow-wrap:anywhere}
    .cinf-ventana__texto p{margin:0 0 .6em}
    .cinf-ventana__texto p:last-child{margin:0}
    .cinf-ventana__boton{display:inline-block;padding:9px 26px;border-radius:8px;background:var(--cinf-verde);color:#fff;font-size:.86rem;font-weight:600}
    .cinf-vacio{color:#98A2B3;font-style:italic}

    @media (max-width:640px){
      .cinf-recorrido{grid-template-columns:1fr}
      .cinf-recorrido li:not(:last-child)::after{display:none}
    }
  </style>
@endsection

@section('content')
<div class="ext cinf" data-cinf>
  <x-osole::admin-page-header.v1 eyebrow="Carrito" title="Mensaje del pedido enviado" description="La ventana que ve el cliente apenas hace su pedido desde el carrito. Confirma que el pedido llegó y le dice qué pasa después." />

  @if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: {{ $errors->first() }}</div>
  @endif

  <div class="ext-grid">
    <div>
      <section class="ext-card" aria-labelledby="cinf-donde">
        <h2 id="cinf-donde">Cuándo aparece</h2>
        <ol class="cinf-recorrido">
          <li><b>Arma el carrito</b>Elige productos y cantidades.</li>
          <li><b>Hace el pedido</b>Revisa el carrito y toca «Realizar pedido».</li>
          <li class="is-aca" aria-current="step"><b>Ve esta ventana</b>Sobre el carrito, con el botón «Ir al inicio».</li>
        </ol>
        <ul class="cinf-notas">
          <li><i class="fa-regular fa-envelope" aria-hidden="true"></i><span>El mail de confirmación del pedido es aparte: este texto no se manda por mail.</span></li>
        </ul>
      </section>

      <form id="cinf-form" method="POST" action="{{ route('carrito.informacion.update') }}" novalidate>
        @csrf
        @method('PUT')

        <section class="ext-card" aria-labelledby="cinf-texto">
          <h2 id="cinf-texto">Texto de la ventana</h2>
          <p class="ext-ayuda">A la derecha ves cómo queda mientras escribís.</p>

          <div class="cinf-campo">
            <label for="cinf-titulo">Título <span class="cinf-contador" data-cinf-contador="titulo" aria-live="polite"></span></label>
            <input type="text" id="cinf-titulo" name="pedido_titulo" class="form-control @error('pedido_titulo') is-invalid @enderror"
                   value="{{ old('pedido_titulo', $informacion->pedido_titulo) }}" maxlength="{{ $maximoTitulo }}" required
                   placeholder="¡Recibimos tu pedido!" data-cinf-titulo>
            @error('pedido_titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="cinf-campo">
            <label for="cinf-mensaje">Mensaje <span class="cinf-contador" data-cinf-contador="mensaje" aria-live="polite"></span></label>
            <textarea id="cinf-mensaje" name="pedido" class="form-control @error('pedido') is-invalid @enderror" rows="5"
                      maxlength="{{ $maximoMensaje }}" required data-cinf-mensaje
                      placeholder="Gracias por elegirnos. Te vamos a escribir por mail para confirmar el pedido y coordinar la entrega.">{{ old('pedido', $mensajeTexto) }}</textarea>
            @error('pedido')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small>Contale qué pasa ahora: cuándo le confirman, cómo paga, cómo lo recibe. Dejá una línea en blanco para empezar otro párrafo.</small>
          </div>
        </section>
      </form>
    </div>

    <aside class="ext-preview" aria-label="Vista previa de la ventana">
      <div class="ext-card">
        <p class="ext-preview__titulo">Así lo ve el cliente</p>
        <p class="ext-preview__ayuda">Después de tocar «Realizar pedido».</p>

        <div class="ext-marco" style="margin-top:12px">
          <div class="ext-marco__chrome" aria-hidden="true"><i></i><i></i><i></i><span class="ext-marco__url">bmhbobinajes.com.ar/carrito</span></div>
          <div class="cinf-escenario">
            <div class="cinf-fondo" aria-hidden="true">
              <div class="cinf-fondo__header"></div>
              <div class="cinf-fondo__cuerpo"><span></span><span></span></div>
            </div>
            <div class="cinf-ventana" role="presentation">
              <p class="cinf-ventana__titulo" data-cinf-previa-titulo></p>
              <div class="cinf-ventana__texto" data-cinf-previa-mensaje></div>
              <span class="cinf-ventana__boton">Ir al inicio</span>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <div class="ext-guardar">
    <span class="ext-guardar__estado" role="status" data-cinf-estado>Sin cambios</span>
    <button type="button" class="btn btn-outline-secondary" data-cinf-descartar hidden>Descartar</button>
    <button type="submit" form="cinf-form" class="btn btn-primary" data-cinf-guardar disabled>
      <i class="fa-solid fa-check" aria-hidden="true"></i> Guardar cambios
    </button>
  </div>
</div>
@endsection

@section('script')
<script>
(function () {
  'use strict';

  var raiz = document.querySelector('[data-cinf]');
  if (!raiz) return;

  var form = document.getElementById('cinf-form');
  var titulo = raiz.querySelector('[data-cinf-titulo]');
  var mensaje = raiz.querySelector('[data-cinf-mensaje]');
  var previaTitulo = raiz.querySelector('[data-cinf-previa-titulo]');
  var previaMensaje = raiz.querySelector('[data-cinf-previa-mensaje]');
  var estado = raiz.querySelector('[data-cinf-estado]');
  var guardar = raiz.querySelector('[data-cinf-guardar]');
  var descartar = raiz.querySelector('[data-cinf-descartar]');
  var barra = raiz.querySelector('.ext-guardar');
  var original = { titulo: titulo.defaultValue, mensaje: mensaje.defaultValue };
  var enviando = false;

  function contar(campo, clave) {
    var max = parseInt(campo.getAttribute('maxlength'), 10);
    var quedan = max - campo.value.length;
    var el = raiz.querySelector('[data-cinf-contador="' + clave + '"]');
    el.textContent = quedan + ' de ' + max;
    el.classList.toggle('is-cerca', quedan <= Math.round(max * 0.1));
  }

  function vacio(texto) {
    var s = document.createElement('span'); s.className = 'cinf-vacio'; s.textContent = texto; return s;
  }

  // Misma regla que Carrito::htmlDeTexto: una línea en blanco separa párrafos; un salto simple, renglón.
  function pintar() {
    previaTitulo.textContent = '';
    previaTitulo.appendChild(titulo.value.trim() ? document.createTextNode(titulo.value.trim()) : vacio('Sin título'));

    previaMensaje.textContent = '';
    var parrafos = mensaje.value.replace(/\r\n/g, '\n').trim().split(/\n\s*\n/).filter(function (p) { return p.trim(); });
    if (!parrafos.length) previaMensaje.appendChild(vacio('Sin mensaje'));
    parrafos.forEach(function (p) {
      var el = document.createElement('p');
      p.trim().split('\n').forEach(function (linea, i) {
        if (i) el.appendChild(document.createElement('br'));
        el.appendChild(document.createTextNode(linea));
      });
      previaMensaje.appendChild(el);
    });

    contar(titulo, 'titulo');
    contar(mensaje, 'mensaje');

    var sucio = titulo.value !== original.titulo || mensaje.value !== original.mensaje;
    var completo = titulo.value.trim() && mensaje.value.trim();
    barra.classList.toggle('is-sucio', sucio);
    descartar.hidden = !sucio;
    guardar.disabled = !sucio || !completo;
    estado.textContent = !sucio ? 'Sin cambios' : completo ? 'Cambios sin guardar' : 'Completá el título y el mensaje';
  }

  titulo.addEventListener('input', pintar);
  mensaje.addEventListener('input', pintar);
  descartar.addEventListener('click', function () {
    titulo.value = original.titulo;
    mensaje.value = original.mensaje;
    pintar();
  });
  form.addEventListener('submit', function (e) {
    if (guardar.disabled) { e.preventDefault(); return; }
    enviando = true;
    guardar.disabled = true;
    guardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando…';
  });

  window.bmhAvisarSinGuardar(function () {
    return !enviando && (titulo.value !== original.titulo || mensaje.value !== original.mensaje);
  });

  pintar();
})();
</script>
@endsection
