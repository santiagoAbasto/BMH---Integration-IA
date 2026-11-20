/*
 * Menú de Productos del header (computadora): con el mouse se abre y se cierra como siempre (header-catalog.v1), pero
 * si se hace click en la flecha queda fijo, abierto aunque el mouse salga, hasta volver a hacer click en la flecha o
 * hacer click afuera (eso lo cierra header-catalog). Pedido del cliente, 20/11/2026.
 *
 * header-catalog no tiene esta opción: acá se escucha en fase de captura en el documento, antes que sus listeners del
 * <li>, y se le "esconde" la salida del mouse mientras el menú está fijo. Delegado en el documento: sirve en cada
 * visita de Turbo sin reinicializar.
 */
const DROPDOWN = '.bmh-header [data-osole-header-catalog-v1-dropdown]';
const FIJO = 'data-bmh-fijo';
const DESKTOP = window.matchMedia('(width >= 1366px)');

const panelDe = (item) => item.querySelector('.osole-header-catalog-v1__dropdown');
const estaFijo = (item) => item.hasAttribute(FIJO) && !panelDe(item)?.hidden;

document.addEventListener(
    'click',
    (event) => {
        const flecha = event.target instanceof Element ? event.target.closest('.osole-header-catalog-v1__chevron') : null;
        const item = flecha?.closest(DROPDOWN);

        if (!item || !DESKTOP.matches) {
            return;
        }

        const panel = panelDe(item);

        if (item.hasAttribute(FIJO) && panel.hidden) {
            item.removeAttribute(FIJO); // se cerró por otro lado (Esc, click afuera)
        }

        if (item.hasAttribute(FIJO)) {
            item.removeAttribute(FIJO); // segundo click: header-catalog lo cierra
            return;
        }

        item.setAttribute(FIJO, '');

        // Ya estaba abierto por el hover: header-catalog lo cerraría; en cambio queda abierto y fijo.
        if (!panel.hidden) {
            event.preventDefault();
            event.stopPropagation();
        }
        // Si estaba cerrado, header-catalog lo abre y queda fijo.
    },
    true,
);

// Mientras está fijo, header-catalog no se entera de que el mouse salió (no programa el cierre).
document.addEventListener(
    'mouseleave',
    (event) => {
        if (event.target instanceof Element && event.target.matches(DROPDOWN) && estaFijo(event.target)) {
            event.stopPropagation();
        }
    },
    true,
);

// Un click en una parte sin foco del menú (el fondo del panel) deja el foco en ninguna parte: no lo cierra si está
// fijo. Con Tab hacia otro link (relatedTarget afuera) sí se cierra, como siempre.
document.addEventListener(
    'focusout',
    (event) => {
        const item = event.target instanceof Element ? event.target.closest(DROPDOWN) : null;

        if (item && event.relatedTarget === null && estaFijo(item)) {
            event.stopPropagation();
        }
    },
    true,
);
