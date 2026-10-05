/* Visor único de fotos. Delegación para cards iniciales, relacionadas y AJAX. */
(function () {
  'use strict';

  var dialog = document.getElementById('product-gallery');
  if (!dialog) return;
  var stage = dialog.querySelector('[data-gallery-stage]');
  var image = dialog.querySelector('[data-gallery-image]');
  var thumbs = dialog.querySelector('[data-gallery-thumbs]');
  var counter = dialog.querySelector('[data-gallery-counter]');
  var percent = dialog.querySelector('[data-gallery-percent]');
  var zoomIn = dialog.querySelector('[data-gallery-zoom-in]');
  var zoomOut = dialog.querySelector('[data-gallery-zoom-out]');
  var error = dialog.querySelector('.foto-galeria__error');
  var photos = [], index = 0, title = '', opener = null;
  var scale = 1, x = 0, y = 0, width = 0, height = 0, ready = false;
  var frame = null, bodyStyles = null, rootOverflow = '', pageScroll = 0;
  var MAX_ZOOM = 5;
  var pointers = new Map();
  var gesture = null, pinch = null, hadPinch = false, lastTap = null;
  var openingPointer = null;

  function clamp(value, min, max) { return Math.max(min, Math.min(value, max)); }
  function clampPan() {
    var maxX = Math.max(0, (width * scale - stage.clientWidth) / 2);
    var maxY = Math.max(0, (height * scale - stage.clientHeight) / 2);
    x = clamp(x, -maxX, maxX);
    y = clamp(y, -maxY, maxY);
  }
  function paint() {
    if (frame !== null) return;
    frame = requestAnimationFrame(function () {
      frame = null;
      image.style.transform = 'translate(-50%, -50%) translate(' + x + 'px, ' + y + 'px) scale(' + scale + ')';
      stage.classList.toggle('is-zoomed', scale > 1.01);
      percent.textContent = Math.round(scale * 100) + '%';
      zoomOut.disabled = !ready || scale <= 1.01;
      zoomIn.disabled = !ready || scale >= MAX_ZOOM - .01;
    });
  }
  function fit() {
    if (!dialog.open || !ready) return;
    // Se usa todo el lienzo, incluso cuando la foto original es pequeña.
    // contain permite el mayor tamaño posible sin recortar ninguna parte.
    var ratio = image.naturalWidth / image.naturalHeight;
    width = Math.min(stage.clientWidth, stage.clientHeight * ratio);
    height = width / ratio;
    image.style.width = width + 'px';
    image.style.height = height + 'px';
    clampPan();
    paint();
  }
  function zoomTo(value, clientX, clientY) {
    if (!ready) return;
    var next = clamp(value, 1, MAX_ZOOM);
    var r = stage.getBoundingClientRect();
    var fx = clientX == null ? 0 : clientX - r.left - r.width / 2;
    var fy = clientY == null ? 0 : clientY - r.top - r.height / 2;
    x = fx - (fx - x) * next / scale;
    y = fy - (fy - y) * next / scale;
    scale = next;
    clampPan();
    paint();
  }
  function reset() {
    scale = 1; x = 0; y = 0;
    lastTap = null;
    paint();
  }
  function clearGesture() {
    pointers.clear();
    gesture = null; pinch = null; hadPinch = false;
    stage.classList.remove('is-interacting');
  }
  function selectPhoto(next) {
    index = (next + photos.length) % photos.length;
    clearGesture();
    reset();
    ready = false;
    zoomIn.disabled = zoomOut.disabled = true;
    stage.classList.add('is-loading');
    stage.classList.remove('has-error');
    error.hidden = true;
    image.alt = title + ' — Foto ' + (index + 1);
    image.src = photos[index];
    counter.textContent = (index + 1) + ' / ' + photos.length;
    thumbs.querySelectorAll('button').forEach(function (button, i) {
      button.setAttribute('aria-pressed', i === index ? 'true' : 'false');
      if (i === index) button.scrollIntoView({ block: 'nearest', inline: 'center' });
    });
    // Sólo precargar las fotos vecinas; las miniaturas se cargan con lazy.
    if (photos.length > 1) {
      [index - 1, index + 1].forEach(function (i) {
        var preload = new Image();
        preload.src = photos[(i + photos.length) % photos.length];
      });
    }
  }
  image.addEventListener('load', function () {
    if (!dialog.open) return;
    ready = image.naturalWidth > 0 && image.naturalHeight > 0;
    stage.classList.remove('is-loading');
    fit();
  });
  image.addEventListener('error', function () {
    ready = false;
    stage.classList.remove('is-loading');
    stage.classList.add('has-error');
    error.hidden = false;
    paint();
  });

  function lockPage() {
    pageScroll = window.scrollY;
    bodyStyles = {};
    var style = document.body.style;
    ['position', 'top', 'width', 'overflow', 'paddingRight'].forEach(function (key) { bodyStyles[key] = style[key]; });
    var gap = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
    rootOverflow = document.documentElement.style.overflow;
    var padding = parseFloat(getComputedStyle(document.body).paddingRight) || 0;
    style.paddingRight = padding + gap + 'px';
    style.position = 'fixed';
    style.top = -pageScroll + 'px';
    style.width = '100%';
    style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    document.body.classList.add('product-gallery-open');
  }
  function unlockPage() {
    document.body.classList.remove('product-gallery-open');
    if (!bodyStyles) return;
    Object.keys(bodyStyles).forEach(function (key) { document.body.style[key] = bodyStyles[key]; });
    bodyStyles = null;
    document.documentElement.style.overflow = rootOverflow;
    window.scrollTo({ top: pageScroll, behavior: 'instant' });
  }
  function path(src) {
    try { return new URL(src, window.location.href).pathname; } catch (_) { return src; }
  }
  function open(trigger) {
    var data = trigger.closest('[data-gallery-images]');
    if (!data || dialog.open) return;
    try { photos = JSON.parse(data.dataset.galleryImages).filter(function (src) { return typeof src === 'string' && src !== ''; }); }
    catch (_) { return; }
    if (!photos.length) return;
    title = data.dataset.galleryTitle || 'Fotos del producto';
    dialog.querySelector('#foto-galeria-titulo').textContent = title;
    dialog.querySelector('[data-gallery-code-label]').textContent = data.dataset.galleryCode || 'GALERÍA DE PRODUCTO';
    var current = trigger.querySelector('.fotorama__active img') || trigger.querySelector('img');
    var src = current ? current.currentSrc || current.src : photos[0];
    var selected = photos.findIndex(function (photo) { return path(photo) === path(src); });
    opener = trigger;
    thumbs.replaceChildren();
    photos.forEach(function (photo, i) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'foto-galeria__miniatura';
      button.setAttribute('aria-label', 'Ver foto ' + (i + 1));
      button.setAttribute('aria-pressed', 'false');
      var thumb = document.createElement('img');
      thumb.src = photo; thumb.alt = ''; thumb.loading = 'lazy'; thumb.draggable = false;
      button.appendChild(thumb);
      button.addEventListener('click', function () { selectPhoto(i); });
      thumbs.appendChild(button);
    });
    dialog.classList.toggle('is-single', photos.length === 1);
    lockPage();
    dialog.showModal();
    document.dispatchEvent(new CustomEvent('product-gallery:open'));
    selectPhoto(Math.max(0, selected));
  }
  dialog.querySelector('[data-gallery-close]').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('close', function () {
    clearGesture();
    ready = false;
    unlockPage();
    if (opener && opener.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
  dialog.addEventListener('click', function (event) {
    // El backdrop pertenece al dialog; no cerrar al arrastrar una foto.
    if (event.target !== dialog) return;
    var rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
  });
  dialog.querySelector('[data-gallery-prev]').addEventListener('click', function () { selectPhoto(index - 1); });
  dialog.querySelector('[data-gallery-next]').addEventListener('click', function () { selectPhoto(index + 1); });
  zoomIn.addEventListener('click', function () { zoomTo(scale * 1.5); });
  zoomOut.addEventListener('click', function () { zoomTo(scale / 1.5); });
  dialog.querySelector('[data-gallery-reset]').addEventListener('click', reset);
  dialog.addEventListener('keydown', function (event) {
    if (event.key === 'Tab') {
      var buttons = Array.from(dialog.querySelectorAll('button:not(:disabled)')).filter(function (button) { return button.getClientRects().length > 0; });
      var first = buttons[0], last = buttons[buttons.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      return;
    }
    switch (event.key) {
      case 'ArrowLeft': selectPhoto(index - 1); break;
      case 'ArrowRight': selectPhoto(index + 1); break;
      case '+': case '=': zoomTo(scale * 1.5); break;
      case '-': zoomTo(scale / 1.5); break;
      case '0': reset(); break;
      default: return; // Escape y el fondo inerte los resuelve <dialog> nativamente.
    }
    event.preventDefault();
  });

  // -------------------------- Zoom y desplazamiento: mouse, lápiz y touch.
  function pinchState() {
    var points = Array.from(pointers.values());
    var a = points[0], b = points[1];
    return { distance: Math.hypot(a.x - b.x, a.y - b.y), x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
  }
  function startSingle(point) {
    gesture = { startX: point.x, startY: point.y, x: x, y: y, time: Date.now(), moved: false, type: point.type };
  }
  stage.addEventListener('pointerdown', function (event) {
    if (!ready || event.target.closest('button') || (event.pointerType === 'mouse' && event.button !== 0)) return;
    event.preventDefault();
    stage.setPointerCapture(event.pointerId);
    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY, type: event.pointerType });
    stage.classList.add('is-interacting');
    if (pointers.size === 1) { hadPinch = false; startSingle(pointers.get(event.pointerId)); }
    else if (pointers.size >= 2) { hadPinch = true; lastTap = null; pinch = pinchState(); }
  });
  stage.addEventListener('pointermove', function (event) {
    if (!pointers.has(event.pointerId)) return;
    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY, type: event.pointerType });
    if (pointers.size >= 2 && pinch) {
      var next = pinchState();
      zoomTo(scale * next.distance / Math.max(1, pinch.distance), pinch.x, pinch.y);
      x += next.x - pinch.x; y += next.y - pinch.y;
      clampPan(); paint();
      pinch = next;
    } else if (gesture) {
      var dx = event.clientX - gesture.startX, dy = event.clientY - gesture.startY;
      if (Math.hypot(dx, dy) > 8) gesture.moved = true;
      if (scale > 1) { x = gesture.x + dx; y = gesture.y + dy; clampPan(); paint(); }
    }
  });
  function endPointer(event) {
    if (!pointers.has(event.pointerId)) return;
    var ended = gesture;
    pointers.delete(event.pointerId);
    if (stage.hasPointerCapture(event.pointerId)) stage.releasePointerCapture(event.pointerId);
    if (pointers.size >= 2) { pinch = pinchState(); return; }
    pinch = null;
    if (pointers.size === 1) { startSingle(Array.from(pointers.values())[0]); return; }
    stage.classList.remove('is-interacting');
    gesture = null;
    if (!ended || hadPinch || event.type !== 'pointerup') return;
    var dx = event.clientX - ended.startX, dy = event.clientY - ended.startY;
    if (ended.moved) {
      lastTap = null;
      if (scale === 1 && ended.type !== 'mouse' && Math.abs(dx) > 65 && Math.abs(dx) > Math.abs(dy) * 1.3 && Date.now() - ended.time < 700 && photos.length > 1) {
        selectPhoto(index + (dx < 0 ? 1 : -1));
      }
      return;
    }
    if (ended.type === 'mouse') {
      zoomTo(scale > 1 ? 1 : 2.5, event.clientX, event.clientY);
      return;
    }
    var now = Date.now();
    if (lastTap && now - lastTap.time < 320 && Math.hypot(event.clientX - lastTap.x, event.clientY - lastTap.y) < 32) {
      zoomTo(scale > 1 ? 1 : 2.5, event.clientX, event.clientY);
      lastTap = null;
    } else { lastTap = { time: now, x: event.clientX, y: event.clientY }; }
  }
  stage.addEventListener('pointerup', endPointer);
  stage.addEventListener('pointercancel', endPointer);
  stage.addEventListener('lostpointercapture', function (event) {
    if (pointers.has(event.pointerId)) clearGesture();
  });
  stage.addEventListener('wheel', function (event) {
    event.preventDefault();
    zoomTo(scale * Math.exp(-event.deltaY * .002), event.clientX, event.clientY);
  }, { passive: false });
  stage.addEventListener('dragstart', function (event) { event.preventDefault(); });
  if ('ResizeObserver' in window) new ResizeObserver(fit).observe(stage);
  else window.addEventListener('resize', fit);

  // ------------------------- Abrir desde imágenes, sin confundir swipe con click.
  function triggerFor(target) {
    if (!(target instanceof Element) || target.closest('.fotorama__arr, .fotorama__video-play')) return null;
    return target.closest('[data-gallery-open], .fotorama[data-gallery-images] .fotorama__stage');
  }
  document.addEventListener('pointerdown', function (event) {
    if (dialog.open) return;
    if (event.pointerType !== 'mouse' && !event.isPrimary) { openingPointer = null; return; }
    var trigger = triggerFor(event.target);
    openingPointer = trigger ? { id: event.pointerId, trigger: trigger, x: event.clientX, y: event.clientY, time: Date.now(), moved: false } : null;
  }, true);
  document.addEventListener('pointermove', function (event) {
    if (openingPointer && event.pointerId === openingPointer.id && Math.hypot(event.clientX - openingPointer.x, event.clientY - openingPointer.y) > 8) openingPointer.moved = true;
  }, { capture: true, passive: true });
  // Fotorama cancela el click sintetizado en touch: abrir también con un tap
  // real de Pointer Events, conservando el swipe y los gestos de dos dedos.
  document.addEventListener('pointerup', function (event) {
    if (dialog.open || event.pointerType === 'mouse' || !openingPointer || event.pointerId !== openingPointer.id) return;
    if (!openingPointer.moved && Date.now() - openingPointer.time < 500) {
      var trigger = openingPointer.trigger;
      openingPointer = null;
      open(trigger);
    }
  }, true);
  document.addEventListener('pointercancel', function (event) {
    if (openingPointer && event.pointerId === openingPointer.id) openingPointer.moved = true;
  }, true);
  document.addEventListener('click', function (event) {
    var trigger = triggerFor(event.target);
    if (!trigger || dialog.open || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!openingPointer || !openingPointer.moved) open(trigger);
    openingPointer = null;
  }, true);
  document.addEventListener('keydown', function (event) {
    if (dialog.open || (event.key !== 'Enter' && event.key !== ' ')) return;
    var trigger = triggerFor(event.target);
    if (!trigger || event.target !== trigger) return;
    event.preventDefault();
    open(trigger);
  });
  function initFotorama() {
    document.querySelectorAll('.fotorama[data-gallery-images] .fotorama__stage').forEach(function (box) {
      box.tabIndex = 0;
      box.setAttribute('role', 'button');
      box.setAttribute('aria-haspopup', 'dialog');
      box.setAttribute('aria-label', 'Ver fotos de ' + box.closest('[data-gallery-images]').dataset.galleryTitle);
    });
  }
  initFotorama();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFotorama);
  // Fotorama genera sus elementos después de cargar jQuery y las imágenes.
  document.querySelectorAll('.fotorama[data-gallery-images]').forEach(function (root) {
    new MutationObserver(initFotorama).observe(root, { childList: true, subtree: true });
  });
})();
