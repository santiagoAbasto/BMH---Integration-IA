{{--
    Sección "Aplicaciones" para crear/editar productos.

    Funciona idéntico a la sección de Equivalencias, pero el valor es el
    MODELO en lugar del código.

    Variables esperadas:
    - $aplicaciones   iterable  filas existentes del producto (App\Models\Aplicacion)

    Cada fila es un par nombre (origen, opcional) + valor (el modelo). Las filas
    son inputs directamente editables que viajan como aplic_nombre[] /
    aplic_valor[]. El orden viaja según la posición en la lista.
--}}
<div class="app-card" id="aplicaciones">
    <input type="hidden" name="aplicaciones_presente" value="1">

    <div class="app-header">
        <div>
            <h2 class="app-title">Aplicaciones</h2>
            <p class="app-subtitle">Modelos de otros fabricantes en los que aplica este producto.</p>
        </div>
        <span class="app-count-badge"><span data-app-count>{{ $aplicaciones->count() }}</span> cargadas</span>
    </div>

    {{-- Fila de alta rápida: sin name, se agregan como fila nueva --}}
    <div class="app-adder" data-app-adder>
        <input type="text" class="app-field app-add-nombre" data-app-add-nombre placeholder="Origen (ej: Bosch)" aria-label="Nombre u origen de la aplicación" maxlength="255">
        <input type="text" class="app-field app-add-valor" data-app-add-valor placeholder="Modelo (ej: XY-200)" aria-label="Modelo de la aplicación" maxlength="255">
        <button type="button" class="app-btn-agregar" data-app-agregar>Agregar</button>
    </div>
    <p class="app-hint">Enter agrega rápido. Para carga masiva pegá varias líneas: <code>Bosch: XY-200</code>, <code>Bosch, XY-200</code>, la etiqueta sola terminada en <code>:</code> con el modelo en la línea siguiente, o solo el modelo.</p>

    {{-- Lista editable --}}
    <div class="app-lista" data-app-lista role="list" aria-label="Aplicaciones del producto">
        @forelse ($aplicaciones as $aplicacion)
            <div class="app-item" role="listitem">
                <span class="app-grip" title="Arrastrar para reordenar" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 12 16" fill="currentColor"><circle cx="3" cy="3" r="1.4"/><circle cx="9" cy="3" r="1.4"/><circle cx="3" cy="8" r="1.4"/><circle cx="9" cy="8" r="1.4"/><circle cx="3" cy="13" r="1.4"/><circle cx="9" cy="13" r="1.4"/></svg>
                </span>
                <span class="app-order">{{ $loop->index + 1 }}</span>
                <input type="text" class="app-in app-in-nombre" name="aplic_nombre[]" value="{{ $aplicacion->nombre }}" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">
                <input type="text" class="app-in app-in-valor" name="aplic_valor[]" value="{{ $aplicacion->valor }}" aria-label="Modelo" maxlength="255">
                <div class="app-actions">
                    <button type="button" class="app-btn" data-move="-1" title="Subir" aria-label="Subir">&#8593;</button>
                    <button type="button" class="app-btn" data-move="1" title="Bajar" aria-label="Bajar">&#8595;</button>
                    <button type="button" class="app-btn app-btn-danger" data-remove title="Quitar" aria-label="Quitar aplicación">&times;</button>
                </div>
            </div>
        @empty
            <div class="app-vacio" data-app-vacio>
                Todavía no cargaste aplicaciones. Agregá una arriba o pegá una lista completa.
            </div>
        @endforelse
    </div>
</div>

@once
<style>
    .app-card { background:#fff; border:1px solid #e9ecef; border-radius:14px; padding:20px; margin-top:22px;
        box-shadow:0 1px 2px rgba(16,24,40,.04); font-family:'Poppins',sans-serif; }
    .app-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .app-title { font-size:17px; font-weight:600; color:#212529; margin:0; }
    .app-subtitle { font-size:13px; color:#6c757d; margin:2px 0 0; }
    .app-count-badge { flex-shrink:0; background:#e7f1ff; color:#0b5ed7; font-size:12px; font-weight:600;
        border-radius:999px; padding:4px 10px; }

    {{-- Alta rápida: dos inputs independientes + botón --}}
    .app-adder { display:flex; align-items:stretch; gap:8px; }
    .app-field { min-width:0; border:1px solid #dee2e6; border-radius:9px; background:#fff; outline:none;
        font-size:14px; color:#212529; font-family:inherit; padding:9px 12px;
        transition:border-color .15s, box-shadow .15s; }
    .app-field::placeholder { color:#adb5bd; }
    .app-field:hover { border-color:#ced4da; }
    .app-field:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.10); }
    .app-add-nombre { width:210px; flex-shrink:0; }
    .app-add-valor { flex:1; }
    .app-btn-agregar { flex-shrink:0; border:none; background:#0b5ed7; color:#fff; font-size:13px; font-weight:600;
        font-family:inherit; border-radius:9px; padding:9px 16px; cursor:pointer; transition:background .15s; }
    .app-btn-agregar:hover { background:#0a58ca; }
    .app-btn-agregar:active { transform:translateY(1px); }
    .app-hint { font-size:12px; color:#adb5bd; margin:7px 2px 0; }
    .app-hint code { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:11px;
        background:#f1f3f5; border-radius:4px; padding:1px 5px; color:#495057; }

    {{-- Lista --}}
    .app-lista { display:flex; flex-direction:column; gap:7px; margin-top:14px; }
    .app-item { display:flex; align-items:center; gap:8px; padding:7px 8px; border:1px solid #eef0f2;
        border-radius:10px; background:#fbfcfd; animation:app-flash .8s ease-out; }
    @keyframes app-flash { 0% { background:#e7f1ff; border-color:#bcd7ff; } 100% { background:#fbfcfd; } }
    .app-item.app-arrastrando { opacity:.45; }
    .app-grip { cursor:grab; color:#ced4da; display:flex; align-items:center; padding:4px 2px; flex-shrink:0; }
    .app-grip:hover { color:#868e96; }
    .app-order { width:20px; height:20px; border-radius:50%; background:#e9ecef; color:#495057;
        font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .app-in { flex:1; min-width:40px; border:1px solid #e9ecef; border-radius:8px; background:#fff;
        outline:none; font-size:13.5px; color:#212529; font-family:inherit; padding:7px 10px;
        transition:border-color .12s, box-shadow .12s; }
    .app-in:hover { border-color:#ced4da; }
    .app-in:focus { border-color:#0d6efd; box-shadow:0 0 0 2px rgba(13,110,253,.12); }
    .app-in::placeholder { color:#ced4da; }
    .app-in-nombre { flex:0 0 clamp(90px, 22%, 220px); color:#0b5ed7; font-weight:600; font-size:12.5px; }
    .app-in-nombre:not(:placeholder-shown):not(:focus) { color:#495057; font-weight:500; }
    .app-in-valor { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; letter-spacing:.01em; }
    {{-- Valor duplicado --}}
    .app-item.app-dup { border-color:#ffe08a; background:#fffbeb; }
    .app-item.app-dup .app-in-valor { color:#92400e; }
    .app-item.app-dup .app-in-valor::placeholder { color:#d3a04c; }

    .app-actions { display:flex; gap:3px; opacity:.5; transition:opacity .15s; flex-shrink:0; }
    .app-item:hover .app-actions, .app-item:focus-within .app-actions { opacity:1; }
    .app-btn { width:26px; height:26px; border-radius:7px; border:1px solid #dee2e6; background:#fff;
        color:#495057; font-size:13px; line-height:1; cursor:pointer; transition:all .12s; }
    .app-btn:hover { background:#f1f3f5; }
    .app-btn-danger:hover { background:#fff5f5; color:#dc3545; border-color:#ffc9cd; }
    .app-vacio { border:1.5px dashed #dee2e6; border-radius:10px; padding:22px; text-align:center;
        font-size:13px; color:#adb5bd; }

    @media (max-width:576px){
        .app-adder { flex-wrap:wrap; }
        .app-adder .app-field { flex:1 1 100%; width:auto; }
        .app-btn-agregar { flex:1; }
        .app-item { flex-wrap:wrap; }
        .app-in-nombre { flex:1 1 100%; }
    }
</style>
@endonce

@once
<script>
(function () {
    var root = document.getElementById('aplicaciones');
    if (!root) return;

    var adder = root.querySelector('[data-app-adder]');
    var addNombre = root.querySelector('[data-app-add-nombre]');
    var addValor = root.querySelector('[data-app-add-valor]');
    var btnAgregar = root.querySelector('[data-app-agregar]');
    var lista = root.querySelector('[data-app-lista]');
    var countEl = root.querySelector('[data-app-count]');
    if (!adder || !lista || !countEl) return;

    var MAX_FILAS_LOTE = 300;

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function filas() {
        return Array.prototype.slice.call(lista.querySelectorAll('.app-item'));
    }

    function hayVacio() { return !!lista.querySelector('[data-app-vacio]'); }

    function quitarVacio() {
        var v = lista.querySelector('[data-app-vacio]');
        if (v) v.remove();
    }

    function actualizarEstado() {
        var items = filas();
        countEl.textContent = items.filter(function (it) {
            return it.querySelector('.app-in-valor').value.trim() !== '';
        }).length;
        items.forEach(function (item, i) {
            item.querySelector('.app-order').textContent = i + 1;
        });
        marcarDuplicados();
    }

    function marcarDuplicados() {
        var vistos = {};
        filas().forEach(function (item) {
            var valor = item.querySelector('.app-in-valor').value.replace(/\s+/g, '').toLowerCase();
            var dup = valor !== '' && vistos.hasOwnProperty(valor);
            item.classList.toggle('app-dup', dup);
            if (dup) {
                item.querySelector('.app-in-valor').title = 'Valor duplicado en este producto';
            } else {
                item.querySelector('.app-in-valor').removeAttribute('title');
            }
            if (valor !== '') vistos[valor] = true;
        });
    }

    function agregarFila(nombre, valor, enfocar) {
        quitarVacio();
        var fila = document.createElement('div');
        fila.className = 'app-item';
        fila.setAttribute('role', 'listitem');
        fila.innerHTML =
            '<span class="app-grip" title="Arrastrar para reordenar" aria-hidden="true">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 12 16" fill="currentColor">' +
                '<circle cx="3" cy="3" r="1.4"/><circle cx="9" cy="3" r="1.4"/><circle cx="3" cy="8" r="1.4"/>' +
                '<circle cx="9" cy="8" r="1.4"/><circle cx="3" cy="13" r="1.4"/><circle cx="9" cy="13" r="1.4"/></svg>' +
            '</span>' +
            '<span class="app-order"></span>' +
            '<input type="text" class="app-in app-in-nombre" name="aplic_nombre[]" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">' +
            '<input type="text" class="app-in app-in-valor" name="aplic_valor[]" aria-label="Modelo" maxlength="255">' +
            '<div class="app-actions">' +
                '<button type="button" class="app-btn" data-move="-1" title="Subir" aria-label="Subir">&#8593;</button>' +
                '<button type="button" class="app-btn" data-move="1" title="Bajar" aria-label="Bajar">&#8595;</button>' +
                '<button type="button" class="app-btn app-btn-danger" data-remove title="Quitar" aria-label="Quitar aplicación">&times;</button>' +
            '</div>';
        fila.querySelector('.app-in-nombre').value = nombre || '';
        fila.querySelector('.app-in-valor').value = valor || '';
        lista.appendChild(fila);
        actualizarEstado();
        if (enfocar) fila.querySelector('.app-in-valor').focus();
        return fila;
    }

    function agregarDesdeAlta() {
        var nombre = addNombre.value.trim();
        var valor = addValor.value.trim();
        if (nombre === '' && valor === '') { addValor.focus(); return; }
        agregarFila(nombre, valor, true);
        addNombre.value = '';
        addValor.value = '';
        addValor.focus();
    }

    {{-- Alta rápida --}}
    btnAgregar.addEventListener('click', agregarDesdeAlta);
    [addNombre, addValor].forEach(function (input) {
        input.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); agregarDesdeAlta(); }
        });
    });

    {{-- Pegado inteligente: varias líneas → varias filas --}}
    function partirLinea(linea) {
        if (linea.indexOf('\t') !== -1) {
            var t = linea.split('\t');
            return { nombre: t[0].trim(), valor: t.slice(1).join(' ').trim() };
        }
        var m = linea.match(/^([^:;=]{1,80})\s*[:;=]\s*(.+)$/);
        if (m) return { nombre: m[1].trim(), valor: m[2].trim() };
        if (linea.indexOf(',') !== -1) {
            var c = linea.split(',');
            return { nombre: c[0].trim(), valor: c.slice(1).join(',').trim() };
        }
        return { nombre: '', valor: linea };
    }

    {{-- Parser del lote completo. Además de los formatos en una sola línea
         ("Nombre: valor", "Nombre, valor", TAB, código suelto), soporta el
         formato en bloque de los exports: la etiqueta viaja sola en una línea
         terminada en ":" y el valor en la línea siguiente. --}}
    function parsearLote(lineas) {
        var pares = [];
        var etiquetaPendiente = null;
        lineas.forEach(function (linea) {
            if (/:$/.test(linea)) {
                if (etiquetaPendiente !== null) {
                    pares.push({ nombre: etiquetaPendiente, valor: '' });
                }
                etiquetaPendiente = linea.replace(/:\s*$/, '').trim();
                return;
            }
            if (etiquetaPendiente !== null) {
                pares.push({ nombre: etiquetaPendiente, valor: linea });
                etiquetaPendiente = null;
                return;
            }
            pares.push(partirLinea(linea));
        });
        if (etiquetaPendiente !== null) {
            pares.push({ nombre: etiquetaPendiente, valor: '' });
        }
        return pares;
    }

    root.addEventListener('paste', function (ev) {
        var target = ev.target;
        if (!(target instanceof HTMLInputElement)) return;
        var texto = (ev.clipboardData || window.clipboardData).getData('text') || '';
        var lineas = texto.split(/\r?\n/).map(function (l) { return l.trim(); })
            .filter(function (l) { return l !== ''; });
        if (lineas.length < 2) return;

        ev.preventDefault();
        var pares = parsearLote(lineas).slice(0, MAX_FILAS_LOTE);

        if (adder.contains(target)) {
            pares.forEach(function (p) { agregarFila(p.nombre, p.valor, false); });
            addNombre.value = '';
            addValor.value = '';
            addValor.focus();
            return;
        }

        {{-- Pegado sobre una fila existente: completa esa fila y agrega el resto --}}
        var item = target.closest('.app-item');
        var esValor = target.classList.contains('app-in-valor');
        var primero = pares.shift();
        if (esValor) {
            target.value = primero.valor;
            var inNombre = item.querySelector('.app-in-nombre');
            if (primero.nombre && inNombre.value.trim() === '') inNombre.value = primero.nombre;
        } else {
            target.value = primero.nombre;
            item.querySelector('.app-in-valor').value = primero.valor;
        }
        pares.forEach(function (p) { agregarFila(p.nombre, p.valor, false); });
        actualizarEstado();
    });

    {{-- Quitar y reordenar con botones (delegación) --}}
    lista.addEventListener('click', function (ev) {
        var btn = ev.target.closest('button');
        if (!btn) return;
        var item = btn.closest('.app-item');
        if (!item) return;
        if (btn.hasAttribute('data-remove')) {
            item.style.transition = 'opacity .18s, transform .18s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(8px)';
            setTimeout(function () { item.remove(); actualizarEstado(); }, 180);
            return;
        }
        var delta = parseInt(btn.dataset.move, 10);
        var items = filas();
        var idx = items.indexOf(item);
        var destino = idx + delta;
        if (destino < 0 || destino >= items.length) return;
        if (delta < 0) lista.insertBefore(item, items[destino]);
        else lista.insertBefore(item, items[destino].nextSibling);
        actualizarEstado();
    });

    {{-- Reordenar arrastrando desde el grip --}}
    var arrastre = null;
    lista.addEventListener('mousedown', function (ev) {
        var grip = ev.target.closest('.app-grip');
        if (!grip) return;
        var item = grip.closest('.app-item');
        if (item) item.draggable = true;
    });
    lista.addEventListener('dragstart', function (ev) {
        var item = ev.target.closest('.app-item');
        if (!item) return;
        arrastre = item;
        item.classList.add('app-arrastrando');
        ev.dataTransfer.effectAllowed = 'move';
        try { ev.dataTransfer.setData('text/plain', ''); } catch (e) {}
    });
    lista.addEventListener('dragover', function (ev) {
        if (!arrastre) return;
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
        var objetivo = ev.target.closest('.app-item');
        if (!objetivo || objetivo === arrastre) return;
        var rect = objetivo.getBoundingClientRect();
        var antes = ev.clientY < rect.top + rect.height / 2;
        lista.insertBefore(arrastre, antes ? objetivo : objetivo.nextSibling);
    });
    lista.addEventListener('drop', function (ev) { if (arrastre) ev.preventDefault(); });
    lista.addEventListener('dragend', function () {
        if (!arrastre) return;
        arrastre.classList.remove('app-arrastrando');
        arrastre.draggable = false;
        arrastre = null;
        actualizarEstado();
    });

    actualizarEstado();
})();
</script>
@endonce
