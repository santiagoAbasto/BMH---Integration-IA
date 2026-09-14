{{-- Comportamiento compartido de los editores de Header y Footer (admin → Extras). --}}
<script>
(function () {
  'use strict';

  var raiz = document.querySelector('[data-ext]');
  if (!raiz) return;
  var form = document.getElementById('ext-form');
  var vistas = raiz.querySelectorAll('.ext-vista');

  // Qué variable CSS pinta cada campo. Sale de App\Models\Apariencia para que
  // el editor y el front no puedan quedar con mapeos distintos.
  var VARS = @json(\App\Models\Apariencia::mapaVariables());
  var HEX = /^#[0-9A-F]{6}$/i;
  var TIPOS = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

  function avisar(mensaje) {
    if (window.iziToast) iziToast.warning({ title: 'Revisá esto', message: mensaje });
    else alert(mensaje);
  }

  // ---------------------------------------------------------------- colores
  var campos = {};

  raiz.querySelectorAll('.ext-color').forEach(function (el) {
    var nombre = el.dataset.campo;
    var c = campos[nombre] = {
      el: el,
      hex: el.querySelector('.ext-color__hex'),
      swatch: el.querySelector('.ext-color__swatch'),
      reset: el.querySelector('.ext-color__reset'),
      aviso: el.querySelector('.ext-color__aviso'),
      etiqueta: el.querySelector('label').textContent.trim()
    };

    c.swatch.addEventListener('input', function () {
      c.hex.value = c.swatch.value.toUpperCase();
      aplicar(nombre);
    });
    c.hex.addEventListener('input', function () {
      var v = c.hex.value.trim();
      if (v && v.charAt(0) !== '#') { v = '#' + v; c.hex.value = v; }
      if (HEX.test(v)) { c.hex.value = v.toUpperCase(); c.swatch.value = v.toLowerCase(); }
      aplicar(nombre);
    });
    c.reset.addEventListener('click', function () {
      c.hex.value = el.dataset.original;
      c.swatch.value = el.dataset.original.toLowerCase();
      aplicar(nombre);
      c.hex.focus();
    });
  });

  function valor(nombre) {
    var c = campos[nombre];
    var v = c ? c.hex.value.trim() : '';
    return HEX.test(v) ? v.toUpperCase() : null;
  }

  function pintar(nombre) {
    var c = campos[nombre], v = valor(nombre);
    c.el.classList.toggle('is-invalido', v === null);
    if (v !== null && VARS[nombre]) raiz.style.setProperty(VARS[nombre], v);
    c.reset.hidden = (v || '') === c.el.dataset.original;
  }

  function aplicar(nombre) {
    pintar(nombre);
    revisarContrastes();
    marcarCambios();
  }

  // Contraste WCAG: por debajo de 3:1 un texto o un borde cuesta distinguirlo.
  function luminancia(hex) {
    var canales = [1, 3, 5].map(function (i) {
      var x = parseInt(hex.substr(i, 2), 16) / 255;
      return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * canales[0] + 0.7152 * canales[1] + 0.0722 * canales[2];
  }
  function contraste(a, b) {
    var l1 = luminancia(a), l2 = luminancia(b);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }
  function revisarContrastes() {
    Object.keys(campos).forEach(function (nombre) {
      var c = campos[nombre], otro = c.el.dataset.contraste;
      if (!otro || !campos[otro]) return;
      var a = valor(nombre), b = valor(otro);
      var r = a && b ? contraste(a, b) : 99;
      c.aviso.hidden = r >= 3;
      if (r < 3) {
        c.aviso.textContent = 'Poco contraste con «' + campos[otro].etiqueta + '» (' +
          r.toFixed(1).replace('.', ',') + ':1): puede costar leerlo.';
      }
    });
  }

  // ------------------------------------------------------------------ logos
  var logos = JSON.parse(raiz.dataset.logos || '{}');

  // data-logo-estado: una clave de `logos` fija, o el name del radio que la elige.
  function logoDe(estado) {
    if (Object.prototype.hasOwnProperty.call(logos, estado)) return estado;
    var radio = form.querySelector('input[name="' + estado + '"]:checked');
    return radio ? radio.value : null;
  }
  function pintarLogos() {
    raiz.querySelectorAll('img[data-logo-estado]').forEach(function (img) {
      var cual = logoDe(img.dataset.logoEstado);
      if (!cual) return;
      img.dataset.logoActual = cual;
      if (logos[cual]) img.src = logos[cual];
    });
  }

  raiz.querySelectorAll('input[type="file"][data-logo-input]').forEach(function (input) {
    var clave = input.dataset.logoInput;
    var rotulo = raiz.querySelector('[data-archivo="' + clave + '"]');
    var urlOriginal = logos[clave];
    var textoOriginal = rotulo ? rotulo.textContent : '';

    input.addEventListener('change', function () {
      var archivo = input.files && input.files[0];
      if (!archivo) {
        logos[clave] = urlOriginal;
        if (rotulo) { rotulo.textContent = textoOriginal; rotulo.classList.remove('is-nuevo'); }
        pintarLogos(); marcarCambios();
        return;
      }
      if (TIPOS.indexOf(archivo.type) === -1 || archivo.size > 2 * 1024 * 1024) {
        avisar(TIPOS.indexOf(archivo.type) === -1
          ? 'El logo tiene que ser una imagen PNG, JPG, WEBP o SVG.'
          : 'El logo pesa más de 2 MB. Probá exportarlo más liviano.');
        input.value = '';
        input.dispatchEvent(new Event('change'));
        return;
      }
      var lector = new FileReader();
      lector.onload = function (e) {
        logos[clave] = e.target.result;
        if (rotulo) { rotulo.textContent = 'Nuevo: ' + archivo.name + ' · se sube al guardar'; rotulo.classList.add('is-nuevo'); }
        pintarLogos(); marcarCambios();
      };
      lector.readAsDataURL(archivo);
    });

    // Soltar un archivo sobre la tarjeta equivale a elegirlo.
    var tarjeta = input.closest('.ext-logo');
    if (!tarjeta) return;
    ['dragenter', 'dragover'].forEach(function (ev) {
      tarjeta.addEventListener(ev, function (e) { e.preventDefault(); tarjeta.classList.add('is-arrastrando'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      tarjeta.addEventListener(ev, function (e) { e.preventDefault(); tarjeta.classList.remove('is-arrastrando'); });
    });
    tarjeta.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  // Los radios que eligen logo; los de modo se manejan aparte.
  form.querySelectorAll('input[type="radio"]:not([data-modo])').forEach(function (r) {
    r.addEventListener('change', function () { pintarLogos(); marcarCambios(); });
  });

  // ------------------------------------------------- selector de modo y zoom
  // Un solo control cambia a la vez los campos y la vista previa: los dos
  // paneles del mismo modo llevan el mismo data-modo-panel.
  var modos = raiz.querySelectorAll('input[data-modo]');

  function mostrarModo(nombre) {
    raiz.querySelectorAll('[data-modo-panel]').forEach(function (p) {
      p.hidden = p.dataset.modoPanel !== nombre;
    });
    // Recién ahora los marcos visibles tienen ancho para calcular la escala.
    ajustarZoom();
  }
  modos.forEach(function (r) {
    r.addEventListener('change', function () { if (r.checked) mostrarModo(r.value); });
  });

  // La maqueta mide 1280px de verdad y se escala al ancho disponible.
  function ajustarZoom() {
    raiz.querySelectorAll('.ext-marco__ventana').forEach(function (v) {
      var lienzo = v.querySelector('.ext-lienzo');
      if (lienzo && v.clientWidth) lienzo.style.setProperty('--ext-zoom', (v.clientWidth / 1280).toFixed(4));
    });
  }
  if ('ResizeObserver' in window) {
    var ro = new ResizeObserver(ajustarZoom);
    raiz.querySelectorAll('.ext-marco__ventana').forEach(function (v) { ro.observe(v); });
  }
  window.addEventListener('resize', ajustarZoom);

  // ------------------------------------------------ resaltar lo que cambia
  function objetivos(parte) {
    if (!parte) return [];
    var selector = parte.indexOf('logo-') === 0
      ? 'img[data-logo-actual="' + parte.slice(5) + '"]'
      : '[data-parte~="' + parte + '"]';
    var encontrados = [];
    vistas.forEach(function (vista) {
      vista.querySelectorAll(selector).forEach(function (n) { encontrados.push(n); });
    });
    return encontrados;
  }
  function resaltar(control, encendido) {
    var hover = control.dataset.hover === '1';
    objetivos(control.dataset.parte).forEach(function (n) {
      n.classList.toggle('is-resaltado', encendido);
      if (hover) n.classList.toggle('is-hover', encendido);
    });
  }
  raiz.querySelectorAll('.ext-color, .ext-fila[data-parte], .ext-logo[data-parte]').forEach(function (control) {
    function encender() { resaltar(control, true); }
    function apagar() { if (!control.contains(document.activeElement)) resaltar(control, false); }
    control.addEventListener('focusin', encender);
    control.addEventListener('mouseenter', encender);
    control.addEventListener('focusout', function () { setTimeout(apagar, 0); });
    control.addEventListener('mouseleave', apagar);
  });

  // ----------------------------------------------- cambios sin guardar
  var barra = raiz.querySelector('.ext-guardar');
  var rotuloEstado = raiz.querySelector('.ext-guardar__estado');
  var inicial, sucio = false, enviando = false;

  function foto() {
    var partes = [];
    new FormData(form).forEach(function (v, k) {
      // `ext-modo` sólo dice qué pestaña se está mirando: no se guarda.
      if (k === '_token' || k === '_method' || k === 'ext-modo') return;
      partes.push(k + '=' + (v instanceof File ? (v.name ? v.name + ':' + v.size : '') : v));
    });
    return partes.join('&');
  }
  function marcarCambios() {
    if (inicial === undefined) return;
    sucio = foto() !== inicial;
    barra.classList.toggle('is-sucio', sucio);
    rotuloEstado.textContent = sucio ? 'Tenés cambios sin guardar' : 'Sin cambios';
  }

  window.addEventListener('beforeunload', function (e) {
    if (sucio && !enviando) { e.preventDefault(); e.returnValue = ''; }
  });

  form.addEventListener('submit', function (e) {
    var invalido = Object.keys(campos).filter(function (k) { return valor(k) === null; })[0];
    if (invalido) {
      e.preventDefault();
      // El campo puede estar en un modo que no se está viendo.
      var panel = campos[invalido].el.closest('[data-modo-panel]');
      if (panel) {
        var radio = raiz.querySelector('input[data-modo][value="' + panel.dataset.modoPanel + '"]');
        if (radio) { radio.checked = true; mostrarModo(radio.value); }
      }
      campos[invalido].hex.focus();
      avisar('El color «' + campos[invalido].etiqueta + '» no tiene el formato #RRGGBB.');
      return;
    }
    enviando = true;
    var boton = form.querySelector('[data-guardar]');
    boton.disabled = true;
    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando…';
  });

  var descartar = raiz.querySelector('[data-descartar]');
  if (descartar) {
    descartar.addEventListener('click', function () {
      if (!sucio || confirm('¿Descartar los cambios sin guardar?')) {
        enviando = true;
        window.location.href = window.location.pathname;
      }
    });
  }

  // ------------------------------------------------------------- arranque
  Object.keys(campos).forEach(pintar);
  revisarContrastes();
  pintarLogos();
  ajustarZoom();
  // Si volvió con errores de validación, lo que se ve todavía no está guardado.
  inicial = raiz.dataset.errores === '1' ? '' : foto();
  marcarCambios();
})();
</script>
