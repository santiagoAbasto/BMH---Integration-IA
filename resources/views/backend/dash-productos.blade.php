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
  
  <form method="GET" action="{{ route('dashboard.productos') }}" class="mb-3 d-flex align-items-center">
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
    {{$productos->links()}}
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

    // BUSCADOR
    $('.search-input').on('input', function(e) {
        var valor = $('.search-input').val();
        $.ajax({
            url: "{{ route('dashboard.buscar.producto') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                valor: valor,
            },
            success: function(response) {
                $('#productos-contenedor').html(response);
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
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