{{--
    CSS y JS de las cards horizontales (frontend.components.productoBmh):
    estilos, lupa de las imágenes y delegación de clicks (tabs, stepper,
    carrito). Va una sola vez por página.
--}}
<style>
    .pbmh-card { background:#fff; border:1px solid #E7E9EC; border-radius:10px; margin-bottom:18px;
        overflow:hidden; font-family:'Roboto',sans-serif; }
    .pbmh-top { display:flex; gap:26px; padding:26px 28px 22px; }
    .pbmh-gallery { display:flex; gap:12px; flex:0 0 auto; align-self:flex-start; }
    .pbmh-thumbs { display:flex; flex-direction:column; gap:9px; width:108px; max-height:365px; overflow-y:auto; overflow-x:hidden; scrollbar-width:thin; scrollbar-color:#E8EBEF transparent; padding-right:2px; }
    .pbmh-thumbs::-webkit-scrollbar { width:3px; height:3px; }
    .pbmh-thumbs::-webkit-scrollbar-track { background:transparent; }
    .pbmh-thumbs::-webkit-scrollbar-thumb { background:#E8EBEF; border-radius:10px; }
    .pbmh-thumbs::-webkit-scrollbar-thumb:hover { background:#D1D6DE; }
    .pbmh-thumb-btn { width:108px; height:98px; border:1px solid #E7E9EC; border-radius:7px; background:#fff; padding:5px; cursor:pointer; display:flex; align-items:center; justify-content:center; overflow:hidden; transition:border-color .15s, box-shadow .15s; flex-shrink:0; }
    .pbmh-thumb-btn:hover { border-color:#B8C0CC; }
    .pbmh-thumb-btn.activo { border-color:#0098DA; border-width:2px; box-shadow:0 0 0 1px rgba(0,152,218,.15); }
    .pbmh-thumb-btn img { width:100%; height:100%; object-fit:contain; display:block; }
    .pbmh-imgbox { flex:0 0 420px; align-self:flex-start; }
    .pbmh-imgbox img { width:420px; height:365px; object-fit:contain; display:block; }
    .pbmh-body { flex:1; min-width:0; display:flex; flex-direction:column; }
    .pbmh-headrow { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
    .pbmh-titulos { display:flex; flex-direction:column; gap:3px; min-width:0; }
    .pbmh-codigo { display:inline-block; align-self:flex-start; font-size:16px; font-weight:800; color:#0098DA; background:#EAF6FC; padding:3px 10px; border-radius:6px; letter-spacing:.03em; }
    .pbmh-nombre { font-size:18px; font-weight:700; color:#1F2430; line-height:1.3;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .pbmh-cars { margin-top:12px; display:flex; flex-direction:column; gap:3px;
        max-height:177px; overflow-y:auto; padding-right:6px; }
    .pbmh-cars::-webkit-scrollbar { width:6px; }
    .pbmh-cars::-webkit-scrollbar-thumb { background:#D9DDE3; border-radius:3px; }
    .pbmh-cars::-webkit-scrollbar-track { background:transparent; }
    .pbmh-car { display:flex; gap:7px; font-size:13px; line-height:1.5; }
    .pbmh-car-label { color:#9AA0A8; letter-spacing:.03em; white-space:nowrap; }
    .pbmh-car-valor { color:#3A3F47; font-weight:500; }
    .pbmh-precios { margin-top:14px; display:flex; flex-direction:column; gap:5px; max-width:380px; }
    .pbmh-precio-fila { display:flex; justify-content:space-between; font-size:15px; color:#3A3F47; }
    .pbmh-precio-valor { font-weight:500; color:#1F2430; }
    .pbmh-reventa-control { display:inline-flex; align-items:center; justify-content:flex-end; gap:9px; min-height:32px; }
    [data-sensitive-fields][hidden] { display:none; }
    .pbmh-reventa-toggle { width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center;
        border:1px solid #D9DDE3; border-radius:7px; background:#fff; color:#0098DA; padding:0; cursor:pointer;
        transition:background .18s, border-color .18s, color .18s, transform .18s, box-shadow .18s; }
    .pbmh-reventa-toggle:hover, .pbmh-reventa-toggle.visible { background:#EAF6FC; border-color:#0098DA; color:#0078AD; }
    .pbmh-reventa-toggle:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,152,218,.16); }
    .pbmh-reventa-toggle:focus-visible { outline:2px solid #0098DA; outline-offset:2px; }
    .pbmh-actions { margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .pbmh-stepper { display:inline-flex; align-items:center; border:1px solid #D9DDE3; border-radius:8px;
        overflow:hidden; background:#fff; }
    .pbmh-step { width:34px; height:36px; border:none; background:none; font-size:18px; color:#3A3F47;
        cursor:pointer; line-height:1; }
    .pbmh-step:hover { background:#F3F5F7; }
    .pbmh-qty { min-width:34px; text-align:center; font-size:15px; font-weight:600; color:#1F2430; }
    .pbmh-cart-btn { display:inline-flex; align-items:center; gap:10px; border:1.5px solid #0098DA;
        color:#0098DA; background:#fff; border-radius:8px; padding:11px 26px; font-size:14px;
        font-weight:600; letter-spacing:.05em; cursor:pointer; transition:all .15s; }
    .pbmh-cart-btn:hover { background:#0098DA; color:#fff; }
    .pbmh-cart-btn:hover svg path { fill:#fff; }
    .pbmh-tabs { display:flex; gap:12px; flex-wrap:wrap; padding:16px 24px; border-top:1px solid #EDEFF2; }
    .pbmh-tab { border:1.5px solid #E2E6EB; background:#F6F8FA; color:#1F2430; cursor:pointer;
        font-family:inherit; font-size:14px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
        display:inline-flex; align-items:center; gap:9px; padding:13px 24px; border-radius:10px;
        transition:background .18s, color .18s, border-color .18s, box-shadow .18s, transform .12s; }
    .pbmh-tab:hover { border-color:#0098DA; color:#0098DA; background:#fff; }
    .pbmh-tab:active { transform:translateY(1px); }
    .pbmh-tab.activa { background:#0098DA; border-color:#0098DA; color:#fff;
        box-shadow:0 8px 18px rgba(0,152,218,.22); }
    .pbmh-caret { font-size:12px; transition:transform .2s ease; }
    .pbmh-tab.activa .pbmh-caret { transform:rotate(180deg); }

    /* Despliegue animado (max-height + opacidad; el contenido queda oculto cuando está cerrado) */
    .pbmh-panel { overflow:hidden; max-height:0; opacity:0; transition:max-height .34s ease, opacity .26s ease; }
    .pbmh-panel.abierto { opacity:1; }
    .pbmh-panel-inner { overflow-x:auto; padding:0 24px 22px;
        scrollbar-width:thin; scrollbar-color:#E8EBEF transparent; }
    .pbmh-panel-inner::-webkit-scrollbar { height:3px; }
    .pbmh-panel-inner::-webkit-scrollbar-track { background:transparent; }
    .pbmh-panel-inner::-webkit-scrollbar-thumb { background:#E8EBEF; border-radius:10px; }
    .pbmh-panel-inner::-webkit-scrollbar-thumb:hover { background:#D1D6DE; }
    .pbmh-tabla { width:100%; border-collapse:separate; border-spacing:0; }
    .pbmh-tabla thead tr { background:#111315; color:#fff; }
    .pbmh-tabla th { font-size:14px; font-weight:500; text-align:left; padding:13px 16px; }
    .pbmh-tabla thead th:first-child { border-top-left-radius:4px; }
    .pbmh-tabla thead th:last-child { border-top-right-radius:4px; }
    .pbmh-tabla td { padding:12px 16px; border-bottom:1px solid #F0F2F4; font-size:14px;
        color:#3A3F47; vertical-align:middle; }
    .pbmh-tabla tbody tr:hover { background:#FAFBFC; }
    .pbmh-col-img { width:205px; position:relative; }
    .pbmh-thumb { width:196px; height:178px; object-fit:contain; display:block; margin:2px 0; }
    .pbmh-celda-cod { font-weight:600; color:#1F2430; white-space:nowrap; }
    .pbmh-celda-desc { min-width:210px; }
    .pbmh-celda-cod a { color:inherit; text-decoration:none; }
    .pbmh-celda-cod a:hover { color:#0098DA; }
    .pbmh-celda-desc a { display:inline-flex; flex-direction:column; gap:2px; color:inherit; text-decoration:none; justify-content:center; vertical-align:middle; }
    .pbmh-celda-desc a:hover { color:#0098DA; }
    .pbmh-col-img a { display:block; }
    .pbmh-num { white-space:nowrap; }
    .pbmh-stepper-sm .pbmh-qty { min-width:28px; padding:0 2px 0 11px; font-size:15px; text-align:left; }
    .pbmh-steps { display:flex; flex-direction:column; padding:0 8px 0 2px; }
    .pbmh-stepper-sm .pbmh-step { width:18px; height:16px; display:flex; align-items:center; justify-content:center;
        border:none; background:none; padding:0; color:#5A6169; cursor:pointer; }
    .pbmh-stepper-sm .pbmh-step:hover { background:none; color:#0098DA; }
    .pbmh-stepper-sm .pbmh-step svg { display:block; }
    .pbmh-total { font-weight:600; color:#1F2430; }
    .pbmh-mini-cart { width:44px; height:44px; border-radius:10px; border:1.5px solid #0098DA;
        background:#fff; color:#0098DA; cursor:pointer; display:inline-flex; align-items:center;
        justify-content:center; gap:3px; padding:0; transition:all .15s; }
    .pbmh-mini-cart svg { display:block; flex-shrink:0; }
    .pbmh-mini-cart:hover { background:#0098DA; color:#fff; }
    .pbmh-consultar { display:inline-flex; align-items:center; justify-content:center; border-radius:10px;
        background:#0098DA; color:#fff; border:1px solid #0098DA; font-family:'Montserrat',sans-serif;
        font-size:15px; font-weight:600; letter-spacing:.02em; padding:11px 26px; text-decoration:none;
        transition:all .15s; cursor:pointer; white-space:nowrap; }
    .pbmh-consultar:hover { background:#fff; color:#0098DA; }
    .pbmh-consultar-sm { padding:9px 17px; font-size:13px; }
    .pbmh-vacio { font-size:14px; color:#9AA0A8; padding:14px 0 2px; margin:0; }
    @media (max-width: 991px) {
        .pbmh-top { flex-direction:column; }
        .pbmh-gallery { width:100%; flex-direction:column; }
        .pbmh-thumbs { flex-direction:row; width:100%; max-height:none; overflow-x:auto; overflow-y:hidden; padding-bottom:4px; padding-right:0; }
        .pbmh-thumb-btn { flex:0 0 108px; }
        .pbmh-imgbox { flex:none; width:100%; }
        .pbmh-imgbox img { width:100%; height:auto; max-height:500px; }
        .pbmh-tabs { gap:20px; flex-wrap:wrap; }
        .pbmh-panel-inner .pbmh-tabla { min-width:900px; }
    }
    /* Lupa zoom - preview flotante */
    #pbmh-zoom { position:fixed; display:none; width:520px; height:520px; background:#fff; border:1px solid #E7E9EC;
        border-radius:10px; box-shadow:0 14px 36px rgba(16,24,40,.16); z-index:1060; pointer-events:none;
        background-repeat:no-repeat; background-position:center; overflow:hidden; }
    .pbmh-imgbox { position:relative; overflow:visible; }
    .pbmh-lens { position:absolute; display:none; border:1px solid rgba(0,152,218,.35);
        background:rgba(0,152,218,.08); pointer-events:none; border-radius:6px; z-index:2; }
    @media (max-width: 991px) { #pbmh-zoom, .pbmh-lens { display:none !important; } }
</style>

{{-- Lupa por hover: apagada por ahora (config imagenes.zoom_hover). El clic abre el modal con zoom. --}}
@if (config('imagenes.zoom_hover'))
<script>
(function () {
    // Lupa zoom para las imágenes de las cards (hover -> preview flotante al costado)
    if (!window.__pbmhZoomInit) {
        window.__pbmhZoomInit = true;
        var preview = document.createElement('div');
        preview.id = 'pbmh-zoom';
        document.body.appendChild(preview);
        var lens = document.createElement('div');
        lens.className = 'pbmh-lens';
        var activeImg = null, activeBox = null, activeSrc = '', zoom = 2.4;
        // La ampliación va a la IZQUIERDA de la imagen, para no tapar los datos
        // del producto (que están a la derecha). Se achica hasta lo que entre;
        // sólo si ni así entra (pantallas angostas) va a la derecha.
        var LADO_MAX = 520, LADO_MIN = 260, SEPARACION = 24, BORDE = 12;
        var aLaIzquierda = true;
        function ubicarPreview(box) {
            var rect = box.getBoundingClientRect();
            var alto = window.innerHeight - BORDE * 2;
            var izquierda = rect.left - SEPARACION - BORDE;
            var derecha = window.innerWidth - rect.right - SEPARACION - BORDE;
            aLaIzquierda = izquierda >= LADO_MIN || izquierda >= derecha;
            var lado = Math.max(LADO_MIN, Math.min(LADO_MAX, alto, aLaIzquierda ? izquierda : derecha));
            preview.style.width = lado + 'px';
            preview.style.height = lado + 'px';
        }
        function imageSrc(img) {
            return img.currentSrc || img.src || '';
        }
        // La imagen por defecto (propia o la de BMH) no se amplía con la lupa.
        // Se compara sólo el path: el sitio puede abrirse con otro host que APP_URL.
        @php
            $pathsImagenPorDefecto = array_values(array_unique([
                parse_url(\App\Models\Producto::imagenPredeterminadaUrl(), PHP_URL_PATH),
                parse_url(\App\Models\Producto::imagenPredeterminadaBmhUrl(), PHP_URL_PATH),
            ]));
        @endphp
        var placeholders = {{ \Illuminate\Support\Js::from($pathsImagenPorDefecto) }};
        function isPlaceholder(src) {
            try {
                return placeholders.indexOf(new URL(src, window.location.href).pathname) !== -1;
            } catch (e) {
                return false;
            }
        }
        function syncZoomImage(box, img, force) {
            if (!img || window.innerWidth < 992) return false;

            var src = imageSrc(img);
            if (!src || isPlaceholder(src)) return false;

            activeImg = img;
            activeBox = box;
            if (!force && src === activeSrc) return true;

            activeSrc = src;
            preview.style.backgroundImage = 'url("' + src.replace(/["\\]/g, '\\$&') + '")';

            var cw = box.clientWidth || img.clientWidth || 48;
            var ch = box.clientHeight || img.clientHeight || 48;
            if (img.naturalWidth) {
                var base = img.naturalWidth / cw * 1.35;
                var maxScale = cw < 120 ? 10 : (cw < 300 ? 6 : 4.2);
                zoom = Math.min(maxScale, Math.max(3.0, base));
                preview.style.backgroundSize = (cw * zoom) + 'px ' + (ch * zoom) + 'px';
            } else {
                zoom = 3.6;
                preview.style.backgroundSize = '360%';
            }

            return true;
        }
        function showZoom(box, img) {
            if (document.body.classList.contains('product-gallery-open')) return;
            ubicarPreview(box);
            if (!syncZoomImage(box, img, true)) return;
            if (!box.contains(lens)) box.appendChild(lens);
            lens.style.display = 'block';
            preview.style.display = 'block';
        }
        function hideZoom() {
            preview.style.display = 'none';
            lens.style.display = 'none';
            activeImg = null; activeBox = null; activeSrc = '';
        }
        document.addEventListener('product-gallery:open', hideZoom);
        function moveZoom(e) {
            if (!activeImg || !activeBox) return;
            // El src puede haber cambiado mientras el cursor seguia sobre la card.
            if (!syncZoomImage(activeBox, activeImg)) {
                hideZoom();
                return;
            }
            var rect = activeBox.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var cw = rect.width, ch = rect.height;
            var pw = preview.offsetWidth || 460, ph = preview.offsetHeight || 460;
            var lensW = pw / zoom, lensH = ph / zoom;
            // Para miniaturas muy pequeñas el lens debe ser menor (más zoom útil) y caber en el contenedor
            var frac = cw < 120 ? 0.42 : (cw < 300 ? 0.5 : 0.58);
            lensW = Math.min(lensW, cw * frac);
            lensH = Math.min(lensH, ch * frac);
            lensW = Math.max(lensW, 22);
            lensH = Math.max(lensH, 22);
            lens.style.width = lensW + 'px';
            lens.style.height = lensH + 'px';
            var lx = x - lensW / 2, ly = y - lensH / 2;
            lx = Math.max(0, Math.min(lx, cw - lensW));
            ly = Math.max(0, Math.min(ly, ch - lensH));
            lens.style.left = lx + 'px';
            lens.style.top = ly + 'px';
            var xPct = (cw - lensW) > 0 ? (lx / (cw - lensW)) * 100 : (x / cw) * 100;
            var yPct = (ch - lensH) > 0 ? (ly / (ch - lensH)) * 100 : (y / ch) * 100;
            preview.style.backgroundPosition = xPct + '% ' + yPct + '%';
            var left = aLaIzquierda ? rect.left - pw - SEPARACION : rect.right + SEPARACION;
            var top = rect.top + (ch / 2) - (ph / 2);
            left = Math.max(BORDE, Math.min(left, window.innerWidth - pw - BORDE));
            top = Math.max(12, Math.min(top, window.innerHeight - ph - 12));
            preview.style.left = left + 'px';
            preview.style.top = top + 'px';
        }
        function initZoomBoxes() {
            document.querySelectorAll('.pbmh-imgbox').forEach(function (box) {
                if (box.dataset.pbmhZoomAttached) return;
                box.dataset.pbmhZoomAttached = '1';
                var img = box.querySelector('img');
                if (!img) return;
                box.addEventListener('mouseenter', function () { showZoom(box, img); });
                box.addEventListener('mousemove', moveZoom);
                box.addEventListener('mouseleave', hideZoom);
                img.addEventListener('pbmh:image-change', function () {
                    if (activeImg === img) syncZoomImage(box, img, true);
                });
                img.addEventListener('load', function () {
                    if (activeImg === img) syncZoomImage(box, img, true);
                });
            });
            // También las miniaturas de Partes relacionadas (48px) — mismo zoom preparado para imagen pequeña
            document.querySelectorAll('.pbmh-thumb').forEach(function (img) {
                var cell = img.closest('.pbmh-col-img') || img.closest('td') || img.parentElement;
                if (!cell || cell.dataset.pbmhZoomAttachedThumb) return;
                cell.dataset.pbmhZoomAttachedThumb = '1';
                cell.style.position = 'relative';
                cell.addEventListener('mouseenter', function () { showZoom(cell, img); });
                cell.addEventListener('mousemove', moveZoom);
                cell.addEventListener('mouseleave', hideZoom);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initZoomBoxes);
        } else {
            initZoomBoxes();
        }
        // Cards que agrega el catálogo al filtrar o al hacer scroll (public/js/catalogo.js).
        document.addEventListener('catalogo:cards', initZoomBoxes);
        // Por si las filas de partes se abren después (panel oculto al inicio), reintentar al abrir tabs
        document.addEventListener('click', function (e) {
            if (e.target.closest('.pbmh-tab')) setTimeout(initZoomBoxes, 80);
        });
    }
})();
</script>
@endif

<script>
(function () {
    // Delegación global: una sola suscripción para todas las cards.
    if (window.__pbmhDelegado) return;
    window.__pbmhDelegado = true;

    function formatear(n) {
        return '$' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function toastOk() {
        if (window.iziToast) {
            iziToast.success({
                title: 'Producto agregado al carrito',
                backgroundColor: '#DAF6D3', titleColor: '#479831', iconColor: '#479831',
                progressBar: false, position: 'bottomRight', timeout: 2500,
            });
        }
    }

    document.addEventListener('click', function (ev) {
        var card = ev.target.closest('[data-pbmh]');
        if (!card) return;

        // ---- Mostrar u ocultar precios y campos, conservando reventa ----
        var reventaToggle = ev.target.closest('[data-reventa-toggle]');
        if (reventaToggle) {
            var ocultarDatos = reventaToggle.getAttribute('aria-pressed') === 'true';
            card.querySelectorAll('[data-sensitive-fields]').forEach(function (field) {
                field.hidden = ocultarDatos;
            });
            reventaToggle.classList.toggle('visible', !ocultarDatos);
            reventaToggle.setAttribute('aria-pressed', ocultarDatos ? 'false' : 'true');
            reventaToggle.setAttribute('aria-label', ocultarDatos ? 'Mostrar precios y campos' : 'Ocultar precios y campos');
            reventaToggle.title = ocultarDatos ? 'Mostrar precios y campos' : 'Ocultar precios y campos';
            return;
        }

        // ---- Galería: cambiar imagen principal desde las previews (thumbs verticales) ----
        var thumbBtn = ev.target.closest('.pbmh-thumb-btn');
        if (thumbBtn) {
            var gallery = thumbBtn.closest('.pbmh-gallery');
            var mainImg = gallery ? gallery.querySelector('.pbmh-imgbox img') : null;
            if (mainImg && thumbBtn.dataset.src) {
                // Evitar recargar si ya es la activa
                if (mainImg.getAttribute('src') !== thumbBtn.dataset.src && mainImg.src !== thumbBtn.dataset.src) {
                    mainImg.src = thumbBtn.dataset.src;
                    mainImg.dispatchEvent(new Event('pbmh:image-change'));
                }
                gallery.querySelectorAll('.pbmh-thumb-btn').forEach(function (b) { b.classList.remove('activo'); });
                thumbBtn.classList.add('activo');
            }
            return;
        }

        // ---- Steppers (+/-) ----
        var stepBtn = ev.target.closest('[data-step]');
        if (stepBtn) {
            var qtyEl = stepBtn.closest('.pbmh-stepper').querySelector('[data-qty]');
            var valor = Math.max(1, parseInt(qtyEl.textContent, 10) + parseInt(stepBtn.dataset.step, 10));
            qtyEl.textContent = valor;

            // Si la fila tiene total, recalcularlo.
            var fila = stepBtn.closest('tr');
            if (fila && fila.dataset.precio) {
                fila.querySelector('[data-total]').textContent = formatear(valor * parseFloat(fila.dataset.precio));
            }
            return;
        }

        // ---- Tabs desplegables (uno abierto a la vez, animado) ----
        var tab = ev.target.closest('.pbmh-tab');
        if (tab) {
            var nombre = tab.dataset.tab;
            var abrir = !tab.classList.contains('activa');
            // Cerrar el panel que esté abierto (animado).
            card.querySelectorAll('.pbmh-panel').forEach(function (p) {
                if (p.classList.contains('abierto')) {
                    p.style.maxHeight = p.scrollHeight + 'px';
                    void p.offsetHeight; // fuerza reflow para que la transición a 0 se vea
                    p.classList.remove('abierto');
                    p.style.maxHeight = '0px';
                }
            });
            card.querySelectorAll('.pbmh-tab').forEach(function (t) { t.classList.remove('activa'); });
            if (abrir) {
                tab.classList.add('activa');
                var panel = card.querySelector('[data-panel="' + nombre + '"]');
                panel.style.maxHeight = panel.scrollHeight + 'px';
                panel.classList.add('abierto');
                panel.addEventListener('transitionend', function te(e) {
                    if (e.propertyName === 'max-height' && panel.classList.contains('abierto')) {
                        panel.style.maxHeight = 'none';
                    }
                }, { once: true });
            }
            return;
        }

        // ---- Sumar al carrito (card principal y filas de partes) ----
        var addBtn = ev.target.closest('[data-add]');
        if (addBtn) {
            var grupo = addBtn.closest('.pbmh-actions') || addBtn.closest('tr');
            var qty = grupo ? parseInt(grupo.querySelector('[data-qty]').textContent, 10) || 1 : 1;
            var body = new URLSearchParams();
            body.append('producto_id', addBtn.dataset.productoId);
            body.append('precio', addBtn.dataset.precio);
            body.append('qty', qty);

            fetch(card.dataset.aggUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': card.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            })
                .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(function () { toastOk(); })
                .catch(function (e) { console.error('carrito', e); });
        }
    });
})();
</script>
