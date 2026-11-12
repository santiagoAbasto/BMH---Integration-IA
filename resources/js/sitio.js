// Sitio público y Zona de Clientes (rediseño 2026): JS de los componentes de osole/ui que se usan.
import '../../vendor/osole/ui/resources/components/header-catalog/v1/header-catalog.js';
import '../../vendor/osole/ui/resources/components/hero/v1/hero.js';

// Ingreso y cuenta (popovers del header): se abren justo debajo del botón que los abrió.
document.querySelectorAll('.bmh-popover[popover]').forEach((popover) => {
    popover.addEventListener('beforetoggle', (event) => {
        if (event.newState !== 'open') {
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
    });
});
