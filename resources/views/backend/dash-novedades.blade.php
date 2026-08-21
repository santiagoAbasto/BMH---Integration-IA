@extends('layouts.plantilla-back')

@section('content')

  <h1 class='mb-4'>Novedades</h1>

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

  <div class="card">
    <div class="card-header">
      <a href='{{route('novedad.crear')}}'>
        <button type="button" class="btn btn-success"><i class="fa-solid fa-plus"></i> CREAR</button>
      </a>
    </div>
    <div class="card-body">

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Orden</th>
            <th>Portada</th>
            <th>Etiqueta</th>
            <th>Título</th>
            <th>Epígrafe</th>
            <th>Destacada</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($novedades as $novedad)
            <tr>
              <td>{{$novedad->orden}}</td>
              <td><img src="{{asset('imagenes/'.$novedad->portada)}}" class="img-thumbnail" style="max-width: 100px;"></td>
              <td>{{$novedad->etiqueta}}</td>
              <td>{{$novedad->titulo}}</td>
              <td>{{$novedad->epigrafe}}</td>
              <td>
                  <input class='categoriaCheckbox form-check-input' style='cursor: pointer;' type="checkbox" data-id="{{$novedad->id}}" id="novedad{{$novedad->id}}" name="destacada" value="1" {{ $novedad->destacada ? 'checked' : '' }}>
              </td>
              <td>
                  <div class='d-flex'>
                  <a href='{{route('novedad.editar', ['id' => $novedad->id])}}'>
                      <button type="button" class="btn btn-primary btn-sm me-1" ><i class="fa-regular fa-pen-to-square"></i></button>
                  </a>
                  <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$novedad->id}}">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                  
                  </div>
                  
              </td>
            </tr>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$novedad->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body fs-4 d-flex justify-content-center" style='text-align:center;'>
                    ¿Desea eliminar la novedad "{{$novedad->titulo}}"?
                  </div>
                  <div class="modal-footer d-flex justify-content-center">
                    <form action="{{ route('novedad.delete', ['id' => $novedad->id]) }}" method="POST">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$novedad->id}}">Eliminar</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>
          @endforeach

        </tbody>
      </table>
    </div>
  </div>


@endsection

@section('script')

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
  });

  // Destacada ajax
  $(document).ready(function() {
    $('.categoriaCheckbox').click(function() {
        
        var novedadId = $(this).data('id');
        var estadoDestacada = $(this).is(':checked');
        $.ajax({
            url: "{{ route('novedad.destacada') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                novedad_id: novedadId,
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