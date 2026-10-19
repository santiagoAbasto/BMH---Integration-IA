/*
 * Biblioteca de imágenes (admin): subida masiva, copiar, renombrar y
 * eliminar códigos. La página funciona con resources/views/backend/biblioteca-imagenes.
 */
(function () {
  'use strict';

  var raiz = document.getElementById('biblioteca');
  if (!raiz) return;

  var CSRF = raiz.dataset.csrf;
  var URL_SUBIR = raiz.dataset.storeUrl;
  var EN_PARALELO = 3;
  var TIPOS = /^image\/(jpeg|png|webp)$/;
  var MAXIMO = 15 * 1024 * 1024;

  var input = document.getElementById('input-imagenes');
  var zona = raiz.querySelector('[data-zona]');
  var panel = document.getElementById('panel-subidas');
  var lista = document.getElementById('lista-subidas');
  var resumen = document.getElementById('resumen-subidas');
  var barra = document.getElementById('barra-subidas');
  var botonCopiarTodos = document.getElementById('copiar-codigos');
  var grilla = document.getElementById('grilla');
  var plantilla = document.getElementById('plantilla-tile');

  var cola = [];
  var activas = 0;
  var total = 0;
  var listas = 0;
  var fallidas = 0;
  var codigosSubidos = [];

  function el(tag, clase, texto) {
    var nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto != null) nodo.textContent = texto;
    return nodo;
  }

  function pedir(url, metodo, cuerpo) {
    var opciones = { method: metodo, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (cuerpo instanceof FormData) {
      opciones.body = cuerpo;
    } else if (cuerpo) {
      opciones.headers['Content-Type'] = 'application/json';
      opciones.body = JSON.stringify(cuerpo);
    }
    return fetch(url, opciones).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (datos) {
        if (!r.ok) {
          var mensaje = datos.mensaje || (datos.errors && Object.values(datos.errors)[0] && Object.values(datos.errors)[0][0]) ||
            (r.status === 413 ? 'El archivo es demasiado pesado para el servidor.' : r.status === 419 ? 'La sesión venció: recargá la página.' : 'No se pudo completar (error ' + r.status + ').');
          throw new Error(mensaje);
        }
        return datos;
      });
    });
  }

  function copiar(texto, boton) {
    var hecho = function () {
      if (!boton) return;
      var icono = boton.innerHTML;
      boton.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
      boton.setAttribute('aria-label', 'Copiado');
      setTimeout(function () { boton.innerHTML = icono; }, 1200);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(texto).then(hecho);
    } else {
      var area = el('textarea');
      area.value = texto;
      document.body.appendChild(area);
      area.select();
      try { document.execCommand('copy'); hecho(); } catch (e) { /* sin portapapeles */ }
      area.remove();
    }
  }

  // ------------------------------------------------------------- subida

  function pintarResumen() {
    var faltan = total - listas - fallidas;
    var partes = [listas + ' de ' + total + ' subidas'];
    if (fallidas) partes.push(fallidas + (fallidas === 1 ? ' con error' : ' con errores'));
    if (faltan > 0) partes.push('subiendo ' + faltan + '…');
    else partes.push(fallidas ? 'revisá las que dieron error' : 'listo: ya podés usar los códigos en el Excel');
    resumen.textContent = partes.join(' · ');
    barra.style.width = (total ? Math.round(100 * (listas + fallidas) / total) : 0) + '%';
    botonCopiarTodos.hidden = codigosSubidos.length === 0;
  }

  function itemDeSubida(archivo) {
    var item = el('div', 'bi-subida-item');
    var miniatura = el('img');
    miniatura.alt = '';
    miniatura.src = URL.createObjectURL(archivo);
    miniatura.onload = function () { URL.revokeObjectURL(miniatura.src); };
    var texto = el('div');
    texto.appendChild(el('div', 'bi-subida-nombre', archivo.name));
    var detalle = el('div', 'bi-subida-detalle', 'En espera');
    texto.appendChild(detalle);
    var estado = el('span', 'bi-estado');
    estado.appendChild(el('span', 'bi-girando'));
    estado.appendChild(document.createTextNode('Subiendo'));
    item.appendChild(miniatura);
    item.appendChild(texto);
    item.appendChild(estado);
    lista.appendChild(item);
    return { item: item, detalle: detalle, estado: estado };
  }

  function marcarError(fila, mensaje) {
    fila.item.classList.add('is-error');
    fila.detalle.textContent = mensaje;
    fila.estado.className = 'bi-estado is-error';
    fila.estado.innerHTML = '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Error';
    fallidas++;
    pintarResumen();
  }

  function marcarLista(fila, datos) {
    var imagen = datos.imagen;
    fila.detalle.innerHTML = '';
    var codigo = el('span', 'bi-codigo');
    codigo.appendChild(el('span', null, imagen.codigo));
    var boton = el('button', 'bi-copiar');
    boton.type = 'button';
    boton.dataset.copiar = imagen.codigo;
    boton.title = 'Copiar código';
    boton.setAttribute('aria-label', 'Copiar el código ' + imagen.codigo);
    boton.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i>';
    codigo.appendChild(boton);
    fila.detalle.appendChild(codigo);
    fila.detalle.appendChild(document.createTextNode(' ' + datos.motivo));
    fila.estado.className = 'bi-estado is-lista';
    fila.estado.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Lista';
    listas++;
    codigosSubidos.push(imagen.codigo);
    agregarTile(imagen);
    pintarResumen();
  }

  function subir(tarea) {
    activas++;
    tarea.fila.detalle.textContent = 'Preparando…';
    var achicar = window.BmhAchicarImagenes ? window.BmhAchicarImagenes.achicar(tarea.archivo) : Promise.resolve(tarea.archivo);
    achicar.then(function (archivo) {
      tarea.fila.detalle.textContent = 'Subiendo…';
      var datos = new FormData();
      // El nombre original decide el código, aunque el navegador la haya pasado a WebP.
      datos.append('imagen', archivo, nombreParaSubir(tarea.archivo.name, archivo));
      return pedir(URL_SUBIR, 'POST', datos);
    }).then(function (respuesta) {
      marcarLista(tarea.fila, respuesta);
    }).catch(function (error) {
      marcarError(tarea.fila, error.message || 'No se pudo subir.');
    }).then(function () {
      activas--;
      siguiente();
    });
  }

  /** Mismo nombre que el original, con la extensión del archivo que realmente se sube. */
  function nombreParaSubir(original, archivo) {
    var base = original.replace(/\.[^.]+$/, '');
    var extension = archivo.type === 'image/webp' ? 'webp' : archivo.type === 'image/jpeg' ? 'jpg' : archivo.type === 'image/png' ? 'png' : original.split('.').pop();
    return base + '.' + extension;
  }

  function siguiente() {
    while (activas < EN_PARALELO && cola.length) {
      subir(cola.shift());
    }
  }

  function encolar(archivos) {
    if (!archivos.length) return;
    panel.hidden = false;
    Array.prototype.forEach.call(archivos, function (archivo) {
      total++;
      var fila = itemDeSubida(archivo);
      if (!TIPOS.test(archivo.type)) {
        marcarError(fila, 'No es una imagen JPG, PNG o WEBP.');
        return;
      }
      if (archivo.size > MAXIMO) {
        marcarError(fila, 'Pesa más de 15 MB.');
        return;
      }
      cola.push({ archivo: archivo, fila: fila });
    });
    pintarResumen();
    siguiente();
  }

  input.addEventListener('change', function () {
    encolar(input.files);
    input.value = '';
  });

  ['dragenter', 'dragover'].forEach(function (evento) {
    zona.addEventListener(evento, function (e) { e.preventDefault(); zona.classList.add('is-encima'); });
  });
  ['dragleave', 'drop'].forEach(function (evento) {
    zona.addEventListener(evento, function (e) { e.preventDefault(); zona.classList.remove('is-encima'); });
  });
  zona.addEventListener('drop', function (e) {
    encolar(e.dataTransfer ? e.dataTransfer.files : []);
  });

  window.addEventListener('beforeunload', function (e) {
    if (activas > 0 || cola.length > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  botonCopiarTodos.addEventListener('click', function () {
    copiar(codigosSubidos.join(', '), botonCopiarTodos);
  });

  function agregarTile(imagen) {
    var tile = plantilla.content.firstElementChild.cloneNode(true);
    tile.dataset.updateUrl = imagen.update_url;
    tile.dataset.destroyUrl = imagen.destroy_url;
    var foto = tile.querySelector('img');
    foto.src = imagen.url;
    foto.alt = 'Imagen ' + imagen.codigo;
    tile.querySelector('.bi-tile-abrir').href = imagen.url;
    tile.querySelector('[data-rol="codigo"]').textContent = imagen.codigo;
    tile.querySelector('.bi-copiar').dataset.copiar = imagen.codigo;
    tile.querySelector('[data-rol="edicion"] input').value = imagen.codigo;
    tile.querySelector('.bi-tile-nombre').textContent = imagen.medidas || imagen.nombre_original || '';
    var vacia = document.getElementById('grilla-vacia');
    if (vacia) vacia.remove();
    grilla.insertBefore(tile, grilla.firstChild);
  }

  // ------------------------------------------------------ copiar / editar

  raiz.addEventListener('click', function (e) {
    var copiarBoton = e.target.closest('[data-copiar]');
    if (copiarBoton) {
      e.preventDefault();
      copiar(copiarBoton.dataset.copiar, copiarBoton);
      return;
    }

    var boton = e.target.closest('[data-accion]');
    if (!boton) return;
    var tile = boton.closest('.bi-tile');
    if (!tile) return;
    var accion = boton.dataset.accion;

    if (accion === 'editar') mostrarEdicion(tile, true);
    if (accion === 'cancelar') mostrarEdicion(tile, false);
    if (accion === 'eliminar') mostrarConfirmacion(tile, true);
    if (accion === 'no-eliminar') mostrarConfirmacion(tile, false);
    if (accion === 'confirmar-eliminar') eliminar(tile, boton);
  });

  raiz.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-rol="edicion"]');
    if (!form) return;
    e.preventDefault();
    guardar(form.closest('.bi-tile'), form);
  });

  raiz.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && e.target.closest('[data-rol="edicion"]')) {
      mostrarEdicion(e.target.closest('.bi-tile'), false);
    }
  });

  function error(tile, mensaje) {
    var caja = tile.querySelector('[data-rol="error"]');
    caja.textContent = mensaje || '';
    caja.hidden = !mensaje;
  }

  function mostrarEdicion(tile, mostrar) {
    var vista = tile.querySelector('[data-rol="vista"]');
    var form = tile.querySelector('[data-rol="edicion"]');
    var campo = form.querySelector('input');
    vista.hidden = mostrar;
    form.hidden = !mostrar;
    error(tile, '');
    if (mostrar) {
      campo.value = tile.querySelector('[data-rol="codigo"]').textContent.trim();
      campo.focus();
      campo.select();
    } else {
      tile.querySelector('[data-accion="editar"]').focus();
    }
  }

  function guardar(tile, form) {
    var campo = form.querySelector('input');
    var nuevo = campo.value.trim().toUpperCase();
    var actual = tile.querySelector('[data-rol="codigo"]').textContent.trim();
    if (nuevo === actual) {
      mostrarEdicion(tile, false);
      return;
    }
    var botones = form.querySelectorAll('button');
    botones.forEach(function (b) { b.disabled = true; });
    pedir(tile.dataset.updateUrl, 'PATCH', { codigo: nuevo }).then(function (datos) {
      var codigo = datos.imagen.codigo;
      tile.querySelector('[data-rol="codigo"]').textContent = codigo;
      tile.querySelector('.bi-copiar').dataset.copiar = codigo;
      tile.querySelector('.bi-codigo').title = codigo;
      mostrarEdicion(tile, false);
      if (tile.dataset.enUso === '1') {
        error(tile, '');
        var aviso = tile.querySelector('[data-rol="error"]');
        aviso.textContent = 'Código cambiado. Si ya tenías un Excel exportado, volvé a exportarlo: el código viejo dejó de existir.';
        aviso.style.color = '#8A5A00';
        aviso.hidden = false;
      }
    }).catch(function (e) {
      error(tile, e.message);
      campo.focus();
    }).then(function () {
      botones.forEach(function (b) { b.disabled = false; });
    });
  }

  function mostrarConfirmacion(tile, mostrar) {
    tile.querySelector('[data-rol="confirmar"]').hidden = !mostrar;
    if (mostrar) tile.querySelector('[data-accion="confirmar-eliminar"]').focus();
  }

  function eliminar(tile, boton) {
    boton.disabled = true;
    pedir(tile.dataset.destroyUrl, 'DELETE').then(function () {
      tile.style.transition = 'opacity .2s';
      tile.style.opacity = '0';
      setTimeout(function () { tile.remove(); }, 200);
    }).catch(function (e) {
      mostrarConfirmacion(tile, false);
      error(tile, e.message);
      boton.disabled = false;
    });
  }
})();
