{{--
    Degradación de imágenes faltantes.

    En este checkout faltan 244 archivos que la base referencia (240 de producto
    + portadas de rubro y novedades): los binarios viven en el servidor, el dump
    SQL sólo trae las filas. En producción también pasa cuando se borra un
    archivo sin limpiar la fila.

    Hay que cubrir DOS casos, porque el sitio usa los dos:

      1. <img src="...">        → dispara `error`, se engancha en captura.
      2. background-image: url() → NO dispara nada: el elemento queda vacío y
         no hay forma de enterarse sin probar la URL a mano.

    Se hace en un solo lugar en vez de tocar decenas de vistas. Las URLs ya las
    iba a pedir el navegador igual, así que el sondeo del caso 2 sale del caché
    y no agrega tráfico real.

    Para saber qué falta: php artisan bmh:missing-images
--}}
<script>
  (function () {
    var PLACEHOLDER = @json(asset('imagenes/_placeholder.svg'));
    var MARCA = 'bmhFallback';

    // ---- Caso 1: <img> ----------------------------------------------------
    document.addEventListener('error', function (event) {
      var img = event.target;

      if (!(img instanceof HTMLImageElement)) return;
      if (img.dataset[MARCA] === '1') return;          // evita el bucle
      if (img.src === PLACEHOLDER) return;

      img.dataset[MARCA] = '1';
      img.setAttribute('data-bmh-original', img.getAttribute('src') || '');
      img.classList.add('bmh-img-faltante');
      img.src = PLACEHOLDER;
    }, true);

    // ---- Caso 2: background-image ------------------------------------------
    var verificadas = Object.create(null);  // url -> 'ok' | 'rota'

    function extraerUrl(valorCss) {
      if (!valorCss || valorCss === 'none') return null;
      var m = /url\((['"]?)(.*?)\1\)/.exec(valorCss);
      return m && m[2] ? m[2] : null;
    }

    function aplicarPlaceholder(el, url) {
      el.dataset[MARCA] = '1';
      el.setAttribute('data-bmh-original', url);
      el.classList.add('bmh-bg-faltante');
      el.style.backgroundImage = 'url("' + PLACEHOLDER + '")';
    }

    // El sitio declara TODOS sus fondos con `style` inline (verificado: 22 de 22
    // en la home). Acotar el selector así evita recorrer los ~800 elementos de
    // la página en cada mutación del DOM.
    var SELECTOR_FONDOS = '[style*="imagenes"]';

    function revisarFondos() {
      Array.prototype.forEach.call(document.querySelectorAll(SELECTOR_FONDOS), function (el) {
        if (el.dataset[MARCA] === '1') return;

        var url = extraerUrl(getComputedStyle(el).backgroundImage);
        if (!url || url.indexOf('/imagenes/') === -1) return;
        if (url.indexOf('_placeholder.svg') !== -1) return;

        var estado = verificadas[url];

        if (estado === 'ok') return;
        if (estado === 'rota') { aplicarPlaceholder(el, url); return; }

        // Primera vez que se ve esta URL: se sondea una sola vez.
        verificadas[url] = 'probando';

        var sonda = new Image();
        sonda.onload = function () { verificadas[url] = 'ok'; };
        sonda.onerror = function () {
          verificadas[url] = 'rota';
          // Todos los elementos que compartan esa imagen.
          Array.prototype.forEach.call(document.querySelectorAll(SELECTOR_FONDOS), function (otro) {
            if (otro.dataset[MARCA] === '1') return;
            if (extraerUrl(getComputedStyle(otro).backgroundImage) === url) {
              aplicarPlaceholder(otro, url);
            }
          });
        };
        sonda.src = url;
      });
    }

    function alEstarListo(fn) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
      } else {
        fn();
      }
    }

    alEstarListo(revisarFondos);
    window.addEventListener('load', revisarFondos);

    // El listado de productos se arma por JS: hay que revisar lo que aparece
    // después. Con debounce para no barrer el DOM en cada mutación.
    if (window.MutationObserver) {
      var pendiente = null;

      new MutationObserver(function () {
        clearTimeout(pendiente);
        pendiente = setTimeout(revisarFondos, 250);
      }).observe(document.documentElement, { childList: true, subtree: true });
    }
  })();
</script>

<style>
  .bmh-img-faltante,
  .bmh-bg-faltante {
    object-fit: contain;
    background-color: #EAEEF2;
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
  }
</style>
