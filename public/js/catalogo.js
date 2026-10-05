/*
 * Catálogo con filtros (resources/views/frontend/catalogo*.blade.php).
 *
 * Sin este archivo el catálogo funciona igual: el sidebar es un form GET y
 * «Cargar más productos» es un link. Esto le suma:
 *
 *   - desplegables con contadores y búsqueda, armados desde cada <select>;
 *   - filtros que se aplican al instante, sin recargar la página, con la URL
 *     y el botón «atrás» del navegador al día;
 *   - scroll infinito: las cards llegan de a tandas al acercarse al final;
 *   - en celular, el sidebar como panel lateral;
 *   - el sidebar fijo justo debajo del header cuando este queda arriba.
 */
(function () {
  'use strict';

  var raiz = document.querySelector('[data-catalogo]');
  if (!raiz) return;

  document.documentElement.classList.add('js-catalogo');

  var contenedor = raiz.querySelector('[data-catalogo-contenido]');
  // En la ficha los filtros abren el catálogo, conservando la selección.
  var esDetalle = raiz.hasAttribute('data-catalogo-detalle');
  var header = document.getElementById('site-header');
  var DEBOUNCE_TEXTO = 600;

  var pedidoFiltros = null;
  var temporizadorTexto = null;
  var observador = null;
  var cargandoPagina = false;

  // ------------------------------------------------------------ utilidades

  function el(tag, clase, texto) {
    var nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto != null) nodo.textContent = texto;
    return nodo;
  }

  function conParcial(url, parcial) {
    var u = new URL(url, window.location.href);
    u.searchParams.set('parcial', parcial);
    return u.toString();
  }

  function sinParciales(url) {
    var u = new URL(url, window.location.href);
    ['parcial', 'page', 'paginas'].forEach(function (p) { u.searchParams.delete(p); });
    return u.toString();
  }

  function pedirJson(url, signal) {
    return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: signal })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
  }

  function sinAcentos(texto) {
    return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  }

  function esCelular() {
    return window.matchMedia('(max-width: 991px)').matches;
  }

  // ----------------------------------------------------------- desplegable

  /*
   * Convierte un <select data-desplegable> en un botón que abre la lista de
   * opciones con sus contadores. El select queda escondido y sigue siendo el
   * que manda en el form: elegir una opción le cambia el valor y dispara
   * «change», como si se hubiera usado el select.
   */
  var cuentaDesplegables = 0;

  function crearDesplegable(select) {
    if (select.dataset.desplegableListo) return;
    select.dataset.desplegableListo = '1';

    var base = 'desplegable-' + (++cuentaDesplegables);
    var caja = el('div', 'desplegable');
    select.parentNode.insertBefore(caja, select);
    caja.appendChild(select);

    // El <label for> del select pasa a apuntar al botón.
    var boton = el('button', 'desplegable__boton');
    boton.type = 'button';
    boton.id = select.id;
    select.id = select.id + '-nativo';
    boton.setAttribute('aria-haspopup', 'listbox');
    boton.setAttribute('aria-expanded', 'false');
    boton.setAttribute('aria-controls', base + '-lista');

    var valor = el('span', 'desplegable__valor');
    var cantidad = el('span', 'desplegable__cantidad');
    var flecha = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    flecha.setAttribute('class', 'desplegable__flecha');
    flecha.setAttribute('width', '16');
    flecha.setAttribute('height', '16');
    flecha.setAttribute('viewBox', '0 0 24 24');
    flecha.setAttribute('aria-hidden', 'true');
    flecha.innerHTML = '<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
    boton.appendChild(valor);
    boton.appendChild(cantidad);
    boton.appendChild(flecha);
    caja.insertBefore(boton, select);

    var panel = el('div', 'desplegable__panel');
    panel.hidden = true;
    var buscar = null;
    if (select.dataset.buscable) {
      buscar = el('input', 'desplegable__buscar');
      buscar.type = 'search';
      buscar.placeholder = select.dataset.buscable;
      buscar.setAttribute('aria-label', select.dataset.buscable);
      buscar.autocomplete = 'off';
      buscar.spellcheck = false;
      panel.appendChild(buscar);
    }
    var lista = el('ul', 'desplegable__lista');
    lista.id = base + '-lista';
    lista.tabIndex = -1;
    lista.setAttribute('role', 'listbox');
    lista.setAttribute('aria-labelledby', boton.id);
    panel.appendChild(lista);
    caja.appendChild(panel);

    var opciones = Array.prototype.map.call(select.options, function (opcion, i) {
      var li = el('li', 'desplegable__opcion');
      li.id = base + '-op-' + i;
      li.setAttribute('role', 'option');
      li.dataset.valor = opcion.value;
      li.dataset.busqueda = sinAcentos(opcion.dataset.texto || opcion.textContent);
      li.appendChild(el('span', 'desplegable__opcion-texto', opcion.dataset.texto || opcion.textContent.trim()));
      if (opcion.dataset.cantidad != null) {
        li.appendChild(el('span', 'desplegable__opcion-cantidad', Number(opcion.dataset.cantidad).toLocaleString('es-AR')));
      }
      if (opcion.disabled) li.setAttribute('aria-disabled', 'true');
      lista.appendChild(li);
      return li;
    });
    var sinOpciones = el('li', 'desplegable__sin-opciones', 'Sin coincidencias');
    sinOpciones.hidden = true;
    lista.appendChild(sinOpciones);

    var visibles = opciones;
    var activa = -1;
    var control = buscar || lista; // quien recibe el teclado con el panel abierto

    function pintarBoton() {
      var elegida = select.options[select.selectedIndex];
      valor.textContent = elegida ? (elegida.dataset.texto || elegida.textContent.trim()) : '';
      var n = elegida && elegida.dataset.cantidad;
      cantidad.hidden = n == null;
      cantidad.textContent = n != null ? Number(n).toLocaleString('es-AR') : '';
      caja.classList.toggle('desplegable--elegido', select.value !== '');
      caja.classList.toggle('desplegable--deshabilitado', select.disabled);
      boton.disabled = select.disabled;
      if (select.disabled && select.dataset.ayuda) boton.title = select.dataset.ayuda;
      opciones.forEach(function (li) {
        li.setAttribute('aria-selected', li.dataset.valor === select.value ? 'true' : 'false');
      });
    }

    function marcar(indice) {
      visibles.forEach(function (li) { li.classList.remove('is-activa'); });
      activa = indice;
      var li = visibles[indice];
      if (!li) {
        control.removeAttribute('aria-activedescendant');
        return;
      }
      li.classList.add('is-activa');
      control.setAttribute('aria-activedescendant', li.id);
      li.scrollIntoView({ block: 'nearest' });
    }

    function filtrar(texto) {
      var buscado = sinAcentos(texto.trim());
      visibles = opciones.filter(function (li) {
        var mostrar = !buscado || li.dataset.busqueda.indexOf(buscado) !== -1;
        li.hidden = !mostrar;
        return mostrar;
      });
      sinOpciones.hidden = visibles.length > 0;
      var elegida = visibles.findIndex(function (li) { return li.dataset.valor === select.value; });
      marcar(buscado ? 0 : Math.max(0, elegida));
    }

    function abrir() {
      if (select.disabled) return;
      document.querySelectorAll('.desplegable--abierto').forEach(function (otra) {
        if (otra !== caja && otra.cerrarDesplegable) otra.cerrarDesplegable(false);
      });
      panel.hidden = false;
      caja.classList.add('desplegable--abierto');
      boton.setAttribute('aria-expanded', 'true');
      if (buscar) buscar.value = '';
      filtrar('');
      control.focus({ preventScroll: true });
      panel.scrollIntoView({ block: 'nearest' });
    }

    function cerrar(devolverFoco) {
      panel.hidden = true;
      caja.classList.remove('desplegable--abierto');
      boton.setAttribute('aria-expanded', 'false');
      if (devolverFoco) boton.focus({ preventScroll: true });
    }
    caja.cerrarDesplegable = cerrar;

    function elegir(li) {
      if (!li || li.getAttribute('aria-disabled') === 'true') return;
      var cambio = select.value !== li.dataset.valor;
      select.value = li.dataset.valor;
      cerrar(true);
      pintarBoton();
      if (cambio) select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    boton.addEventListener('click', function () {
      if (panel.hidden) abrir(); else cerrar(true);
    });

    boton.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        abrir();
      }
    });

    control.addEventListener('keydown', function (e) {
      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          marcar(Math.min(activa + 1, visibles.length - 1));
          break;
        case 'ArrowUp':
          e.preventDefault();
          marcar(Math.max(activa - 1, 0));
          break;
        case 'Home':
          if (!buscar) { e.preventDefault(); marcar(0); }
          break;
        case 'End':
          if (!buscar) { e.preventDefault(); marcar(visibles.length - 1); }
          break;
        case 'Enter':
          e.preventDefault();
          elegir(visibles[activa]);
          break;
        case 'Escape':
          e.preventDefault();
          e.stopPropagation();
          cerrar(true);
          break;
        case 'Tab':
          cerrar(false);
          break;
      }
    });

    if (buscar) {
      buscar.addEventListener('input', function () { filtrar(buscar.value); });
    }

    lista.addEventListener('mousedown', function (e) {
      // Que el click no le saque el foco al buscador antes de elegir.
      e.preventDefault();
    });
    lista.addEventListener('click', function (e) {
      elegir(e.target.closest('.desplegable__opcion'));
    });
    lista.addEventListener('mousemove', function (e) {
      var li = e.target.closest('.desplegable__opcion');
      var i = li ? visibles.indexOf(li) : -1;
      if (i !== -1 && i !== activa) marcar(i);
    });

    pintarBoton();
  }

  document.addEventListener('click', function (e) {
    document.querySelectorAll('.desplegable--abierto').forEach(function (caja) {
      if (!caja.contains(e.target) && caja.cerrarDesplegable) caja.cerrarDesplegable(false);
    });
  });

  // -------------------------------------------------------- aplicar filtros

  function urlDelFormulario(form) {
    var params = new URLSearchParams();
    new FormData(form).forEach(function (valor, clave) {
      if (String(valor).trim() !== '') params.append(clave, String(valor).trim());
    });
    var qs = params.toString();
    return form.action + (qs ? '?' + qs : '');
  }

  /** Lo enfocado antes de reemplazar el contenido, para devolverle el foco. */
  function recordarFoco() {
    var activo = document.activeElement;
    if (!activo || !contenedor.contains(activo) || !activo.id) return null;
    return {
      id: activo.id,
      inicio: typeof activo.selectionStart === 'number' ? activo.selectionStart : null,
    };
  }

  function devolverFoco(foco) {
    if (!foco) return;
    var nodo = document.getElementById(foco.id);
    if (!nodo || nodo.disabled) return;
    nodo.focus({ preventScroll: true });
    if (foco.inicio !== null && typeof nodo.setSelectionRange === 'function') {
      try { nodo.setSelectionRange(foco.inicio, foco.inicio); } catch (e) { /* type=search en algunos navegadores */ }
    }
  }

  function cargarContenido(url, historial) {
    if (esDetalle) {
      window.location.href = sinParciales(url);
      return;
    }
    if (pedidoFiltros) pedidoFiltros.abort();
    pedidoFiltros = new AbortController();
    raiz.classList.add('is-cargando');

    var foco = recordarFoco();
    var cuerpo = contenedor.querySelector('.filtros__cuerpo');
    var scrollFiltros = cuerpo ? cuerpo.scrollTop : 0;

    pedirJson(conParcial(url, 'contenido'), pedidoFiltros.signal)
      .then(function (datos) {
        contenedor.innerHTML = datos.html;
        iniciar();

        var nuevoCuerpo = contenedor.querySelector('.filtros__cuerpo');
        if (nuevoCuerpo) nuevoCuerpo.scrollTop = scrollFiltros;
        devolverFoco(foco);

        var limpia = sinParciales(url);
        if (historial === 'push') history.pushState({ catalogo: true }, '', limpia);
        if (historial === 'replace') history.replaceState({ catalogo: true }, '', limpia);

        // Si los resultados quedaron arriba, fuera de la vista, volver a ellos.
        var resultados = contenedor.querySelector('[data-catalogo-resultados]');
        var arriba = resultados.getBoundingClientRect().top - tope() - 16;
        if (arriba < 0 && !document.body.classList.contains('filtros-abiertos')) {
          window.scrollTo({ top: window.scrollY + arriba, behavior: 'smooth' });
        }

        raiz.classList.remove('is-cargando');
        document.dispatchEvent(new CustomEvent('catalogo:cards'));
      })
      .catch(function (error) {
        if (error.name === 'AbortError') return;
        // Si algo falla, la navegación de siempre.
        window.location.href = sinParciales(url);
      });
  }

  function aplicar(form, origen) {
    // Lo que depende de otro filtro deja de valer si ese cambia.
    if (origen && origen.name === 'vehiculo' && form.elements.modelo) form.elements.modelo.value = '';
    if (origen && origen.name === 'categoria') {
      form.querySelectorAll('select[name^="atributo["]').forEach(function (s) { s.value = ''; });
    }
    clearTimeout(temporizadorTexto);
    cargarContenido(urlDelFormulario(form), 'push');
  }

  contenedor.addEventListener('change', function (e) {
    // .form y no closest(): el orden por código está fuera del form (form="…").
    var form = e.target.form;
    if (!form || !form.matches('[data-catalogo-form]') || e.target.matches('[data-catalogo-texto]')) return;
    aplicar(form, e.target);
  });

  contenedor.addEventListener('input', function (e) {
    if (!e.target.matches('[data-catalogo-texto]')) return;
    var form = e.target.closest('[data-catalogo-form]');
    clearTimeout(temporizadorTexto);
    temporizadorTexto = setTimeout(function () { aplicar(form, e.target); }, DEBOUNCE_TEXTO);
  });

  contenedor.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-catalogo-form]');
    if (!form) return;
    e.preventDefault();
    aplicar(form, null);
  });

  contenedor.addEventListener('click', function (e) {
    var link = e.target.closest('a[data-catalogo-link]');
    if (link && !e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) {
      e.preventDefault();
      cargarContenido(link.href, 'push');
      return;
    }
    if (e.target.closest('[data-catalogo-cargar]')) {
      e.preventDefault();
      cargarPagina();
      return;
    }
    if (e.target.closest('[data-catalogo-abrir-filtros]')) {
      abrirFiltros(e.target.closest('[data-catalogo-abrir-filtros]'));
      return;
    }
    if (e.target.closest('[data-catalogo-cerrar-filtros]')) {
      cerrarFiltros();
    }
  });

  window.addEventListener('popstate', function () {
    if (!esDetalle) cargarContenido(window.location.href, null);
  });

  // ------------------------------------------------------- scroll infinito

  function iniciarScroll() {
    if (observador) observador.disconnect();
    var mas = contenedor.querySelector('[data-catalogo-siguiente]');
    if (!mas || !mas.dataset.catalogoSiguiente || !('IntersectionObserver' in window)) return;

    observador = new IntersectionObserver(function (entradas) {
      if (entradas[0].isIntersecting) cargarPagina();
    }, { rootMargin: '0px 0px 1400px 0px' });
    observador.observe(mas);
  }

  function cargarPagina() {
    var mas = contenedor.querySelector('[data-catalogo-siguiente]');
    var url = mas && mas.dataset.catalogoSiguiente;
    if (!url || cargandoPagina) return;

    cargandoPagina = true;
    mas.classList.add('is-cargando');
    mas.classList.remove('con-error');

    pedirJson(url)
      .then(function (datos) {
        var lista = contenedor.querySelector('[data-catalogo-lista]');
        var temporal = document.createElement('div');
        temporal.innerHTML = datos.html;
        Array.prototype.slice.call(temporal.children).forEach(function (card) {
          card.classList.add('is-nueva');
          lista.appendChild(card);
        });

        mas.dataset.catalogoSiguiente = datos.siguiente || '';
        mas.dataset.paginas = datos.pagina;

        // Al volver con «atrás» se muestran de entrada las mismas tandas.
        var u = new URL(window.location.href);
        u.searchParams.set('paginas', datos.pagina);
        history.replaceState(history.state, '', u.toString());

        if (!datos.siguiente) {
          if (observador) observador.disconnect();
          var total = Number(mas.dataset.total || 0);
          mas.textContent = '';
          mas.appendChild(el('p', 'catalogo__fin', 'Viste los ' + total.toLocaleString('es-AR') + ' productos.'));
        } else if (observador) {
          // Si el final sigue a la vista, pedir otra tanda.
          observador.unobserve(mas);
          observador.observe(mas);
        }

        document.dispatchEvent(new CustomEvent('catalogo:cards'));
      })
      .catch(function () {
        mas.classList.add('con-error');
        var boton = mas.querySelector('[data-catalogo-cargar]');
        if (boton) boton.textContent = 'No se pudo cargar. Reintentar';
      })
      .then(function () {
        cargandoPagina = false;
        mas.classList.remove('is-cargando');
      });
  }

  // ------------------------------------------------------- filtros celular

  function abrirFiltros(boton) {
    document.body.classList.add('filtros-abiertos');
    if (boton) boton.setAttribute('aria-expanded', 'true');
    var primero = contenedor.querySelector('.catalogo__sidebar .filtros__cerrar');
    if (primero) primero.focus({ preventScroll: true });
  }

  function cerrarFiltros() {
    if (!document.body.classList.contains('filtros-abiertos')) return;
    document.body.classList.remove('filtros-abiertos');
    var boton = contenedor.querySelector('[data-catalogo-abrir-filtros]');
    if (boton) {
      boton.setAttribute('aria-expanded', 'false');
      if (esCelular()) boton.focus({ preventScroll: true });
    }
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('filtros-abiertos')) cerrarFiltros();
  });

  window.addEventListener('resize', function () {
    if (!esCelular()) cerrarFiltros();
  });

  // -------------------------------------------- sidebar debajo del header

  function tope() {
    if (!header || esCelular()) return 0;
    var posicion = getComputedStyle(header).position;
    return posicion === 'fixed' || posicion === 'sticky' ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
  }

  var pendiente = false;
  function alHacerScroll() {
    if (pendiente) return;
    pendiente = true;
    window.requestAnimationFrame(function () {
      pendiente = false;
      document.documentElement.style.setProperty('--catalogo-tope', tope() + 'px');
      arriba.classList.toggle('is-visible', window.scrollY > window.innerHeight * 1.5);
    });
  }

  // ---------------------------------------------------------- volver arriba

  var arriba = el('button', 'catalogo__arriba');
  arriba.type = 'button';
  arriba.setAttribute('aria-label', 'Volver arriba');
  arriba.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 15l6-6 6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  arriba.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  raiz.appendChild(arriba);

  window.addEventListener('scroll', alHacerScroll, { passive: true });
  window.addEventListener('resize', alHacerScroll);

  // ----------------------------------------------------------------- inicio

  function iniciar() {
    contenedor.querySelectorAll('select[data-desplegable]').forEach(crearDesplegable);
    iniciarScroll();
    alHacerScroll();
  }

  if (!esDetalle) history.replaceState({ catalogo: true }, '', window.location.href);
  iniciar();
})();
