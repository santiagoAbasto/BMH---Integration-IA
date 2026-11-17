/**
 * Selector con búsqueda para el admin (patrón combobox de ARIA 1.2): se escribe, se filtra con debounce, se navega
 * con las flechas, Enter elige y Escape cierra. Lo elegido queda en un campo oculto con el `name` del formulario y
 * se muestra como una ficha con «Cambiar».
 *
 * Marcado (ver backend/partials/selector-buscable):
 *   <div data-selector-buscable data-url="…">            → busca en el servidor (?q=), mínimo 2 letras
 *   <div data-selector-buscable data-local>               → filtra las opciones del <script type="application/json">
 * Cada opción: { valor, titulo, codigo?, detalle?, imagen?, meta?, extra? }.
 */

const DEBOUNCE_SERVIDOR = 250;
const DEBOUNCE_LOCAL = 120;

let secuencia = 0;

/** Minúsculas y sin tildes, para comparar «Estaño» con «estano». */
const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

function el(tag, atributos = {}, ...hijos) {
    const nodo = document.createElement(tag);
    for (const [clave, valor] of Object.entries(atributos)) {
        if (valor === null || valor === undefined || valor === false) continue;
        if (clave === 'texto') nodo.textContent = valor;
        else if (clave.startsWith('on')) nodo.addEventListener(clave.slice(2), valor);
        else nodo.setAttribute(clave, valor === true ? '' : valor);
    }
    nodo.append(...hijos.filter((h) => h !== null && h !== undefined && h !== false));
    return nodo;
}

/** El texto con lo buscado marcado (<mark>), armado con nodos: nunca HTML del servidor. */
function resaltar(texto, consulta) {
    const fuente = String(texto ?? '');
    const q = normalizar(consulta).trim();
    if (!q) return document.createTextNode(fuente);
    const indice = normalizar(fuente).indexOf(q);
    if (indice < 0) return document.createTextNode(fuente);
    const span = el('span');
    span.append(fuente.slice(0, indice), el('mark', { texto: fuente.slice(indice, indice + q.length) }), fuente.slice(indice + q.length));
    return span;
}

class SelectorBuscable {
    constructor(raiz) {
        this.raiz = raiz;
        this.input = raiz.querySelector('[data-selector-input]');
        this.oculto = raiz.querySelector('[data-selector-valor]');
        this.lista = raiz.querySelector('[data-selector-lista]');
        this.estado = raiz.querySelector('[data-selector-estado]');
        this.ficha = raiz.querySelector('[data-selector-ficha]');
        this.limpiar = raiz.querySelector('[data-selector-limpiar]');
        this.url = raiz.dataset.url || null;
        this.minimo = this.url ? 2 : 0;
        this.opcionesLocales = this.url ? [] : JSON.parse(raiz.querySelector('[data-selector-opciones]')?.textContent || '[]');
        this.items = [];
        this.activo = -1;
        this.consulta = '';

        const id = `selector-${++secuencia}`;
        this.lista.id = `${id}-lista`;
        this.input.setAttribute('role', 'combobox');
        this.input.setAttribute('aria-autocomplete', 'list');
        this.input.setAttribute('aria-expanded', 'false');
        this.input.setAttribute('aria-controls', this.lista.id);
        this.input.setAttribute('autocomplete', 'off');
        this.lista.setAttribute('role', 'listbox');

        this.input.addEventListener('input', () => this.alEscribir());
        this.input.addEventListener('keydown', (e) => this.teclado(e));
        this.input.addEventListener('focus', () => this.alEnfocar());
        this.limpiar?.addEventListener('click', () => this.reiniciar(true));
        // Se suelta al pasar a otra página (Turbo).
        document.addEventListener('pointerdown', (e) => { if (!raiz.contains(e.target)) this.cerrar(); }, { signal: window.bmhSenalPagina?.() });

        // Sin elegir nada, el formulario no se manda: se avisa en el campo.
        raiz.closest('form')?.addEventListener('submit', (e) => {
            if (this.oculto.dataset.requerido !== undefined && !this.oculto.value) {
                e.preventDefault();
                this.raiz.classList.add('is-invalido');
                this.anunciar(this.raiz.dataset.mensajeRequerido || 'Elegí una opción de la lista.');
                this.input.focus();
            }
        });

        // Valor inicial (p. ej. «Todas las categorías» o lo que vuelve de un error de validación).
        const inicial = this.oculto.value && this.opcionesLocales.find((o) => String(o.valor) === String(this.oculto.value));
        if (inicial) this.mostrarFicha(inicial);
    }

    alEnfocar() {
        // En las locales se abre la lista entera al entrar: con pocas opciones es más rápido elegir que escribir.
        if (!this.url && !this.items.length) this.consultar();
        else if (this.items.length) this.abrir();
    }

    alEscribir() {
        this.raiz.classList.remove('is-invalido');
        clearTimeout(this.espera);
        this.espera = setTimeout(() => this.consultar(), this.url ? DEBOUNCE_SERVIDOR : DEBOUNCE_LOCAL);
    }

    async consultar() {
        const q = this.input.value.trim();
        this.consulta = q;
        this.controlador?.abort();

        if (q.length < this.minimo) {
            this.items = [];
            this.cerrar();
            this.anunciar(q.length ? `Escribí al menos ${this.minimo} letras del código o del nombre.` : '');
            return;
        }

        if (!this.url) {
            const nq = normalizar(q);
            const encontrados = this.opcionesLocales.filter((o) => !nq || normalizar(`${o.titulo} ${o.codigo ?? ''}`).includes(nq));
            this.dibujar(encontrados, q);
            return;
        }

        this.controlador = new AbortController();
        this.raiz.classList.add('is-buscando');
        this.anunciar('Buscando…');
        try {
            const respuesta = await fetch(`${this.url}?${new URLSearchParams({ q })}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controlador.signal,
            });
            if (!respuesta.ok) throw new Error('No se pudo buscar. Probá de nuevo.');
            const { data = [] } = await respuesta.json();
            this.dibujar(data.map((p) => this.mapear(p)), q);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.items = [];
                this.cerrar();
                this.anunciar(error.message || 'No se pudo buscar. Probá de nuevo.');
            }
        } finally {
            this.raiz.classList.remove('is-buscando');
        }
    }

    /** Un producto de producto.buscar.partes → opción del selector. */
    mapear(p) {
        const ajuste = Number(p.aumento) > 0 ? `+${Number(p.aumento)}%` : Number(p.descuento) > 0 ? `−${Number(p.descuento)}%` : null;
        return {
            valor: p.id,
            titulo: p.nombre,
            codigo: p.codigo,
            detalle: [p.marca, p.modelo].filter(Boolean).join(' · ') || null,
            imagen: p.portada_url || null,
            meta: p.precio ? `$ ${Number(p.precio).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : null,
            extra: ajuste ? `Ajuste actual: ${ajuste}` : 'Sin ajuste',
        };
    }

    dibujar(opciones, q) {
        this.items = opciones;
        this.activo = -1;
        this.input.removeAttribute('aria-activedescendant');
        this.lista.replaceChildren(...opciones.map((o, i) => el('li', {
            id: `${this.lista.id}-${i}`,
            role: 'option',
            class: 'adm-selector__opcion',
            'aria-selected': 'false',
            onpointerdown: (e) => { e.preventDefault(); this.elegir(i); },
            onpointermove: () => this.marcar(i, false),
        },
            o.imagen !== undefined ? el('span', { class: 'adm-selector__foto' }, o.imagen ? el('img', { src: o.imagen, alt: '', loading: 'lazy' }) : null) : null,
            el('span', { class: 'adm-selector__textos' },
                el('span', { class: 'adm-selector__titulo' }, o.codigo ? el('span', { class: 'adm-selector__codigo' }, resaltar(o.codigo, q)) : null, resaltar(o.titulo, q)),
                o.detalle ? el('small', { class: 'adm-selector__detalle', texto: o.detalle }) : null,
            ),
            o.meta ? el('span', { class: 'adm-selector__meta', texto: o.meta }) : null,
        )));

        if (opciones.length) {
            this.abrir();
            this.anunciar(this.url
                ? `${opciones.length === 1 ? '1 producto' : `${opciones.length} productos`}${opciones.length >= 8 ? ' (los primeros 8: seguí escribiendo para afinar)' : ''}. Flechas y Enter para elegir.`
                : '');
        } else {
            this.cerrar();
            this.anunciar(`No hay ${this.url ? 'productos' : 'categorías'} con «${q}». Probá con otra parte del código o del nombre.`);
        }
    }

    teclado(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!this.items.length) { this.consultar(); return; }
            this.abrir();
            const paso = e.key === 'ArrowDown' ? 1 : -1;
            this.marcar((this.activo + paso + this.items.length) % this.items.length);
        } else if (e.key === 'Enter') {
            // Enter elige; nunca manda el formulario desde el buscador.
            e.preventDefault();
            if (this.activo >= 0) this.elegir(this.activo);
            else if (this.items.length === 1) this.elegir(0);
        } else if (e.key === 'Escape') {
            if (!this.lista.hidden) { e.preventDefault(); this.cerrar(); }
        } else if (e.key === 'Tab') {
            this.cerrar();
        }
    }

    marcar(indice, desplazar = true) {
        this.activo = indice;
        [...this.lista.children].forEach((li, i) => li.setAttribute('aria-selected', String(i === indice)));
        const actual = this.lista.children[indice];
        if (actual) {
            this.input.setAttribute('aria-activedescendant', actual.id);
            if (desplazar) actual.scrollIntoView({ block: 'nearest' });
        }
    }

    elegir(indice) {
        const opcion = this.items[indice];
        if (!opcion) return;
        this.oculto.value = opcion.valor;
        this.oculto.dispatchEvent(new Event('change', { bubbles: true }));
        this.raiz.classList.remove('is-invalido');
        this.mostrarFicha(opcion);
        this.cerrar();
        this.anunciar(`Elegiste ${opcion.codigo ? `${opcion.codigo} · ` : ''}${opcion.titulo}.`);
    }

    mostrarFicha(opcion) {
        this.ficha.replaceChildren(...[
            opcion.imagen !== undefined ? el('span', { class: 'adm-selector__foto adm-selector__foto--grande' }, opcion.imagen ? el('img', { src: opcion.imagen, alt: '' }) : null) : null,
            el('span', { class: 'adm-selector__textos' },
                el('span', { class: 'adm-selector__titulo' }, opcion.codigo ? el('span', { class: 'adm-selector__codigo', texto: opcion.codigo }) : null, opcion.titulo),
                el('small', { class: 'adm-selector__detalle', texto: [opcion.detalle, opcion.meta, opcion.extra].filter(Boolean).join(' · ') }),
            ),
        ].filter(Boolean));
        this.raiz.classList.add('is-elegido');
        this.input.value = '';
        this.items = [];
    }

    reiniciar(enfocar) {
        this.oculto.value = '';
        this.oculto.dispatchEvent(new Event('change', { bubbles: true }));
        this.raiz.classList.remove('is-elegido');
        this.ficha.replaceChildren();
        this.items = [];
        this.anunciar('');
        if (enfocar) {
            this.input.focus();
            if (!this.url) this.consultar();
        }
    }

    abrir() {
        this.lista.hidden = false;
        this.input.setAttribute('aria-expanded', 'true');
    }

    cerrar() {
        this.lista.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.input.removeAttribute('aria-activedescendant');
    }

    anunciar(texto) {
        if (this.estado) this.estado.textContent = texto;
    }
}

export function iniciarSelectoresBuscables(raiz = document) {
    raiz.querySelectorAll('[data-selector-buscable]:not([data-selector-listo])').forEach((nodo) => {
        nodo.setAttribute('data-selector-listo', '');
        new SelectorBuscable(nodo);
    });
}
