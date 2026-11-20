/*
 * Agrega el indicador «Ampliar foto» (resources/css/sitio/ampliar-foto.css) a la foto principal de las cards y de la
 * ficha. El visor lo abre public/js/galeria-producto.js con cualquier click dentro de la foto; el indicador no recibe
 * clicks (pointer-events: none).
 *
 * Fotos principales: la caja de la card horizontal, la portada de la card vertical, la de rodamientos y el escenario
 * de fotorama en la ficha. Las miniaturas de las tablas quedan afuera (no hay lugar).
 */
const FOTOS = [
    '.pbmh-imgbox[data-gallery-open]',
    '.producto-portada[data-gallery-open]',
    '.hoverGradient[data-gallery-open]',
    '.fotorama[data-gallery-images] .fotorama__stage',
].join(', ');

const ICONO =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5M10.5 7.75v5.5M7.75 10.5h5.5"/></svg>';

function preparar(foto) {
    if (foto.querySelector(':scope > .bmh-ampliar')) {
        return;
    }

    if (getComputedStyle(foto).position === 'static') {
        foto.style.position = 'relative';
    }

    const indicador = document.createElement('span');
    indicador.className = 'bmh-ampliar';
    indicador.setAttribute('aria-hidden', 'true');
    indicador.innerHTML = `<span class="bmh-ampliar__icono">${ICONO}</span>Ampliar foto`;

    const velo = document.createElement('span');
    velo.className = 'bmh-ampliar-velo';
    velo.setAttribute('aria-hidden', 'true');

    foto.classList.add('bmh-ampliar-host');
    foto.append(velo, indicador);
}

function prepararTodas() {
    document.querySelectorAll(FOTOS).forEach(preparar);
}

// Fotorama arma el escenario después de cargar; las cards del catálogo llegan al filtrar o al hacer scroll. Por las
// dudas, también al pasar el mouse por una foto que todavía no lo tenga.
document.addEventListener(
    'pointerover',
    (event) => {
        const foto = event.target instanceof Element ? event.target.closest(FOTOS) : null;
        if (foto) {
            preparar(foto);
        }
    },
    { capture: true, passive: true },
);

document.addEventListener('turbo:load', prepararTodas);
document.addEventListener('catalogo:cards', prepararTodas);
window.addEventListener('load', prepararTodas);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', prepararTodas);
} else {
    prepararTodas();
}
