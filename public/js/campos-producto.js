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

    // Campos ya renderizados al editar.
    contenedor.querySelectorAll('input[name]').forEach(input => {
        const label = input.parentElement.querySelector('label');
        input.id = input.id || input.name;
        if (label) label.htmlFor = input.id;
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

            // Los campos con algo escrito se quedan aunque la nueva categoría no los tenga.
            contenedor.querySelectorAll('input[name]').forEach(input => {
                if (input.value.trim() === '') input.closest('.col-3')?.remove();
            });
            for (const caracteristica of datos.caracteristicas || []) {
                if (contenedor.querySelector(`[name="caracteristicas[${caracteristica.id}]"]`)) continue;
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
