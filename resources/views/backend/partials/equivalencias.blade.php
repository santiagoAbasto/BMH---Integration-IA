{{--
    Sección "Equivalencias" para crear/editar productos.

    Variables esperadas:
    - $equivalencias   iterable  filas existentes del producto (App\Models\Equivalencia)

    Cada fila es un par nombre (origen, opcional) + valor (el código). Las filas
    son inputs directamente editables que viajan como equiv_nombre[] /
    equiv_valor[]. El orden viaja según la posición en la lista.
--}}
<div class="eqv-card" id="equivalencias">
    <input type="hidden" name="equivalencias_presente" value="1">

    <div class="eqv-header">
        <div>
            <h2 class="eqv-title">Equivalencias</h2>
            <p class="eqv-subtitle">Códigos equivalentes en otros fabricantes u originales.</p>
        </div>
        <span class="eqv-count-badge"><span data-eqv-count>{{ $equivalencias->count() }}</span> cargadas</span>
    </div>

    {{-- Fila de alta rápida: sin name, se agregan como fila nueva --}}
    <div class="eqv-adder" data-eqv-adder>
        <input type="text" class="eqv-field eqv-add-nombre" data-eqv-add-nombre placeholder="Origen (ej: Bosch)" aria-label="Nombre u origen de la equivalencia" maxlength="255">
        <input type="text" class="eqv-field eqv-add-valor" data-eqv-add-valor placeholder="Código equivalente (ej: 0986AB1234)" aria-label="Valor de la equivalencia" maxlength="255">
        <button type="button" class="eqv-btn-agregar" data-eqv-agregar>Agregar</button>
    </div>
    <p class="eqv-hint">Enter agrega rápido. Para carga masiva pegá varias líneas: <code>Bosch: 0986AB1234</code>, <code>Bosch, 0986AB1234</code>, la etiqueta sola terminada en <code>:</code> con el código en la línea siguiente, o solo el código.</p>

    {{-- Lista editable --}}
    <div class="eqv-lista" data-eqv-lista role="list" aria-label="Equivalencias del producto">
        @forelse ($equivalencias as $equivalencia)
            <div class="eqv-item" role="listitem">
                <span class="eqv-grip" title="Arrastrar para reordenar" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 12 16" fill="currentColor"><circle cx="3" cy="3" r="1.4"/><circle cx="9" cy="3" r="1.4"/><circle cx="3" cy="8" r="1.4"/><circle cx="9" cy="8" r="1.4"/><circle cx="3" cy="13" r="1.4"/><circle cx="9" cy="13" r="1.4"/></svg>
                </span>
                <span class="eqv-order">{{ $loop->index + 1 }}</span>
                <input type="text" class="eqv-in eqv-in-nombre" name="equiv_nombre[]" value="{{ $equivalencia->nombre }}" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">
                <input type="text" class="eqv-in eqv-in-valor" name="equiv_valor[]" value="{{ $equivalencia->valor }}" aria-label="Valor" maxlength="255">
                <div class="eqv-actions">
                    <button type="button" class="eqv-btn" data-move="-1" title="Subir" aria-label="Subir">&#8593;</button>
                    <button type="button" class="eqv-btn" data-move="1" title="Bajar" aria-label="Bajar">&#8595;</button>
                    <button type="button" class="eqv-btn eqv-btn-danger" data-remove title="Quitar" aria-label="Quitar equivalencia">&times;</button>
                </div>
            </div>
        @empty
            <div class="eqv-vacio" data-eqv-vacio>
                Todavía no cargaste equivalencias. Agregá una arriba o pegá una lista completa.
            </div>
        @endforelse
    </div>
</div>

@once
<style>
    .eqv-card { background:#fff; border:1px solid #e9ecef; border-radius:14px; padding:20px; margin-top:22px;
        box-shadow:0 1px 2px rgba(16,24,40,.04); font-family:'Poppins',sans-serif; }
    .eqv-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .eqv-title { font-size:17px; font-weight:600; color:#212529; margin:0; }
    .eqv-subtitle { font-size:13px; color:#6c757d; margin:2px 0 0; }
    .eqv-count-badge { flex-shrink:0; background:#e7f1ff; color:#0b5ed7; font-size:12px; font-weight:600;
        border-radius:999px; padding:4px 10px; }

    {{-- Alta rápida: dos inputs independientes + botón --}}
    .eqv-adder { display:flex; align-items:stretch; gap:8px; }
    .eqv-field { min-width:0; border:1px solid #dee2e6; border-radius:9px; background:#fff; outline:none;
        font-size:14px; color:#212529; font-family:inherit; padding:9px 12px;
        transition:border-color .15s, box-shadow .15s; }
    .eqv-field::placeholder { color:#adb5bd; }
    .eqv-field:hover { border-color:#ced4da; }
    .eqv-field:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.10); }
    .eqv-add-nombre { width:210px; flex-shrink:0; }
    .eqv-add-valor { flex:1; }
    .eqv-btn-agregar { flex-shrink:0; border:none; background:#0b5ed7; color:#fff; font-size:13px; font-weight:600;
        font-family:inherit; border-radius:9px; padding:9px 16px; cursor:pointer; transition:background .15s; }
    .eqv-btn-agregar:hover { background:#0a58ca; }
    .eqv-btn-agregar:active { transform:translateY(1px); }
    .eqv-hint { font-size:12px; color:#adb5bd; margin:7px 2px 0; }
    .eqv-hint code { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:11px;
        background:#f1f3f5; border-radius:4px; padding:1px 5px; color:#495057; }

    {{-- Lista --}}
    .eqv-lista { display:flex; flex-direction:column; gap:7px; margin-top:14px; }
    .eqv-item { display:flex; align-items:center; gap:8px; padding:7px 8px; border:1px solid #eef0f2;
        border-radius:10px; background:#fbfcfd; animation:eqv-flash .8s ease-out; }
    @keyframes eqv-flash { 0% { background:#e7f1ff; border-color:#bcd7ff; } 100% { background:#fbfcfd; } }
    .eqv-item.eqv-arrastrando { opacity:.45; }
    .eqv-grip { cursor:grab; color:#ced4da; display:flex; align-items:center; padding:4px 2px; flex-shrink:0; }
    .eqv-grip:hover { color:#868e96; }
    .eqv-order { width:20px; height:20px; border-radius:50%; background:#e9ecef; color:#495057;
        font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .eqv-in { flex:1; min-width:40px; border:1px solid #e9ecef; border-radius:8px; background:#fff;
        outline:none; font-size:13.5px; color:#212529; font-family:inherit; padding:7px 10px;
        transition:border-color .12s, box-shadow .12s; }
    .eqv-in:hover { border-color:#ced4da; }
    .eqv-in:focus { border-color:#0d6efd; box-shadow:0 0 0 2px rgba(13,110,253,.12); }
    .eqv-in::placeholder { color:#ced4da; }
    .eqv-in-nombre { flex:0 0 clamp(90px, 22%, 220px); color:#0b5ed7; font-weight:600; font-size:12.5px; }
    .eqv-in-nombre:not(:placeholder-shown):not(:focus) { color:#495057; font-weight:500; }
    .eqv-in-valor { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; letter-spacing:.01em; }
    {{-- Valor duplicado --}}
    .eqv-item.eqv-dup { border-color:#ffe08a; background:#fffbeb; }
    .eqv-item.eqv-dup .eqv-in-valor { color:#92400e; }
    .eqv-item.eqv-dup .eqv-in-valor::placeholder { color:#d3a04c; }

    .eqv-actions { display:flex; gap:3px; opacity:.5; transition:opacity .15s; flex-shrink:0; }
    .eqv-item:hover .eqv-actions, .eqv-item:focus-within .eqv-actions { opacity:1; }
    .eqv-btn { width:26px; height:26px; border-radius:7px; border:1px solid #dee2e6; background:#fff;
        color:#495057; font-size:13px; line-height:1; cursor:pointer; transition:all .12s; }
    .eqv-btn:hover { background:#f1f3f5; }
    .eqv-btn-danger:hover { background:#fff5f5; color:#dc3545; border-color:#ffc9cd; }
    .eqv-vacio { border:1.5px dashed #dee2e6; border-radius:10px; padding:22px; text-align:center;
        font-size:13px; color:#adb5bd; }

    @media (max-width:576px){
        .eqv-adder { flex-wrap:wrap; }
        .eqv-adder .eqv-field { flex:1 1 100%; width:auto; }
        .eqv-btn-agregar { flex:1; }
        .eqv-item { flex-wrap:wrap; }
        .eqv-in-nombre { flex:1 1 100%; }
    }
</style>
@endonce

@once
<script>
(function () {
    var root = document.getElementById('equivalencias');
    if (!root) return;

    var adder = root.querySelector('[data-eqv-adder]');
    var addNombre = root.querySelector('[data-eqv-add-nombre]');
    var addValor = root.querySelector('[data-eqv-add-valor]');
    var btnAgregar = root.querySelector('[data-eqv-agregar]');
    var lista = root.querySelector('[data-eqv-lista]');
    var countEl = root.querySelector('[data-eqv-count]');
    if (!adder || !lista || !countEl) return;

    var MAX_FILAS_LOTE = 300;

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function filas() {
        return Array.prototype.slice.call(lista.querySelectorAll('.eqv-item'));
    }

    function hayVacio() { return !!lista.querySelector('[data-eqv-vacio]'); }

    function quitarVacio() {
        var v = lista.querySelector('[data-eqv-vacio]');
        if (v) v.remove();
    }

    function actualizarEstado() {
        var items = filas();
        countEl.textContent = items.filter(function (it) {
            return it.querySelector('.eqv-in-valor').value.trim() !== '';
        }).length;
        items.forEach(function (item, i) {
            item.querySelector('.eqv-order').textContent = i + 1;
        });
        marcarDuplicados();
    }

    function marcarDuplicados() {
        var vistos = {};
        filas().forEach(function (item) {
            var valor = item.querySelector('.eqv-in-valor').value.replace(/\s+/g, '').toLowerCase();
            var dup = valor !== '' && vistos.hasOwnProperty(valor);
            item.classList.toggle('eqv-dup', dup);
            if (dup) {
                item.querySelector('.eqv-in-valor').title = 'Valor duplicado en este producto';
            } else {
                item.querySelector('.eqv-in-valor').removeAttribute('title');
            }
            if (valor !== '') vistos[valor] = true;
        });
    }

    function agregarFila(nombre, valor, enfocar) {
        quitarVacio();
        var fila = document.createElement('div');
        fila.className = 'eqv-item';
        fila.setAttribute('role', 'listitem');
        fila.innerHTML =
            '<span class="eqv-grip" title="Arrastrar para reordenar" aria-hidden="true">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 12 16" fill="currentColor">' +
                '<circle cx="3" cy="3" r="1.4"/><circle cx="9" cy="3" r="1.4"/><circle cx="3" cy="8" r="1.4"/>' +
                '<circle cx="9" cy="8" r="1.4"/><circle cx="3" cy="13" r="1.4"/><circle cx="9" cy="13" r="1.4"/></svg>' +
            '</span>' +
            '<span class="eqv-order"></span>' +
            '<input type="text" class="eqv-in eqv-in-nombre" name="equiv_nombre[]" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">' +
            '<input type="text" class="eqv-in eqv-in-valor" name="equiv_valor[]" aria-label="Valor" maxlength="255">' +
            '<div class="eqv-actions">' +
                '<button type="button" class="eqv-btn" data-move="-1" title="Subir" aria-label="Subir">&#8593;</button>' +
                '<button type="button" class="eqv-btn" data-move="1" title="Bajar" aria-label="Bajar">&#8595;</button>' +
                '<button type="button" class="eqv-btn eqv-btn-danger" data-remove title="Quitar" aria-label="Quitar equivalencia">&times;</button>' +
            '</div>';
        fila.querySelector('.eqv-in-nombre').value = nombre || '';
        fila.querySelector('.eqv-in-valor').value = valor || '';
        lista.appendChild(fila);
        actualizarEstado();
        if (enfocar) fila.querySelector('.eqv-in-valor').focus();
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
         terminada en ":" y el valor en la línea siguiente:
             EQUIVALENCIA NOSSO:
             RNB592791 --}}
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
        var item = target.closest('.eqv-item');
        var esValor = target.classList.contains('eqv-in-valor');
        var primero = pares.shift();
        if (esValor) {
            target.value = primero.valor;
            var inNombre = item.querySelector('.eqv-in-nombre');
            if (primero.nombre && inNombre.value.trim() === '') inNombre.value = primero.nombre;
        } else {
            target.value = primero.nombre;
            item.querySelector('.eqv-in-valor').value = primero.valor;
        }
        pares.forEach(function (p) { agregarFila(p.nombre, p.valor, false); });
        actualizarEstado();
    });

    {{-- Quitar y reordenar con botones (delegación) --}}
    lista.addEventListener('click', function (ev) {
        var btn = ev.target.closest('button');
        if (!btn) return;
        var item = btn.closest('.eqv-item');
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
        var grip = ev.target.closest('.eqv-grip');
        if (!grip) return;
        var item = grip.closest('.eqv-item');
        if (item) item.draggable = true;
    });
    lista.addEventListener('dragstart', function (ev) {
        var item = ev.target.closest('.eqv-item');
        if (!item) return;
        arrastre = item;
        item.classList.add('eqv-arrastrando');
        ev.dataTransfer.effectAllowed = 'move';
        try { ev.dataTransfer.setData('text/plain', ''); } catch (e) {}
    });
    lista.addEventListener('dragover', function (ev) {
        if (!arrastre) return;
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
        var objetivo = ev.target.closest('.eqv-item');
        if (!objetivo || objetivo === arrastre) return;
        var rect = objetivo.getBoundingClientRect();
        var antes = ev.clientY < rect.top + rect.height / 2;
        lista.insertBefore(arrastre, antes ? objetivo : objetivo.nextSibling);
    });
    lista.addEventListener('drop', function (ev) { if (arrastre) ev.preventDefault(); });
    lista.addEventListener('dragend', function () {
        if (!arrastre) return;
        arrastre.classList.remove('eqv-arrastrando');
        arrastre.draggable = false;
        arrastre = null;
        actualizarEstado();
    });

    actualizarEstado();
})();
</script>
@endonce
