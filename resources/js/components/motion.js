/*
 * Apariciones al hacer scroll (lista de bloques en App\Support\Motion), como en Conelec y Rapivac: sin librerías,
 * con IntersectionObserver y transiciones de opacity / translate / clip-path. Cada elemento aparece una sola vez; lo
 * que entra junto a la pantalla lo hace en cascada, de arriba abajo y de izquierda a derecha. Al terminar se quitan
 * las clases de la transición para no pisar los efectos propios de cada componente (hover de tarjetas, etc.).
 * Sin html.motion (reducir movimiento, o sin JS) no hace nada: el contenido ya está visible.
 *
 * Con Turbo:
 * - Se arma en cada turbo:load y se desarma antes de la visita siguiente (nunca quedan observers duplicados).
 * - Antes de guardar la página en la caché todo queda visible y sin clases de transición: la copia que Turbo muestra
 *   al volver atrás (o como vista previa) no tiene nada invisible.
 * - Al volver atrás, o cuando la página nueva reemplaza a la vista previa de la caché, la persona ya la vio:
 *   html.motion-quiet hace que lo que está a la vista aparezca de una, sin repetir la entrada del hero.
 */
const root = document.documentElement;
const STEP = 80;
const ROW_STEP = 45;
const MAX_STEPS = 6;
const DURATION = 1200;
// Lo que asoma abajo de la pantalla al cargar la home espera a que termine la entrada del hero (motion.css).
const INTRO = 550;
let page = null;
let introUntil = 0;

function targets() {
    try {
        const data = JSON.parse(document.getElementById('motion-targets')?.textContent ?? 'null');
        if (!data) return null;
        const children = (selectors) => selectors.map((selector) => `${selector} > *`);
        return {
            all: [...data.blocks, ...data.media, ...children(data.grids), ...children(data.rows)].join(', '),
            media: data.media.join(', '),
            rows: children(data.rows).join(', '),
            swaps: data.swaps.join(', '),
        };
    } catch {
        return null;
    }
}

const byPosition = (a, b) => {
    const ra = a.getBoundingClientRect();
    const rb = b.getBoundingClientRect();
    return Math.abs(ra.top - rb.top) > 8 ? ra.top - rb.top : ra.left - rb.left;
};

// Visible ahora mismo en la pantalla (un elemento con display: none no lo está).
function onScreen(element) {
    const rect = element.getBoundingClientRect();
    return (rect.width > 0 || rect.height > 0) && rect.bottom > 0 && rect.top < window.innerHeight;
}

function reveal(elements, selectors) {
    let index = 0;
    let rows = 0;
    const wait = Math.max(0, Math.round(introUntil - performance.now()));
    elements.sort(byPosition).forEach((element) => {
        const isRow = element.matches(selectors.rows);
        const delay = wait + (isRow ? Math.min(rows++, MAX_STEPS) * ROW_STEP : Math.min(index++, MAX_STEPS) * STEP);
        element.style.setProperty('--reveal-delay', `${delay}ms`);
        element.classList.add('is-revealing', 'is-revealed');
        if (element.matches(selectors.media)) element.classList.add('is-revealing--media');
        if (isRow) element.classList.add('is-revealing--row');
        setTimeout(() => settle(element), DURATION + delay);
    });
}

function settle(element) {
    element.classList.remove('is-revealing', 'is-revealing--media', 'is-revealing--row');
    element.style.removeProperty('--reveal-delay');
}

function init() {
    root.classList.add('motion-ready');
    page?.dispose();
    page = null;
    const selectors = targets();
    if (!root.classList.contains('motion') || !selectors) return;
    if (!('IntersectionObserver' in window)) {
        root.classList.remove('motion');
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        const entered = [];
        entries.forEach((entry) => {
            // Lo que quedó arriba de la pantalla (volver atrás, un link a un ancla) ya se pasó: se muestra sin animar.
            const passed = !entry.isIntersecting && entry.boundingClientRect.height > 0 && entry.boundingClientRect.bottom <= 0;
            if (!entry.isIntersecting && !passed) return;
            observer.unobserve(entry.target);
            if (passed) entry.target.classList.add('is-revealed');
            else entered.push(entry.target);
        });
        reveal(entered.filter((element) => !element.classList.contains('is-revealing')), selectors);
    }, { rootMargin: '0px 0px -40px 0px' });

    const watched = new WeakSet();
    let swapped = [];
    const scan = (quiet = false) => {
        const roots = swapped;
        swapped = [];
        document.querySelectorAll(selectors.all).forEach((element) => {
            if (watched.has(element) || element.classList.contains('is-revealed')) return;
            watched.add(element);
            // Un bloque dentro de otro que ya se anima lo acompaña: no se anima dos veces.
            if (element.parentElement?.closest(selectors.all)) {
                element.classList.add('is-revealed');
                return;
            }
            // Lo que la persona ya tiene delante (página que ya vio, resultados recién filtrados) aparece de una.
            if ((quiet || roots.some((container) => container.contains(element))) && onScreen(element)) {
                element.classList.add('is-revealed');
                return;
            }
            observer.observe(element);
        });
    };

    // El catálogo suma resultados al scrollear y los cambia al filtrar, sin recargar: lo nuevo también se observa.
    // Los cambios internos del mapa (teselas de Leaflet) no tienen nada que animar.
    let pending = 0;
    const mutations = new MutationObserver((records) => {
        let relevant = false;
        records.forEach((record) => {
            if (!record.addedNodes.length || record.target.closest?.('.leaflet-container')) return;
            if (record.target.matches?.(selectors.swaps)) swapped.push(record.target);
            relevant = true;
        });
        if (!relevant) return;
        cancelAnimationFrame(pending);
        pending = requestAnimationFrame(() => scan());
    });
    mutations.observe(document.body, { childList: true, subtree: true });

    page = {
        dispose() {
            observer.disconnect();
            mutations.disconnect();
            cancelAnimationFrame(pending);
        },
    };
    const quiet = root.classList.contains('motion-quiet');
    introUntil = !quiet && document.querySelector('.bmh-home-hero') ? performance.now() + INTRO : 0;
    scan(quiet);
}

// Antes de que Turbo guarde la página en la caché: todo visible y sin transiciones a medias.
function freeze() {
    page?.dispose();
    page = null;
    const selectors = targets();
    if (!selectors) return;
    document.querySelectorAll(selectors.all).forEach((element) => {
        element.classList.add('is-revealed');
        settle(element);
    });
}

// motion-quiet se decide justo antes de cambiar la página (no al empezar la visita): si se quitara antes, la página
// que todavía está a la vista podría repetir su entrada mientras llega la nueva.
let restoring = false;
let previewed = false;
document.addEventListener('turbo:visit', (event) => {
    restoring = event.detail.action === 'restore';
    previewed = false;
});
document.addEventListener('turbo:before-render', () => {
    // Turbo marca la vista previa (data-turbo-preview) antes de este evento. La versión nueva que llega después de
    // una vista previa tampoco se anima: la persona ya está viendo esa página.
    if (root.hasAttribute('data-turbo-preview')) previewed = true;
    root.classList.toggle('motion-quiet', restoring || previewed);
});
// Recién cambiada la página y antes de pintarla: si es una que la persona ya vio, lo que está a la vista se muestra
// ya mismo (turbo:load llega un momento después y no tiene que haber ni un cuadro con eso invisible).
document.addEventListener('turbo:render', () => {
    if (!root.classList.contains('motion-quiet') || root.hasAttribute('data-turbo-preview')) return;
    const selectors = targets();
    if (!selectors) return;
    document.querySelectorAll(selectors.all).forEach((element) => {
        if (onScreen(element)) element.classList.add('is-revealed');
    });
});
document.addEventListener('turbo:before-cache', freeze);
document.addEventListener('turbo:load', init);

// Si se activa «reducir movimiento» con la página abierta, todo queda quieto y visible al instante.
const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)');
reduce?.addEventListener?.('change', () => {
    if (!reduce.matches) return;
    freeze();
    root.classList.remove('motion');
});
