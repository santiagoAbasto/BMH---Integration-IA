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
  
  @php
      if (isset($categoria_id) && $categoria_id && $categoria_id != 'todos') {
          $catName = $categorias->firstWhere('id', $categoria_id)->nombre ?? 'la categoría';
          $catLabel = ' de ' . $catName;
      } else {
          $catLabel = ' de todos los productos';
      }
  @endphp

  <div class="productos-tools">
    <form method="GET" action="{{ route('dashboard.productos') }}" class="productos-filtro">
      <label for="categoria_id" class="form-label">Filtrar por categoría</label>
      <select name="categoria_id" id="categoria_id" class="form-select" onchange="this.form.submit()">
          <option value="todos">Todas</option>
          @foreach($categorias as $categoria)
              <option value="{{ $categoria->id }}" {{ (isset($categoria_id) && $categoria_id == $categoria->id) ? 'selected' : '' }}>
                  {{ $categoria->nombre }}
              </option>
          @endforeach
      </select>
    </form>

    <div class="productos-descargas">
    {{-- Descarga lo que se ve filtrado: la categoría y, si hay, el texto del buscador (lo agrega el script de abajo). --}}
    <a href="{{ route('dashboard.productos.exportar', ['categoria_id' => $categoria_id ?? 'todos']) }}" id="btn-exportar-excel" class="btn btn-outline-success"
       title="Descargar Excel{{ $catLabel }}" data-titulo="Descargar Excel{{ $catLabel }}">
      <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
      <span data-rol="texto">Descargar Excel</span>
    </a>
    <a href="{{ route('dashboard.productos.excel.importar') }}" class="btn btn-outline-primary" title="Actualizar precio e imágenes de muchos productos con el Excel, con vista previa antes de aplicar">
      <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
      <span>Importar Excel</span>
    </a>
    <a href="{{ route('dashboard.imagenes') }}" class="btn btn-outline-secondary" title="Subir imágenes masivamente y ver sus códigos">
      <i class="fa-solid fa-images" aria-hidden="true"></i>
      <span>Imágenes</span>
    </a>

    {{-- Sólo con una categoría elegida: un ZIP con las imágenes nombradas por código. --}}
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
    @endif
    </div>
  </div>


    <div class="card productos-panel">
    <div class="card-header productos-toolbar">
      <form class="productos-buscador" role="search" id="productos-buscar-form">
        <label for="productos-busqueda" class="form-label">Buscar productos</label>
        <div class="productos-search-control">
          <input type="search" id="productos-busqueda" placeholder="Código, nombre o descripción" class="search-input" autocomplete="off" aria-controls="productos-contenedor" aria-describedby="productos-busqueda-ayuda">
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
    <div id="productos-feedback" class="productos-feedback" role="status" aria-live="polite" hidden></div>
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
<link rel="stylesheet" href="{{ asset('css/dashboard-productos.css') }}">
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

    // El Excel descarga lo mismo que muestra el listado: categoría + texto buscado.
    function actualizarExcel(valor) {
        var boton = document.getElementById('btn-exportar-excel');
        var url = new URL(boton.href, window.location.origin);
        var texto = (valor || '').trim();
        if (texto) {
            url.searchParams.set('q', texto);
        } else {
            url.searchParams.delete('q');
        }
        boton.href = url.toString();
        boton.querySelector('[data-rol="texto"]').textContent = texto ? 'Descargar Excel filtrado' : 'Descargar Excel';
        boton.title = texto
            ? boton.dataset.titulo + ' que coinciden con «' + texto + '»'
            : boton.dataset.titulo;
    }

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
