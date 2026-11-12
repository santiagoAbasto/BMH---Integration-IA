/*
 * Mapa de /contacto (components/contact-map.blade.php), como el de Conelec: Leaflet con teselas de OpenStreetMap en
 * gris (solo las teselas: el pin y los controles mantienen su color) y el pin azul de BMH del Figma como
 * marcador anclado a la dirección, así acompaña el desplazamiento y el zoom. Leaflet se carga solo en las páginas
 * que tienen mapa. Se crea en turbo:load y se destruye antes de que Turbo guarde la página en caché.
 *
 * Tarjeta de la dirección ([data-contact-map-card], con el link a Google Maps): va en un popup anclado al pin. Se abre
 * al pasar el mouse por el mapa, al enfocar el pin con el teclado o al tocarlo, y se cierra al salir del mapa o con
 * Esc. Al destruir el mapa vuelve a su lugar en el HTML, así la página guardada por Turbo la conserva.
 */
const CLOSE_DELAY = 250;
const HOVER = matchMedia('(hover: hover)');
const SELECTOR = '[data-contact-map]';
// Pin del Figma (31,25 × 39,07, #0098DA con el punto blanco). Colores en el SVG: se ve bien aunque falte el CSS.
const PIN = '<svg viewBox="0 0 31.2542 39.0673" aria-hidden="true" focusable="false"><path d="M31.2542 15.6271C31.2542 25.3804 20.4344 35.538 16.8011 38.6751C16.4626 38.9296 16.0506 39.0673 15.6271 39.0673C15.2036 39.0673 14.7916 38.9296 14.4531 38.6751C10.8198 35.538 0 25.3804 0 15.6271C0 11.4825 1.64642 7.50772 4.57707 4.57707C7.50772 1.64642 11.4825 0 15.6271 0C19.7717 0 23.7465 1.64642 26.6771 4.57707C29.6078 7.50772 31.2542 11.4825 31.2542 15.6271Z" fill="#0098da"/><circle cx="15.6271" cy="15.6271" r="5.8602" fill="#fff"/></svg>';
const maps = new WeakMap();
let leaflet;

function load() {
    leaflet ??= Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]).then(([module]) => module.default);
    return leaflet;
}

function create(L, node) {
    const location = JSON.parse(node.dataset.location);
    const map = L.map(node, {
        // Con mouse, la tarjeta se cierra al salir del mapa (no con un clic). En el celular, al tocar fuera de ella.
        closePopupOnClick: !HOVER.matches,
        zoomControl: false,
        attributionControl: false,
        // La rueda no hace zoom hasta que se hace clic en el mapa: así no se traba el scroll de la página.
        scrollWheelZoom: false,
    });
    L.control.zoom({ position: 'topright', zoomInTitle: 'Acercar', zoomOutTitle: 'Alejar' }).addTo(map);
    L.control.attribution({ position: 'bottomright', prefix: false })
        .addAttribution('© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>')
        .addTo(map);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const point = [location.lat, location.lng];
    if (location.bounds) map.fitBounds(location.bounds);
    else map.setView(point, location.zoom);

    // La punta del pin (abajo al centro) queda sobre la coordenada.
    const icon = L.divIcon({ className: 'contact-map__marker', html: PIN, iconSize: [31, 39], iconAnchor: [15.6, 39] });
    const marker = L.marker(point, { icon, keyboard: true }).addTo(map);
    const card = node.parentElement.querySelector('[data-contact-map-card]');
    const entry = { map, card, home: card?.parentElement ?? null };

    if (card) {
        // Popup propio (no bindPopup): un clic en el pin la abre, nunca la cierra.
        const popup = L.popup({ closeButton: false, autoPan: false, autoPanPadding: [16, 16], offset: [0, -36], maxWidth: 320, className: 'contact-map__popup' })
            .setLatLng(point)
            .setContent(card);
        const pin = marker.getElement();
        pin.setAttribute('aria-label', 'Ver la dirección de BMH');

        let timer = 0;
        let refocusing = false;
        // Con un toque o el teclado, el mapa se corre si hace falta para que la tarjeta entre entera; con el hover, no
        // (el mapa no se mueve solo por pasar el mouse).
        const open = (event) => {
            clearTimeout(timer);
            if (popup.isOpen()) return;
            popup.options.autoPan = event?.type !== 'mouseenter';
            map.openPopup(popup);
        };
        const close = () => { clearTimeout(timer); timer = setTimeout(() => map.closePopup(popup), CLOSE_DELAY); };
        const container = map.getContainer();

        marker.on('click', () => open());
        // Al cerrar con Esc el foco vuelve al pin: ese foco no la reabre.
        pin.addEventListener('focus', (event) => { if (!refocusing) open(event); });
        if (HOVER.matches) {
            container.addEventListener('mouseenter', open);
            container.addEventListener('mouseleave', close);
        }
        container.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && popup.isOpen()) {
                map.closePopup(popup);
                refocusing = true;
                pin.focus();
                refocusing = false;
            }
        });
        entry.dispose = () => clearTimeout(timer);
    }

    map.once('click focus', () => map.scrollWheelZoom.enable());
    maps.set(node, entry);
}

async function init() {
    const nodes = [...document.querySelectorAll(SELECTOR)].filter((node) => !maps.has(node));
    if (!nodes.length) return;
    try {
        const L = await load();
        nodes.forEach((node) => { if (node.isConnected && !maps.has(node)) create(L, node); });
    } catch {
        nodes.forEach((node) => node.classList.add('contact-map__canvas--failed'));
    }
}

function destroy() {
    document.querySelectorAll(SELECTOR).forEach((node) => {
        const entry = maps.get(node);
        entry?.dispose?.();
        // La tarjeta vuelve a su lugar antes de que Leaflet borre el popup.
        if (entry?.card && entry.home) entry.home.append(entry.card);
        entry?.map.remove();
        maps.delete(node);
    });
}

document.addEventListener('turbo:load', init);
document.addEventListener('turbo:render', init);
document.addEventListener('turbo:before-cache', destroy);
