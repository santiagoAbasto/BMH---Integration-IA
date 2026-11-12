// Panel de administración (rediseño 2026), con los componentes de administración de osole/ui.
// Sin Turbo: el JS viejo del panel (jQuery, Bootstrap, Summernote) espera recargas completas.
import '../../vendor/osole/ui/resources/components/admin-sidebar/v1/admin-sidebar.js';
import '../../vendor/osole/ui/resources/components/admin-login/v1/admin-login.js';
import '../../vendor/osole/ui/resources/components/admin-inbox/v1/admin-inbox.js';

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

/*
 * Contacto › Mensajes: contador de consultas sin leer en la sidebar (admin-sidebar.v1 no trae contadores).
 * El número lo deja el layout en <body data-contacto-sin-leer>; se repite en «Contacto» mientras el grupo
 * está cerrado (contacto.css lo oculta al abrirlo).
 */
function contarMensajesSinLeer() {
    const sinLeer = Number(document.body.dataset.contactoSinLeer || 0);
    const link = document.querySelector(`.adm-sidebar a[href="${document.body.dataset.contactoMensajes}"]`);
    if (!link || sinLeer < 1) return;

    const contador = () => {
        const span = document.createElement('span');
        span.className = 'adm-sidebar__contador';
        span.textContent = sinLeer > 99 ? '99+' : String(sinLeer);
        const oculto = document.createElement('span');
        oculto.className = 'visually-hidden';
        oculto.textContent = sinLeer === 1 ? ' consulta sin leer' : ' consultas sin leer';
        span.append(oculto);
        return span;
    };

    link.append(contador());
    link.closest('details')?.querySelector('summary .osole-admin-sidebar-v1__chevron')?.before(contador());
}

function iniciar() {
    mudarModales();
    marcarScrollDelMenu();
    contarMensajesSinLeer();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
