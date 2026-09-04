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

    @php
        $modoAplic = old('orden_aplicaciones_mode', isset($producto) ? ($producto->orden_aplicaciones ?? 'manual') : 'manual');
    @endphp

    <div class="app-orden-mode-wrap">
        <label class="app-orden-mode-label" for="app-orden-mode">Criterio de orden</label>
        <select id="app-orden-mode" name="orden_aplicaciones_mode" class="app-orden-mode" data-app-orden-mode>
            <option value="manual" @selected($modoAplic === 'manual')>Campo orden (manual)</option>
            <option value="alfa_asc" @selected($modoAplic === 'alfa_asc')>Alfabético ascendente</option>
            <option value="alfa_desc" @selected($modoAplic === 'alfa_desc')>Alfabético descendente</option>
        </select>
    </div>

    <div class="app-header">
        <div>
            <h2 class="app-title">Aplicaciones</h2>
            <p class="app-subtitle">Modelos de otros fabricantes en los que aplica este producto.</p>
        </div>
        <div class="app-header-acciones">
            <span class="app-count-badge"><span data-app-count>{{ $aplicaciones->count() }}</span> cargadas</span>
            <button type="button" class="app-btn-borrar-todas" data-app-borrar-todas hidden>Borrar todas</button>
        </div>
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
                <input type="text" class="app-in app-orden" name="aplic_orden[]" value="{{ $aplicacion->orden }}" aria-label="Orden" maxlength="2" pattern="[A-Za-z0-9]{1,2}" placeholder="orden" title="Hasta 2 caracteres alfanuméricos (ej: aa, a1)">
                <input type="text" class="app-in app-in-nombre" name="aplic_nombre[]" value="{{ mb_strtoupper((string) $aplicacion->nombre) }}" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">
                <input type="text" class="app-in app-in-valor" name="aplic_valor[]" value="{{ mb_strtoupper((string) $aplicacion->valor) }}" aria-label="Modelo" maxlength="255">
                <div class="app-actions">
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
    .app-header-acciones { display:flex; align-items:center; gap:8px; flex-shrink:0; }
    .app-btn-borrar-todas { border:1px solid #ffc9cd; background:#fff; color:#dc3545; font-size:12px;
        font-weight:600; font-family:inherit; border-radius:999px; padding:4px 12px; cursor:pointer;
        transition:background .15s, border-color .15s; }
    .app-btn-borrar-todas:hover { background:#fff5f5; border-color:#f5a3aa; }
    .app-btn-borrar-todas:active { transform:translateY(1px); }
    .app-btn-borrar-todas[hidden] { display:none; }

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
    .app-item .app-orden { width:64px; flex:0 0 64px; border:1px solid #e9ecef; border-radius:8px; background:#fff;
        outline:none; font-size:13.5px; color:#212529; font-family:inherit; padding:7px 8px; text-align:center;
        transition:border-color .12s, box-shadow .12s; }
    .app-orden:hover { border-color:#ced4da; }
    .app-orden:focus { border-color:#0d6efd; box-shadow:0 0 0 2px rgba(13,110,253,.12); }
    .app-orden--off { background:#f1f3f5; color:#adb5bd; cursor:not-allowed; border-color:#e9ecef; box-shadow:none; }

    .app-orden-mode-wrap { display:flex; align-items:center; gap:10px; margin:0 0 14px; flex-wrap:wrap; }
    .app-orden-mode-label { font-size:13px; font-weight:600; color:#495057; }
    .app-orden-mode { font-family:'Poppins',sans-serif; font-size:13px; color:#212529; background:#fff;
        border:1px solid #ced4da; border-radius:8px; padding:7px 10px; cursor:pointer; }
    .app-orden-mode:focus { border-color:#0d6efd; box-shadow:0 0 0 2px rgba(13,110,253,.12); outline:none; }
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
    var btnBorrarTodas = root.querySelector('[data-app-borrar-todas]');
    if (!adder || !lista || !countEl) return;

    var MAX_FILAS_LOTE = 300;

    // Origen y modelo se guardan y se muestran siempre en mayúsculas.
    var SELECTOR_MAYUS = '.app-add-nombre, .app-add-valor, .app-in-nombre, .app-in-valor';

    function mayus(texto) {
        return (texto == null ? '' : String(texto)).toUpperCase();
    }

    // Pasa el input a mayúsculas conservando la posición del cursor: el largo
    // no cambia, pero asignar .value lo mandaría al final.
    function forzarMayus(input) {
        var arriba = mayus(input.value);
        if (arriba === input.value) return;
        var ini = input.selectionStart;
        var fin = input.selectionEnd;
        input.value = arriba;
        try { input.setSelectionRange(ini, fin); } catch (e) {}
    }

    root.addEventListener('input', function (ev) {
        var t = ev.target;
        if (t instanceof HTMLInputElement && t.matches(SELECTOR_MAYUS)) forzarMayus(t);
    });

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

    function mostrarVacio() {
        if (lista.querySelector('[data-app-vacio]')) return;
        var v = document.createElement('div');
        v.className = 'app-vacio';
        v.setAttribute('data-app-vacio', '');
        v.textContent = 'Todavía no cargaste aplicaciones. Agregá una arriba o pegá una lista completa.';
        lista.appendChild(v);
    }

    // Vacía la lista entera. Sigue siendo un cambio del formulario: recién se
    // aplica sobre la base al guardar el producto.
    function borrarTodas() {
        var total = filas().length;
        if (total === 0) return;
        var queEs = total === 1 ? 'la aplicación' : ('las ' + total + ' aplicaciones');
        if (!window.confirm('¿Borrar ' + queEs + ' de este producto? El cambio se aplica al guardar.')) return;
        filas().forEach(function (f) { f.remove(); });
        mostrarVacio();
        actualizarEstado();
    }

    function actualizarEstado() {
        var items = filas();
        countEl.textContent = items.filter(function (it) {
            return it.querySelector('.app-in-nombre').value.trim() !== '' ||
                it.querySelector('.app-in-valor').value.trim() !== '';
        }).length;
        if (btnBorrarTodas) btnBorrarTodas.hidden = items.length === 0;
        marcarDuplicados();
        ordenarSegunModo();
    }

    // Ordena todas las filas según el criterio elegido en el selector.
    function ordenarSegunModo() {
        var sel = document.querySelector('[data-app-orden-mode]');
        var modo = sel ? sel.value : 'manual';
        var nodos = Array.prototype.slice.call(lista.querySelectorAll('.app-item'));
        nodos.forEach(function (n, i) { n.dataset.__ordenIdx = i; });

        nodos.sort(function (a, b) {
            if (modo === 'alfa_asc' || modo === 'alfa_desc') {
                var na = (a.querySelector('.app-in-nombre').value || '').trim().toLowerCase();
                var nb = (b.querySelector('.app-in-nombre').value || '').trim().toLowerCase();
                if (na !== nb) return na < nb ? -1 : 1;
                var va = (a.querySelector('.app-in-valor').value || '').trim().toLowerCase();
                var vb = (b.querySelector('.app-in-valor').value || '').trim().toLowerCase();
                if (va !== vb) return va < vb ? -1 : 1;
                return parseInt(a.dataset.__ordenIdx, 10) - parseInt(b.dataset.__ordenIdx, 10);
            }
            // Manual: por el valor del campo orden (texto, vacío al final).
            var va = (a.querySelector('.app-orden').value || '').trim().toLowerCase();
            var vb = (b.querySelector('.app-orden').value || '').trim().toLowerCase();
            if (va === '') va = '~';
            if (vb === '') vb = '~';
            if (va !== vb) return va < vb ? -1 : 1;
            return parseInt(a.dataset.__ordenIdx, 10) - parseInt(b.dataset.__ordenIdx, 10);
        });

        if (modo === 'alfa_desc') nodos.reverse();

        nodos.forEach(function (n) { lista.appendChild(n); });

        var deshabilitar = (modo !== 'manual');
        nodos.forEach(function (n) {
            var inp = n.querySelector('.app-orden');
            if (!inp) return;
            // readOnly y no disabled: un input deshabilitado no se envía y el
            // servidor tendría que inventar el orden para cada fila.
            inp.readOnly = deshabilitar;
            inp.classList.toggle('app-orden--off', deshabilitar);
        });
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

    // Genera un código alfanumérico de 2 letras para el índice (aa, ab, …, az, ba, …).
    function codigoAlfa(i) {
        i = Math.max(0, Math.min(i, 675)); // 26*26-1: más allá no entra en los 2 caracteres
        var a = Math.floor(i / 26);
        var b = i % 26;
        return String.fromCharCode(97 + a) + String.fromCharCode(97 + b);
    }

    function agregarFila(nombre, valor, enfocar) {
        quitarVacio();
        var fila = document.createElement('div');
        fila.className = 'app-item';
        fila.setAttribute('role', 'listitem');
        fila.innerHTML =
            '<input type="text" class="app-in app-orden" name="aplic_orden[]" aria-label="Orden" maxlength="2" pattern="[A-Za-z0-9]{1,2}" placeholder="orden" title="Hasta 2 caracteres alfanuméricos (ej: aa, a1)">' +
            '<input type="text" class="app-in app-in-nombre" name="aplic_nombre[]" placeholder="Sin etiqueta" aria-label="Nombre u origen" maxlength="255">' +
            '<input type="text" class="app-in app-in-valor" name="aplic_valor[]" aria-label="Modelo" maxlength="255">' +
            '<div class="app-actions">' +
                '<button type="button" class="app-btn app-btn-danger" data-remove title="Quitar" aria-label="Quitar aplicación">&times;</button>' +
            '</div>';
        fila.querySelector('.app-in-nombre').value = mayus(nombre);
        fila.querySelector('.app-in-valor').value = mayus(valor);
        fila.querySelector('.app-orden').value = codigoAlfa(filas().length);
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
    if (btnBorrarTodas) btnBorrarTodas.addEventListener('click', borrarTodas);
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
            target.value = mayus(primero.valor);
            var inNombre = item.querySelector('.app-in-nombre');
            if (primero.nombre && inNombre.value.trim() === '') inNombre.value = mayus(primero.nombre);
        } else {
            target.value = mayus(primero.nombre);
            item.querySelector('.app-in-valor').value = mayus(primero.valor);
        }
        pares.forEach(function (p) { agregarFila(p.nombre, p.valor, false); });
        actualizarEstado();
    });

    {{-- Quitar fila (delegación) --}}
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
    });

    // Reordenar al cambiar cualquier dato de la fila o el criterio de orden.
    lista.addEventListener('change', function () { ordenarSegunModo(); });

    var selAplicModo = document.querySelector('[data-app-orden-mode]');
    if (selAplicModo) selAplicModo.addEventListener('change', ordenarSegunModo);

    actualizarEstado();
    ordenarSegunModo();
})();
</script>
@endonce
