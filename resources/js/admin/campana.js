/**
 * Campana del panel (como en Conelec): cada 45 segundos, sólo con la pestaña visible, pide el estado a
 * panel.notificaciones y actualiza el contador y la lista. Si llegó algo que no estaba, la campana se mueve y aparece
 * un aviso (iziToast del layout). El panel abierto no se reemplaza mientras se lo está mirando.
 */
const INTERVALO = 45000;

export function iniciarCampana() {
    const raiz = document.querySelector('[data-campana]');
    if (!raiz) return;

    const contador = raiz.querySelector('[data-campana-contador]');
    const boton = raiz.querySelector('[data-campana-boton]');
    const panel = raiz.querySelector('[data-campana-panel]');
    const claves = (nodo) => new Set([...nodo.querySelectorAll('[data-campana-item]')].map((item) => item.dataset.campanaItem));
    let conocidas = claves(raiz);

    async function actualizar() {
        if (document.visibilityState !== 'visible') return;
        try {
            const respuesta = await fetch(raiz.dataset.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!respuesta.ok) return;
            const datos = await respuesta.json();

            contador.textContent = datos.sin_ver > 99 ? '99+' : String(datos.sin_ver);
            contador.hidden = datos.sin_ver === 0;
            boton.setAttribute('aria-label', datos.sin_ver ? `Notificaciones: ${datos.sin_ver} sin ver` : 'Notificaciones: estás al día');
            if (!panel.matches(':popover-open')) panel.innerHTML = datos.html;

            const nuevas = datos.avisos.filter((aviso) => !conocidas.has(aviso.key));
            conocidas = new Set([...conocidas, ...datos.avisos.map((aviso) => aviso.key)]);
            if (nuevas.length) {
                raiz.removeAttribute('data-sonando');
                requestAnimationFrame(() => raiz.setAttribute('data-sonando', ''));
                window.iziToast?.success({
                    message: nuevas.length === 1 ? `Nuevo: ${nuevas[0].etiqueta.toLowerCase()} de ${nuevas[0].nombre}.` : `Llegaron ${nuevas.length} avisos nuevos.`,
                    position: 'topRight',
                });
            }
        } catch {
            // Sin conexión: se reintenta en el próximo intervalo.
        }
    }

    setInterval(actualizar, INTERVALO);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') actualizar(); });
    raiz.addEventListener('animationend', () => raiz.removeAttribute('data-sonando'));
}
