/*
 * Biblioteca de imágenes (admin): subida masiva, copiar, renombrar y
 * eliminar códigos. La página funciona con resources/views/backend/biblioteca-imagenes.
 *
 * Cada foto subida termina en uno de estos estados:
 *  - ok:     quedó asignada a su producto (el nombre del archivo es su código);
 *  - error:  ningún producto tiene ese código → se corrige ahí mismo;
 *  - aviso:  ya había una foto con ese código → elegir cuál reemplazar o en
 *            qué lugar ponerla, u omitir (lo mismo al corregir un código que ya
 *            tiene otra foto del producto: ver armarLugar());
 *  - omitida, o fallo de la subida (red, tamaño…).
 * Lo que necesita atención va a «Para revisar», arriba de lo que salió bien.
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
  var listaRevisar = document.getElementById('lista-revisar');
  var grupoRevisar = document.getElementById('grupo-revisar');
  var resumen = document.getElementById('resumen-subidas');
  var barra = document.getElementById('barra-subidas');
  var grilla = document.getElementById('grilla');
  var plantilla = document.getElementById('plantilla-tile');
  var plantillaCorregir = document.getElementById('plantilla-corregir');
  var plantillaConflicto = document.getElementById('plantilla-conflicto');
  var dialogo = document.getElementById('dialogo-lugar');

  var tareas = [];
  var cola = [];
  var activas = 0;

  var ESTADOS = {
    espera: { clase: '', icono: '<span class="bi-girando"></span>', texto: 'En espera' },
    subiendo: { clase: '', icono: '<span class="bi-girando"></span>', texto: 'Subiendo' },
    ok: { clase: 'is-lista', icono: '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>', texto: 'Asignada' },
    error: { clase: 'is-error', icono: '<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>', texto: 'Sin producto' },
    fallo: { clase: 'is-error', icono: '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>', texto: 'No se subió' },
    aviso: { clase: 'is-aviso', icono: '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>', texto: 'Ya existe' },
    omitida: { clase: 'is-omitida', icono: '<i class="fa-solid fa-ban" aria-hidden="true"></i>', texto: 'Omitida' }
  };

  function el(tag, clase, texto) {
    var nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto != null) nodo.textContent = texto;
    return nodo;
  }

  /** fetch con JSON; si falla, el Error trae status y datos (el 409 de «ya existe» trae el detalle). */
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
          var error = new Error(mensaje);
          error.status = r.status;
          error.datos = datos;
          throw error;
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

  // ------------------------------------------------------- elegir lugar

  function nombreDelLugar(posicion) {
    return posicion === 1 ? 'Principal' : 'Foto ' + posicion;
  }

  /**
   * Las fotos del producto en orden, con un «+» entre cada una para poner la
   * nueva en ese lugar y un «Reemplazar» en cada foto. Reemplazar pide una
   * segunda confirmación porque saca esa foto del producto.
   * alElegir recibe { modo: 'agregar', posicion } o { modo: 'reemplazar', objetivo }.
   */
  function armarLugar(datos, urlNueva, alElegir) {
    var caja = el('div', 'bi-lugar');
    var fotos = datos.fotos || [];

    var nueva = el('figure', 'bi-lugar-nueva');
    var imagenNueva = el('img');
    imagenNueva.src = urlNueva;
    imagenNueva.alt = 'Foto nueva';
    nueva.appendChild(imagenNueva);
    nueva.appendChild(el('figcaption', null, 'Foto nueva'));
    caja.appendChild(nueva);

    var cuerpo = el('div', 'bi-lugar-cuerpo');
    var ayuda = el('p', 'bi-lugar-ayuda');
    ayuda.appendChild(document.createTextNode('Así están hoy las fotos de '));
    ayuda.appendChild(el('strong', null, datos.producto.codigo));
    ayuda.appendChild(document.createTextNode('. Tocá '));
    ayuda.appendChild(el('span', 'bi-lugar-mas-chico', '+'));
    ayuda.appendChild(document.createTextNode(' para poner la nueva en ese lugar, o «Reemplazar» para cambiar esa foto por la nueva.'));
    cuerpo.appendChild(ayuda);

    var tira = el('ol', 'bi-lugar-tira');
    var botones = [];
    var hueco = function (posicion) {
      var li = el('li', 'bi-lugar-item');
      var boton = el('button', 'bi-lugar-hueco');
      boton.type = 'button';
      var texto = posicion === 1 ? 'Poner como principal' : posicion === fotos.length + 1 ? 'Agregar al final' : 'Poner como foto ' + posicion;
      boton.title = texto;
      boton.setAttribute('aria-label', texto);
      boton.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i>';
      boton.appendChild(el('span', null, posicion === fotos.length + 1 && posicion > 1 ? 'Al final' : nombreDelLugar(posicion)));
      boton.addEventListener('click', function () { elegir({ modo: 'agregar', posicion: posicion }); });
      botones.push(boton);
      li.appendChild(boton);
      tira.appendChild(li);
    };

    fotos.forEach(function (foto) {
      hueco(foto.posicion);
      var li = el('li', 'bi-lugar-item bi-lugar-foto' + (foto.path === datos.objetivo ? ' is-objetivo' : ''));
      var imagen = el('img');
      imagen.src = foto.url;
      imagen.alt = nombreDelLugar(foto.posicion) + ' de ' + datos.producto.codigo;
      imagen.loading = 'lazy';
      li.appendChild(imagen);
      li.appendChild(el('span', 'bi-lugar-rol', nombreDelLugar(foto.posicion)));
      li.appendChild(el('span', 'bi-lugar-codigo', foto.codigo || 'sin código'));
      var reemplazar = el('button', 'bi-btn bi-btn-chico', 'Reemplazar');
      reemplazar.type = 'button';
      reemplazar.setAttribute('aria-label', 'Reemplazar la ' + nombreDelLugar(foto.posicion).toLowerCase() + ' por la nueva');
      var espera;
      reemplazar.addEventListener('click', function () {
        if (!reemplazar.classList.contains('bi-btn-peligro')) {
          reemplazar.classList.add('bi-btn-peligro');
          reemplazar.textContent = 'Sí, reemplazar';
          espera = setTimeout(function () {
            reemplazar.classList.remove('bi-btn-peligro');
            reemplazar.textContent = 'Reemplazar';
          }, 4000);
          return;
        }
        clearTimeout(espera);
        elegir({ modo: 'reemplazar', objetivo: foto.path });
      });
      botones.push(reemplazar);
      li.appendChild(reemplazar);
      tira.appendChild(li);
    });
    hueco(fotos.length + 1);
    cuerpo.appendChild(tira);

    var error = el('div', 'bi-error-campo');
    error.setAttribute('role', 'alert');
    error.hidden = true;
    cuerpo.appendChild(error);
    caja.appendChild(cuerpo);

    function elegir(lugar) {
      botones.forEach(function (b) { b.disabled = true; });
      error.hidden = true;
      Promise.resolve(alElegir(lugar)).catch(function (e) {
        botones.forEach(function (b) { b.disabled = false; });
        error.textContent = e.message;
        error.hidden = false;
      });
    }

    return caja;
  }

  /** Cuerpo del PATCH al elegir el lugar. */
  function pedidoDeLugar(codigo, lugar) {
    var cuerpo = { codigo: codigo, modo: lugar.modo };
    if (lugar.objetivo) cuerpo.objetivo = lugar.objetivo;
    if (lugar.posicion) cuerpo.posicion = lugar.posicion;
    return cuerpo;
  }

  // ------------------------------------------------------------- subida

  function contar(estado) {
    return tareas.filter(function (t) { return t.estado === estado; }).length;
  }

  function pintarResumen() {
    var ok = contar('ok');
    var problemas = contar('error') + contar('fallo');
    var avisos = contar('aviso');
    var enCurso = contar('espera') + contar('subiendo');
    var terminadas = tareas.length - enCurso;

    raiz.querySelector('[data-cuenta="ok"]').textContent = ok;
    raiz.querySelector('[data-cuenta="error"]').textContent = problemas;
    raiz.querySelector('[data-cuenta="aviso"]').textContent = avisos;
    raiz.querySelector('[data-contador="error"]').hidden = problemas === 0;
    raiz.querySelector('[data-contador="aviso"]').hidden = avisos === 0;

    if (enCurso > 0) {
      resumen.textContent = 'Subiendo… ' + terminadas + ' de ' + tareas.length + ' listas.';
    } else if (problemas + avisos === 0) {
      resumen.textContent = ok === 1 ? 'Listo: la foto quedó asignada a su producto.' : 'Listo: las ' + ok + ' fotos quedaron asignadas a sus productos.';
    } else {
      resumen.textContent = 'Terminó. ' + (problemas + avisos === 1 ? 'Una foto necesita' : (problemas + avisos) + ' fotos necesitan') + ' que la revises: están arriba, en «Para revisar».';
    }
    barra.style.width = (tareas.length ? Math.round(100 * terminadas / tareas.length) : 0) + '%';
    grupoRevisar.hidden = listaRevisar.children.length === 0;
    lista.closest('.bi-grupo').hidden = lista.children.length === 0;
  }

  /** Cambia el estado de una subida y la mueve a «Para revisar» o a la lista de abajo. */
  function ponerEstado(tarea, estado, mensaje) {
    var datos = ESTADOS[estado];
    tarea.estado = estado;
    tarea.item.className = 'bi-subida-item' + (datos.clase ? ' ' + datos.clase : '');
    tarea.badge.className = 'bi-estado' + (datos.clase ? ' ' + datos.clase : '');
    tarea.badge.innerHTML = datos.icono + ' ' + datos.texto;
    if (mensaje != null) {
      tarea.detalle.textContent = mensaje;
    }
    var destino = estado === 'error' || estado === 'fallo' || estado === 'aviso' ? listaRevisar : lista;
    if (tarea.item.parentNode !== destino && estado !== 'espera' && estado !== 'subiendo') {
      destino.insertBefore(tarea.item, destino.firstChild);
    }
    pintarResumen();
  }

  function itemDeSubida(archivo) {
    var item = el('div', 'bi-subida-item');
    var miniatura = el('img');
    miniatura.alt = '';
    miniatura.src = URL.createObjectURL(archivo);
    var texto = el('div', 'bi-subida-texto');
    texto.appendChild(el('div', 'bi-subida-nombre', archivo.name));
    var detalle = el('div', 'bi-subida-detalle', 'En espera');
    var accion = el('div', 'bi-subida-accion');
    texto.appendChild(detalle);
    texto.appendChild(accion);
    var badge = el('span', 'bi-estado');
    item.appendChild(miniatura);
    item.appendChild(texto);
    item.appendChild(badge);
    lista.appendChild(item);
    return { item: item, miniatura: miniatura, detalle: detalle, accion: accion, badge: badge };
  }

  /** «Es la foto principal de NFSO42.» + link al producto. */
  function mostrarAsignada(tarea, datos) {
    tarea.accion.innerHTML = '';
    ponerEstado(tarea, 'ok', datos.mensaje + ' ');
    if (datos.productos && datos.productos.length) {
      var link = el('a', 'bi-enlace', 'Ver producto');
      link.href = datos.productos[0].url;
      link.target = '_blank';
      link.rel = 'noopener';
      link.title = datos.productos[0].nombre;
      tarea.detalle.appendChild(link);
    }
  }

  function mostrarResultado(tarea, datos) {
    tarea.imagen = datos.imagen;
    tarea.tile = tarea.tile || agregarTile(datos.imagen);
    actualizarTile(tarea.tile, datos);
    if (datos.resultado === 'sin_producto') {
      mostrarCorreccion(tarea, datos);
    } else {
      mostrarAsignada(tarea, datos);
    }
  }

  /** Sin producto: campo para corregir el código y, si hay, la sugerencia. */
  function mostrarCorreccion(tarea, datos) {
    ponerEstado(tarea, 'error', datos.mensaje);
    tarea.accion.innerHTML = '';
    var form = plantillaCorregir.content.firstElementChild.cloneNode(true);
    var campo = form.querySelector('input');
    var id = 'corregir-' + tareas.indexOf(tarea);
    campo.id = id;
    form.querySelector('label').htmlFor = id;
    campo.value = datos.imagen.codigo;
    var sugerencia = form.querySelector('.bi-corregir-sugerencia');
    if (datos.sugerencia) {
      sugerencia.hidden = false;
      sugerencia.querySelector('[data-rol="sugerencia-producto"]').textContent = datos.sugerencia.producto;
      var boton = sugerencia.querySelector('[data-accion="usar-sugerencia"]');
      boton.textContent = 'Usar ' + datos.sugerencia.codigo;
      boton.addEventListener('click', function () {
        campo.value = datos.sugerencia.codigo;
        corregir(tarea, form);
      });
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      corregir(tarea, form);
    });
    tarea.accion.appendChild(form);
  }

  function corregir(tarea, form) {
    var campo = form.querySelector('input');
    var aviso = form.querySelector('.bi-error-campo');
    var botones = form.querySelectorAll('button, input');
    botones.forEach(function (b) { b.disabled = true; });
    aviso.hidden = true;
    var codigo = campo.value.trim().toUpperCase();
    var anterior = form.parentNode.querySelector('.bi-lugar-envoltura');
    if (anterior) anterior.remove();
    pedir(tarea.imagen.update_url, 'PATCH', { codigo: codigo }).then(function (datos) {
      despuesDeCorregir(tarea, datos);
    }).catch(function (e) {
      botones.forEach(function (b) { b.disabled = false; });
      if (e.status === 409 && e.datos && e.datos.fotos) {
        // El código ya lo tiene otra foto del producto: elegir el lugar.
        var envoltura = el('div', 'bi-lugar-envoltura');
        envoltura.appendChild(el('p', 'bi-lugar-mensaje', e.datos.mensaje + ' ¿Qué hacemos con esta?'));
        envoltura.appendChild(armarLugar(e.datos, tarea.miniatura.src, function (lugar) {
          return pedir(tarea.imagen.update_url, 'PATCH', pedidoDeLugar(codigo, lugar)).then(function (datos) {
            despuesDeCorregir(tarea, datos);
          });
        }));
        form.parentNode.appendChild(envoltura);
        return;
      }
      aviso.textContent = e.message;
      aviso.hidden = false;
      campo.focus();
    });
  }

  function despuesDeCorregir(tarea, datos) {
    tarea.imagen = datos.imagen;
    actualizarTile(tarea.tile, datos);
    if (datos.resultado === 'sin_producto') {
      mostrarCorreccion(tarea, datos);
      tarea.accion.querySelector('input').focus();
    } else {
      mostrarAsignada(tarea, datos);
    }
  }

  /**
   * Ya hay una foto con ese código. Si es de un producto, se elige el lugar
   * entre sus fotos; si no, reemplazar la otra o guardarla con otro código.
   */
  function mostrarConflicto(tarea, datos) {
    ponerEstado(tarea, 'aviso', datos.mensaje);
    tarea.accion.innerHTML = '';
    if (datos.fotos) {
      tarea.accion.appendChild(armarLugar(datos, tarea.miniatura.src, function (lugar) {
        tarea.modo = lugar.modo;
        tarea.lugar = lugar;
        encolarTarea(tarea);
      }));
      var omitir = el('button', 'bi-btn bi-btn-fantasma bi-btn-chico bi-lugar-omitir', 'Omitir: dejar las fotos como están');
      omitir.type = 'button';
      omitir.addEventListener('click', function () {
        tarea.accion.innerHTML = '';
        ponerEstado(tarea, 'omitida', 'No se subió: quedaron las fotos que ya estaban.');
      });
      tarea.accion.appendChild(omitir);
      return;
    }
    var caja = plantillaConflicto.content.firstElementChild.cloneNode(true);
    var fotos = caja.querySelectorAll('img');
    fotos[0].src = datos.existente.url;
    fotos[1].src = tarea.miniatura.src;
    caja.addEventListener('click', function (e) {
      var boton = e.target.closest('[data-accion]');
      if (!boton) return;
      if (boton.dataset.accion === 'omitir') {
        tarea.accion.innerHTML = '';
        ponerEstado(tarea, 'omitida', 'No se subió: quedó la foto que ya estaba.');
        return;
      }
      caja.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
      tarea.modo = boton.dataset.accion;
      encolarTarea(tarea);
    });
    tarea.accion.appendChild(caja);
  }

  function subir(tarea) {
    activas++;
    ponerEstado(tarea, 'subiendo', tarea.modo ? 'Subiendo…' : 'Preparando…');
    // Se achica una sola vez, aunque después se vuelva a subir para reemplazar.
    tarea.listo = tarea.listo || (window.BmhAchicarImagenes ? window.BmhAchicarImagenes.achicar(tarea.archivo) : Promise.resolve(tarea.archivo));
    tarea.listo.then(function (archivo) {
      tarea.detalle.textContent = 'Subiendo…';
      var datos = new FormData();
      // El nombre original es el código, aunque el navegador la haya pasado a WebP.
      datos.append('imagen', archivo, nombreParaSubir(tarea.archivo.name, archivo));
      if (tarea.modo) datos.append('modo', tarea.modo);
      if (tarea.lugar && tarea.lugar.objetivo) datos.append('objetivo', tarea.lugar.objetivo);
      if (tarea.lugar && tarea.lugar.posicion) datos.append('posicion', tarea.lugar.posicion);
      return pedir(URL_SUBIR, 'POST', datos);
    }).then(function (respuesta) {
      tarea.modo = null;
      tarea.lugar = null;
      mostrarResultado(tarea, respuesta);
    }).catch(function (error) {
      tarea.modo = null;
      tarea.lugar = null;
      if (error.status === 409 && error.datos && error.datos.existente) {
        mostrarConflicto(tarea, error.datos);
      } else {
        tarea.accion.innerHTML = '';
        ponerEstado(tarea, 'fallo', error.message || 'No se pudo subir.');
      }
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

  function encolarTarea(tarea) {
    ponerEstado(tarea, 'espera', 'En espera');
    cola.push(tarea);
    siguiente();
  }

  function encolar(archivos) {
    if (!archivos.length) return;
    panel.hidden = false;
    Array.prototype.forEach.call(archivos, function (archivo) {
      var tarea = itemDeSubida(archivo);
      tarea.archivo = archivo;
      tareas.push(tarea);
      if (!TIPOS.test(archivo.type)) {
        ponerEstado(tarea, 'fallo', 'No es una imagen JPG, PNG o WEBP.');
      } else if (archivo.size > MAXIMO) {
        ponerEstado(tarea, 'fallo', 'Pesa más de 15 MB.');
      } else {
        encolarTarea(tarea);
      }
    });
    pintarResumen();
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
    if (activas > 0 || cola.length > 0 || contar('aviso') > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // -------------------------------------------------------------- grilla

  function agregarTile(imagen) {
    var tile = plantilla.content.firstElementChild.cloneNode(true);
    tile.dataset.updateUrl = imagen.update_url;
    tile.dataset.destroyUrl = imagen.destroy_url;
    var foto = tile.querySelector('img');
    foto.src = imagen.url;
    foto.alt = 'Imagen ' + imagen.codigo;
    tile.querySelector('.bi-tile-abrir').href = imagen.url;
    tile.querySelector('.bi-tile-nombre').textContent = imagen.medidas || imagen.nombre_original || '';
    var vacia = document.getElementById('grilla-vacia');
    if (vacia) vacia.remove();
    grilla.insertBefore(tile, grilla.firstChild);
    return tile;
  }

  /** Código, «en uso» y dónde se usa, con lo que respondió el servidor. */
  function actualizarTile(tile, datos) {
    if (!tile) return;
    var codigo = datos.imagen.codigo;
    tile.querySelector('[data-rol="codigo"]').textContent = codigo;
    tile.querySelector('.bi-copiar').dataset.copiar = codigo;
    tile.querySelector('.bi-codigo').title = codigo;
    tile.querySelector('[data-rol="edicion"] input').value = codigo;

    var usos = tile.querySelector('[data-rol="usos"]');
    if (!usos) return;
    usos.innerHTML = '';
    if (datos.resultado === 'sin_producto') {
      var error = el('span', 'bi-usos-error');
      error.innerHTML = '<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> ';
      error.appendChild(document.createTextNode('No hay ningún producto con este código. Corregilo con el lápiz y se asigna sola.'));
      usos.appendChild(error);
    } else if (datos.productos && datos.productos.length) {
      usos.textContent = datos.donde || '';
      tile.dataset.enUso = '1';
      var estado = tile.querySelector('.bi-tile-estado');
      if (estado) estado.innerHTML = '<span class="bi-etiqueta bi-etiqueta-uso"><i class="fa-solid fa-check" aria-hidden="true"></i> En uso</span>';
      var pregunta = tile.querySelector('[data-rol="pregunta"]');
      if (pregunta) {
        pregunta.textContent = 'Está en ' + datos.productos.map(function (p) { return p.codigo; }).join(', ') +
          ': se va a sacar de ese producto y, si era la principal, la siguiente foto pasa a serlo. No se puede deshacer.';
      }
    }
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

  /** Mensaje debajo del código: error (rojo), 'aviso' (ámbar) u 'ok' (verde). */
  function error(tile, mensaje, tipo) {
    var caja = tile.querySelector('[data-rol="error"]');
    caja.textContent = mensaje || '';
    caja.style.color = tipo === 'aviso' ? '#8A5A00' : tipo === 'ok' ? '#1E7B3A' : '';
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
    var estabaEnUso = tile.dataset.enUso === '1';
    var botones = form.querySelectorAll('button');
    botones.forEach(function (b) { b.disabled = true; });
    var listo = function (datos) {
      actualizarTile(tile, datos);
      mostrarEdicion(tile, false);
      if (estabaEnUso) {
        error(tile, 'Código cambiado. Si ya tenías un Excel exportado, volvé a exportarlo: el código viejo dejó de existir.', 'aviso');
      } else if (datos.resultado !== 'sin_producto') {
        error(tile, 'Listo: ' + datos.mensaje.charAt(0).toLowerCase() + datos.mensaje.slice(1), 'ok');
      }
    };
    pedir(tile.dataset.updateUrl, 'PATCH', { codigo: nuevo }).then(listo).catch(function (e) {
      if (e.status === 409 && e.datos && e.datos.fotos) {
        abrirDialogo(e.datos, tile.querySelector('.bi-tile-foto img').src, function (lugar) {
          return pedir(tile.dataset.updateUrl, 'PATCH', pedidoDeLugar(nuevo, lugar)).then(function (datos) {
            dialogo.close();
            listo(datos);
          });
        });
        return;
      }
      error(tile, e.message);
      campo.focus();
    }).then(function () {
      botones.forEach(function (b) { b.disabled = false; });
    });
  }

  function abrirDialogo(datos, urlNueva, alElegir) {
    var lugar = dialogo.querySelector('[data-rol="lugar"]');
    lugar.innerHTML = '';
    lugar.appendChild(el('p', 'bi-lugar-mensaje', datos.mensaje + ' ¿Qué hacemos con esta?'));
    lugar.appendChild(armarLugar(datos, urlNueva, alElegir));
    dialogo.showModal();
  }

  dialogo.addEventListener('click', function (e) {
    // Cerrar con la X o tocando afuera de la ventana.
    if (e.target === dialogo || e.target.closest('[data-accion="cerrar-dialogo"]')) dialogo.close();
  });

  function mostrarConfirmacion(tile, mostrar) {
    tile.querySelector('[data-rol="confirmar"]').hidden = !mostrar;
    if (mostrar) tile.querySelector('[data-accion="confirmar-eliminar"]').focus();
  }

  function eliminar(tile, boton) {
    boton.disabled = true;
    // Si está en productos, ya se mostró en cuáles: se confirma sacarla de ahí.
    pedir(tile.dataset.destroyUrl, 'DELETE', { quitar_de_productos: tile.dataset.enUso === '1' }).then(function () {
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
