// Panel de administración (rediseño 2026), con los componentes de administración de osole/ui.
// Sin Turbo: el JS viejo del panel (jQuery, Bootstrap, Summernote) espera recargas completas.
import '../../vendor/osole/ui/resources/components/admin-sidebar/v1/admin-sidebar.js';
import '../../vendor/osole/ui/resources/components/admin-login/v1/admin-login.js';

/*
 * Modales de Bootstrap: se mudan al final del <body>. Muchas pantallas los dejan dentro de una
 * tabla o una tarjeta; si algún ancestro crea un contexto de apilamiento (transform, opacity,
 * filter…), el modal queda por debajo de su fondo oscuro y no se puede usar. Los que están dentro
 * de un <form> se quedan donde están: sus campos son parte de ese formulario.
 */
function mudarModales() {
    document.querySelectorAll('.adm-content .modal').forEach((modal) => {
        if (!modal.closest('form')) {
            document.body.appendChild(modal);
        }
    });
}

/* Sidebar: la barra de scroll de la lista de links se marca más mientras se scrollea. */
function marcarScrollDelMenu() {
    const nav = document.querySelector('.adm-sidebar .osole-admin-sidebar-v1__nav');
    if (!nav) return;

    let espera;
    nav.addEventListener('scroll', () => {
        nav.classList.add('is-scrolling');
        clearTimeout(espera);
        espera = setTimeout(() => nav.classList.remove('is-scrolling'), 900);
    }, { passive: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { mudarModales(); marcarScrollDelMenu(); });
} else {
    mudarModales();
    marcarScrollDelMenu();
}
