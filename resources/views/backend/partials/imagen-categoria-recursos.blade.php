{{-- Estilos y script de backend.partials.imagen-categoria. Una vez por página. --}}
<style>
    .cimg { --cimg-azul: #0098DA; --cimg-tinta: #1F2A37; --cimg-suave: #667085; --cimg-borde: #E3E8EE; --cimg-fondo-tarjeta: #F3F5F8;
        --cimg-ok: #12805C; --cimg-alerta: #B54708; --cimg-alerta-bg: #FEF6E7;
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 23rem); gap: 1.5rem; align-items: start; margin-top: 1rem; }
    @media (max-width: 767px) { .cimg { grid-template-columns: 1fr; } }

    .cimg__rotulo { font-weight: 600; margin-bottom: .25rem; }
    .cimg__medida { margin: 0 0 .5rem; font-size: .9rem; color: var(--cimg-tinta); }
    .cimg__consejos { margin: 0 0 .875rem; padding-left: 1.1rem; font-size: .82rem; line-height: 1.5; color: var(--cimg-suave); }
    .cimg__consejos li + li { margin-top: .2rem; }
    .cimg__consejos strong { color: var(--cimg-tinta); font-weight: 600; }

    .cimg__chequeos:empty { display: none; }
    .cimg__chequeos { margin-top: .625rem; display: grid; gap: .375rem; }
    .cimg__chequeo { display: flex; gap: .5rem; align-items: baseline; font-size: .82rem; line-height: 1.45; margin: 0; }
    .cimg__chequeo::before { flex: 0 0 auto; font: var(--fa-font-solid, 900 1em "Font Awesome 6 Free"); }
    .cimg__chequeo--ok { color: var(--cimg-ok); }
    .cimg__chequeo--ok::before { content: "\f00c"; }
    .cimg__chequeo--aviso { color: var(--cimg-alerta); background: var(--cimg-alerta-bg); border-radius: 6px; padding: .4rem .6rem; }
    .cimg__chequeo--aviso::before { content: "\f071"; }
    .cimg__chequeo--info { color: var(--cimg-suave); }
    .cimg__chequeo--info::before { content: "\f05a"; }

    .cimg__ajuste { margin-top: .875rem; padding: .75rem .875rem; border: 1px solid var(--cimg-borde); border-radius: 8px; background: #fff; }
    .cimg__ajuste[hidden] { display: none; }
    .cimg__ajuste-fila { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .cimg__ajuste-fila label { font-size: .85rem; font-weight: 600; margin: 0; }
    .cimg__ajuste-fila output { font-size: .85rem; font-variant-numeric: tabular-nums; color: var(--cimg-suave); }
    .cimg__ajuste-fila small { color: var(--cimg-suave); font-size: .78rem; flex: 1 1 12rem; }
    .cimg__ajuste .form-range { margin: .25rem 0 .375rem; }

    .cimg__vista { background: #fff; border: 1px solid var(--cimg-borde); border-radius: 10px; padding: .875rem; }
    .cimg__vista-titulo { margin: 0 0 .625rem; font-size: .8rem; font-weight: 600; color: var(--cimg-suave); }
    .cimg__tarjetas { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .625rem; }
    /* Como la tarjeta del sitio (category-card.v1): imagen 5:4 con object-fit cover (220 × 176 px a 1366 px de ancho). */
    .cimg__tarjeta { margin: 0; border: 1px solid var(--cimg-borde); border-radius: 8px; overflow: hidden; background: #fff; }
    .cimg__media { position: relative; aspect-ratio: 5 / 4; background: var(--cimg-fondo-tarjeta); overflow: hidden; }
    .cimg__media img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .cimg__media img[hidden] { display: none; }
    .cimg__vacia { position: absolute; inset: 0; display: grid; place-items: center; font-size: .75rem; color: var(--cimg-suave); }
    .cimg__vacia[hidden] { display: none; }
    /* Guía: el ancho que ocupa el producto en las demás categorías (unos dos tercios). */
    .cimg__guia { position: absolute; inset: 6% 17%; border-inline: 2px dashed var(--cimg-azul); background: rgb(0 152 218 / .06); opacity: 0; transition: opacity .15s; pointer-events: none; }
    .cimg--guia .cimg__guia { opacity: 1; }
    .cimg__tarjeta figcaption { display: flex; align-items: center; justify-content: space-between; gap: .375rem; padding: .5rem .5625rem .5625rem;
        font-size: .625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .01em; color: var(--cimg-tinta); line-height: 1.25; }
    .cimg__tarjeta figcaption span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cimg__tarjeta figcaption i { color: var(--cimg-azul); flex: 0 0 auto; }
    .cimg__tarjeta--vecina { opacity: .85; }
    .cimg__mostrar-guia { display: flex; align-items: center; gap: .5rem; margin: .75rem 0 0; font-size: .8rem; color: var(--cimg-suave); cursor: pointer; }
    .cimg__mostrar-guia .form-check-input { margin: 0; }
</style>

<script>
    (() => {
        const LADO = 1080;            // lo que se sube al achicar el producto
        const LADO_MINIMO = 800;
        const ANCHO_IDEAL = 66;       // % del ancho que ocupa el producto en las demás categorías
        const ANCHO_MAXIMO = 80;
        const ANCHO_MINIMO = 45;

        const pesoTexto = (bytes) => (bytes / 1048576).toLocaleString('es-AR', { maximumFractionDigits: 1 }) + ' MB';

        const cargar = (file) => new Promise((ok, mal) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => ok({ img, url });
            img.onerror = () => { URL.revokeObjectURL(url); mal(new Error('no se pudo leer')); };
            img.src = url;
        });

        // Fondo de la foto: los colores de las 4 esquinas, interpolados (sirve para fondos lisos y con degradé).
        const esquinas = (img) => {
            const c = document.createElement('canvas');
            const w = c.width = 160, h = c.height = Math.max(1, Math.round(160 * img.naturalHeight / img.naturalWidth));
            const ctx = c.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, w, h);
            const datos = ctx.getImageData(0, 0, w, h).data;
            const k = 4;
            const promedio = (x0, y0) => {
                const s = [0, 0, 0];
                for (let y = y0; y < y0 + k; y++) for (let x = x0; x < x0 + k; x++) { const i = (y * w + x) * 4; s[0] += datos[i]; s[1] += datos[i + 1]; s[2] += datos[i + 2]; }
                return s.map((v) => v / (k * k));
            };
            return { w, h, datos, tl: promedio(0, 0), tr: promedio(w - k, 0), bl: promedio(0, h - k), br: promedio(w - k, h - k) };
        };
        const fondoEn = (e, s, t) => [0, 1, 2].map((i) => (e.tl[i] * (1 - s) + e.tr[i] * s) * (1 - t) + (e.bl[i] * (1 - s) + e.br[i] * s) * t);

        // Qué % del ancho ocupa el producto: las columnas con píxeles que no son fondo.
        const anchoDelProducto = (e) => {
            let primera = -1, ultima = -1;
            for (let x = 0; x < e.w; x++) {
                let distintos = 0;
                for (let y = 0; y < e.h; y++) {
                    const i = (y * e.w + x) * 4, f = fondoEn(e, x / (e.w - 1), y / (e.h - 1));
                    if (Math.max(Math.abs(e.datos[i] - f[0]), Math.abs(e.datos[i + 1] - f[1]), Math.abs(e.datos[i + 2] - f[2])) > 28) distintos++;
                }
                if (distintos > e.h * 0.02) { if (primera < 0) primera = x; ultima = x; }
            }
            return primera < 0 ? null : Math.round((ultima - primera + 1) / e.w * 100);
        };

        // La foto en un cuadrado de 1080 con el producto al {escala}% y el fondo extendido alrededor.
        const componer = (img, e, escala) => {
            const c = document.createElement('canvas');
            c.width = c.height = LADO;
            const ctx = c.getContext('2d');
            const fondo = ctx.createImageData(LADO, LADO);
            for (let y = 0; y < LADO; y++) {
                for (let x = 0; x < LADO; x++) {
                    const f = fondoEn(e, x / (LADO - 1), y / (LADO - 1)), i = (y * LADO + x) * 4;
                    fondo.data[i] = f[0]; fondo.data[i + 1] = f[1]; fondo.data[i + 2] = f[2]; fondo.data[i + 3] = 255;
                }
            }
            ctx.putImageData(fondo, 0, 0);
            const ajuste = Math.min(LADO / img.naturalWidth, LADO / img.naturalHeight) * escala / 100;
            const w = img.naturalWidth * ajuste, h = img.naturalHeight * ajuste;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, (LADO - w) / 2, (LADO - h) / 2, w, h);
            return c;
        };

        const iniciar = (raiz) => {
            raiz.dataset.cimgListo = '1';
            const input = raiz.querySelector('[data-cimg-input]');
            const vista = raiz.querySelector('[data-cimg-img]');
            const vacia = raiz.querySelector('[data-cimg-vacia]');
            const chequeos = raiz.querySelector('[data-cimg-chequeos]');
            const ajuste = raiz.querySelector('[data-cimg-ajuste]');
            const escala = raiz.querySelector('[data-cimg-escala]');
            const escalaValor = raiz.querySelector('[data-cimg-escala-valor]');
            const nombre = raiz.closest('form')?.querySelector('[name="nombre"]');
            const nombreVista = raiz.querySelector('[data-cimg-nombre]');
            const puedeReemplazar = typeof DataTransfer === 'function';

            let original = null, cargada = null, bordes = null, ancho = null, urlVista = null, espera = null, turno = 0;

            nombre?.addEventListener('input', () => { nombreVista.textContent = nombre.value.trim() || 'Nueva categoría'; });
            raiz.querySelector('[data-cimg-mostrar-guia]').addEventListener('change', (ev) => raiz.classList.toggle('cimg--guia', ev.target.checked));

            const mostrar = (url) => {
                if (urlVista && urlVista !== cargada?.url) URL.revokeObjectURL(urlVista);
                urlVista = url;
                vista.src = url;
                vista.hidden = false;
                vacia.hidden = true;
            };

            const chequeo = (tipo, texto) => {
                const p = document.createElement('p');
                p.className = 'cimg__chequeo cimg__chequeo--' + tipo;
                p.textContent = texto;
                chequeos.append(p);
            };

            const evaluar = () => {
                chequeos.replaceChildren();
                const { naturalWidth: w, naturalHeight: h } = cargada.img;
                const valor = Number(escala.value);
                const anchoFinal = ancho === null ? null : Math.round(ancho * (valor < 100 ? valor / 100 * Math.min(1, w / h) : 1));
                const cuadrada = Math.abs(w - h) / Math.max(w, h) < 0.03 || valor < 100;

                if (Math.min(w, h) < LADO_MINIMO) chequeo('aviso', `Es chica (${w} × ${h} px): en pantallas grandes se va a ver borrosa. Usá una de al menos ${LADO_MINIMO} px.`);
                else chequeo('ok', valor < 100 ? `Medida: ${w} × ${h} px; se sube cuadrada, de ${LADO} × ${LADO} px.` : `Medida: ${w} × ${h} px.`);

                if (!cuadrada) chequeo('aviso', `No es cuadrada: la tarjeta recorta ${w > h ? 'los costados' : 'arriba y abajo'}. Mirá la vista previa, o usá «Tamaño del producto», que la deja cuadrada.`);

                if (anchoFinal !== null) {
                    if (anchoFinal > ANCHO_MAXIMO) chequeo('aviso', `El producto ocupa el ${anchoFinal}% del ancho; en las otras categorías ocupa cerca del ${ANCHO_IDEAL}%, por eso se ve más grande. Achicalo con «Tamaño del producto».`);
                    else if (anchoFinal < ANCHO_MINIMO) chequeo('aviso', `El producto ocupa sólo el ${anchoFinal}% del ancho: se va a ver más chico que en las otras categorías.`);
                    else chequeo('ok', `El producto ocupa el ${anchoFinal}% del ancho, como en las otras categorías.`);
                }

                const peso = input.files[0]?.size ?? original.size;
                if (peso > 1048576) chequeo('info', `Pesa ${pesoTexto(peso)}: al guardar se optimiza para que cargue rápido.`);
            };

            const aplicar = () => {
                const valor = Number(escala.value);
                escalaValor.textContent = valor + '%';
                const mio = ++turno;
                if (valor >= 100) {
                    const dt = new DataTransfer();
                    dt.items.add(original);
                    input.files = dt.files;
                    mostrar(cargada.url);
                    evaluar();
                    return;
                }
                const lienzo = componer(cargada.img, bordes, valor);
                lienzo.toBlob((blob) => {
                    if (!blob || mio !== turno) return;
                    const base = original.name.replace(/\.[^.]+$/, '') || 'categoria';
                    const archivo = new File([blob], `${base}-ajustada.${blob.type === 'image/webp' ? 'webp' : 'png'}`, { type: blob.type });
                    const dt = new DataTransfer();
                    dt.items.add(archivo);
                    input.files = dt.files;
                    mostrar(URL.createObjectURL(blob));
                    evaluar();
                }, 'image/webp', 0.95);
            };

            escala.addEventListener('input', () => {
                escalaValor.textContent = escala.value + '%';
                clearTimeout(espera);
                espera = setTimeout(aplicar, 120);
            });

            raiz.querySelector('[data-cimg-auto]').addEventListener('click', () => {
                if (!ancho) return;
                escala.value = String(Math.max(50, Math.min(100, Math.round(ANCHO_IDEAL / ancho * 100))));
                aplicar();
            });

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (!file) return;
                original = file;
                escala.value = '100';
                escalaValor.textContent = '100%';
                try {
                    if (cargada) URL.revokeObjectURL(cargada.url);
                    cargada = await cargar(file);
                } catch {
                    chequeos.replaceChildren();
                    chequeo('aviso', 'No se pudo leer la imagen. Probá con un JPG, PNG o WebP.');
                    ajuste.hidden = true;
                    return;
                }
                bordes = esquinas(cargada.img);
                ancho = anchoDelProducto(bordes);
                mostrar(cargada.url);
                ajuste.hidden = !puedeReemplazar;
                evaluar();
            });
        };

        document.querySelectorAll('[data-cimg]:not([data-cimg-listo])').forEach(iniciar);
    })();
</script>
