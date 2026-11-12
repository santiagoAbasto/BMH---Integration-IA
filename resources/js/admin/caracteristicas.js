/*
 * Características en el admin:
 *  - [data-car-pantalla]  admin → Características: ejemplo en vivo del formato, qué pasa con los valores
 *                         cargados al cambiarlo, confirmación de borrado con su impacto y fusión.
 *  - [data-car-selector]  el modal de una categoría: elegir con un buscador, crear al vuelo, traer de otra
 *                         categoría o producto y agregar las que no usa nadie.
 *  - [data-car-producto]  crear/editar producto: filas nombre + valor, «Usar las de la categoría», copiar de
 *                         otro producto y validación en vivo con el mismo criterio que el servidor.
 * Los textos que ve el usuario están acá en lenguaje de negocio; los pedidos JSON, en CaracteristicaController.
 */

// ---------------------------------------------------------------- formato
// El mismo criterio que App\Support\Caracteristicas\FormatoCaracteristica.

function numeroCanonico(numero) {
    let n = numero.trim().replace(',', '.');
    if (n.includes('.')) n = n.replace(/0+$/, '').replace(/\.$/, '');
    n = n.replace(/^0+/, '');
    return n === '' || n.startsWith('.') ? `0${n}` : n;
}

function escaparRegex(texto) {
    return texto.replace(/[.*+?^${}()|[\]\\/]/g, '\\$&');
}

/** Un valor vacío o «-» no se guarda. */
function esVacio(valor) {
    const v = String(valor ?? '').trim();
    return v === '' || v.replace(/[ \-–—]/g, '') === '';
}

/** @returns {{valor: string|null, error: string|null}} */
export function normalizarValor(valor, formato) {
    if (esVacio(valor)) return { valor: null, error: null };
    const texto = String(valor).trim().replace(/\s+/g, ' ');

    if (formato.numerica) {
        const unidad = formato.unidad ? `(?:\\s*${escaparRegex(formato.unidad)})?` : '';
        const num = '\\d+(?:[.,]\\d+)?';
        const m = new RegExp(`^(${num})\\s*(?:\\/\\s*(${num}))?${unidad}$`, 'iu').exec(texto);
        if (!m) {
            return {
                valor: null,
                error: formato.unidad
                    ? `Tiene que ser un número (o dos separados por «/», como 12/24). La unidad ${formato.unidad} se agrega sola.`
                    : 'Tiene que ser un número (o dos separados por «/», como 12/24).',
            };
        }
        return { valor: numeroCanonico(m[1]) + (m[2] ? `/${numeroCanonico(m[2])}` : '') + (formato.unidad || ''), error: null };
    }

    return { valor: formato.mayusculas ? texto.toLocaleUpperCase('es-AR') : texto, error: null };
}

export function describirFormato(formato) {
    if (formato.numerica) return formato.unidad ? `numérica · se guarda con ${formato.unidad}` : 'numérica';
    return formato.mayusculas ? 'texto en mayúsculas' : 'texto libre';
}

// ------------------------------------------------------------------ utilidades

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/** Pedido JSON al admin. Si falla, el Error trae el mensaje para mostrar y los datos de la respuesta. */
async function pedir(url, { metodo = 'GET', datos = null, senal } = {}) {
    const respuesta = await fetch(url, {
        method: metodo,
        signal: senal,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf(),
            ...(datos ? { 'Content-Type': 'application/json' } : {}),
        },
        body: datos ? JSON.stringify(datos) : undefined,
    });
    const cuerpo = await respuesta.json().catch(() => ({}));
    if (!respuesta.ok) {
        const primero = cuerpo.errors ? Object.values(cuerpo.errors)[0]?.[0] : null;
        const error = new Error(primero || cuerpo.message || 'No se pudo completar. Probá de nuevo en un momento.');
        error.datos = cuerpo;
        throw error;
    }
    return cuerpo;
}

function crearEl(etiqueta, atributos = {}, ...hijos) {
    const el = document.createElement(etiqueta);
    for (const [clave, valor] of Object.entries(atributos)) {
        if (valor === null || valor === undefined || valor === false) continue;
        if (clave === 'class') el.className = valor;
        else if (clave === 'texto') el.textContent = valor;
        else if (clave.startsWith('on')) el.addEventListener(clave.slice(2), valor);
        else el.setAttribute(clave, valor === true ? '' : valor);
    }
    el.append(...hijos.filter((h) => h !== null && h !== undefined));
    return el;
}

function plural(n, singular, pluralTexto) {
    return `${n.toLocaleString('es-AR')} ${n === 1 ? singular : pluralTexto}`;
}

let secuencia = 0;
const nuevoId = (prefijo) => `${prefijo}-${++secuencia}`;

/** Aviso flotante (iziToast del layout) o, si no está, nada: el estado ya se anuncia en la pantalla. */
function avisar(mensaje, tipo = 'success') {
    if (window.iziToast) window.iziToast[tipo === 'error' ? 'error' : 'success']({ message: mensaje, position: 'topRight' });
}

// -------------------------------------------------------------- buscador

/**
 * Combobox accesible: escribir, flechas, Enter para elegir, Escape para cerrar. Los resultados se anuncian
 * en una región aria-live. `buscar(q)` devuelve los ítems; `presentar(item)` → {texto, detalle, deshabilitado}.
 */
class Buscador {
    constructor({ input, estado, buscar, presentar, alElegir, crear = null, minimo = 1 }) {
        Object.assign(this, { input, estado, buscar, presentar, alElegir, crear, minimo });
        this.lista = crearEl('ul', { class: 'car-resultados', role: 'listbox', id: nuevoId('car-lista'), hidden: true });
        input.closest('.car-buscador__caja').after(this.lista);
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', this.lista.id);
        input.setAttribute('autocomplete', 'off');
        this.items = [];
        this.activo = -1;

        input.addEventListener('input', () => {
            clearTimeout(this.espera);
            this.espera = setTimeout(() => this.consultar(), 200);
        });
        input.addEventListener('keydown', (e) => this.teclado(e));
        input.addEventListener('focus', () => { if (this.items.length) this.abrir(); });
        input.addEventListener('blur', () => setTimeout(() => this.cerrar(), 150));
    }

    async consultar() {
        const q = this.input.value.trim();
        this.controlador?.abort();
        if (q.length < this.minimo) {
            this.items = [];
            this.cerrar();
            this.anunciar(q.length ? `Escribí al menos ${this.minimo} letras.` : '');
            return;
        }
        this.controlador = new AbortController();
        this.anunciar('Buscando…');
        try {
            const items = await this.buscar(q, this.controlador.signal);
            this.dibujar(items, q);
        } catch (error) {
            if (error.name !== 'AbortError') this.anunciar(error.message);
        }
    }

    dibujar(items, q) {
        this.items = items.map((item) => ({ item, ...this.presentar(item) }));
        if (this.crear && !items.some((i) => (i.nombre || '').toLocaleUpperCase('es-AR') === q.toLocaleUpperCase('es-AR'))) {
            this.items.push({ crear: q, texto: `Crear «${q.toLocaleUpperCase('es-AR')}»`, detalle: 'característica nueva' });
        }
        this.lista.replaceChildren(...this.items.map((opcion, i) => crearEl('li', {
            id: `${this.lista.id}-${i}`,
            role: 'option',
            class: `car-resultado${opcion.crear ? ' car-resultado--crear' : ''}`,
            'aria-selected': 'false',
            'aria-disabled': opcion.deshabilitado ? 'true' : null,
            onmousedown: (e) => { e.preventDefault(); this.elegir(i); },
        }, crearEl('span', { texto: opcion.texto }), opcion.detalle ? crearEl('small', { texto: opcion.detalle }) : null)));
        this.activo = -1;
        const encontrados = items.length;
        this.anunciar(encontrados ? `${plural(encontrados, 'resultado', 'resultados')}. Usá las flechas y Enter para elegir.` : `No hay ninguna con «${q}».${this.crear ? ' Podés crearla.' : ''}`);
        this.items.length ? this.abrir() : this.cerrar();
    }

    teclado(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!this.items.length) return;
            this.abrir();
            const paso = e.key === 'ArrowDown' ? 1 : -1;
            this.marcar((this.activo + paso + this.items.length) % this.items.length);
        } else if (e.key === 'Enter') {
            // Enter nunca envía el formulario desde el buscador.
            e.preventDefault();
            if (this.activo >= 0) this.elegir(this.activo);
            else if (this.items.length === 1) this.elegir(0);
        } else if (e.key === 'Escape' && !this.lista.hidden) {
            e.preventDefault();
            e.stopPropagation();
            this.cerrar();
        }
    }

    marcar(indice) {
        this.activo = indice;
        [...this.lista.children].forEach((li, i) => li.setAttribute('aria-selected', String(i === indice)));
        const actual = this.lista.children[indice];
        this.input.setAttribute('aria-activedescendant', actual?.id ?? '');
        actual?.scrollIntoView({ block: 'nearest' });
    }

    elegir(indice) {
        const opcion = this.items[indice];
        if (!opcion || opcion.deshabilitado) return;
        if (opcion.crear) this.crear(opcion.crear);
        else this.alElegir(opcion.item);
        this.input.value = '';
        this.items = [];
        this.cerrar();
        this.input.focus();
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

// ------------------------------------------------- piezas compartidas

/** «Crear característica»: nombre + texto/número (+ unidad). Llama a alCrear con la creada (o la existente). */
function iniciarAlta(panel, { url, alCrear }) {
    if (!panel) return { abrirCon() {} };
    const nombre = panel.querySelector('[data-car-alta-nombre]');
    const unidad = panel.querySelector('[data-car-alta-unidad]');
    const estado = panel.querySelector('[data-car-alta-estado]');
    const tipos = panel.querySelectorAll('[data-car-alta-tipo]');
    const boton = panel.querySelector('[data-car-alta-crear]');

    const actualizarTipo = () => {
        const numero = [...tipos].find((t) => t.checked)?.value === 'numero';
        unidad.disabled = !numero;
        unidad.closest('label').hidden = !numero;
    };
    tipos.forEach((t) => t.addEventListener('change', actualizarTipo));
    actualizarTipo();

    const crear = async () => {
        const texto = nombre.value.trim();
        if (!texto) {
            estado.textContent = 'Escribí el nombre de la característica.';
            nombre.focus();
            return;
        }
        boton.disabled = true;
        estado.textContent = 'Creando…';
        try {
            const tipo = [...tipos].find((t) => t.checked)?.value || 'texto';
            const { caracteristica } = await pedir(url, { metodo: 'POST', datos: { nombre: texto, tipo, unidad: tipo === 'numero' ? unidad.value.trim() : '' } });
            estado.textContent = `Se creó «${caracteristica.nombre}» y quedó agregada.`;
            nombre.value = '';
            unidad.value = '';
            alCrear(caracteristica);
        } catch (error) {
            if (error.datos?.existente) {
                estado.textContent = `Ya existe «${error.datos.existente.nombre}»: la agregué en lugar de crear otra igual.`;
                alCrear(error.datos.existente);
                nombre.value = '';
            } else {
                estado.textContent = error.message;
            }
        } finally {
            boton.disabled = false;
        }
    };
    boton.addEventListener('click', crear);
    nombre.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); crear(); } });
    unidad.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); crear(); } });

    return {
        abrirCon(texto) {
            panel.open = true;
            nombre.value = texto;
            nombre.focus();
            estado.textContent = 'Elegí si es texto o número y tocá «Crear y agregar».';
        },
    };
}

/**
 * «Traer de otra categoría o producto»: buscar el origen, ver sus características y elegir cuáles traer.
 * alTraer(lista, {conValores}) recibe las elegidas.
 */
function iniciarOrigen(panel, { url, excluir = 0, yaEsta, alTraer, conValores = false }) {
    if (!panel) return;
    const input = panel.querySelector('[data-car-origen-buscar]');
    const estado = panel.querySelector('[data-car-origen-estado]');
    const destino = panel.querySelector('[data-car-origen-detalle]');
    const tipos = panel.querySelectorAll('[data-car-origen-tipo]');
    const tipoActual = () => [...tipos].find((t) => t.checked)?.value || 'categoria';

    const actualizarAyuda = () => {
        input.placeholder = tipoActual() === 'producto' ? 'Código o nombre del producto…' : 'Nombre de la categoría…';
        input.value = '';
        destino.replaceChildren();
    };
    tipos.forEach((t) => t.addEventListener('change', actualizarAyuda));
    actualizarAyuda();

    const mostrar = (titulo, caracteristicas, esProducto) => {
        if (!caracteristicas.length) {
            destino.replaceChildren(crearEl('p', { class: 'car-panel__ayuda', texto: `«${titulo}» no tiene características.` }));
            return;
        }
        const nuevas = caracteristicas.filter((c) => !yaEsta(c.id));
        const casillas = caracteristicas.map((c) => {
            const repetida = yaEsta(c.id);
            const id = nuevoId('car-traer');
            return crearEl('li', {}, crearEl('label', { for: id },
                crearEl('input', { type: 'checkbox', id, class: 'form-check-input', value: c.id, checked: !repetida, disabled: repetida }),
                crearEl('span', { texto: c.nombre }),
                crearEl('small', { texto: repetida ? 'ya está' : (esProducto && c.valor ? c.valor : c.formato) })));
        });
        const lista = crearEl('ul', { class: 'car-lista-elegir', 'aria-label': `Características de ${titulo}` }, ...casillas);
        const valores = esProducto && conValores ? crearEl('label', { class: 'adm-switch' },
            crearEl('input', { type: 'checkbox', role: 'switch', checked: true, 'data-car-con-valores': true }), 'Copiar también los valores') : null;
        const traer = (todas) => {
            const ids = todas
                ? nuevas.map((c) => c.id)
                : [...lista.querySelectorAll('input:checked:not(:disabled)')].map((i) => Number(i.value));
            const elegidas = caracteristicas.filter((c) => ids.includes(c.id));
            if (!elegidas.length) {
                estado.textContent = 'No elegiste ninguna nueva.';
                return;
            }
            alTraer(elegidas, { conValores: !!valores?.querySelector('input').checked });
            estado.textContent = `Se agregaron ${plural(elegidas.length, 'característica', 'características')} de «${titulo}».`;
            destino.replaceChildren();
        };
        destino.replaceChildren(
            crearEl('p', { class: 'car-panel__ayuda', texto: nuevas.length ? `«${titulo}»: ${plural(caracteristicas.length, 'característica', 'características')}, ${plural(nuevas.length, 'nueva', 'nuevas')} para agregar.` : `Ya tenés todas las de «${titulo}».` }),
            lista,
            valores,
            crearEl('div', { class: 'car-acciones' },
                crearEl('button', { type: 'button', class: 'btn btn-primary btn-sm', disabled: !nuevas.length, onclick: () => traer(false), texto: 'Agregar las marcadas' }),
                crearEl('button', { type: 'button', class: 'btn btn-outline-secondary btn-sm', disabled: !nuevas.length, onclick: () => traer(true), texto: `Agregar todas (${nuevas.length})` })),
        );
        destino.querySelector('input:not(:disabled)')?.focus();
    };

    new Buscador({
        input,
        estado,
        minimo: 1,
        buscar: async (q, senal) => {
            const params = new URLSearchParams({ q, tipo: tipoActual(), excluir: String(excluir) });
            return (await pedir(`${url}?${params}`, { senal })).data;
        },
        presentar: (o) => ({ texto: o.titulo, detalle: o.detalle, deshabilitado: o.cantidad === 0 }),
        alElegir: async (o) => {
            estado.textContent = 'Cargando…';
            try {
                const datos = await pedir(o.url);
                estado.textContent = '';
                mostrar(o.titulo, datos.caracteristicas, tipoActual() === 'producto');
            } catch (error) {
                estado.textContent = error.message;
            }
        },
    });
}

/** «Características sin usar»: se cargan al abrir el panel; un clic las agrega. */
function iniciarSinUso(panel, { url, yaEsta, alAgregar }) {
    if (!panel) return { refrescar() {} };
    const lista = panel.querySelector('[data-car-sin-uso-lista]');
    const estado = panel.querySelector('[data-car-sin-uso-estado]');
    let cargado = false;

    const dibujar = async () => {
        estado.textContent = 'Cargando…';
        try {
            const { data } = await pedir(url);
            cargado = true;
            lista.replaceChildren(...data.map((c) => crearEl('li', {}, crearEl('button', {
                type: 'button',
                disabled: yaEsta(c.id),
                'aria-label': `Agregar ${c.nombre}`,
                onclick: (e) => { alAgregar(c); e.currentTarget.disabled = true; },
            }, crearEl('span', { 'aria-hidden': 'true', texto: '+' }), c.nombre))));
            estado.textContent = data.length
                ? `${plural(data.length, 'característica no la usa', 'características no las usa')} ninguna categoría ni producto.`
                : 'Todas las características están en uso.';
        } catch (error) {
            estado.textContent = error.message;
        }
    };
    panel.addEventListener('toggle', () => { if (panel.open && !cargado) dibujar(); });

    return {
        refrescar() {
            if (cargado) lista.querySelectorAll('button').forEach((b) => { b.disabled = b.disabled || false; });
        },
        marcarAgregada(id) {
            lista.querySelectorAll('button').forEach((b) => { if (b.getAttribute('aria-label') && yaEsta(id)) b.disabled = b.disabled || false; });
        },
    };
}

// ------------------------------------------------ selector (categorías)

function iniciarSelector(raiz) {
    const urls = raiz.dataset;
    const chips = raiz.querySelector('[data-car-chips]');
    const vacio = raiz.querySelector('[data-car-chips-vacio]');
    const contador = raiz.querySelector('[data-car-contador]');
    const anuncio = raiz.querySelector('[data-car-anuncio]');

    const elegidas = () => [...chips.querySelectorAll('input[name="caracteristicas[]"]')].map((i) => Number(i.value));
    const yaEsta = (id) => elegidas().includes(Number(id));

    const actualizar = () => {
        const n = elegidas().length;
        vacio.hidden = n > 0;
        contador.textContent = n ? plural(n, 'elegida', 'elegidas') : 'ninguna elegida';
    };

    const quitar = (chip) => {
        const siguiente = chip.nextElementSibling?.querySelector('button') || chip.previousElementSibling?.querySelector('button');
        const nombre = chip.dataset.nombre;
        chip.remove();
        actualizar();
        anuncio.textContent = `Se quitó «${nombre}».`;
        (siguiente || raiz.querySelector('[data-car-buscar]')).focus();
    };

    const agregar = (c, { silencioso = false } = {}) => {
        if (yaEsta(c.id)) {
            if (!silencioso) anuncio.textContent = `«${c.nombre}» ya estaba agregada.`;
            return false;
        }
        const chip = crearEl('li', { class: 'car-chip is-nueva', 'data-nombre': c.nombre },
            crearEl('span', { texto: c.nombre }),
            crearEl('input', { type: 'hidden', name: 'caracteristicas[]', value: c.id }),
            crearEl('button', { type: 'button', 'aria-label': `Quitar ${c.nombre}`, title: 'Quitar', texto: '×' }));
        chips.append(chip);
        actualizar();
        if (!silencioso) anuncio.textContent = `Se agregó «${c.nombre}».`;
        return true;
    };

    chips.addEventListener('click', (e) => {
        const boton = e.target.closest('button');
        if (boton) quitar(boton.closest('.car-chip'));
    });
    chips.addEventListener('keydown', (e) => {
        if ((e.key === 'Delete' || e.key === 'Backspace') && e.target.matches('.car-chip button')) {
            e.preventDefault();
            quitar(e.target.closest('.car-chip'));
        }
    });

    const alta = iniciarAlta(raiz.querySelector('[data-car-alta]'), { url: urls.urlCrear, alCrear: (c) => agregar(c) });

    new Buscador({
        input: raiz.querySelector('[data-car-buscar]'),
        estado: raiz.querySelector('[data-car-buscar-estado]'),
        buscar: async (q, senal) => (await pedir(`${urls.urlBuscar}?${new URLSearchParams({ q })}`, { senal })).data,
        presentar: (c) => ({ texto: c.nombre, detalle: yaEsta(c.id) ? 'ya agregada' : c.uso, deshabilitado: yaEsta(c.id) }),
        alElegir: (c) => agregar(c),
        crear: (q) => alta.abrirCon(q),
    });

    iniciarOrigen(raiz.querySelector('[data-car-origen]'), {
        url: urls.urlOrigenes,
        excluir: Number(urls.categoriaId || 0),
        yaEsta,
        alTraer: (lista) => {
            const agregadas = lista.filter((c) => agregar(c, { silencioso: true })).length;
            anuncio.textContent = `Se agregaron ${plural(agregadas, 'característica', 'características')}.`;
        },
    });

    iniciarSinUso(raiz.querySelector('[data-car-sin-uso]'), { url: urls.urlSinUso, yaEsta, alAgregar: (c) => agregar(c) });
    actualizar();
}

// ------------------------------------------------------------ productos

function iniciarProducto(raiz) {
    const urls = raiz.dataset;
    const filas = raiz.querySelector('[data-car-filas]');
    const vacio = raiz.querySelector('[data-car-filas-vacio]');
    const anuncio = raiz.querySelector('[data-car-anuncio]');
    const avisoCategoria = raiz.querySelector('[data-car-aviso-categoria]');
    const usarCategoria = raiz.querySelector('[data-car-usar-categoria]');
    const resumenCategoria = raiz.querySelector('[data-car-resumen-categoria]');
    const selectCategoria = document.getElementById(raiz.dataset.selectCategoria);
    let categoria = { id: null, nombre: '', caracteristicas: [] };

    const filasActuales = () => [...filas.querySelectorAll('.car-fila')];
    const yaEsta = (id) => filasActuales().some((f) => Number(f.dataset.id) === Number(id));
    const enCategoria = (id) => categoria.caracteristicas.some((c) => Number(c.id) === Number(id));
    const formatoDe = (fila) => ({ mayusculas: fila.dataset.mayusculas === '1', numerica: fila.dataset.numerica === '1', unidad: fila.dataset.unidad || null });

    /** Ejemplo y validación en vivo de una fila. */
    const revisar = (fila) => {
        const input = fila.querySelector('input[type="text"]');
        const previa = fila.querySelector('[data-car-previa]');
        const error = fila.querySelector('[data-car-error]');
        if (error.dataset.servidor === '1' && input.value === input.defaultValue) return; // el error del servidor queda hasta que se cambie el valor
        error.dataset.servidor = '0';
        const { valor, error: mensaje } = normalizarValor(input.value, formatoDe(fila));
        input.classList.toggle('is-invalid', !!mensaje);
        input.setAttribute('aria-invalid', mensaje ? 'true' : 'false');
        error.textContent = mensaje || '';
        error.hidden = !mensaje;
        if (mensaje || valor === null) {
            previa.textContent = esVacio(input.value) ? 'Vacía: no se guarda.' : '';
        } else if (valor !== input.value.trim()) {
            previa.replaceChildren('Se guarda: ', crearEl('strong', { texto: valor }));
        } else {
            previa.textContent = '';
        }
    };

    /** Aviso por fila: la categoría no la usa, no se ve en el sitio. */
    const marcarCategoria = (fila) => {
        const aviso = fila.querySelector('[data-car-fuera]');
        const id = Number(fila.dataset.id);
        const sumada = fila.querySelector('input[name="sumar_a_categoria[]"]');
        const fuera = categoria.id !== null && !enCategoria(id);
        fila.classList.toggle('is-fuera', fuera && !sumada);
        aviso.replaceChildren();
        aviso.hidden = !fuera;
        if (!fuera) {
            sumada?.remove();
            return;
        }
        if (sumada) {
            aviso.append(crearEl('span', { class: 'adm-chip adm-chip--ok', texto: `Se suma a «${categoria.nombre}» al guardar` }),
                crearEl('button', { type: 'button', class: 'btn btn-link btn-sm p-0', texto: 'Deshacer', onclick: () => { sumada.remove(); marcarCategoria(fila); } }));
            return;
        }
        aviso.append(crearEl('span', { class: 'adm-chip adm-chip--alerta', texto: `No se ve en el sitio: «${categoria.nombre}» no la usa` }),
            crearEl('button', {
                type: 'button',
                class: 'btn btn-link btn-sm p-0',
                texto: `Sumarla a «${categoria.nombre}»`,
                onclick: () => {
                    fila.append(crearEl('input', { type: 'hidden', name: 'sumar_a_categoria[]', value: id }));
                    marcarCategoria(fila);
                },
            }));
    };

    const actualizar = () => {
        const n = filasActuales().length;
        vacio.hidden = n > 0;
        const faltan = categoria.caracteristicas.filter((c) => !yaEsta(c.id));
        if (usarCategoria) {
            usarCategoria.hidden = categoria.id === null || faltan.length === 0;
            usarCategoria.textContent = `Usar las de «${categoria.nombre}» (${faltan.length})`;
        }
        if (resumenCategoria) {
            resumenCategoria.textContent = categoria.id === null
                ? 'Elegí la categoría arriba para ver sus características.'
                : categoria.caracteristicas.length === 0
                    ? `«${categoria.nombre}» todavía no tiene características elegidas (admin → Categorías).`
                    : faltan.length
                        ? `«${categoria.nombre}» usa ${plural(categoria.caracteristicas.length, 'característica', 'características')}: ${plural(faltan.length, 'falta agregar', 'faltan agregar')}.`
                        : `Están todas las de «${categoria.nombre}».`;
        }
    };

    const agregarFila = (c, valor = '', { enfocar = false, silencioso = false } = {}) => {
        if (yaEsta(c.id)) {
            const fila = filas.querySelector(`.car-fila[data-id="${c.id}"]`);
            const input = fila.querySelector('input[type="text"]');
            if (valor && !input.value.trim()) { input.value = valor; revisar(fila); }
            if (!silencioso) anuncio.textContent = `«${c.nombre}» ya estaba en la lista.`;
            if (enfocar) input.focus();
            return false;
        }
        const id = `car-valor-${c.id}`;
        const ayuda = `${id}-formato`;
        const fila = crearEl('li', {
            class: 'car-fila is-nueva', 'data-id': c.id, 'data-nombre': c.nombre,
            'data-mayusculas': c.mayusculas ? '1' : '0', 'data-numerica': c.numerica ? '1' : '0', 'data-unidad': c.unidad || '',
        },
        crearEl('div', { class: 'car-fila__nombre' }, crearEl('label', { for: id, texto: c.nombre }), crearEl('small', { id: ayuda, texto: describirFormato(c) })),
        crearEl('div', { class: 'car-fila__valor' },
            crearEl('input', { type: 'text', class: 'form-control', id, name: `caracteristicas[${c.id}]`, value: valor, maxlength: '255', 'aria-describedby': `${ayuda} ${id}-error` , inputmode: c.numerica ? 'decimal' : null, placeholder: c.numerica ? `Ej.: 12${c.unidad || ''}` : null }),
            crearEl('p', { class: 'car-fila__previa', 'data-car-previa': true, 'aria-live': 'polite' }),
            crearEl('div', { class: 'invalid-feedback d-block', id: `${id}-error`, 'data-car-error': true, hidden: true })),
        crearEl('button', { type: 'button', class: 'btn btn-outline-danger btn-sm car-fila__quitar', 'aria-label': `Quitar ${c.nombre}`, title: 'Quitar', 'data-car-quitar': true },
            crearEl('i', { class: 'fa-solid fa-xmark', 'aria-hidden': 'true' })),
        crearEl('div', { class: 'car-fila__aviso', 'data-car-fuera': true, hidden: true }));
        filas.append(fila);
        revisar(fila);
        marcarCategoria(fila);
        actualizar();
        if (!silencioso) anuncio.textContent = `Se agregó «${c.nombre}».`;
        if (enfocar) fila.querySelector('input').focus();
        return true;
    };

    filas.addEventListener('input', (e) => { if (e.target.matches('input[type="text"]')) revisar(e.target.closest('.car-fila')); });
    filas.addEventListener('click', (e) => {
        const boton = e.target.closest('[data-car-quitar]');
        if (!boton) return;
        const fila = boton.closest('.car-fila');
        const destino = fila.nextElementSibling?.querySelector('input[type="text"]') || fila.previousElementSibling?.querySelector('input[type="text"]') || raiz.querySelector('[data-car-buscar]');
        anuncio.textContent = `Se quitó «${fila.dataset.nombre}».`;
        fila.remove();
        actualizar();
        destino?.focus();
    });

    // Filas que ya vienen del servidor (con su valor y, si hubo, el error de validación).
    filasActuales().forEach((fila) => {
        const error = fila.querySelector('[data-car-error]');
        if (error.textContent.trim()) {
            error.dataset.servidor = '1';
            error.hidden = false;
        } else {
            revisar(fila);
        }
    });

    const alta = iniciarAlta(raiz.querySelector('[data-car-alta]'), {
        url: urls.urlCrear,
        alCrear: (c) => {
            agregarFila(c, '', { enfocar: true });
            // Una creada acá se ofrece sumarla a la categoría: si no, no se vería en el sitio.
            const fila = filas.querySelector(`.car-fila[data-id="${c.id}"]`);
            if (fila && categoria.id !== null && !enCategoria(c.id)) {
                fila.append(crearEl('input', { type: 'hidden', name: 'sumar_a_categoria[]', value: c.id }));
                marcarCategoria(fila);
            }
        },
    });

    new Buscador({
        input: raiz.querySelector('[data-car-buscar]'),
        estado: raiz.querySelector('[data-car-buscar-estado]'),
        buscar: async (q, senal) => (await pedir(`${urls.urlBuscar}?${new URLSearchParams({ q })}`, { senal })).data,
        presentar: (c) => ({
            texto: c.nombre,
            detalle: yaEsta(c.id) ? 'ya está en la lista' : (enCategoria(c.id) ? `de «${categoria.nombre}»` : describirFormato(c)),
            deshabilitado: yaEsta(c.id),
        }),
        alElegir: (c) => agregarFila(c, '', { enfocar: true }),
        crear: (q) => alta.abrirCon(q),
    });

    iniciarOrigen(raiz.querySelector('[data-car-origen]'), {
        url: urls.urlOrigenes,
        excluir: Number(urls.productoId || 0),
        yaEsta,
        conValores: true,
        alTraer: (lista, { conValores }) => {
            const agregadas = lista.filter((c) => agregarFila(c, conValores ? (c.valor || '') : '', { silencioso: true })).length;
            anuncio.textContent = `Se agregaron ${plural(agregadas, 'característica', 'características')}${conValores ? ' con sus valores' : ''}.`;
        },
    });

    usarCategoria?.addEventListener('click', () => {
        const faltan = categoria.caracteristicas.filter((c) => !yaEsta(c.id));
        faltan.forEach((c) => agregarFila(c, '', { silencioso: true }));
        anuncio.textContent = `Se agregaron ${plural(faltan.length, 'característica', 'características')} de «${categoria.nombre}». Completá las que correspondan: las vacías no se guardan.`;
        filas.querySelector('.car-fila input[type="text"]:placeholder-shown, .car-fila input[type="text"]')?.focus();
    });

    /** Al cambiar la categoría: nunca se borra lo cargado; se avisa qué queda fuera y se deja decidir. */
    const avisarCambio = () => {
        avisoCategoria.replaceChildren();
        const fuera = filasActuales().filter((f) => !enCategoria(f.dataset.id) && f.querySelector('input[type="text"]').value.trim() !== '');
        avisoCategoria.hidden = fuera.length === 0;
        if (!fuera.length) return;
        const nombres = fuera.map((f) => f.dataset.nombre);
        avisoCategoria.append(
            crearEl('p', {}, crearEl('strong', { texto: `«${categoria.nombre}» no usa ${plural(fuera.length, 'característica', 'características')} que ya cargaste: ` }), nombres.join(', ') + '.'),
            crearEl('p', { texto: 'No se borra nada: si las dejás quedan guardadas pero no se ven en el sitio. También podés sumarlas a la categoría o quitarlas.' }),
            crearEl('div', { class: 'car-barra' },
                crearEl('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary', texto: 'Dejarlas', onclick: () => { avisoCategoria.hidden = true; } }),
                crearEl('button', { type: 'button', class: 'btn btn-sm btn-outline-primary', texto: `Sumarlas a «${categoria.nombre}»`, onclick: () => {
                    fuera.forEach((f) => { if (!f.querySelector('input[name="sumar_a_categoria[]"]')) f.append(crearEl('input', { type: 'hidden', name: 'sumar_a_categoria[]', value: f.dataset.id })); marcarCategoria(f); });
                    avisoCategoria.hidden = true;
                    anuncio.textContent = `Al guardar, se suman a «${categoria.nombre}».`;
                } }),
                crearEl('button', { type: 'button', class: 'btn btn-sm btn-outline-danger', texto: 'Quitarlas del producto', onclick: () => {
                    fuera.forEach((f) => f.remove());
                    avisoCategoria.hidden = true;
                    actualizar();
                    anuncio.textContent = `Se quitaron ${plural(fuera.length, 'característica', 'características')}.`;
                } })),
        );
    };

    let pedido;
    const cargarCategoria = async (avisar) => {
        const id = selectCategoria?.value;
        if (!id) {
            categoria = { id: null, nombre: '', caracteristicas: [] };
            filasActuales().forEach(marcarCategoria);
            actualizar();
            return;
        }
        pedido?.abort();
        pedido = new AbortController();
        try {
            const datos = await pedir(urls.urlCategoria.replace(':id', encodeURIComponent(id)), { senal: pedido.signal });
            categoria = { id: Number(datos.id), nombre: datos.nombre, caracteristicas: datos.caracteristicas };
            filasActuales().forEach(marcarCategoria);
            actualizar();
            if (avisar) avisarCambio();
        } catch (error) {
            if (error.name !== 'AbortError') resumenCategoria.textContent = error.message;
        }
    };
    selectCategoria?.addEventListener('change', () => cargarCategoria(true));
    cargarCategoria(false);
}

// ------------------------------------------------- pantalla del ABM

function iniciarPantalla(raiz) {
    const modal = document.getElementById('car-modal');
    const form = document.getElementById('car-form');
    const titulo = document.getElementById('car-modal-titulo');
    const campo = (nombre) => form.querySelector(`[name="${nombre}"]`);
    const ejemploInput = document.getElementById('car-ejemplo');
    const ejemploSalida = document.getElementById('car-ejemplo-salida');
    const impacto = document.getElementById('car-impacto');
    const unidadCampo = document.getElementById('car-unidad-campo');
    const mayusculasCampo = document.getElementById('car-mayusculas-campo');
    const usoTexto = document.getElementById('car-uso');
    let actual = null;
    let pedido;

    const formatoElegido = () => ({
        numerica: campo('tipo').form.querySelector('[name="tipo"]:checked')?.value === 'numero',
        mayusculas: campo('mayusculas').checked,
        unidad: campo('unidad').value.trim() || null,
    });

    const mostrarEjemplo = () => {
        const formato = formatoElegido();
        unidadCampo.hidden = !formato.numerica;
        mayusculasCampo.hidden = formato.numerica;
        if (!ejemploInput.dataset.tocado) ejemploInput.value = formato.numerica ? '12,50' : 'rueda libre';
        const { valor, error } = normalizarValor(ejemploInput.value, formato);
        ejemploSalida.classList.toggle('is-error', !!error);
        ejemploSalida.replaceChildren(...(error
            ? [error]
            : valor === null ? ['Vacío: no se guarda.'] : ['Se guarda: ', crearEl('strong', { texto: valor })]));
    };

    const formatoCambio = () => actual && (
        formatoElegido().numerica !== actual.numerica
        || (formatoElegido().numerica ? (formatoElegido().unidad || '') !== (actual.unidad || '') : formatoElegido().mayusculas !== actual.mayusculas)
    );

    const revisarImpacto = () => {
        clearTimeout(revisarImpacto.espera);
        impacto.hidden = true;
        impacto.replaceChildren();
        if (!actual || !actual.productos || !formatoCambio()) return;
        revisarImpacto.espera = setTimeout(async () => {
            pedido?.abort();
            pedido = new AbortController();
            const f = formatoElegido();
            const params = new URLSearchParams({ tipo: f.numerica ? 'numero' : 'texto', mayusculas: f.mayusculas ? '1' : '0', unidad: f.unidad || '' });
            try {
                const datos = await pedir(actual.urlVistaFormato + '?' + params, { senal: pedido.signal });
                dibujarImpacto(datos);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    impacto.hidden = false;
                    impacto.textContent = error.message;
                }
            }
        }, 300);
    };

    const dibujarImpacto = (d) => {
        impacto.hidden = false;
        const hijos = [crearEl('p', {}, crearEl('strong', { texto: `Ya hay ${plural(d.total, 'valor cargado', 'valores cargados')} en productos.` }),
            ` Con el formato nuevo cambiarían ${plural(d.cambian, 'valor', 'valores')}${d.no_convertibles_total ? ` y ${plural(d.no_convertibles_total, 'no se puede convertir', 'no se pueden convertir')} (quedan como están)` : ''}.`)];
        if (d.ejemplos.length) {
            hijos.push(crearEl('ul', {}, ...d.ejemplos.map((e) => crearEl('li', {}, `${e.codigo}: `, crearEl('span', { class: 'car-cambio', texto: `${e.antes} → ${e.despues}` })))));
        }
        if (d.no_convertibles.length) {
            hijos.push(crearEl('p', { texto: 'No se pueden convertir: ' + d.no_convertibles.slice(0, 8).map((n) => `${n.codigo} («${n.valor}»)`).join(', ') + (d.no_convertibles_total > 8 ? '…' : '') }));
        }
        if (d.cambian > 0) {
            hijos.push(crearEl('div', { class: 'form-check' },
                crearEl('input', { class: 'form-check-input', type: 'checkbox', id: 'car-aplicar', name: 'aplicar_formato', value: '1' }),
                crearEl('label', { class: 'form-check-label', for: 'car-aplicar', texto: `Actualizar también esos ${plural(d.cambian, 'valor', 'valores')} al guardar` })));
        } else {
            hijos.push(crearEl('p', { texto: 'Los valores cargados ya quedan bien con este formato.' }));
        }
        impacto.replaceChildren(...hijos);
    };

    form.addEventListener('change', (e) => { if (e.target.matches('[name="tipo"], [name="mayusculas"]')) { mostrarEjemplo(); revisarImpacto(); } });
    campo('unidad').addEventListener('input', () => { mostrarEjemplo(); revisarImpacto(); });
    ejemploInput.addEventListener('input', () => { ejemploInput.dataset.tocado = '1'; mostrarEjemplo(); });

    const abrir = (datos) => {
        actual = datos;
        form.reset();
        delete ejemploInput.dataset.tocado;
        const editar = !!datos;
        titulo.textContent = editar ? `Editar «${datos.nombre}»` : 'Nueva característica';
        form.action = editar ? form.dataset.urlEditar : form.dataset.urlCrear;
        form.querySelector('[name="_method"]').disabled = !editar;
        campo('id').value = editar ? datos.id : '';
        campo('nombre').value = editar ? datos.nombre : '';
        campo('orden').value = editar ? (datos.orden || '') : form.dataset.siguienteOrden;
        form.querySelector(`[name="tipo"][value="${editar && datos.numerica ? 'numero' : 'texto'}"]`).checked = true;
        campo('mayusculas').checked = editar ? datos.mayusculas : true;
        campo('unidad').value = editar ? (datos.unidad || '') : '';
        usoTexto.hidden = !editar;
        usoTexto.textContent = editar ? (datos.uso ? `La usan ${datos.uso}.` : 'Todavía no la usa ninguna categoría ni producto.') : '';
        form.querySelector('[data-car-guardar]').textContent = editar ? 'Guardar cambios' : 'Crear característica';
        impacto.hidden = true;
        mostrarEjemplo();
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
    };
    modal.addEventListener('shown.bs.modal', () => campo('nombre').focus());

    raiz.querySelectorAll('[data-car-nueva]').forEach((b) => b.addEventListener('click', () => abrir(null)));
    raiz.addEventListener('click', (e) => {
        const editar = e.target.closest('[data-car-editar]');
        if (editar) abrir(JSON.parse(editar.dataset.carEditar));
        const borrar = e.target.closest('[data-car-borrar]');
        if (borrar) confirmarBorrado(JSON.parse(borrar.dataset.carBorrar));
        const fusionar = e.target.closest('[data-car-fusionar]');
        if (fusionar) abrirFusion(JSON.parse(fusionar.dataset.carFusionar));
    });

    // Volver a abrir el modal si el servidor devolvió un error de validación.
    if (form.dataset.reabrir) abrir(JSON.parse(form.dataset.reabrir));

    // --- borrar
    const modalBorrar = document.getElementById('car-borrar');
    const confirmarBorrado = (c) => {
        modalBorrar.querySelector('[data-car-borrar-nombre]').textContent = c.nombre;
        modalBorrar.querySelector('[data-car-borrar-impacto]').textContent = c.uso
            ? `Se quita de ${c.uso}. Los valores cargados en esos productos se borran. No se puede deshacer.`
            : 'No la usa ninguna categoría ni producto: se puede borrar sin perder nada.';
        modalBorrar.querySelector('input[name="id"]').value = c.id;
        window.bootstrap.Modal.getOrCreateInstance(modalBorrar).show();
    };
    modalBorrar.querySelector('form').addEventListener('submit', (e) => {
        const boton = e.currentTarget.querySelector('[type="submit"]');
        if (boton.disabled) { e.preventDefault(); return; }
        boton.disabled = true;
        boton.textContent = 'Eliminando…';
    });

    // --- fusionar
    const modalFusion = document.getElementById('car-fusion');
    const formFusion = modalFusion.querySelector('form');
    const detalleFusion = modalFusion.querySelector('[data-car-fusion-detalle]');
    const botonFusion = formFusion.querySelector('[type="submit"]');
    let origen = null;
    const abrirFusion = (c) => {
        origen = c;
        formFusion.action = c.urlFusionar;
        formFusion.querySelector('[name="con"]').value = '';
        modalFusion.querySelector('[data-car-fusion-origen]').textContent = c.nombre;
        detalleFusion.replaceChildren(crearEl('p', { class: 'car-panel__ayuda', texto: 'Elegí la característica que queda. Antes de confirmar vas a ver qué pasa con cada valor.' }));
        botonFusion.disabled = true;
        window.bootstrap.Modal.getOrCreateInstance(modalFusion).show();
    };
    modalFusion.addEventListener('shown.bs.modal', () => modalFusion.querySelector('[data-car-buscar]').focus());
    new Buscador({
        input: modalFusion.querySelector('[data-car-buscar]'),
        estado: modalFusion.querySelector('[data-car-buscar-estado]'),
        buscar: async (q, senal) => (await pedir(`${raiz.dataset.urlBuscar}?${new URLSearchParams({ q })}`, { senal })).data,
        presentar: (c) => ({ texto: c.nombre, detalle: c.id === origen?.id ? 'es la misma' : c.uso, deshabilitado: c.id === origen?.id }),
        alElegir: async (destino) => {
            detalleFusion.replaceChildren(crearEl('p', { class: 'car-panel__ayuda', texto: 'Calculando…' }));
            try {
                const d = await pedir(`${origen.urlVistaFusion}?${new URLSearchParams({ con: destino.id })}`);
                formFusion.querySelector('[name="con"]').value = destino.id;
                botonFusion.disabled = false;
                const partes = [
                    crearEl('p', {}, 'Queda ', crearEl('strong', { texto: `«${destino.nombre}»` }), ` y se elimina «${origen.nombre}».`),
                    crearEl('ul', {},
                        crearEl('li', { texto: `${plural(d.se_pasan, 'valor pasa', 'valores pasan')} a «${destino.nombre}».` }),
                        d.iguales ? crearEl('li', { texto: `${plural(d.iguales, 'producto ya tenía', 'productos ya tenían')} el mismo valor en las dos.` }) : null,
                        crearEl('li', { texto: d.categorias_nuevas ? `${plural(d.categorias_nuevas, 'categoría pasa', 'categorías pasan')} a usar «${destino.nombre}».` : 'Las categorías ya usaban las dos (o ninguna).' })),
                ];
                if (d.conflictos.length) {
                    partes.push(crearEl('p', {}, crearEl('strong', { texto: `${plural(d.conflictos.length, 'producto tiene', 'productos tienen')} valores distintos en las dos.` }), ' Elegí cuál conservar:'));
                    partes.push(crearEl('div', { class: 'car-conflictos' }, crearEl('table', { class: 'table table-sm' },
                        crearEl('thead', {}, crearEl('tr', {}, crearEl('th', { texto: 'Producto' }), crearEl('th', { texto: origen.nombre }), crearEl('th', { texto: destino.nombre }))),
                        crearEl('tbody', {}, ...d.conflictos.map((c) => crearEl('tr', {}, crearEl('td', { texto: c.codigo }), crearEl('td', { texto: c.origen }), crearEl('td', { texto: c.destino })))))));
                    partes.push(crearEl('div', { class: 'car-opciones mt-2' },
                        crearEl('label', { class: 'car-opcion' }, crearEl('input', { type: 'radio', name: 'en_conflicto', value: 'destino', checked: true }), crearEl('span', {}, crearEl('strong', { texto: `Conservar los de «${destino.nombre}»` }))),
                        crearEl('label', { class: 'car-opcion' }, crearEl('input', { type: 'radio', name: 'en_conflicto', value: 'origen' }), crearEl('span', {}, crearEl('strong', { texto: `Conservar los de «${origen.nombre}»` })))));
                }
                detalleFusion.replaceChildren(...partes);
            } catch (error) {
                detalleFusion.replaceChildren(crearEl('p', { class: 'text-danger', texto: error.message }));
            }
        },
    });
    formFusion.addEventListener('submit', () => { botonFusion.disabled = true; botonFusion.textContent = 'Fusionando…'; });

    // Ordenar al elegir.
    raiz.querySelector('[data-car-ordenar]')?.addEventListener('change', (e) => e.target.form.submit());
}

// ------------------------------------------------------------- arranque

export function iniciarCaracteristicas() {
    document.querySelectorAll('[data-car-pantalla]').forEach(iniciarPantalla);
    document.querySelectorAll('[data-car-selector]').forEach(iniciarSelector);
    document.querySelectorAll('[data-car-producto]').forEach(iniciarProducto);
}

export { avisar };
