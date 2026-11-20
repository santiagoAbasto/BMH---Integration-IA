/*
 * Pista de deslizamiento para las tablas de Partes relacionadas, Equivalencias y Aplicaciones
 * (resources/css/sitio/tablas-deslizables.css). Envuelve el contenedor que desliza en un .bmh-desliza con dos
 * degradés y la píldora «Deslizá», y mantiene al día sus atributos:
 *   data-desborda   la tabla no entra (solo así se ve la pista)
 *   data-desplazado ya no está al principio (degradé izquierdo)
 *   data-al-final   llegó al final (se va el degradé derecho)
 *   data-visto      la persona ya deslizó: la píldora no vuelve
 *   data-a-la-vista la tabla está a la vista: recién ahí la flecha empuja
 *
 * Contenedores: el .pbmh-panel-inner de las cards y el .pbmh-panel de la ficha. Delegado en el documento para el
 * scroll; se prepara en cada visita de Turbo y con las cards que agrega el catálogo.
 */
const CONTENEDORES = '.pbmh-panel-inner, .pbmh-card[data-pbmh-detalle] .pbmh-panel';
const FLECHA =
    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 8h9M8.5 4.5 12 8l-3.5 3.5"/></svg>';
const MARGEN = 4;

const tamanos = 'ResizeObserver' in window ? new ResizeObserver((entradas) => entradas.forEach((e) => actualizar(e.target))) : null;
const vista =
    'IntersectionObserver' in window
        ? new IntersectionObserver(
              (entradas) =>
                  entradas.forEach((e) => {
                      if (e.isIntersecting && e.intersectionRatio > 0) {
                          e.target.setAttribute('data-a-la-vista', '');
                      }
                  }),
              { threshold: 0.5 },
          )
        : null;

function marcoDe(contenedor) {
    return contenedor.parentElement?.classList.contains('bmh-desliza') ? contenedor.parentElement : null;
}

function preparar(contenedor) {
    if (marcoDe(contenedor)) {
        actualizar(contenedor);
        return;
    }

    const marco = document.createElement('div');
    marco.className = 'bmh-desliza';
    contenedor.before(marco);
    marco.append(contenedor);
    marco.insertAdjacentHTML(
        'beforeend',
        '<span class="bmh-desliza__borde bmh-desliza__borde--inicio" aria-hidden="true"></span>' +
            '<span class="bmh-desliza__borde bmh-desliza__borde--fin" aria-hidden="true"></span>' +
            `<span class="bmh-desliza__pista" aria-hidden="true">Deslizá ${FLECHA}</span>`,
    );
    marco.style.setProperty('--bmh-desliza-pie', getComputedStyle(contenedor).paddingBlockEnd);

    tamanos?.observe(contenedor);
    contenedor.firstElementChild && tamanos?.observe(contenedor.firstElementChild);
    vista?.observe(marco);
    actualizar(contenedor);
}

function actualizar(elemento) {
    const contenedor = elemento.matches(CONTENEDORES) ? elemento : elemento.closest(CONTENEDORES);
    const marco = contenedor && marcoDe(contenedor);

    if (!marco) {
        return;
    }

    const sobra = contenedor.scrollWidth - contenedor.clientWidth;
    const desborda = contenedor.clientWidth > 0 && sobra > MARGEN;

    marco.toggleAttribute('data-desborda', desborda);
    marco.toggleAttribute('data-desplazado', contenedor.scrollLeft > MARGEN);
    marco.toggleAttribute('data-al-final', contenedor.scrollLeft >= sobra - MARGEN);

    // Con teclado también se puede deslizar: el contenedor recibe el foco solo cuando hace falta.
    if (desborda && !contenedor.hasAttribute('tabindex')) {
        contenedor.tabIndex = 0;
        contenedor.setAttribute('role', 'region');
        contenedor.setAttribute('aria-label', 'Tabla: deslizá para ver todas las columnas');
    }
}

function prepararTodas() {
    document.querySelectorAll(CONTENEDORES).forEach(preparar);
}

document.addEventListener(
    'scroll',
    (event) => {
        const contenedor = event.target instanceof Element && event.target.matches(CONTENEDORES) ? event.target : null;
        const marco = contenedor && marcoDe(contenedor);

        if (!marco) {
            return;
        }

        actualizar(contenedor);
        if (contenedor.scrollLeft > 12) {
            marco.setAttribute('data-visto', '');
        }
    },
    { capture: true, passive: true },
);

// La ficha abre las tablas sacando .d-none: el ResizeObserver lo ve. Las cards las abren con max-height, que no
// cambia el ancho: ahí alcanza con medir al preparar y al abrir la pestaña.
document.addEventListener('click', (event) => {
    if (event.target instanceof Element && event.target.closest('.pbmh-tab')) {
        setTimeout(prepararTodas, 60);
    }
});

document.addEventListener('turbo:load', prepararTodas);
document.addEventListener('catalogo:cards', prepararTodas);
window.addEventListener('load', prepararTodas);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', prepararTodas);
} else {
    prepararTodas();
}
