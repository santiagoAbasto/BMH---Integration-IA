@extends('layouts.plantilla-back')

@section('content')

  <h1 class='mb-4'>Medidas</h1>

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

  <?php
  use App\Models\Medida;
  ?>

  <div class="card">
    <div class="card-header">
      <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success"><i class="fa-solid fa-plus"></i>  CREAR</button>

      {{-- MODAL CREAR --}}
      <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              Crear medida
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form class='pb-3' id='formulario-crear' action="{{route('medida.store')}}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class='row'>
                    <div class="col-12 mb-3">
                        <label for="orden" class="form-label">Codigo</label>
                        <input type="number" class="form-control" name='codigo' min="0" required>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="orden" class="form-label">Descripcion</label>
                        <input type="text" class="form-control" name='descripcion' required>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="orden" class="form-label">Cantidad minima</label>
                        <input type="number" class="form-control" name='cantidad' min="0"  required>
                    </div>
                    
                    
                </div>

              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button  data-form-id="formulario-crear" type="submit" class="btn btn-primary submit" >Agregar</button> 
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Codigo</th>
            <th>Descripcion</th>
            <th>Cantidad</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($medidas as $medida)
            <tr>
              <td>{{$medida->codigo}}</td>
              <td>{{$medida->descripcion}}</td>
              <td>{{$medida->cantidad}}</td>

              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="{{'#editar'.$medida->id}}"><i class="fa-regular fa-pen-to-square"></i></button>
                  <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$medida->id}}"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                
              </td>
            </tr>

            
          @endforeach

        </tbody>
      </table>

  </div>
</div>

@foreach ($medidas as $medida)
  {{-- MODAL EDITAR --}}
  <div class="modal fade" id="editar{{$medida->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          Editar medida
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id='agregar{{$medida->id}}' class='mb-4 loading' action="{{route('medida.update', ['id' => $medida->id])}}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('put')    
            
            <div class="row g-3 align-items-center">
              <div class="col-12 ">
                <label for="orden" class="form-label">Codigo</label>
                <input type="number" class="form-control" name='codigo' value='{{$medida->codigo}}' required>
              </div>
              <div class="col-12">
                <label for="nombre" class="form-label">Descripcion</label>
                <input type="text" class="form-control" name='descripcion' min="0"  value='{{$medida->descripcion}}' required>
              </div>
              <div class="col-12 ">
                <label for="orden" class="form-label">Cantidad minima</label>
                <input type="number" class="form-control" name='cantidad' min="0"  value='{{$medida->cantidad}}' required>
              </div>
            
            
              
              
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button data-form-id="agregar{{$medida->id}}" type="submit" class="btn btn-primary submit">Actualizar</button> 
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL ELIMINAR --}}
  <div class="modal fade" id="{{'eliminar'.$medida->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body fs-4 d-flex justify-content-center" style='text-align:center;'>
          ¿Desea eliminar la Medida {{ucfirst($medida->descripcion)}}?<br>
        </div>
        <div class="modal-footer d-flex justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <form action="{{ route('medida.delete', ['id' => $medida->id]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$medida->id}}">Eliminar</button>
          </form>
          
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection

@section('script')
  <script>

    // CARACTERÍSTICAS
    $(document).ready(function() {
      $('.categoriaCheckbox').click(function() {
        
        var productoID = $(this).data('id');
        var estadoDestacada = $(this).is(':checked');
        $.ajax({
          url: "{{ route('categoria.destacada') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              producto_id: productoID,
              destacada: estadoDestacada
          },
          success: function(response) {
              // Manejo de respuesta exitosa
              console.log(response.mensaje);
          },
          error: function(xhr) {
              // Manejo de error
              console.error(xhr.responseText);
          }
        });
      });
        
    });
  </script>
@endsection