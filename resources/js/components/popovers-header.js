/*
 * Ingreso y cuenta del cliente (popovers del header, layouts/partials/sitio-header): se abren justo debajo
 * del botón que los abrió. En celular (menú del header) quedan arriba, a lo ancho.
 *
 * Un solo listener en el documento (beforetoggle no burbujea: se escucha en captura), así sirve para el
 * header de cada visita de Turbo sin volver a enlazar nada.
 */
document.addEventListener('beforetoggle', (event) => {
    const popover = event.target;

    if (!(popover instanceof HTMLElement) || !popover.matches('.bmh-popover') || event.newState !== 'open') {
        return;
    }

    const boton = [...document.querySelectorAll(`[popovertarget="${popover.id}"]`)]
        .find((el) => el.getClientRects().length > 0);

    if (!boton || window.matchMedia('(width < 1366px)').matches) {
        popover.style.removeProperty('--bmh-popover-top');
        popover.style.removeProperty('inset-inline-end');
        return;
    }

    const caja = boton.getBoundingClientRect();
    popover.style.setProperty('--bmh-popover-top', `${Math.round(caja.bottom + 8)}px`);
    popover.style.setProperty('inset-inline-end', `${Math.round(document.documentElement.clientWidth - caja.right)}px`);
}, true);
