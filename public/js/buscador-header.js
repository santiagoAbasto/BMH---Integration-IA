/*
 * Buscador del header (layouts/partials/buscador-header.blade.php).
 *
 * Sin este archivo el buscador sigue andando: es un form GET a /buscar. Esto
 * le suma:
 *   - sugerencias mientras se escribe (debounce + cancelación de pedidos
 *     viejos, así nunca pisa una respuesta nueva con una vieja);
 *   - navegación con flechas, Enter y Escape (patrón combobox de ARIA);
 *   - búsquedas recientes y ejemplos de qué se puede buscar;
 *   - atajo «/» para enfocar el campo;
 *   - en celular, se abre a pantalla completa desde la lupa del header.
 */
(function () {
  'use strict';

  var form = document.getElementById('buscador-header');
  if (!form) return;

  var DEBOUNCE_MS = 250;
  var MAX_RECIENTES = 5;
  var CLAVE_RECIENTES = 'bmh.busquedasRecientes';

  var url = form.dataset.sugerenciasUrl;
  var minimo = parseInt(form.dataset.minimo, 10) || 2;

  var input = form.querySelector('.buscador-header__input');
  var panel = form.querySelector('[data-buscador-panel]');
  var ayuda = form.querySelector('[data-buscador-ayuda]');
  var resultados = form.querySelector('[data-buscador-resultados]');
  var estado = form.querySelector('[data-buscador-estado]');
  var limpiar = form.querySelector('[data-buscador-limpiar]');
  var bloqueRecientes = form.querySelector('[data-buscador-recientes]');
  var listaRecientes = form.querySelector('[data-buscador-lista-recientes]');

  var cache = new Map();
  var pedido = null;      // AbortController del pedido en curso
  var temporizador = null;
  var opciones = [];      // elementos navegables con el teclado
  var activa = -1;
  var ultimoTermino = null;

  // ------------------------------------------------------------ utilidades

  function termino() {
    return input.value.replace(/\s+/g, ' ').trim();
  }

  function esCelular() {
    return window.matchMedia('(max-width: 990px)').matches;
  }

  function el(tag, clase, texto) {
    var nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto != null) nodo.textContent = texto;
    return nodo;
  }

  function escaparRegex(texto) {
    return texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  /** El mismo singular simple que BuscadorCatalogo::raiz(): rotores → rotor. */
  function raiz(palabra) {
    if (palabra.length > 5 && /es$/.test(palabra)) return palabra.slice(0, -2);
    if (palabra.length > 4 && /s$/.test(palabra)) return palabra.slice(0, -1);
    return palabra;
  }

  /** Texto con las palabras buscadas resaltadas, sin innerHTML. */
  function resaltar(texto, busqueda) {
    var fragmento = document.createDocumentFragment();
    var palabras = busqueda.toLowerCase().split(/[\s,;.\-\/]+/)
      .filter(function (p) { return p.length >= 2; })
      .map(raiz);
    if (!texto || palabras.length === 0) {
      fragmento.appendChild(document.createTextNode(texto || ''));
      return fragmento;
    }

    var patron = new RegExp('(' + palabras.map(escaparRegex).join('|') + ')', 'gi');
    texto.split(patron).forEach(function (parte, i) {
      // split con un grupo de captura alterna: texto, coincidencia, texto…
      fragmento.appendChild(i % 2 ? el('mark', null, parte) : document.createTextNode(parte));
    });
    return fragmento;
  }

  function anunciar(mensaje) {
    estado.textContent = mensaje;
  }

  // ------------------------------------------------------------- recientes

  function leerRecientes() {
    try {
      var guardadas = JSON.parse(localStorage.getItem(CLAVE_RECIENTES) || '[]');
      return Array.isArray(guardadas) ? guardadas.filter(function (r) { return typeof r === 'string'; }) : [];
    } catch (e) {
      return [];
    }
  }

  function guardarReciente(texto) {
    if (texto.length < minimo) return;
    var recientes = leerRecientes().filter(function (r) { return r.toLowerCase() !== texto.toLowerCase(); });
    recientes.unshift(texto);
    try {
      localStorage.setItem(CLAVE_RECIENTES, JSON.stringify(recientes.slice(0, MAX_RECIENTES)));
    } catch (e) { /* almacenamiento bloqueado: no es grave */ }
  }

  function pintarRecientes() {
    var recientes = leerRecientes();
    listaRecientes.textContent = '';
    bloqueRecientes.hidden = recientes.length === 0;

    recientes.forEach(function (texto) {
      var li = el('li');
      var link = el('a', 'buscador-header__reciente');
      link.href = form.action + '?q=' + encodeURIComponent(texto);
      link.appendChild(iconoReloj());
      link.appendChild(el('span', null, texto));
      li.appendChild(link);
      listaRecientes.appendChild(li);
    });
  }

  function iconoReloj() {
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', '16');
    svg.setAttribute('height', '16');
    svg.setAttribute('aria-hidden', 'true');
    svg.innerHTML = '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
    return svg;
  }

  // ----------------------------------------------------------------- panel

  function abrirPanel() {
    panel.hidden = false;
    input.setAttribute('aria-expanded', resultados.hidden ? 'false' : 'true');
    form.classList.add('buscador-header--abierto');
  }

  function cerrarPanel() {
    panel.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    form.classList.remove('buscador-header--abierto');
    marcarActiva(-1);
  }

  function mostrarAyuda() {
    pintarRecientes();
    ayuda.hidden = false;
    resultados.hidden = true;
    opciones = Array.prototype.slice.call(ayuda.querySelectorAll('.buscador-header__reciente, .buscador-header__ejemplo'));
    marcarActiva(-1);
    abrirPanel();
  }

  function mostrarResultados() {
    ayuda.hidden = true;
    resultados.hidden = false;
    opciones = Array.prototype.slice.call(resultados.querySelectorAll('[role="option"]'));
    marcarActiva(-1);
    abrirPanel();
  }

  function marcarActiva(indice) {
    opciones.forEach(function (op) {
      op.classList.remove('is-activa');
      op.setAttribute('aria-selected', 'false');
    });
    activa = indice;

    if (indice < 0 || !opciones[indice]) {
      input.removeAttribute('aria-activedescendant');
      return;
    }

    var opcion = opciones[indice];
    opcion.classList.add('is-activa');
    opcion.setAttribute('aria-selected', 'true');
    if (opcion.id) input.setAttribute('aria-activedescendant', opcion.id);
    opcion.scrollIntoView({ block: 'nearest' });
  }

  // ------------------------------------------------------------ resultados

  function pintar(datos, busqueda) {
    resultados.textContent = '';
    resultados.classList.remove('is-cargando');
    var n = 0;
    var id = function () { return 'buscador-opcion-' + (n++); };

    if (datos.productos.length === 0 && datos.categorias.length === 0) {
      var vacio = el('div', 'buscador-header__vacio');
      vacio.appendChild(el('p', 'buscador-header__vacio-titulo', 'Sin coincidencias para «' + busqueda + '»'));
      vacio.appendChild(el('p', 'buscador-header__vacio-texto', 'Revisá cómo está escrito, probá con menos palabras o buscá por el código BMH o una equivalencia.'));
      resultados.appendChild(vacio);
      anunciar('Sin coincidencias');
      mostrarResultados();
      return;
    }

    if (datos.productos.length) {
      var grupo = el('div', 'buscador-header__grupo');
      grupo.setAttribute('role', 'group');
      grupo.setAttribute('aria-label', 'Productos');
      grupo.appendChild(el('div', 'buscador-header__titulo', 'Productos'));

      datos.productos.forEach(function (p) {
        var item = el('a', 'buscador-header__producto');
        item.href = p.url;
        item.id = id();
        item.setAttribute('role', 'option');

        var foto = el('span', 'buscador-header__foto');
        var img = el('img');
        img.src = p.imagen;
        img.alt = '';
        img.loading = 'lazy';
        img.decoding = 'async';
        foto.appendChild(img);
        item.appendChild(foto);

        var info = el('span', 'buscador-header__info');
        var linea = el('span', 'buscador-header__linea');
        var codigo = el('span', 'buscador-header__codigo');
        codigo.appendChild(resaltar(p.codigo, busqueda));
        linea.appendChild(codigo);
        if (p.estado) {
          linea.appendChild(el('span', 'buscador-header__estado buscador-header__estado--' + (p.estado === 'Nuevo' ? 'nuevo' : 'reconstruido'), p.estado));
        }
        info.appendChild(linea);

        var nombre = el('span', 'buscador-header__nombre');
        nombre.appendChild(resaltar(p.nombre, busqueda));
        info.appendChild(nombre);

        var meta = [p.marca, p.categoria].filter(Boolean);
        if (meta.length) {
          var detalle = el('span', 'buscador-header__meta');
          detalle.appendChild(resaltar(meta.join(' · '), busqueda));
          info.appendChild(detalle);
        }
        item.appendChild(info);
        grupo.appendChild(item);
      });
      resultados.appendChild(grupo);
    }

    if (datos.categorias.length) {
      var rubros = el('div', 'buscador-header__grupo');
      rubros.setAttribute('role', 'group');
      rubros.setAttribute('aria-label', 'Rubros');
      rubros.appendChild(el('div', 'buscador-header__titulo', 'Rubros'));

      datos.categorias.forEach(function (c) {
        var rubro = el('a', 'buscador-header__rubro');
        rubro.href = c.url;
        rubro.id = id();
        rubro.setAttribute('role', 'option');
        rubro.appendChild(el('span', 'buscador-header__rubro-prefijo', 'Ver rubro'));
        var nombre = el('span', 'buscador-header__rubro-nombre');
        nombre.appendChild(resaltar(c.nombre, busqueda));
        rubro.appendChild(nombre);
        rubros.appendChild(rubro);
      });
      resultados.appendChild(rubros);
    }

    if (datos.total > 0) {
      var todos = el('a', 'buscador-header__ver-todos');
      todos.href = datos.ver_todos;
      todos.id = id();
      todos.setAttribute('role', 'option');
      todos.dataset.guardaReciente = busqueda;
      var texto = el('span', 'buscador-header__ver-todos-texto',
        datos.total === 1 ? 'Ver el resultado para ' : 'Ver los ' + datos.total.toLocaleString('es-AR') + ' resultados para ');
      texto.appendChild(el('strong', null, '«' + busqueda + '»'));
      todos.appendChild(texto);
      todos.appendChild(el('span', 'buscador-header__enter', '↵'));
      resultados.appendChild(todos);
    }

    anunciar(datos.total === 1 ? '1 producto encontrado' : datos.total + ' productos encontrados');
    mostrarResultados();
  }

  function pintarError() {
    resultados.textContent = '';
    resultados.classList.remove('is-cargando');
    var error = el('div', 'buscador-header__vacio');
    error.appendChild(el('p', 'buscador-header__vacio-titulo', 'No pudimos cargar las sugerencias'));
    error.appendChild(el('p', 'buscador-header__vacio-texto', 'Presioná Enter para ver los resultados igual.'));
    resultados.appendChild(error);
    mostrarResultados();
  }

  function cargando(si) {
    form.classList.toggle('buscador-header--cargando', si);
    // Los resultados anteriores quedan a la vista, atenuados, para que el
    // panel no parpadee entre tecla y tecla.
    resultados.classList.toggle('is-cargando', si && !resultados.hidden);
  }

  function buscar(busqueda) {
    if (busqueda === ultimoTermino && !resultados.hidden) return;
    ultimoTermino = busqueda;

    var clave = busqueda.toLowerCase();
    if (cache.has(clave)) {
      cargando(false);
      pintar(cache.get(clave), busqueda);
      return;
    }

    if (pedido) pedido.abort();
    pedido = new AbortController();
    cargando(true);

    fetch(url + '?q=' + encodeURIComponent(busqueda), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      signal: pedido.signal,
    })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (datos) {
        cache.set(clave, datos);
        // Si mientras tanto se siguió escribiendo, esta respuesta ya no sirve.
        if (termino() !== busqueda) return;
        cargando(false);
        pintar(datos, busqueda);
      })
      .catch(function (e) {
        if (e.name === 'AbortError') return;
        cargando(false);
        if (termino() === busqueda) pintarError();
      });
  }

  function alEscribir() {
    var busqueda = termino();
    limpiar.hidden = input.value === '';
    clearTimeout(temporizador);

    if (busqueda.length < minimo) {
      if (pedido) pedido.abort();
      cargando(false);
      ultimoTermino = null;
      mostrarAyuda();
      return;
    }

    // Primera búsqueda: en vez de dejar la ayuda, avisar que ya se está buscando.
    if (resultados.hidden) {
      ultimoTermino = null; // que buscar() no lo dé por resuelto
      resultados.textContent = '';
      resultados.appendChild(el('div', 'buscador-header__buscando', 'Buscando «' + busqueda + '»…'));
      mostrarResultados();
      cargando(true);
    }

    temporizador = setTimeout(function () { buscar(busqueda); }, DEBOUNCE_MS);
  }

  // ---------------------------------------------------------------- eventos

  input.addEventListener('input', alEscribir);

  input.addEventListener('focus', function () {
    if (termino().length < minimo) {
      mostrarAyuda();
    } else if (resultados.childElementCount && ultimoTermino === termino()) {
      mostrarResultados();
    } else {
      alEscribir();
    }
  });

  input.addEventListener('keydown', function (e) {
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (panel.hidden) { input.dispatchEvent(new Event('focus')); return; }
        if (opciones.length) marcarActiva(activa + 1 >= opciones.length ? 0 : activa + 1);
        break;
      case 'ArrowUp':
        e.preventDefault();
        if (opciones.length) marcarActiva(activa <= 0 ? opciones.length - 1 : activa - 1);
        break;
      case 'Enter':
        if (activa >= 0 && opciones[activa] && !panel.hidden) {
          e.preventDefault();
          opciones[activa].click();
        }
        break;
      case 'Escape':
        // En type=search el navegador borra el texto con Escape; acá el
        // primer Escape sólo cierra el panel.
        e.preventDefault();
        if (!panel.hidden) {
          cerrarPanel();
        } else if (input.value) {
          input.value = '';
          alEscribir();
        } else {
          cerrarCelular();
        }
        break;
      case 'Tab':
        cerrarPanel();
        break;
    }
  });

  form.addEventListener('submit', function (e) {
    var busqueda = termino();
    if (busqueda.length < minimo) {
      e.preventDefault();
      input.focus();
      form.classList.remove('buscador-header--sacudir');
      void form.offsetWidth; // reinicia la animación
      form.classList.add('buscador-header--sacudir');
      return;
    }
    input.value = busqueda;
    guardarReciente(busqueda);
  });

  limpiar.addEventListener('click', function () {
    input.value = '';
    alEscribir();
    input.focus();
  });

  panel.addEventListener('mousedown', function (e) {
    // Que el click en el panel no le saque el foco al campo antes de tiempo.
    if (e.target.closest('a, button')) e.preventDefault();
  });

  panel.addEventListener('click', function (e) {
    var ejemplo = e.target.closest('[data-buscador-ejemplo]');
    if (ejemplo) {
      input.value = ejemplo.dataset.buscadorEjemplo;
      alEscribir();
      clearTimeout(temporizador);
      buscar(termino());
      input.focus();
      return;
    }

    var todos = e.target.closest('[data-guarda-reciente]');
    if (todos) guardarReciente(todos.dataset.guardaReciente);

    if (e.target.closest('[data-buscador-borrar-recientes]')) {
      try { localStorage.removeItem(CLAVE_RECIENTES); } catch (err) { /* nada */ }
      mostrarAyuda();
      input.focus();
    }
  });

  panel.addEventListener('mousemove', function (e) {
    var opcion = e.target.closest('[role="option"], .buscador-header__reciente, .buscador-header__ejemplo');
    var indice = opcion ? opciones.indexOf(opcion) : -1;
    if (indice !== -1 && indice !== activa) marcarActiva(indice);
  });

  document.addEventListener('click', function (e) {
    if (!form.contains(e.target) && !e.target.closest('[data-buscador-abrir]')) cerrarPanel();
  });

  /*
   * ¿El visitante está escribiendo en otro campo? Un campo fuera de la
   * pantalla no cuenta: el login de «Zona Clientes» tiene autofocus y vive
   * escondido arriba del header, así que al cargar la página el foco ya
   * está ahí sin que nadie lo vea.
   */
  function escribiendoEn(t) {
    if (!t.isContentEditable && !/^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)) return false;
    var r = t.getBoundingClientRect();
    return r.width > 0 && r.bottom > 0 && r.top < window.innerHeight;
  }

  // Atajo «/»: enfoca el buscador salvo que ya se esté escribiendo en algo.
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    if (escribiendoEn(e.target)) return;
    e.preventDefault();
    if (esCelular()) abrirCelular(); else input.focus();
  });

  // ---------------------------------------------------------------- celular

  function abrirCelular() {
    document.body.classList.add('buscador-abierto');
    form.classList.add('buscador-header--pantalla');
    // iOS sólo abre el teclado si el foco llega en el mismo gesto del usuario.
    input.focus();
    ajustarPlaceholder();
  }

  function cerrarCelular() {
    if (!form.classList.contains('buscador-header--pantalla')) return;
    document.body.classList.remove('buscador-abierto');
    form.classList.remove('buscador-header--pantalla');
    cerrarPanel();
    input.blur();
  }

  document.querySelectorAll('[data-buscador-abrir]').forEach(function (boton) {
    boton.addEventListener('click', abrirCelular);
  });
  form.querySelector('[data-buscador-cerrar]').addEventListener('click', cerrarCelular);

  window.addEventListener('resize', function () {
    if (!esCelular()) cerrarCelular();
    ajustarPlaceholder();
  });

  // ------------------------------------------------------------ placeholder

  /*
   * El placeholder explica qué se puede buscar, así que no debe quedar
   * cortado: se usa la variante más larga que entra en el ancho del campo.
   */
  var placeholders = [];
  try { placeholders = JSON.parse(input.dataset.placeholders || '[]'); } catch (e) { /* queda el del HTML */ }
  var regla = document.createElement('canvas').getContext('2d');

  function ajustarPlaceholder() {
    if (!placeholders.length || !regla) return;
    // Se mide con la letra del placeholder, que es más liviana que la del texto.
    var estilo = getComputedStyle(input, '::placeholder');
    // Margen: el navegador pone los «…» un poco antes del borde.
    var ancho = input.clientWidth - 4;
    if (ancho <= 0) return; // oculto (celular cerrado)
    regla.font = estilo.fontWeight + ' ' + estilo.fontSize + ' ' + estilo.fontFamily;
    var elegido = placeholders[placeholders.length - 1];
    for (var i = 0; i < placeholders.length; i++) {
      if (regla.measureText(placeholders[i]).width <= ancho) { elegido = placeholders[i]; break; }
    }
    input.placeholder = elegido;
  }

  ajustarPlaceholder();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(ajustarPlaceholder);

  // Volver con el botón «atrás» del navegador no debe mostrar el panel viejo.
  window.addEventListener('pageshow', function () {
    cerrarPanel();
    cerrarCelular();
  });

  limpiar.hidden = input.value === '';
})();
