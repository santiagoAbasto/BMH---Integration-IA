@extends('layouts.plantilla-back')

@section('body-class', 'productos-dashboard')
@section('sidebar-collapsed', '1')

@section('content')

<section class="productos-page" aria-labelledby="productos-titulo">
  <header class="productos-heading">
    <div>
      <p class="productos-eyebrow">Administración del catálogo</p>
      <h1 id="productos-titulo">Productos</h1>
      <p class="productos-intro">Buscá, organizá y actualizá tus productos.</p>
    </div>
    <a href="{{ route('producto.create') }}" class="btn btn-success productos-crear">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Crear producto
    </a>
  </header>

  @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
  @endif
  @if(session('warning'))
    <div class="alert alert-danger">
        {{ session('warning') }}
    </div>
  @endif

  {{-- Reporte de «Actualizar desde Excel → Precios» (ActualizacionPreciosController). --}}
  @if (! empty($reportePrecios))
    @php
      $rp = $reportePrecios;
      $rpFaltan = $rp['no_actualizados'];
      $rpN = $rp['actualizados'];
      $rpFmt = fn (int $n) => number_format($n, 0, ',', '.');
    @endphp
    <section class="precios-reporte {{ $rpFaltan ? 'precios-reporte--faltan' : '' }}" role="status" aria-labelledby="precios-reporte-titulo" data-precios-reporte>
      <div class="precios-reporte-cabecera">
        <i class="fa-solid {{ $rpN > 0 ? 'fa-circle-check' : 'fa-circle-info' }} precios-reporte-icono" aria-hidden="true"></i>
        <div class="precios-reporte-texto">
          <h2 id="precios-reporte-titulo">
            @if ($rpN > 0)
              Se {{ $rpN === 1 ? 'actualizó 1 producto' : 'actualizaron '.$rpFmt($rpN).' productos' }}
            @else
              No se actualizó ningún producto
            @endif
          </h2>
          <p>
            <i class="fa-solid fa-file-excel" aria-hidden="true"></i> {{ $rp['archivo'] }} · {{ $rpFmt($rp['filas']) }} {{ $rp['filas'] === 1 ? 'fila' : 'filas' }} con datos
            @if ($rp['sin_cambios'] > 0)
              · {{ $rpFmt($rp['sin_cambios']) }} ya {{ $rp['sin_cambios'] === 1 ? 'tenía' : 'tenían' }} ese precio
            @endif
          </p>
        </div>
        <button type="button" class="btn-close" aria-label="Cerrar el reporte" data-cerrar-reporte></button>
      </div>

      @if ($rpFaltan)
        <div class="precios-reporte-faltan">
          <div class="precios-reporte-faltan-cabecera">
            <strong>
              <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
              {{ count($rpFaltan) === 1 ? '1 fila no se pudo actualizar' : $rpFmt(count($rpFaltan)).' filas no se pudieron actualizar' }}
            </strong>
            <a class="btn btn-sm btn-outline-danger" href="{{ route('actualizar.precios.no-actualizados', $tokenPrecios) }}">
              <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Descargar Excel con los no actualizados
            </a>
          </div>
          <div class="precios-reporte-tabla">
            <table class="table table-sm mb-0">
              <thead>
                <tr><th scope="col">Fila</th><th scope="col">Código en el Excel</th><th scope="col">Precio</th><th scope="col">Motivo</th><th scope="col">Código parecido en la web</th></tr>
              </thead>
              <tbody>
                @foreach ($rpFaltan as $f)
                  <tr>
                    <td>{{ $f['fila'] }}</td>
                    <td class="precios-reporte-codigo">{{ $f['codigo'] !== '' ? $f['codigo'] : '—' }}</td>
                    <td>{{ $f['precio'] !== '' ? $f['precio'] : '—' }}</td>
                    <td>{{ $f['motivo'] }}</td>
                    <td>
                      @if ($f['sugerencia'])
                        ¿Será <a class="precios-reporte-codigo" href="{{ route('dashboard.productos', ['q' => $f['sugerencia']]) }}" title="Ver el producto {{ $f['sugerencia'] }}">{{ $f['sugerencia'] }}</a>?
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <p class="precios-reporte-ayuda">Corregí el código en el Excel (o en el producto) y volvé a subirlo: los que ya se actualizaron no cambian.</p>
        </div>
      @endif
    </section>
  @endif
  
  @php
      if (isset($categoria_id) && $categoria_id && $categoria_id != 'todos') {
          $catName = $categorias->firstWhere('id', $categoria_id)->nombre ?? 'la categoría';
          $catLabel = ' de ' . $catName;
      } else {
          $catLabel = ' de todos los productos';
      }
      $hayFiltros = (isset($categoria_id) && $categoria_id && $categoria_id != 'todos') || ($busqueda ?? '') !== '';
  @endphp

  <div class="productos-tools">
    <form method="GET" action="{{ route('dashboard.productos') }}" class="productos-filtro">
      {{-- Cambiar de categoría conserva lo escrito en el buscador. --}}
      <input type="hidden" name="q" id="filtro-q" value="{{ $busqueda ?? '' }}">
      <label for="categoria_id" class="form-label">Filtrar por categoría</label>
      <div class="productos-filtro-fila">
        <select name="categoria_id" id="categoria_id" class="form-select" onchange="this.form.submit()">
            <option value="todos">Todas</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}" {{ (isset($categoria_id) && $categoria_id == $categoria->id) ? 'selected' : '' }}>
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
        {{-- Su lugar queda reservado aunque no se vea: al aparecer no mueve nada. --}}
        <a href="{{ route('dashboard.productos') }}" class="productos-borrar-filtros" data-borrar-filtros @unless($hayFiltros) hidden @endunless title="Quitar la categoría y la búsqueda">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Borrar filtros
        </a>
      </div>
    </form>

    <div class="productos-descargas">
    {{-- Abre el modal para tildar los rubros (viene tildada la categoría filtrada, o todas). --}}
    <button type="button" id="btn-exportar-excel" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportar-excel"
       title="Descargar Excel: elegí los rubros" data-titulo="Descargar Excel: elegí los rubros">
      <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
      <span data-rol="texto">Descargar Excel</span>
    </button>
    <a href="{{ route('dashboard.productos.excel.importar') }}" class="btn btn-outline-primary" title="Actualizar precio e imágenes de muchos productos con el Excel, con vista previa antes de aplicar">
      <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
      <span>Importar Excel</span>
    </a>
    <a href="{{ route('dashboard.imagenes') }}" class="btn btn-outline-secondary" title="Subir imágenes masivamente y ver sus códigos">
      <i class="fa-solid fa-images" aria-hidden="true"></i>
      <span>Imágenes</span>
    </a>

    {{-- Un ZIP con las imágenes nombradas por código: las de la categoría elegida o,
         sin categoría, las de todo el catálogo. El botón está siempre, así el bloque
         no cambia de alto al filtrar. --}}
    @if (isset($imagenesCategoria))
      @if ($imagenesCategoria > 0)
        <a href="{{ route('dashboard.productos.imagenes', ['categoria_id' => $categoria_id]) }}"
           class="btn btn-outline-primary"
           title="Cada imagen lleva el código del producto (CODIGO-1, CODIGO-2 si tiene varias)">
          <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
           <span>Descargar imágenes ({{ number_format($imagenesCategoria, 0, ',', '.') }})</span>
        </a>
      @else
        <span class="btn btn-outline-secondary disabled" aria-disabled="true">
          <i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Esta categoría no tiene imágenes
        </span>
      @endif
    @else
      <a href="{{ route('dashboard.productos.imagenes', ['categoria_id' => 'todos']) }}"
         class="btn btn-outline-primary"
         title="Todas las imágenes del catálogo, cada una con el código del producto. Es un archivo grande: puede tardar en descargar">
        <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
        <span>Descargar todas las imágenes</span>
      </a>
    @endif
    </div>
  </div>


    <div class="card productos-panel">
    <div class="card-header productos-toolbar">
      <form class="productos-buscador" role="search" id="productos-buscar-form">
        <label for="productos-busqueda" class="form-label">Buscar productos</label>
        <div class="productos-search-control">
          <input type="search" id="productos-busqueda" value="{{ $busqueda ?? '' }}" placeholder="Código, nombre o descripción" class="search-input" autocomplete="off" aria-controls="productos-contenedor" aria-describedby="productos-busqueda-ayuda">
          <button type="submit" id="buscador-productos" class="productos-search-btn" aria-label="Buscar productos">
            <i class="fa fa-search" aria-hidden="true"></i>
          </button>
        </div>
        <p id="productos-busqueda-ayuda" class="productos-search-hint">Las coincidencias de código aparecen primero.</p>
      </form>
      <div class="productos-importaciones" role="group" aria-label="Actualizaciones desde Excel">
        <span class="productos-importaciones-label">Actualizar desde Excel</span>
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#actualizar-precios">
          <i class="fa-solid fa-tags" aria-hidden="true"></i> Precios
        </button>
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#actualizar-clientes">
          <i class="fa-solid fa-users" aria-hidden="true"></i> Clientes
        </button>
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#actualizar-categoriasExcel">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Categorías
        </button>
      </div>
    </div>

    <p id="productos-estado" class="productos-estado" role="status" aria-live="polite"></p>
    <div id="productos-feedback" class="productos-feedback" role="status" aria-live="polite" hidden
         data-sin-resultados-categoria="{{ !empty($sinResultadosCategoria) ? '1' : '0' }}" data-hay-alternativas="{{ !empty($hayAlternativas) ? '1' : '0' }}"></div>
    <div id="productos-alternativas-titulo" class="productos-alternativas-titulo" role="heading" aria-level="2" hidden></div>

    <div class="card-body productos-listado">

      <table class="table productos-table" role="table" aria-label="Listado de productos">
        <caption class="visually-hidden">Productos con su código, imagen, categoría y acciones de administración.</caption>
        <thead>
          <tr role="row">
            <th scope="col">Orden</th>
            <th scope="col">Código</th>
            <th scope="col">Portada</th>
            <th scope="col">Nombre</th>
            <th scope="col">Categoría</th>
            <th scope="col">Destacada</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
         <tbody id='productos-contenedor' role="rowgroup">

          @include('backend/dash-productos-listado')

        </tbody>
      </table>

  </div>
   <div class='card-footer productos-footer' @if(!$productos->hasPages()) hidden @endif>
     <div id="productos-paginacion">
       {{$productos->links('pagination::bootstrap-5')}}
    </div>
  </div>
</div>

{{-- MODAL CONFIRMAR ELIMINACION --}}
<div class="modal fade" id="confirmar-eliminar-producto" tabindex="-1" aria-labelledby="confirmar-eliminar-titulo" aria-describedby="confirmar-eliminar-desc" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content confirmar-eliminar">
      <div class="modal-body text-center">
        <div class="confirmar-eliminar__icono"><i class="fa-solid fa-trash-can"></i></div>
        <h5 class="confirmar-eliminar__titulo" id="confirmar-eliminar-titulo">¿Eliminar este producto?</h5>
        <p class="confirmar-eliminar__producto" id="confirmar-eliminar-producto-nombre"></p>
        <p class="confirmar-eliminar__texto" id="confirmar-eliminar-desc">Esta acción no se puede deshacer. Se perderán sus datos y sus imágenes.</p>
      </div>
      <div class="modal-footer confirmar-eliminar__acciones">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="confirmar-eliminar-cancelar">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmar-eliminar-aceptar"><i class="fa-solid fa-trash-can me-1"></i> Sí, eliminar</button>
      </div>
    </div>
  </div>
</div>

@include('backend.partials.exportar-excel-modal', [
    'seleccionadas' => is_numeric($categoria_id ?? null) ? [(int) $categoria_id] : null,
    'fuenteBusqueda' => '#productos-busqueda',
])

{{-- MODAL PRECIOS --}}
<div class="modal fade" id="actualizar-precios" tabindex="-1" aria-labelledby="actualizar-precios-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="actualizar-precios-titulo">Actualización de precios</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Los productos de la web deben tener el mismo código que en el Excel. Podés modificarlo en cada producto. El orden de las columnas debe coincidir con el modelo.</p>
        <p class="mb-0">Al terminar vas a ver cuántos productos se actualizaron y cuáles códigos no, con un Excel para descargarlos.</p>
        <form id='actualizar-precios-form' action="{{ route('actualizar.precios') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista-precios" class="form-label">Listado de precios (Excel)</label>
          <input class="form-control" type="file" id="lista-precios" name="lista" accept=".xlsx,.xls,.csv" required>
        </form>
        <div class='d-flex mt-2'>
          <a href="{{ asset('archivos/ejemploExcel.xlsx')}}" download class="btn btn-outline-secondary"><i class="fa-solid fa-download" aria-hidden="true"></i> Descargar modelo</a>
        </div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-precios-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>




<div class="modal fade" id="actualizar-clientes" tabindex="-1" aria-labelledby="actualizar-clientes-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="actualizar-clientes-titulo">Actualización de clientes</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id='actualizar-clientes-form' action="{{ route('actualizar.clientes') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista-clientes" class="form-label">Listado de clientes (Excel)</label>
          <input class="form-control" type="file" id="lista-clientes" name="lista" accept=".xlsx,.xls,.csv" required>
        </form>

      </div>
      <div class="modal-footer d-flex justify-content-center">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-clientes-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>

<div class="modal fade" id="actualizar-categoriasExcel" tabindex="-1" aria-labelledby="actualizar-categorias-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="actualizar-categorias-titulo">Actualización de categorías</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id='actualizar-categorias-form' action="{{ route('actualizar.categoriasExcel') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista-categorias" class="form-label">Listado de categorías (Excel)</label>
          <input class="form-control" type="file" id="lista-categorias" name="lista" accept=".xlsx,.xls,.csv" required>
        </form>

      </div>
      <div class="modal-footer d-flex justify-content-center">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-categorias-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>
</section>
@endsection

@section('styles')
@unless (request()->getQueryString() || session()->has('success') || session()->has('warning') || session()->has('precios_reporte'))
<script>
  // Al entrar al listado sin filtros (el sidebar, «volver», una redirección),
  // retomarlo como se lo dejó en esta pestaña. Va en el <head>, antes de que
  // se dibuje la página. Los filtros los guarda guardarFiltros() más abajo.
  try {
    var filtrosGuardados = sessionStorage.getItem('bmh.admin.productos.filtros');
    if (filtrosGuardados) location.replace(location.pathname + '?' + filtrosGuardados);
  } catch (e) { /* sin sessionStorage: se abre sin filtros */ }
</script>
@endunless
<link rel="stylesheet" href="{{ asset('css/dashboard-productos.css') }}?v=4">
@endsection

@section('script')
<script>
  // Confirmacion al eliminar producto (delegado: el listado se recarga por AJAX)
  (function() {
    var modalEl = document.getElementById('confirmar-eliminar-producto');
    var modal = new bootstrap.Modal(modalEl);
    var formPendiente = null;
    var nombreEl = document.getElementById('confirmar-eliminar-producto-nombre');
    var btnAceptar = document.getElementById('confirmar-eliminar-aceptar');

    document.addEventListener('submit', function(e) {
      var form = e.target.closest ? e.target.closest('.form-eliminar-producto') : null;
      if (!form) return;
      e.preventDefault();
      formPendiente = form;
      var nombre = form.dataset.nombre || '';
      var codigo = form.dataset.codigo || '';
      nombreEl.textContent = codigo ? nombre + ' (' + codigo + ')' : nombre;
      btnAceptar.disabled = false;
      modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function() {
      document.getElementById('confirmar-eliminar-cancelar').focus();
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
      if (!btnAceptar.disabled) formPendiente = null;
    });

    btnAceptar.addEventListener('click', function() {
      if (!formPendiente) return;
      btnAceptar.disabled = true;
      formPendiente.submit();
    });
  })();

  $(document).ready(function() {
    var solicitudProductos;
    var esperaBusqueda;

    function actualizarFeedback(data) {
        var feedback = $('#productos-feedback');
        var alternativasTitulo = $('#productos-alternativas-titulo');

        if (!data.sinResultadosCategoria) {
            feedback.prop('hidden', true).empty();
            alternativasTitulo.prop('hidden', true).empty();
            return;
        }

        var categoria = $('#categoria_id option:selected').text();
        var detalle = data.hayAlternativas
            ? 'No hay coincidencias dentro de <strong>' + $('<div>').text(categoria).html() + '</strong>. Revisá las alternativas que aparecen debajo.'
            : 'No hay coincidencias dentro de <strong>' + $('<div>').text(categoria).html() + '</strong>. Probá con otro código o término de búsqueda.';
        var contenido = '<div class="productos-feedback__notice">' +
            '<span class="productos-feedback__icon"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i></span>' +
            '<div>' +
                '<span class="productos-feedback__eyebrow">Filtro de categoría</span>' +
                '<p class="productos-feedback__title">No se encontraron resultados para esta categoría</p>' +
                '<p class="productos-feedback__copy">' + detalle + '</p>' +
            '</div>' +
        '</div>';

        feedback.html(contenido).prop('hidden', false);

        if (data.hayAlternativas) {
            alternativasTitulo.html(
                '<h2 class="productos-alternativas-titulo__heading">Otras coincidencias</h2>' +
                '<p class="productos-alternativas-titulo__copy">Encontramos productos relacionados en otras categorías.</p>'
            ).prop('hidden', false);
        } else {
            alternativasTitulo.prop('hidden', true).empty();
        }
    }

    // Con texto en el buscador, el modal del Excel ofrece descargar sólo eso.
    function actualizarExcel(valor) {
        var boton = document.getElementById('btn-exportar-excel');
        var texto = (valor || '').trim();
        boton.querySelector('[data-rol="texto"]').textContent = texto ? 'Descargar Excel filtrado' : 'Descargar Excel';
        boton.title = texto
            ? boton.dataset.titulo + ' que coinciden con «' + texto + '»'
            : boton.dataset.titulo;
    }

    /*
     * Filtros del listado (categoría, búsqueda y página). La URL los refleja,
     * así recargar o volver «atrás» los conserva, y una copia queda en
     * sessionStorage para retomarlos al volver desde el sidebar o después de
     * editar (ver el script del <head>). sessionStorage es de la pestaña: al
     * cerrarla, los filtros se olvidan.
     */
    var CLAVE_FILTROS = 'bmh.admin.productos.filtros';

    function guardarFiltros(pagina) {
        var params = new URLSearchParams();
        var categoria = $('#categoria_id').val();
        var texto = ($('#productos-busqueda').val() || '').trim();
        if (categoria && categoria !== 'todos') params.set('categoria_id', categoria);
        if (texto) params.set('q', texto);
        if (Number(pagina) > 1) params.set('page', pagina);

        var query = params.toString();
        history.replaceState(history.state, '', location.pathname + (query ? '?' + query : ''));
        try {
            if (query) sessionStorage.setItem(CLAVE_FILTROS, query);
            else sessionStorage.removeItem(CLAVE_FILTROS);
        } catch (e) { /* sin sessionStorage: quedan sólo en la URL */ }

        $('#filtro-q').val(texto);
        $('[data-borrar-filtros]').prop('hidden', !(params.has('categoria_id') || params.has('q')));
    }

    $('[data-cerrar-reporte]').on('click', function() {
      $(this).closest('[data-precios-reporte]').remove();
    });

    $('[data-borrar-filtros]').on('click', function() {
        try { sessionStorage.removeItem(CLAVE_FILTROS); } catch (e) {}
    });

    function buscarProductos(valor, pagina) {
        actualizarExcel(valor);
        clearTimeout(esperaBusqueda);
        if (solicitudProductos) {
            solicitudProductos.abort();
        }

        $('#productos-contenedor').attr('aria-busy', 'true');
        $('#productos-estado').text('Buscando productos…');

        solicitudProductos = $.ajax({
            url: "{{ route('dashboard.buscar.producto') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                valor: valor,
                categoria_id: $('#categoria_id').val(),
                page: pagina || 1,
            },
            success: function(response) {
                guardarFiltros(pagina || 1);
                $('#productos-contenedor').html(response.html);
                $('#productos-paginacion').html(response.pagination);
                $('#productos-paginacion').closest('.card-footer').prop('hidden', response.pagination.trim().length === 0);
                actualizarFeedback(response);
                $('#productos-contenedor').attr('aria-busy', 'false');
                $('#productos-estado').text('Listado actualizado.');
            },
            error: function(xhr) {
                if (xhr.statusText !== 'abort') {
                    $('#productos-contenedor').attr('aria-busy', 'false');
                    $('#productos-estado').text('No se pudo actualizar el listado. Volvé a intentar.');
                    console.error(xhr.responseText);
                }
            }
        });
    }

    // Al cargar: la URL queda prolija, el Excel y el aviso de «sin resultados
    // en esta categoría» reflejan lo que vino filtrado desde el servidor.
    guardarFiltros({{ (int) $productos->currentPage() }});
    actualizarExcel($('#productos-busqueda').val());
    var avisoInicial = $('#productos-feedback');
    if (avisoInicial.data('sin-resultados-categoria') == 1) {
        actualizarFeedback({ sinResultadosCategoria: true, hayAlternativas: avisoInicial.data('hay-alternativas') == 1 });
    }

    // BUSCADOR
    $('.search-input').on('input', function() {
        var valor = $(this).val();
        clearTimeout(esperaBusqueda);
        if (solicitudProductos) solicitudProductos.abort();
        esperaBusqueda = setTimeout(function() { buscarProductos(valor, 1); }, 250);
    });

    $('#productos-buscar-form').on('submit', function(e) {
        e.preventDefault();
        buscarProductos($('#productos-busqueda').val(), 1);
    });

    // El paginado del resultado AJAX conserva texto y categoría.
    $(document).on('click', '#productos-paginacion a', function(e) {
        e.preventDefault();
        var pagina = new URL(this.href, window.location.origin).searchParams.get('page') || 1;
        buscarProductos($('.search-input').val(), pagina);
    });

    // Delegación de eventos para los checkboxes
    $(document).on('click', '.categoriaCheckbox', function() {
        var productoID = $(this).data('id');
        var estadoDestacada = $(this).is(':checked');

        $.ajax({
          url: "{{ route('producto.destacada') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              producto_id: productoID,
              destacada: estadoDestacada
          },
          success: function(response) {
              console.log(response.mensaje);
          },
          error: function(xhr) {
              console.error(xhr.responseText);
          }
        });
      });
  });

</script>


@endsection
