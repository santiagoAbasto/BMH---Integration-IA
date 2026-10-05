/*
 * Menú desplegable de «Productos» en el header
 * (layouts/partials/menu-productos.blade.php).
 *
 * Sin JS se abre con :hover / :focus-within (CSS). Esto le agrega:
 *   - una pequeña demora al entrar y al salir, para que no se abra al pasar
 *     de largo ni se cierre al bajar el mouse hasta el panel;
 *   - Escape para cerrarlo y aria-expanded al día;
 *   - en pantallas táctiles, el primer toque abre el menú y el segundo va
 *     a /categorias;
 *   - las imágenes se piden recién la primera vez que se abre.
 */
(function () {
  'use strict';

  var item = document.querySelector('[data-menu-productos]');
  if (!item) return;

  document.documentElement.classList.add('js-menu-productos');

  var link = item.querySelector('.menu-productos__link');
  var ABRIR_MS = 90;
  var CERRAR_MS = 240;
  var temporizador = null;
  var imagenesCargadas = false;

  function esComputadora() {
    return window.matchMedia('(min-width: 991px)').matches;
  }

  function cargarImagenes() {
    if (imagenesCargadas) return;
    imagenesCargadas = true;
    item.querySelectorAll('img[data-src]').forEach(function (img) {
      img.addEventListener('load', function () { img.classList.add('is-cargada'); }, { once: true });
      img.addEventListener('error', function () { img.remove(); }, { once: true });
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
    });
  }

  function abierto() {
    return item.classList.contains('is-abierto');
  }

  function abrir() {
    if (!esComputadora()) return;
    clearTimeout(temporizador);
    cargarImagenes();
    item.classList.add('is-abierto');
    link.setAttribute('aria-expanded', 'true');
  }

  function cerrar() {
    clearTimeout(temporizador);
    item.classList.remove('is-abierto');
    link.setAttribute('aria-expanded', 'false');
  }

  item.addEventListener('mouseenter', function () {
    clearTimeout(temporizador);
    if (!abierto()) temporizador = setTimeout(abrir, ABRIR_MS);
  });

  item.addEventListener('mouseleave', function () {
    clearTimeout(temporizador);
    if (abierto()) temporizador = setTimeout(cerrar, CERRAR_MS);
  });

  // Teclado: abre al llegar con Tab y se cierra al salir del menú.
  item.addEventListener('focusin', abrir);
  item.addEventListener('focusout', function (e) {
    if (!item.contains(e.relatedTarget)) cerrar();
  });

  item.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && abierto()) {
      cerrar();
      link.focus();
    }
  });

  // Táctil: el primer toque abre, el segundo navega.
  link.addEventListener('click', function (e) {
    if (window.matchMedia('(hover: none)').matches && esComputadora() && !abierto()) {
      e.preventDefault();
      abrir();
    }
  });

  document.addEventListener('click', function (e) {
    if (abierto() && !item.contains(e.target)) cerrar();
  });

  // En celular el menú no existe: si se achica la ventana, cerrarlo.
  window.addEventListener('resize', function () {
    if (!esComputadora()) cerrar();
  });
})();
