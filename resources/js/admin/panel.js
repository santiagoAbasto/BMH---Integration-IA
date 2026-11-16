/**
 * Inicio del panel (como Conelec): el rango (7/30/90 días) actualiza indicadores, gráfico, tabla y rankings con los
 * datos que ya trae la página (App\Support\Panel\MetricasPanel). Gráfico de líneas en SVG con línea guía y tooltip
 * (mouse y teclado), vista de tabla y leyenda: el color nunca es el único modo de leer cada serie.
 */
const SVG = 'http://www.w3.org/2000/svg';
const CLAVE = 'bmh.panel.rango';
const fechaLarga = new Intl.DateTimeFormat('es-AR', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
const fechaCorta = new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: '2-digit', timeZone: 'UTC' });
const numero = new Intl.NumberFormat('es-AR');
const pesos = (n) => `$ ${new Intl.NumberFormat('es-AR', { maximumFractionDigits: 0 }).format(n)}`;
const pesosCorto = (n) => (Math.abs(n) >= 1e6 ? `$ ${new Intl.NumberFormat('es-AR', { maximumFractionDigits: 1 }).format(n / 1e6)} M` : pesos(n));

const svg = (tag, atributos = {}) => {
    const nodo = document.createElementNS(SVG, tag);
    Object.entries(atributos).forEach(([clave, valor]) => nodo.setAttribute(clave, valor));
    return nodo;
};
const el = (tag, clase, texto) => {
    const nodo = document.createElement(tag);
    if (clase) nodo.className = clase;
    if (texto !== undefined) nodo.textContent = texto;
    return nodo;
};
const suma = (valores) => valores.reduce((total, valor) => total + valor, 0);
const techo = (valor) => {
    if (valor <= 4) return 4;
    const paso = 10 ** Math.floor(Math.log10(valor));
    return [1, 2, 2.5, 5, 10].map((f) => f * paso).find((c) => c >= valor) ?? valor;
};

export function iniciarPanel() {
    const raiz = document.querySelector('[data-panel]');
    if (!raiz) return;

    let datos;
    try {
        datos = JSON.parse(raiz.querySelector('[data-panel-datos]').textContent);
    } catch {
        return;
    }

    const fin = new Date(`${datos.fin}T00:00:00Z`);
    const fechaDe = (indice, dias) => new Date(fin.getTime() - (dias - 1 - indice) * 86400000);
    const tramo = (diario, dias, corrimiento = 0) => diario.slice(diario.length - dias - corrimiento, diario.length - corrimiento);
    let rango = datos.rango;
    try {
        const guardado = Number(localStorage.getItem(CLAVE));
        if (datos.rangos.includes(guardado)) rango = guardado;
    } catch { /* sin almacenamiento: queda el rango por defecto */ }

    // Saludo con la hora de quien mira.
    const hora = new Date().getHours();
    const saludo = raiz.querySelector('[data-saludo]');
    if (saludo) saludo.textContent = hora < 12 ? 'Buen día' : hora < 20 ? 'Buenas tardes' : 'Buenas noches';

    /* --- Indicadores (la facturación en pesos; el resto, cantidades) --- */
    const indicadores = [
        { key: 'facturacion', daily: datos.facturacion, plata: true },
        ...datos.series.map((s) => ({ key: s.key, daily: s.daily, plata: false })),
    ];
    const dibujarIndicadores = () => {
        indicadores.forEach((ind) => {
            const tarjeta = raiz.querySelector(`[data-kpi="${ind.key}"]`);
            if (!tarjeta) return;
            const actual = tramo(ind.daily, rango);
            const total = suma(actual);
            const delta = total - suma(tramo(ind.daily, rango, rango));
            tarjeta.querySelector('[data-kpi-valor]').textContent = ind.plata ? pesos(total) : numero.format(total);
            const nodo = tarjeta.querySelector('[data-kpi-delta]');
            nodo.classList.toggle('is-sube', delta > 0);
            nodo.classList.toggle('is-baja', delta < 0);
            const cuanto = ind.plata ? pesosCorto(Math.abs(delta)) : numero.format(Math.abs(delta));
            nodo.replaceChildren(
                document.createTextNode(delta > 0 ? `▲ ${cuanto} ` : delta < 0 ? `▼ ${cuanto} ` : 'Igual '),
                el('small', '', `que los ${rango} días anteriores`),
            );
            const mini = tarjeta.querySelector('[data-kpi-mini]');
            const max = Math.max(1, ...actual);
            const puntos = actual.map((v, i) => `${(i / Math.max(1, actual.length - 1)) * 120},${30 - (v / max) * 26}`);
            mini.replaceChildren(
                svg('polygon', { points: `0,32 ${puntos.join(' ')} 120,32`, class: 'panel-kpi__mini-area' }),
                svg('polyline', { points: puntos.join(' '), class: 'panel-kpi__mini-linea' }),
            );
        });
    };

    /* --- Gráfico --- */
    const caja = raiz.querySelector('[data-grafico]');
    const grafico = raiz.querySelector('[data-grafico-svg]');
    const tooltip = raiz.querySelector('[data-grafico-tooltip]');
    let geometria = null;
    let foco = null;

    const dibujarGrafico = () => {
        const ancho = Math.max(320, caja.clientWidth);
        const alto = 260;
        const pad = { top: 12, right: 12, bottom: 28, left: 34 };
        const series = datos.series.map((s) => ({ ...s, valores: tramo(s.daily, rango) }));
        const max = techo(Math.max(0, ...series.flatMap((s) => s.valores)));
        const x = (i) => pad.left + (i / Math.max(1, rango - 1)) * (ancho - pad.left - pad.right);
        const y = (v) => pad.top + (1 - v / max) * (alto - pad.top - pad.bottom);
        geometria = { series, x, y, pad, ancho, alto };

        grafico.setAttribute('viewBox', `0 0 ${ancho} ${alto}`);
        grafico.setAttribute('aria-label', `Entradas por día de los últimos ${rango} días. La tabla tiene todos los valores.`);
        const nodos = [];
        for (let t = 0; t <= 4; t += 1) {
            const v = (max / 4) * t;
            nodos.push(svg('line', { x1: pad.left, x2: ancho - pad.right, y1: y(v), y2: y(v), class: 'panel-grafico__grilla' }));
            const etiqueta = svg('text', { x: pad.left - 8, y: y(v) + 4, class: 'panel-grafico__marca', 'text-anchor': 'end' });
            etiqueta.textContent = Number.isInteger(v) ? numero.format(v) : '';
            nodos.push(etiqueta);
        }
        const etiquetas = rango <= 7 ? rango : 6;
        for (let p = 0; p < etiquetas; p += 1) {
            const i = Math.round((p / Math.max(1, etiquetas - 1)) * (rango - 1));
            const etiqueta = svg('text', { x: x(i), y: alto - 8, class: 'panel-grafico__marca', 'text-anchor': p === 0 ? 'start' : p === etiquetas - 1 ? 'end' : 'middle' });
            etiqueta.textContent = fechaCorta.format(fechaDe(i, rango));
            nodos.push(etiqueta);
        }
        series.forEach((s) => {
            nodos.push(svg('polyline', { points: s.valores.map((v, i) => `${x(i)},${y(v)}`).join(' '), class: 'panel-grafico__linea', stroke: s.color }));
        });
        if (series.every((s) => suma(s.valores) === 0)) {
            const vacio = svg('text', { x: ancho / 2, y: alto / 2, class: 'panel-grafico__vacio', 'text-anchor': 'middle' });
            vacio.textContent = 'Sin entradas en este período';
            nodos.push(vacio);
        }
        nodos.push(svg('g', { 'data-grafico-foco': '' }));
        grafico.replaceChildren(...nodos);
        if (foco !== null) mostrarFoco(Math.min(foco, rango - 1));
    };

    const mostrarFoco = (i) => {
        if (!geometria) return;
        foco = i;
        const { series, x, y, pad, alto } = geometria;
        grafico.querySelector('[data-grafico-foco]').replaceChildren(
            svg('line', { x1: x(i), x2: x(i), y1: pad.top, y2: alto - pad.bottom, class: 'panel-grafico__guia' }),
            ...series.map((s) => svg('circle', { cx: x(i), cy: y(s.valores[i]), r: 4.5, fill: s.color, class: 'panel-grafico__punto' })),
        );
        const plata = tramo(datos.facturacion, rango)[i];
        tooltip.replaceChildren(
            el('p', 'panel-tooltip__fecha', fechaLarga.format(fechaDe(i, rango)).replace(/^./, (l) => l.toUpperCase())),
            ...series.map((s) => {
                const fila = el('p', 'panel-tooltip__fila');
                const color = el('span', 'panel-tooltip__color');
                color.style.background = s.color;
                fila.append(color, el('strong', '', numero.format(s.valores[i])), el('span', '', s.short));
                return fila;
            }),
            el('p', 'panel-tooltip__plata', plata ? `Facturación: ${pesos(plata)}` : 'Sin facturación'),
        );
        tooltip.hidden = false;
        const escala = caja.clientWidth / geometria.ancho;
        const izquierda = x(i) * escala;
        tooltip.style.insetInlineStart = `${Math.min(Math.max(izquierda + 12, 0), caja.clientWidth - tooltip.offsetWidth)}px`;
        if (izquierda + 12 + tooltip.offsetWidth > caja.clientWidth) tooltip.style.insetInlineStart = `${Math.max(0, izquierda - tooltip.offsetWidth - 12)}px`;
    };
    const ocultarFoco = () => {
        foco = null;
        tooltip.hidden = true;
        grafico.querySelector('[data-grafico-foco]')?.replaceChildren();
    };

    grafico.setAttribute('tabindex', '0');
    grafico.addEventListener('pointermove', (e) => {
        if (!geometria) return;
        const r = grafico.getBoundingClientRect();
        const posicion = ((e.clientX - r.left) / r.width) * geometria.ancho;
        const i = Math.round(((posicion - geometria.pad.left) / (geometria.ancho - geometria.pad.left - geometria.pad.right)) * (rango - 1));
        mostrarFoco(Math.min(rango - 1, Math.max(0, i)));
    });
    grafico.addEventListener('pointerleave', ocultarFoco);
    grafico.addEventListener('focus', () => mostrarFoco(rango - 1));
    grafico.addEventListener('blur', ocultarFoco);
    grafico.addEventListener('keydown', (e) => {
        const pasos = { ArrowLeft: -1, ArrowRight: 1, Home: -Infinity, End: Infinity };
        if (!(e.key in pasos)) return;
        e.preventDefault();
        mostrarFoco(Math.min(rango - 1, Math.max(0, (foco ?? rango - 1) + pasos[e.key])));
    });

    /* --- Tabla (mismos datos, del día más reciente al más viejo) --- */
    const tabla = raiz.querySelector('[data-grafico-tabla]');
    const dibujarTabla = () => {
        const t = el('table');
        const cabeza = el('tr');
        ['Fecha', ...datos.series.map((s) => s.short), 'Facturación'].forEach((texto) => {
            const celda = el('th', '', texto);
            celda.scope = 'col';
            cabeza.append(celda);
        });
        const cuerpo = el('tbody');
        const plata = tramo(datos.facturacion, rango);
        for (let i = rango - 1; i >= 0; i -= 1) {
            const fila = el('tr');
            const fecha = el('th', '', fechaCorta.format(fechaDe(i, rango)));
            fecha.scope = 'row';
            const valores = datos.series.map((s) => tramo(s.daily, rango)[i]);
            fila.append(fecha, ...valores.map((v) => el('td', v ? '' : 'is-cero', numero.format(v))), el('td', plata[i] ? 'is-plata' : 'is-cero', plata[i] ? pesos(plata[i]) : '—'));
            cuerpo.append(fila);
        }
        const thead = el('thead');
        thead.append(cabeza);
        t.append(el('caption', 'visually-hidden', `Entradas por día de los últimos ${rango} días`), thead, cuerpo);
        tabla.replaceChildren(t);
    };

    raiz.querySelectorAll('[data-vista]').forEach((boton) => boton.addEventListener('click', () => {
        const comoTabla = boton.dataset.vista === 'tabla';
        raiz.querySelectorAll('[data-vista]').forEach((otro) => otro.setAttribute('aria-pressed', String(otro === boton)));
        caja.hidden = comoTabla;
        raiz.querySelector('.panel-leyenda').hidden = comoTabla;
        tabla.hidden = !comoTabla;
    }));

    /* --- Rankings (lo más pedido): cada tarjeta trae su color y su texto vacío --- */
    const dibujarBarras = (contenedor) => {
        const filas = datos[contenedor.dataset.barras]?.[rango] ?? [];
        if (!filas.length) {
            contenedor.replaceChildren(el('p', 'panel-vacio', contenedor.dataset.barrasVacio));
            return;
        }
        const max = Math.max(1, ...filas.map((f) => f.value));
        const lista = el('ul', 'panel-barras');
        lista.setAttribute('role', 'list');
        lista.style.setProperty('--barra-color', contenedor.dataset.barrasColor);
        filas.forEach((f) => {
            const item = el('li', 'panel-barras__fila');
            const etiqueta = f.url ? el('a', 'panel-barras__etiqueta', f.label) : el('span', 'panel-barras__etiqueta', f.label);
            if (f.url) etiqueta.href = f.url;
            const pista = el('span', 'panel-barras__pista');
            pista.setAttribute('aria-hidden', 'true');
            const relleno = el('span', 'panel-barras__relleno');
            relleno.style.inlineSize = `${Math.max(2, (f.value / max) * 100)}%`;
            pista.append(relleno);
            item.append(etiqueta, pista, el('span', 'panel-barras__valor', `${numero.format(f.value)} u.`));
            lista.append(item);
        });
        contenedor.replaceChildren(lista);
    };

    /* --- Rango --- */
    const elegirRango = (dias) => {
        rango = dias;
        try { localStorage.setItem(CLAVE, String(dias)); } catch { /* opcional */ }
        raiz.querySelectorAll('[data-rango]').forEach((b) => b.setAttribute('aria-pressed', String(Number(b.dataset.rango) === dias)));
        raiz.querySelector('[data-rango-leyenda]').textContent = `Últimos ${dias} días, comparados con los ${dias} anteriores.`;
        ocultarFoco();
        dibujarIndicadores();
        dibujarGrafico();
        dibujarTabla();
        raiz.querySelectorAll('[data-barras]').forEach(dibujarBarras);
    };
    raiz.querySelectorAll('[data-rango]').forEach((b) => b.addEventListener('click', () => elegirRango(Number(b.dataset.rango))));

    new ResizeObserver(() => dibujarGrafico()).observe(caja);
    elegirRango(rango);
}
