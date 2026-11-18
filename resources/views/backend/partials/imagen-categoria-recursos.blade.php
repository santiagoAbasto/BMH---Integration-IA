{{-- Estilos y script de backend.partials.imagen-categoria. Una vez por página. --}}
<style>
    .cimg { --cimg-azul: #0098DA; --cimg-tinta: #1F2A37; --cimg-suave: #667085; --cimg-borde: #E3E8EE; --cimg-fondo-tarjeta: #F3F5F8;
        --cimg-ok: #12805C; --cimg-alerta: #B54708; --cimg-alerta-bg: #FEF6E7;
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 23rem); gap: 1.5rem; align-items: start; margin-top: 1rem; }
    @media (max-width: 767px) { .cimg { grid-template-columns: 1fr; } }

    .cimg__rotulo { font-weight: 600; margin-bottom: .25rem; }
    .cimg__medida { margin: 0 0 .625rem; font-size: .85rem; color: var(--cimg-suave); }
    .cimg__medida strong { color: var(--cimg-tinta); font-weight: 600; }

    .cimg__emparejar { margin: .75rem 0 0; padding: .625rem .75rem .625rem 2.25rem; border: 1px solid var(--cimg-borde); border-radius: 8px; background: #fff; }
    .cimg__emparejar .form-check-input { margin-top: .2rem; }
    .cimg__emparejar label { font-size: .875rem; line-height: 1.4; }
    .cimg__emparejar small { display: block; margin-top: .125rem; color: var(--cimg-suave); font-size: .78rem; }

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

    .cimg__guardada { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
        margin-top: .875rem; padding-top: .875rem; border-top: 1px dashed var(--cimg-borde); }
    .cimg__guardada p { margin: 0; font-size: .85rem; }
    .cimg__estado { color: var(--cimg-suave); }
    .cimg__estado--ok { color: var(--cimg-ok); font-weight: 600; }

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
    .cimg__tarjeta figcaption { display: flex; align-items: center; justify-content: space-between; gap: .375rem; padding: .5rem .5625rem .5625rem;
        font-size: .625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .01em; color: var(--cimg-tinta); line-height: 1.25; }
    .cimg__tarjeta figcaption span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cimg__tarjeta figcaption i { color: var(--cimg-azul); flex: 0 0 auto; }
    .cimg__tarjeta--vecina { opacity: .85; }

    /* Encabezado del listado: emparejar todas / volver todas. */
    .cimg-todas { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin: 0; }
    .cimg-todas__estado { font-size: .8rem; color: #667085; margin-right: .25rem; }
</style>

<script>
    (() => {
        const LADO_MINIMO = 800;
        // Lo mismo que hace el servidor al guardar (App\Services\Imagenes\EmparejarBordes, perfil categoría), para que
        // la vista previa muestre el resultado.
        const LADO = 1080, ANCHO_MAX = 0.68, ALTO_MAX = 0.54, SUPERFICIE = 0.52;

        const pesoTexto = (bytes) => (bytes / 1048576).toLocaleString('es-AR', { maximumFractionDigits: 1 }) + ' MB';

        const cargar = (file) => new Promise((ok, mal) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => ok({ img, url });
            img.onerror = () => { URL.revokeObjectURL(url); mal(new Error('no se pudo leer')); };
            img.src = url;
        });

        // Dónde está el producto: lo que se distingue del fondo (estimado con las 4 esquinas, sirve con degradé).
        const analizar = (img) => {
            const c = document.createElement('canvas');
            const W = img.naturalWidth, H = img.naturalHeight;
            const e = Math.min(1, 320 / Math.max(W, H));
            const w = c.width = Math.max(8, Math.round(W * e)), h = c.height = Math.max(8, Math.round(H * e));
            const ctx = c.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, w, h);
            const d = ctx.getImageData(0, 0, w, h).data;
            const k = Math.max(3, Math.floor(w / 60));
            const esquina = (x0, y0) => {
                const s = [0, 0, 0, 0];
                for (let y = y0; y < y0 + k; y++) for (let x = x0; x < x0 + k; x++) { const i = (y * w + x) * 4; for (let j = 0; j < 4; j++) s[j] += d[i + j]; }
                return s.map((v) => v / (k * k));
            };
            const tl = esquina(0, 0), tr = esquina(w - k, 0), bl = esquina(0, h - k), br = esquina(w - k, h - k);
            const transparente = (tl[3] + tr[3] + bl[3] + br[3]) / 4 < 32;
            const dif = (a, b) => Math.max(Math.abs(a[0] - b[0]), Math.abs(a[1] - b[1]), Math.abs(a[2] - b[2]));
            const escena = !transparente && (dif(tl, tr) > 40 || dif(bl, br) > 40);
            const cols = new Array(w).fill(0), filas = new Array(h).fill(0);
            for (let y = 0; y < h; y++) {
                const t = y / (h - 1);
                for (let x = 0; x < w; x++) {
                    const i = (y * w + x) * 4, s = x / (w - 1);
                    let distinto;
                    if (transparente) distinto = d[i + 3] > 54;
                    else {
                        distinto = false;
                        for (let j = 0; j < 3; j++) {
                            const f = (tl[j] * (1 - s) + tr[j] * s) * (1 - t) + (bl[j] * (1 - s) + br[j] * s) * t;
                            if (Math.abs(d[i + j] - f) > 22) { distinto = true; break; }
                        }
                    }
                    if (distinto) { cols[x]++; filas[y]++; }
                }
            }
            const xs = cols.flatMap((n, x) => (n > h * 0.006 ? [x] : [])), ys = filas.flatMap((n, y) => (n > w * 0.006 ? [y] : []));
            if (!xs.length || !ys.length) return { escena, caja: null };
            const fx = W / w, fy = H / h;
            return { escena, caja: [Math.floor(xs[0] * fx), Math.floor(ys[0] * fy), Math.min(W, Math.ceil((xs[xs.length - 1] + 1) * fx)), Math.min(H, Math.ceil((ys[ys.length - 1] + 1) * fy))] };
        };

        // Igual que el servidor: el producto centrado con la misma superficie y el fondo de la foto extendido.
        const componer = (img, caja) => {
            const W = img.naturalWidth, H = img.naturalHeight;
            const [x0, y0, x1, y1] = caja, bw = x1 - x0, bh = y1 - y0;
            const izq = x0 <= W * 0.012, der = x1 >= W * 0.988, arr = y0 <= H * 0.012, aba = y1 >= H * 0.988;
            if (izq && der && arr && aba) return null;
            const costado = izq || der;
            let s = Math.min((costado ? 0.99 : ANCHO_MAX) * LADO / bw, ALTO_MAX * LADO / bh);
            if (!costado) s = Math.min(s, SUPERFICIE * LADO / Math.sqrt(bw * bh));
            const rw = Math.round(W * s), rh = Math.round(H * s);
            let ox = Math.round(LADO / 2 - (x0 + x1) / 2 * s);
            const oy = Math.round(LADO / 2 - (y0 + y1) / 2 * s);
            if (izq) ox = 0; else if (der) ox = LADO - rw;

            const chica = document.createElement('canvas');
            const cw = chica.width = Math.max(1, Math.floor(W / 8)), ch = chica.height = Math.max(1, Math.floor(H / 8));
            chica.getContext('2d').drawImage(img, 0, 0, cw, ch);
            const c = document.createElement('canvas');
            c.width = c.height = LADO;
            const ctx = c.getContext('2d');
            const pegar = (sx, sy, sw, sh, dx, dy, dw, dh) => { if (dw > 0 && dh > 0) ctx.drawImage(chica, sx, sy, sw, sh, dx, dy, dw, dh); };
            const pi = Math.max(0, ox), pa = Math.max(0, oy), pd = Math.max(0, LADO - ox - rw), pb = Math.max(0, LADO - oy - rh);
            pegar(0, 0, cw, ch, ox, oy, rw, rh);
            pegar(0, 0, 1, ch, 0, oy, pi, rh);
            pegar(cw - 1, 0, 1, ch, ox + rw, oy, pd, rh);
            pegar(0, 0, cw, 1, ox, 0, rw, pa);
            pegar(0, ch - 1, cw, 1, ox, oy + rh, rw, pb);
            pegar(0, 0, 1, 1, 0, 0, pi, pa);
            pegar(cw - 1, 0, 1, 1, ox + rw, 0, pd, pa);
            pegar(0, ch - 1, 1, 1, 0, oy + rh, pi, pb);
            pegar(cw - 1, ch - 1, 1, 1, ox + rw, oy + rh, pd, pb);
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, ox, oy, rw, rh);
            return c.toDataURL('image/jpeg', 0.9);
        };

        const iniciar = (raiz) => {
            raiz.dataset.cimgListo = '1';
            const input = raiz.querySelector('[data-cimg-input]');
            const emparejar = raiz.querySelector('[data-cimg-emparejar]');
            const vista = raiz.querySelector('[data-cimg-img]');
            const vacia = raiz.querySelector('[data-cimg-vacia]');
            const titulo = raiz.querySelector('[data-cimg-vista-titulo]');
            const chequeos = raiz.querySelector('[data-cimg-chequeos]');
            const nombre = raiz.closest('form')?.querySelector('[name="nombre"]');
            const nombreVista = raiz.querySelector('[data-cimg-nombre]');
            const fotoGuardada = vista.getAttribute('src');
            let cargada = null, analisis = null, emparejada = null;

            nombre?.addEventListener('input', () => { nombreVista.textContent = nombre.value.trim() || 'Nueva categoría'; });

            const chequeo = (tipo, texto) => {
                const p = document.createElement('p');
                p.className = 'cimg__chequeo cimg__chequeo--' + tipo;
                p.textContent = texto;
                chequeos.append(p);
            };

            const mostrar = () => {
                if (!cargada) return;
                const usarPareja = emparejar.checked && emparejada;
                vista.src = usarPareja ? emparejada : cargada.url;
                vista.hidden = false;
                vacia.hidden = true;
                titulo.textContent = usarPareja ? 'Así se va a ver (con los bordes emparejados)' : 'Así se va a ver';

                chequeos.replaceChildren();
                const { naturalWidth: w, naturalHeight: h } = cargada.img;
                if (Math.min(w, h) < LADO_MINIMO) chequeo('aviso', `Es chica (${w} × ${h} px): en pantallas grandes se va a ver borrosa. Usá una de al menos ${LADO_MINIMO} px.`);
                else chequeo('ok', `Medida: ${w} × ${h} px.`);
                if (analisis.escena) chequeo('aviso', 'El fondo no es liso: esta foto no se puede emparejar y se guarda tal cual. Mirá la vista previa.');
                else if (!emparejada) chequeo('info', 'El producto ya llena la foto: se guarda tal cual.');
                else if (emparejar.checked) chequeo('ok', 'Bordes emparejados: el producto queda del mismo tamaño que en las otras categorías.');
                else chequeo('info', 'Sin emparejar: el producto se ve del tamaño que tiene en la foto.');
                if (input.files[0]?.size > 1048576) chequeo('info', `Pesa ${pesoTexto(input.files[0].size)}: al guardar se optimiza para que cargue rápido.`);
            };

            emparejar.addEventListener('change', mostrar);

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (cargada) URL.revokeObjectURL(cargada.url);
                cargada = null;
                if (!file) {
                    chequeos.replaceChildren();
                    if (fotoGuardada) vista.src = fotoGuardada; else { vista.hidden = true; vacia.hidden = false; }
                    return;
                }
                try {
                    cargada = await cargar(file);
                } catch {
                    chequeos.replaceChildren();
                    chequeo('aviso', 'No se pudo leer la imagen. Probá con un JPG, PNG o WebP.');
                    return;
                }
                analisis = analizar(cargada.img);
                emparejada = !analisis.escena && analisis.caja ? componer(cargada.img, analisis.caja) : null;
                mostrar();
            });
        };

        document.querySelectorAll('[data-cimg]:not([data-cimg-listo])').forEach(iniciar);
    })();
</script>
