{{--
    Modal «Descargar Excel de productos»: se tildan los rubros a descargar
    (ExcelProductosController@exportar). Sale en una sola hoja, ordenado por
    rubro y código como las listas de precios de los clientes.

    Espera:
      $categorias       Categorías (con productos_count, si se quiere mostrar cuántos tiene cada una).
      $seleccionadas    ids tildados al abrir; null = todos.
      $fuenteBusqueda   (opcional) selector del buscador del listado: si tiene texto,
                        se ofrece descargar sólo lo que coincide.
    Se abre con un botón data-bs-toggle="modal" data-bs-target="#exportar-excel".
--}}
@php
    $seleccionadas = isset($seleccionadas) && $seleccionadas !== null ? array_map('intval', (array) $seleccionadas) : null;
@endphp
<div class="modal fade" id="exportar-excel" tabindex="-1" aria-labelledby="exportar-excel-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content exp" method="GET" action="{{ route('dashboard.productos.exportar') }}" data-exportar-excel
          @isset($fuenteBusqueda) data-fuente-busqueda="{{ $fuenteBusqueda }}" @endisset>
      <div class="modal-header">
        <h2 class="modal-title exp-titulo" id="exportar-excel-titulo">Descargar Excel de productos</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <p class="exp-ayuda">Tildá los rubros que querés descargar. Sale en una sola hoja, ordenado por rubro y código.</p>

        <div class="exp-barra">
          <input type="search" class="form-control form-control-sm" placeholder="Buscar rubro" aria-label="Buscar rubro" data-exp-filtro autocomplete="off">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-exp-todos>Todos</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" data-exp-ninguno>Ninguno</button>
        </div>

        <fieldset class="exp-lista">
          <legend class="visually-hidden">Rubros</legend>
          @foreach ($categorias as $categoria)
            <label class="exp-rubro" data-nombre="{{ mb_strtolower($categoria->nombre) }}">
              <input type="checkbox" class="form-check-input" name="categorias[]" value="{{ $categoria->id }}"
                     @checked($seleccionadas === null || in_array((int) $categoria->id, $seleccionadas, true))>
              <span class="exp-rubro-nombre">{{ $categoria->nombre }}</span>
              @isset($categoria->productos_count)
                <span class="exp-rubro-cant">{{ number_format($categoria->productos_count, 0, ',', '.') }}</span>
              @endisset
            </label>
          @endforeach
          <p class="exp-sin-resultados" data-exp-sin-resultados hidden>Ningún rubro coincide con la búsqueda.</p>
        </fieldset>

        <label class="exp-busqueda" data-exp-busqueda hidden>
          <input type="checkbox" class="form-check-input" name="q" value="" checked>
          <span>Sólo los productos que coinciden con «<strong data-exp-busqueda-texto></strong>»</span>
        </label>
      </div>

      <div class="modal-footer exp-pie">
        <span class="exp-contador" data-exp-contador aria-live="polite"></span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" data-exp-descargar>
          <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Descargar
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  .exp-titulo { font-size: 1.15rem !important; font-weight: 700 !important; margin: 0; }
  .exp-ayuda { margin: 0 0 .75rem; font-size: .85rem; color: #4b5d6b; }
  .exp-barra { display: flex; gap: .4rem; align-items: center; margin-bottom: .6rem; }
  .exp-barra input { flex: 1; min-width: 0; }
  .exp-barra .btn { white-space: nowrap; }
  .exp-lista { max-height: 46vh; overflow: auto; margin: 0; padding: .25rem; border: 1px solid #dce4eb; border-radius: 8px; }
  .exp-rubro { display: flex; align-items: center; gap: .6rem; padding: .45rem .55rem; border-radius: 6px; cursor: pointer; font-size: .9rem; }
  .exp-rubro:hover { background: #f1f6fa; }
  .exp-rubro .form-check-input { margin: 0; flex: none; }
  .exp-rubro-nombre { flex: 1; min-width: 0; overflow-wrap: anywhere; }
  .exp-rubro-cant { flex: none; font-size: .75rem; color: #6b7c8a; background: #eef3f7; border-radius: 999px; padding: .05rem .5rem; }
  .exp-sin-resultados { margin: .5rem; font-size: .85rem; color: #6b7c8a; }
  .exp-busqueda { display: flex; align-items: flex-start; gap: .55rem; margin-top: .75rem; padding: .55rem .65rem; border-radius: 8px; background: #fff8e6; font-size: .85rem; cursor: pointer; }
  .exp-busqueda[hidden] { display: none; }
  .exp-busqueda .form-check-input { margin: .15rem 0 0; flex: none; }
  .exp-pie { gap: .5rem; }
  .exp-contador { margin-right: auto; font-size: .85rem; color: #4b5d6b; }
</style>

<script>
(function () {
  var form = document.querySelector('[data-exportar-excel]');
  if (!form) return;

  var modal = document.getElementById('exportar-excel');
  var casillas = Array.prototype.slice.call(form.querySelectorAll('input[name="categorias[]"]'));
  var filtro = form.querySelector('[data-exp-filtro]');
  var contador = form.querySelector('[data-exp-contador]');
  var descargar = form.querySelector('[data-exp-descargar]');
  var sinResultados = form.querySelector('[data-exp-sin-resultados]');
  var busqueda = form.querySelector('[data-exp-busqueda]');
  var casillaBusqueda = busqueda.querySelector('input');

  function visibles() {
    return casillas.filter(function (c) { return !c.closest('.exp-rubro').hidden; });
  }

  function actualizar() {
    var tildadas = casillas.filter(function (c) { return c.checked; }).length;
    contador.textContent = tildadas === casillas.length
      ? 'Todos los rubros'
      : tildadas + ' de ' + casillas.length + (casillas.length === 1 ? ' rubro' : ' rubros');
    descargar.disabled = tildadas === 0;
  }

  // «Todos» y «Ninguno» actúan sobre los rubros que se ven (si se filtró, sólo esos).
  function marcar(valor) {
    visibles().forEach(function (c) { c.checked = valor; });
    actualizar();
  }

  filtro.addEventListener('input', function () {
    var texto = filtro.value.trim().toLowerCase();
    casillas.forEach(function (c) {
      var fila = c.closest('.exp-rubro');
      fila.hidden = texto !== '' && fila.dataset.nombre.indexOf(texto) === -1;
    });
    sinResultados.hidden = visibles().length > 0;
  });
  form.querySelector('[data-exp-todos]').addEventListener('click', function () { marcar(true); });
  form.querySelector('[data-exp-ninguno]').addEventListener('click', function () { marcar(false); });
  form.addEventListener('change', function (ev) { if (ev.target.name === 'categorias[]') actualizar(); });

  // Al abrir: si el listado tiene texto en el buscador, se ofrece filtrar por eso.
  modal.addEventListener('show.bs.modal', function () {
    var fuente = form.dataset.fuenteBusqueda ? document.querySelector(form.dataset.fuenteBusqueda) : null;
    var texto = fuente ? fuente.value.trim() : '';
    busqueda.hidden = texto === '';
    casillaBusqueda.value = texto;
    casillaBusqueda.disabled = texto === '';
    busqueda.querySelector('[data-exp-busqueda-texto]').textContent = texto;
  });

  form.addEventListener('submit', function () {
    // Con todos tildados no hace falta mandarlos: el Excel trae todo.
    var todos = casillas.every(function (c) { return c.checked; });
    if (todos) casillas.forEach(function (c) { c.disabled = true; });
    setTimeout(function () {
      casillas.forEach(function (c) { c.disabled = false; });
      if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(modal).hide();
    }, 0);
  });

  actualizar();
})();
</script>
