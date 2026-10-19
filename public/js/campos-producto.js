(function () {
    'use strict';

    const contenedor = document.getElementById('dynamic-inputs');
    const categoria = document.getElementById('select-categoria');
    if (!contenedor || !categoria) return;

    const categoriaInicial = categoria.value;
    const valoresIniciales = new Map(
        Array.from(contenedor.querySelectorAll('input[name]'), input => [input.name, input.value])
    );
    let solicitud;

    function aplicarUnidad(input, nombre) {
        let sufijo;
        let descripcion;
        if (/\bvoltaje\b/i.test(nombre)) {
            sufijo = 'v';
            descripcion = 'voltios';
        } else if (/\bdi[aá]metro\b/i.test(nombre)) {
            sufijo = 'MM';
            descripcion = 'milímetros';
        } else {
            return;
        }

        const valor = input.value.trim().replace(new RegExp('\\s*' + sufijo + '$', 'i'), '').replace(',', '.');
        input.type = 'number';
        input.min = '0';
        input.step = 'any';
        input.inputMode = 'decimal';
        input.value = valor;
        input.addEventListener('keydown', event => {
            if (!event.ctrlKey && !event.metaKey && ['e', 'E', '+', '-'].includes(event.key)) {
                event.preventDefault();
            }
        });

        const grupo = document.createElement('div');
        grupo.className = 'input-group';
        input.before(grupo);
        grupo.append(input);

        const unidad = document.createElement('span');
        unidad.className = 'input-group-text';
        unidad.id = input.id + '_unidad';
        unidad.textContent = sufijo;
        unidad.setAttribute('aria-label', descripcion);
        input.setAttribute('aria-describedby', unidad.id);
        grupo.append(unidad);
    }

    // Campos ya renderizados al editar (columnas legacy y características).
    contenedor.querySelectorAll('input[name]').forEach(input => {
        const label = input.parentElement.querySelector('label');
        input.id = input.id || input.name;
        if (label) {
            label.htmlFor = input.id;
            aplicarUnidad(input, label.textContent);
        }
    });

    function agregarCampo(nombre, etiqueta, id) {
        const campo = document.createElement('div');
        campo.className = 'col-3 mb-3';
        const label = document.createElement('label');
        label.className = 'form-label';
        label.htmlFor = id;
        label.textContent = etiqueta;
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.name = nombre;
        input.id = id;
        input.value = categoria.value === categoriaInicial ? (valoresIniciales.get(nombre) || '') : '';
        campo.append(label, input);
        contenedor.append(campo);
        aplicarUnidad(input, etiqueta);
    }

    async function cargarCategoria() {
        if (solicitud) solicitud.abort();
        solicitud = new AbortController();

        try {
            const url = contenedor.dataset.urlCategoria.replace(':id', encodeURIComponent(categoria.value));
            const respuesta = await fetch(url, {
                signal: solicitud.signal,
                headers: { Accept: 'application/json' },
            });
            if (!respuesta.ok) throw new Error('No se pudieron cargar los campos del producto.');
            const datos = await respuesta.json();

            contenedor.replaceChildren();
            for (const [nombre, etiqueta] of Object.entries(datos.columnas || {})) {
                agregarCampo(nombre, etiqueta, nombre);
            }
            for (const caracteristica of datos.caracteristicas || []) {
                agregarCampo(
                    `caracteristicas[${caracteristica.id}]`,
                    caracteristica.nombre,
                    `caracteristica_${caracteristica.id}`
                );
            }
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        }
    }

    categoria.addEventListener('change', cargarCategoria);
    if (contenedor.dataset.cargarInicial === 'true') cargarCategoria();
})();
