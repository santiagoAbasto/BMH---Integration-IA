{{--
    Sección "Partes relacionadas" para crear/editar productos.

    Variables esperadas:
    - $productoActualId     int|null  id del producto en edición (null al crear)
    - $partesRelacionadas   iterable  productos ya asociados (con portadaImagen)

    El estado vive en JS; al enviar el form viaja como partes[] en orden de lista.
--}}
<div class="prt-card" id="partes-relacionadas"
    data-buscar-url="{{ route('producto.buscar.partes') }}"
    data-exclude-id="{{ $productoActualId ?? 0 }}"
    data-csrf="{{ csrf_token() }}">

    <div class="prt-header">
        <div>
            <h2 class="prt-title">Partes relacionadas</h2>
            <p class="prt-subtitle">Asociá repuestos o accesorios que complementan este producto.</p>
        </div>
        <span class="prt-count-badge"><span data-prt-count>{{ $partesRelacionadas->count() }}</span> agregadas</span>
    </div>

    {{-- Buscador --}}
    <div class="prt-search-wrap" data-prt-search-wrap>
        {{-- Centinela: indica que la sección viajó en el form, aunque no quede ninguna parte. --}}
        <input type="hidden" name="partes_presente" value="1">

        <div class="prt-search-box {{ !empty($errors->any()) ? '' : '' }}">
            <svg class="prt-icon-search" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input
                type="text"
                class="prt-input"
                data-prt-input
                placeholder="Buscar por código, nombre, marca o modelo…"
                autocomplete="off"
                role="combobox"
                aria-expanded="false"
                aria-controls="prt-resultados"
            >
            <button type="button" class="prt-clear d-none" data-prt-clear aria-label="Limpiar búsqueda">&times;</button>
            <div class="prt-spinner d-none" data-prt-spinner><span></span></div>
        </div>

        <div class="prt-hint d-none" data-prt-hint>Escribí al menos 2 caracteres para buscar.</div>

        <ul class="prt-resultados list-unstyled d-none" data-prt-resultados id="prt-resultados" role="listbox"></ul>
    </div>

    {{-- Lista de seleccionadas --}}
    <div class="prt-lista" data-prt-lista>
        @forelse ($partesRelacionadas as $parte)
            <div class="prt-item" data-id="{{ $parte->id }}">
                <span class="prt-order">{{ $loop->index + 1 }}</span>
                <img class="prt-thumb" src="{{ $parte->portadaUrl() ?? asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}" alt="">
                <div class="prt-info">
                    <span class="prt-code">{{ $parte->codigo }}</span>
                    <span class="prt-name">{{ $parte->nombre }}</span>
                    <span class="prt-meta">{{ trim(($parte->marca ?? '') . ' ' . ($parte->modelo ?? '')) }}</span>
                </div>
                <input type="hidden" name="partes[]" value="{{ $parte->id }}">
                <div class="prt-actions">
                    <button type="button" class="prt-btn" data-move="-1" title="Subir">&#8593;</button>
                    <button type="button" class="prt-btn" data-move="1" title="Bajar">&#8595;</button>
                    <button type="button" class="prt-btn prt-btn-danger" data-remove title="Quitar">&times;</button>
                </div>
            </div>
        @empty
            <div class="prt-vacio">Todavía no agregaste partes relacionadas. Usá el buscador de arriba.</div>
        @endforelse
    </div>
</div>

@once
<style>
    .prt-card { background:#fff; border:1px solid #e9ecef; border-radius:14px; padding:20px; margin-top:22px;
        box-shadow:0 1px 2px rgba(16,24,40,.04); font-family:'Poppins',sans-serif; }
    .prt-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .prt-title { font-size:17px; font-weight:600; color:#212529; margin:0; }
    .prt-subtitle { font-size:13px; color:#6c757d; margin:2px 0 0; }
    .prt-count-badge { flex-shrink:0; background:#e7f1ff; color:#0b5ed7; font-size:12px; font-weight:600;
        border-radius:999px; padding:4px 10px; }
    .prt-search-wrap { position:relative; }
    .prt-search-box { position:relative; display:flex; align-items:center; }
    .prt-icon-search { position:absolute; left:13px; color:#adb5bd; pointer-events:none; }
    .prt-input { width:100%; border:1px solid #dee2e6; border-radius:10px; padding:11px 74px 11px 40px;
        font-size:14px; transition:border-color .15s, box-shadow .15s; outline:none; background:#f8f9fa; }
    .prt-input:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.12); background:#fff; }
    .prt-clear { position:absolute; right:44px; border:none; background:none; font-size:20px; line-height:1;
        color:#adb5bd; cursor:pointer; padding:4px; } .prt-clear:hover { color:#495057; }
    .prt-spinner { position:absolute; right:14px; width:16px; height:16px; }
    .prt-spinner span { display:block; width:100%; height:100%; border:2px solid #dee2e6;
        border-top-color:#0d6efd; border-radius:50%; animation:prt-spin .6s linear infinite; }
    @keyframes prt-spin { to { transform:rotate(360deg); } }
    .prt-hint { font-size:12px; color:#adb5bd; padding:7px 4px 0; }
    .prt-resultados { position:absolute; z-index:1050; top:calc(100% + 6px); left:0; right:0; max-height:340px;
        overflow-y:auto; background:#fff; border:1px solid #e9ecef; border-radius:12px;
        box-shadow:0 10px 30px rgba(16,24,40,.12); }
    .prt-opcion { display:flex; align-items:center; gap:12px; padding:10px 14px; cursor:pointer;
        border-bottom:1px solid #f1f3f5; transition:background .12s; }
    .prt-opcion:last-child { border-bottom:none; }
    .prt-opcion:hover, .prt-opcion.activa { background:#f1f7ff; }
    .prt-opcion img { width:46px; height:46px; object-fit:contain; border-radius:8px; background:#f8f9fa;
        border:1px solid #eef0f2; flex-shrink:0; }
    .prt-opcion .o-code { font-size:12px; font-weight:700; color:#0b5ed7; letter-spacing:.02em; }
    .prt-opcion .o-nombre { font-size:13px; color:#212529; line-height:1.25; }
    .prt-opcion .o-meta { font-size:12px; color:#868e96; }
    .prt-sin-resultados { padding:16px; text-align:center; font-size:13px; color:#868e96; }
    .prt-lista { display:flex; flex-direction:column; gap:8px; margin-top:14px; }
    .prt-item { display:flex; align-items:center; gap:12px; padding:9px 12px; border:1px solid #e9ecef;
        border-radius:10px; background:#fbfcfd; animation:prt-flash .8s ease-out; }
    @keyframes prt-flash { 0% { background:#e7f1ff; border-color:#bcd7ff; } 100% { background:#fbfcfd; } }
    .prt-order { width:22px; height:22px; border-radius:50%; background:#e9ecef; color:#495057;
        font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .prt-thumb { width:48px; height:48px; object-fit:contain; border-radius:8px; background:#fff;
        border:1px solid #eef0f2; flex-shrink:0; }
    .prt-info { display:flex; flex-direction:column; min-width:0; flex:1; }
    .prt-code { font-size:11px; font-weight:700; color:#0b5ed7; }
    .prt-name { font-size:13px; color:#212529; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .prt-meta { font-size:12px; color:#868e96; }
    .prt-actions { display:flex; gap:4px; opacity:.55; transition:opacity .15s; }
    .prt-item:hover .prt-actions { opacity:1; }
    .prt-btn { width:28px; height:28px; border-radius:8px; border:1px solid #dee2e6; background:#fff;
        color:#495057; font-size:14px; line-height:1; cursor:pointer; transition:all .12s; }
    .prt-btn:hover { background:#f1f3f5; }
    .prt-btn-danger:hover { background:#fff5f5; color:#dc3545; border-color:#ffc9cd; }
    .prt-vacio { border:1.5px dashed #dee2e6; border-radius:10px; padding:22px; text-align:center;
        font-size:13px; color:#adb5bd; }
</style>
@endonce

@once
<script>
(function () {
    var root = document.getElementById('partes-relacionadas');
    if (!root) return;

    var input = root.querySelector('[data-prt-input]');
    var resultados = root.querySelector('[data-prt-resultados]');
    var lista = root.querySelector('[data-prt-lista]');
    var spinner = root.querySelector('[data-prt-spinner]');
    var hint = root.querySelector('[data-prt-hint]');
    var clearBtn = root.querySelector('[data-prt-clear]');
    var countEl = root.querySelector('[data-prt-count]');
    if (!input || !resultados || !lista) return;

    var buscarUrl = root.dataset.buscarUrl;
    var excludeId = parseInt(root.dataset.excludeId || '0', 10);
    var DEBOUNCE_MS = 300;
    var MIN_CHARS = 2;
    var timer = null;
    var controller = null;
    var cache = new Map();

    function debounce(fn, ms) {
        clearTimeout(timer);
        timer = setTimeout(fn, ms);
    }

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function setSpinner(on) {
        spinner.classList.toggle('d-none', !on);
    }

    function abrirResultados() {
        resultados.classList.remove('d-none');
        input.setAttribute('aria-expanded', 'true');
    }

    function cerrarResultados() {
        resultados.classList.add('d-none');
        input.setAttribute('aria-expanded', 'false');
    }

    function actualizarContador() {
        countEl.textContent = lista.querySelectorAll('.prt-item').length;
        var vacio = lista.querySelector('.prt-vacio');
        if (vacio) vacio.remove();
        renumerar();
    }

    function renumerar() {
        var items = lista.querySelectorAll('.prt-item');
        items.forEach(function (item, i) {
            item.querySelector('.prt-order').textContent = i + 1;
        });
    }

    function agregarParte(p) {
        if (lista.querySelector('.prt-item[data-id="' + p.id + '"]')) return;
        var placeholder = 'imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg';
        var fila = document.createElement('div');
        fila.className = 'prt-item';
        fila.dataset.id = p.id;
        fila.innerHTML =
            '<span class="prt-order"></span>' +
            '<img class="prt-thumb" src="' + escapar(p.portada_url || placeholder) + '" alt="">' +
            '<div class="prt-info">' +
                '<span class="prt-code">' + escapar(p.codigo) + '</span>' +
                '<span class="prt-name">' + escapar(p.nombre) + '</span>' +
                '<span class="prt-meta">' + escapar([p.marca, p.modelo].filter(Boolean).join(' ')) + '</span>' +
            '</div>' +
            '<input type="hidden" name="partes[]" value="' + p.id + '">' +
            '<div class="prt-actions">' +
                '<button type="button" class="prt-btn" data-move="-1" title="Subir">&#8593;</button>' +
                '<button type="button" class="prt-btn" data-move="1" title="Bajar">&#8595;</button>' +
                '<button type="button" class="prt-btn prt-btn-danger" data-remove title="Quitar">&times;</button>' +
            '</div>';
        lista.appendChild(fila);
        actualizarContador();
    }

    function buscar(q) {
        if (cache.has(q)) {
            pintar(cache.get(q), q);
            return Promise.resolve();
        }
        if (controller) controller.abort();
        controller = new AbortController();
        setSpinner(true);
        var url = buscarUrl + '?q=' + encodeURIComponent(q) + '&exclude=' + excludeId;
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                cache.set(q, json.data || []);
                pintar(json.data || [], q);
            })
            .catch(function (e) {
                if (e.name !== 'AbortError') cerrarResultados();
            })
            .finally(function () { setSpinner(false); });
    }

    function pintar(items, q) {
        resultados.innerHTML = '';
        if (!items.length) {
            resultados.innerHTML = '<li class="prt-sin-resultados">Sin resultados para &ldquo;' + escapar(q) + '&rdquo;</li>';
        } else {
            var placeholder = 'imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg';
            items.forEach(function (p) {
                var li = document.createElement('li');
                li.className = 'prt-opcion';
                li.setAttribute('role', 'option');
                li.innerHTML =
                    '<img src="' + escapar(p.portada_url || placeholder) + '" alt="">' +
                    '<div>' +
                        '<div class="o-code">' + escapar(p.codigo) + '</div>' +
                        '<div class="o-nombre">' + escapar(p.nombre) + '</div>' +
                        '<div class="o-meta">' + escapar([p.marca, p.modelo].filter(Boolean).join(' · ')) + '</div>' +
                    '</div>';
                li.addEventListener('mousedown', function (ev) {
                    ev.preventDefault(); // evita perder el foco antes de agregar
                    agregarParte(p);
                    input.value = '';
                    clearBtn.classList.add('d-none');
                    hint.classList.add('d-none');
                    cerrarResultados();
                });
                resultados.appendChild(li);
            });
        }
        abrirResultados();
    }

    function manejarBusqueda() {
        var q = input.value.trim();
        clearBtn.classList.toggle('d-none', q === '');
        if (q.length < MIN_CHARS) {
            hint.classList.toggle('d-none', q === '');
            cerrarResultados();
            if (controller) controller.abort();
            setSpinner(false);
            return;
        }
        hint.classList.add('d-none');
        debounce(function () { buscar(q); }, DEBOUNCE_MS);
    }

    // Debounce mientras se escribe + onchange (paste, autocompletado, flechitas).
    input.addEventListener('input', manejarBusqueda);
    input.addEventListener('change', manejarBusqueda);

    input.addEventListener('focus', function () {
        if (resultados.children.length && input.value.trim().length >= MIN_CHARS) abrirResultados();
    });

    input.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { cerrarResultados(); return; }
        if (ev.key !== 'Enter') return;
        var primera = resultados.querySelector('.prt-opcion:not(.prt-sin-resultados)');
        if (primera) { ev.preventDefault(); primera.dispatchEvent(new Event('mousedown')); }
    });

    clearBtn.addEventListener('click', function () {
        input.value = '';
        clearBtn.classList.add('d-none');
        cerrarResultados();
        input.focus();
    });

    document.addEventListener('click', function (ev) {
        if (!root.contains(ev.target)) cerrarResultados();
    });

    // Delegación para quitar y reordenar.
    lista.addEventListener('click', function (ev) {
        var btn = ev.target.closest('button');
        if (!btn) return;
        var item = btn.closest('.prt-item');
        if (btn.hasAttribute('data-remove')) {
            item.remove(); actualizarContador(); return;
        }
        var delta = parseInt(btn.dataset.move, 10);
        var items = Array.prototype.slice.call(lista.querySelectorAll('.prt-item'));
        var idx = items.indexOf(item);
        var destino = idx + delta;
        if (destino < 0 || destino >= items.length) return;
        if (delta < 0) lista.insertBefore(item, items[destino]);
        else lista.insertBefore(item, items[destino].nextSibling);
        renumerar();
    });
})();
</script>
@endonce
