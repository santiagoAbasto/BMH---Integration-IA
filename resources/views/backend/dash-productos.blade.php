@extends('layouts.plantilla-back')

@section('content')

  <h1 class='mb-4'>Productos</h1>

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

  <div class="mb-3 d-flex align-items-center gap-2">
    <form method="GET" action="{{ route('dashboard.productos') }}" class="d-flex align-items-center">
      <label for="categoria_id" class="me-2 fw-bold">Filtrar por categoría:</label>
      <select name="categoria_id" id="categoria_id" class="form-select w-auto" onchange="this.form.submit()">
          <option value="todos">Todas</option>
          @foreach($categorias as $categoria)
              <option value="{{ $categoria->id }}" {{ (isset($categoria_id) && $categoria_id == $categoria->id) ? 'selected' : '' }}>
                  {{ $categoria->nombre }}
              </option>
          @endforeach
      </select>
    </form>

    <a href="#" id="btn-exportar-excel" class="btn btn-success d-flex align-items-center" onclick="exportarExcelProductos(event)">
      <img style="height:18px; padding-right:5px;" src="{{ asset('imagenes/iconos/excel.png') }}" alt="">
      <span id="label-exportar-excel">Descargar Excel{{ $catLabel }}</span>
    </a>
  </div>


    <div class="card">
    <div class="card-header d-flex justify-content-between">
      <div class='d-flex'>
        <a href='{{route('producto.create')}}'>
          <button type="button" class="btn btn-success"><i class="fa-solid fa-plus"></i>  CREAR</button>
        </a>
        <button type='button' style='height:38px;' class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#actualizar-precios">
          <div class='d-flex'>
            <img style='height:20px;padding-right:5px;' src="{{asset('imagenes/iconos/excel.png')}}" alt=""><p>Actualización masiva</p>
          </div>
          
        </button>

        <button type='button' style='height:38px;' class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#actualizar-clientes">
          <div class='d-flex'>
            <img style='height:20px;padding-right:5px;' src="{{asset('imagenes/iconos/excel.png')}}" alt=""><p>Actualización Clientes</p>
          </div>
          
        </button>

        <button type='button' style='height:38px;' class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#actualizar-categoriasExcel">
          <div class='d-flex'>
            <img style='height:20px;padding-right:5px;' src="{{asset('imagenes/iconos/excel.png')}}" alt=""><p>Actualización Categorias</p>
          </div>
          
        </button>
    </div>
        
    <div class="search-container">
      <input type="text" placeholder="Buscar..." class="search-input">
      <button id='buscador-productos' class="search-btn">
        <i class="fa fa-search"></i>
      </button>
    </div>
      
      
    </div>

    <div id="productos-feedback" class="productos-feedback" role="status" aria-live="polite" hidden></div>
    <div id="productos-alternativas-titulo" class="productos-alternativas-titulo" role="heading" aria-level="2" hidden></div>

    <div class="card-body">

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Orden</th>
            <th>Codigo</th>
            <th>Portada</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Destacada</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id='productos-contenedor'>

          @include('backend/dash-productos-listado')

        </tbody>
      </table>

  </div>
  <div class='card-footer'>
    <div id="productos-paginacion">
      {{$productos->links()}}
    </div>
  </div>
</div>

{{-- MODAL PRECIOS --}}
<div class="modal fade" id="actualizar-precios" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <p class='fs-4'>Actualización de precios</p>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body fs-4">
        <label>Aclaración: los productos de la web deben tener el mismo código de producto que en el excel. Puedes modificar este código en cada producto. El orden de las columnas debe coincidir con el del modelo.</label>
        <form id='actualizar-precios-form' action="{{ route('actualizar.precios') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista">Listado de precios (excel)</label>
          <input class="form-control" type="file" id="imagenes" name="lista" accept="file/*" required>
        </form>
        <div class='d-flex mt-2'>
          <a href="{{ asset('archivos/ejemploExcel.xlsx')}}" download><button type="button" class="btn btn-warning btn-sm me-1"><i class="fa-solid fa-download"></i> Descargar modelo</button></a>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-center">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-precios-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>




<div class="modal fade" id="actualizar-clientes" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <p class='fs-4'>Actualización de clientes</p>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body fs-4">
        <form id='actualizar-clientes-form' action="{{ route('actualizar.clientes') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista">Listado de clientes (excel)</label>
          <input class="form-control" type="file" id="imagenes" name="lista" accept="file/*" required>
        </form>

      </div>
      <div class="modal-footer d-flex justify-content-center">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-clientes-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>

<div class="modal fade" id="actualizar-categoriasExcel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
  <div class="modal-content">
      <div class="modal-header">
        <p class='fs-4'>Actualización de categoriasExcel</p>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body fs-4">
        <form id='actualizar-categorias-form' action="{{ route('actualizar.categoriasExcel') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="lista">Listado de precios (excel)</label>
          <input class="form-control" type="file" id="imagenes" name="lista" accept="file/*" required>
        </form>

      </div>
      <div class="modal-footer d-flex justify-content-center">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" data-form-id='actualizar-categorias-form' class="submit btn btn-primary">Actualizar</button>
      </div>
  </div>
  </div>
</div>
@endsection

@section('styles')
<style>
  .productos-feedback {
    align-items: stretch;
    background: linear-gradient(120deg, #f4fbfe 0%, #ffffff 72%);
    border: 1px solid #d8edf6;
    border-left: 4px solid #0098da;
    border-radius: 12px;
    box-shadow: 0 8px 22px rgba(24, 77, 105, .08);
    display: flex;
    justify-content: space-between;
    margin: 18px 20px 12px;
    max-width: 680px;
    overflow: hidden;
    width: calc(100% - 40px);
  }

  .productos-feedback[hidden] {
    display: none;
  }

  .productos-feedback__notice {
    align-items: center;
    display: flex;
    gap: 13px;
    min-width: 0;
    padding: 17px 20px;
  }

  .productos-feedback__icon {
    align-items: center;
    background: #e1f4fb;
    border: 1px solid #bce5f3;
    border-radius: 10px;
    color: #007fb7;
    display: inline-flex;
    flex: 0 0 38px;
    font-size: 16px;
    height: 38px;
    justify-content: center;
    width: 38px;
  }

  .productos-feedback__eyebrow {
    color: #6f8793;
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    margin-bottom: 3px;
    text-transform: uppercase;
  }

  .productos-feedback__title {
    color: #20323b;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
    margin: 0;
  }

  .productos-feedback__copy {
    color: #70838c;
    font-size: 12px;
    line-height: 1.45;
    margin: 4px 0 0;
  }

  .productos-alternativas-titulo {
    margin: 2px 20px 16px;
  }

  .productos-alternativas-titulo[hidden] {
    display: none;
  }

  .productos-alternativas-titulo__heading {
    color: #20323b;
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -.01em;
    line-height: 1.2;
    margin: 0;
}

  .productos-alternativas-titulo__copy {
    color: #70838c;
    font-size: 13px;
    font-weight: 500;
}

  @media (max-width: 767px) {
    .productos-feedback {
      display: block;
      margin-left: 12px;
      margin-right: 12px;
      width: calc(100% - 24px);
    }

    .productos-alternativas-titulo {
      margin-left: 12px;
      margin-right: 12px;
    }
  }
</style>
@endsection

@section('script')
<script src="https://cdn.tailwindcss.com"></script>
<script>
  // Preview de imagenes
  function mostrarImagenSeleccionada(input) {
      var imagenId = $(input).data('form-id');
      if (input.files && input.files[0]) {
          var lector = new FileReader();
          lector.onload = function (e) {
              // Muestra la imagen previa
              document.getElementById(imagenId).src = e.target.result;
              document.getElementById(imagenId).style.display = 'block';
          };
          // Lee el archivo de imagen como una URL de datos
          lector.readAsDataURL(input.files[0]);
      }
  }

  $(document).ready(function() {
    $('.preview').change(function() {
      mostrarImagenSeleccionada(this);
    });

    var solicitudProductos;

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

    function buscarProductos(valor, pagina) {
        if (solicitudProductos) {
            solicitudProductos.abort();
        }

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
                $('#productos-paginacion').closest('.card-footer').toggle(response.pagination.trim().length > 0);
                actualizarFeedback(response);
            },
            error: function(xhr) {
                if (xhr.statusText !== 'abort') {
                    console.error(xhr.responseText);
                }
            }
        });
    }

    // BUSCADOR
    $('.search-input').on('input', function() {
        buscarProductos($(this).val(), 1);
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

  function exportarExcelProductos(e) {
      e.preventDefault();
      var cat = document.getElementById('categoria_id').value;
      window.location.href = '{{ route('dashboard.productos.exportar') }}?categoria_id=' + encodeURIComponent(cat);
  }

  document.getElementById('categoria_id').addEventListener('change', function () {
      var label = document.getElementById('label-exportar-excel');
      label.textContent = this.value === 'todos'
          ? 'Descargar Excel de todos los productos'
          : 'Descargar Excel de ' + this.options[this.selectedIndex].text;
  });
</script>


@endsection
