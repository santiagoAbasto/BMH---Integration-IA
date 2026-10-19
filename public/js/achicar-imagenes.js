/*
 * Achica en el navegador las fotos que se eligen en un <input type="file" data-achicar>
 * antes de subirlas. Una foto de cámara de 5 MB sube como ~300 KB: la subida es
 * mucho más rápida y el formulario no choca con el límite de post_max_size.
 *
 * El servidor igual optimiza lo que llega (OptimizadorImagenes); esto sólo evita
 * mandar píxeles que después se tiran. Si algo falla, se sube el archivo original.
 *
 * Mientras prepara las fotos, frena el botón Guardar (.submit del admin).
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var LADO_MAXIMO = parseInt((script && script.dataset.ladoMaximo) || '2000', 10);
    // Lo que se le sugiere al admin (config imagenes.tamano_sugerido).
    var LADO_SUGERIDO = parseInt((script && script.dataset.ladoSugerido) || '1200', 10);
    var LADO_MINIMO = parseInt((script && script.dataset.ladoMinimo) || '800', 10);
    // Debajo de este peso, y sin pasarse del lado máximo, se sube tal cual.
    var PESO_MINIMO = 1.5 * 1024 * 1024;
    // Casi sin pérdida: la compresión de verdad la hace el servidor, una sola
    // vez y controlando la fidelidad (OptimizadorImagenes). A 0,95 eran dos
    // generaciones de pérdida.
    var CALIDAD = 0.98;

    var pendientes = 0;

    var soportaWebp = (function () {
        try {
            var c = document.createElement('canvas');
            c.width = c.height = 1;
            return c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        } catch (e) {
            return false;
        }
    })();

    function cargar(file) {
        return new Promise(function (ok, mal) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { ok({ img: img, url: url }); };
            img.onerror = function () { URL.revokeObjectURL(url); mal(new Error('no se pudo leer la imagen')); };
            img.src = url;
        });
    }

    // Reduce a la mitad mientras sobre más del doble: un solo drawImage de
    // 6000 px a 2000 px deja bordes serruchados en algunos navegadores.
    function redimensionar(img, ancho, alto) {
        var origen = img;
        var w = img.naturalWidth;
        var h = img.naturalHeight;

        while (w / 2 >= ancho && h / 2 >= alto) {
            w = Math.round(w / 2);
            h = Math.round(h / 2);
            origen = dibujar(origen, w, h);
        }

        return dibujar(origen, ancho, alto);
    }

    function dibujar(origen, w, h) {
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(origen, 0, 0, w, h);

        return canvas;
    }

    function tieneTransparencia(canvas) {
        var datos = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        for (var i = 3; i < datos.length; i += 4) {
            if (datos[i] < 255) {
                return true;
            }
        }

        return false;
    }

    function codificar(canvas, tipo) {
        return new Promise(function (ok) { canvas.toBlob(ok, tipo, CALIDAD); });
    }

    function achicar(file) {
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            return Promise.resolve(file);
        }

        return cargar(file).then(function (cargada) {
            var img = cargada.img;
            var escala = Math.min(1, LADO_MAXIMO / Math.max(img.naturalWidth, img.naturalHeight));

            if (escala === 1 && file.size <= PESO_MINIMO) {
                URL.revokeObjectURL(cargada.url);
                return file;
            }

            var canvas = redimensionar(img, Math.round(img.naturalWidth * escala), Math.round(img.naturalHeight * escala));
            URL.revokeObjectURL(cargada.url);

            // Sin WebP (Safari) queda JPEG, que no tiene transparencia: un PNG
            // recortado se sube como vino.
            var tipo = soportaWebp ? 'image/webp' : 'image/jpeg';
            if (tipo === 'image/jpeg' && file.type !== 'image/jpeg' && tieneTransparencia(canvas)) {
                return file;
            }

            return codificar(canvas, tipo).then(function (blob) {
                if (!blob || blob.type !== tipo || blob.size >= file.size) {
                    return file;
                }
                var base = file.name.replace(/\.[^.]+$/, '') || 'imagen';

                return new File([blob], base + (tipo === 'image/webp' ? '.webp' : '.jpg'), { type: tipo, lastModified: Date.now() });
            });
        }).catch(function () {
            return file;
        });
    }

    function mb(bytes) {
        return (bytes / 1048576).toLocaleString('es-AR', { maximumFractionDigits: 1 }) + ' MB';
    }

    function suma(archivos) {
        return archivos.reduce(function (total, f) { return total + f.size; }, 0);
    }

    function estado(input) {
        var el = input.parentNode.querySelector('.achicar-estado');
        if (!el) {
            el = document.createElement('div');
            el.className = 'form-text achicar-estado';
            input.insertAdjacentElement('afterend', el);
        }

        return el;
    }

    /** Ancho y alto de una foto (null si no se puede leer). */
    function medir(file) {
        if (!/^image\//.test(file.type)) {
            return Promise.resolve(null);
        }

        return cargar(file).then(function (cargada) {
            URL.revokeObjectURL(cargada.url);
            return { ancho: cargada.img.naturalWidth, alto: cargada.img.naturalHeight };
        }).catch(function () { return null; });
    }

    /**
     * Las cards muestran la foto en un cuadrado: avisa (sin frenar nada) si
     * alguna no es cuadrada o es tan chica que puede verse borrosa.
     */
    function sugerir(input, archivos) {
        var caja = input.parentNode.querySelector('.achicar-sugerencia');
        if (!caja) {
            caja = document.createElement('div');
            caja.className = 'form-text achicar-sugerencia';
            caja.setAttribute('role', 'status');
            estado(input).insertAdjacentElement('afterend', caja);
        }
        caja.textContent = '';
        caja.hidden = true;

        return Promise.all(archivos.map(medir)).then(function (medidas) {
            var noCuadradas = [];
            var chicas = [];
            medidas.forEach(function (m) {
                if (!m || !m.alto) return;
                var proporcion = m.ancho / m.alto;
                if (proporcion < 0.9 || proporcion > 1.1) noCuadradas.push(m);
                if (Math.min(m.ancho, m.alto) < LADO_MINIMO) chicas.push(m);
            });

            var avisos = [];
            if (noCuadradas.length) {
                avisos.push((noCuadradas.length === 1
                    ? 'La foto no es cuadrada (' + medida(noCuadradas[0]) + '): se va a ver con márgenes blancos.'
                    : noCuadradas.length + ' fotos no son cuadradas: se van a ver con márgenes blancos.') +
                    ' Lo ideal es ' + LADO_SUGERIDO + ' × ' + LADO_SUGERIDO + ' px.');
            }
            if (chicas.length) {
                avisos.push(chicas.length === 1
                    ? 'La foto es chica (' + medida(chicas[0]) + '): puede verse borrosa. Mínimo ' + LADO_MINIMO + ' px de lado.'
                    : chicas.length + ' fotos miden menos de ' + LADO_MINIMO + ' px de lado: pueden verse borrosas.');
            }
            caja.textContent = avisos.join(' ');
            caja.hidden = avisos.length === 0;
        });
    }

    function medida(m) {
        return m.ancho + ' × ' + m.alto + ' px';
    }

    function procesar(input) {
        var originales = Array.prototype.slice.call(input.files || []);
        if (!originales.length || typeof DataTransfer === 'undefined') {
            return Promise.resolve();
        }

        var aviso = estado(input);
        aviso.textContent = 'Preparando ' + (originales.length > 1 ? originales.length + ' imágenes' : 'la imagen') + '…';
        pendientes++;

        return originales.reduce(function (cadena, file) {
            return cadena.then(function (listos) {
                return achicar(file).then(function (nuevo) { return listos.concat([nuevo]); });
            });
        }, Promise.resolve([])).then(function (nuevos) {
            // Si mientras tanto eligieron otras fotos, esta tanda ya no vale.
            var actuales = Array.prototype.slice.call(input.files || []);
            if (actuales.length !== originales.length || actuales.some(function (f, i) { return f !== originales[i]; })) {
                return;
            }

            var dt = new DataTransfer();
            nuevos.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;

            var antes = suma(originales);
            var despues = suma(nuevos);
            aviso.textContent = despues < antes ? 'Lista para subir: ' + mb(antes) + ' → ' + mb(despues) : '';

            return sugerir(input, originales);
        }).catch(function () {
            aviso.textContent = '';
        }).then(function () {
            pendientes--;
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('input[type="file"][data-achicar]')) {
            procesar(e.target);
        }
    });

    // Fase de captura: corre antes que el click de .submit del layout, que
    // envía el formulario directo con form.submit().
    document.addEventListener('click', function (e) {
        if (pendientes === 0) {
            return;
        }
        var boton = e.target.closest && e.target.closest('.submit, button[type="submit"], input[type="submit"]');
        if (!boton) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        if (window.iziToast) {
            iziToast.info({ title: 'Un momento', message: 'Estoy preparando las imágenes para que suban más rápido.' });
        }
    }, true);

    window.BmhAchicarImagenes = { achicar: achicar, procesar: procesar, pendientes: function () { return pendientes; } };
})();
