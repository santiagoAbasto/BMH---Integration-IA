@extends('layouts.plantilla-back')

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Contenido del sitio" title="Novedades" description="Las notas que se publican en la sección Novedades del sitio.">
    <x-slot:actions>
      <a href="{{ route('novedad.crear') }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Crear novedad</a>
    </x-slot:actions>
  </x-osole::admin-page-header.v1>

  @if(session('success'))
      <div class="alert alert-success" role="status">
          {{ session('success') }}
      </div>
  @endif
  @if(session('warning'))
      <div class="alert alert-danger" role="alert">
          {{ session('warning') }}
      </div>
  @endif

  <div class="card">
    <div class="card-header">
      <div class="adm-card-titulo">
        <h2>Todas las novedades</h2>
        <p>Las destacadas aparecen en el inicio del sitio.</p>
      </div>
    </div>
    <div class="card-body">

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Orden</th>
            <th>Portada</th>
            <th>Etiqueta</th>
            <th>Título</th>
            <th>Epígrafe</th>
            <th>Destacada</th>
            <th><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>

          @foreach ($novedades as $novedad)
            <tr>
              <td>{{$novedad->orden}}</td>
              <td><img src="{{asset('imagenes/'.$novedad->portada)}}" class="adm-miniatura" alt="" loading="lazy"></td>
              <td>{{$novedad->etiqueta}}</td>
              <td class="fw-semibold">{{$novedad->titulo}}</td>
              <td>{{$novedad->epigrafe}}</td>
              <td>
                  <label class="adm-switch" for="novedad{{$novedad->id}}"><input class='categoriaCheckbox' type="checkbox" role="switch" data-id="{{$novedad->id}}" id="novedad{{$novedad->id}}" name="destacada" value="1" {{ $novedad->destacada ? 'checked' : '' }}><span class="visually-hidden">Destacar {{ $novedad->titulo }}</span></label>
              </td>
              <td>
                  <div class='adm-acciones'>
                  <a href='{{route('novedad.editar', ['id' => $novedad->id])}}' class="btn btn-outline-primary btn-sm" aria-label="Editar"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></a>
                  <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$novedad->id}}" aria-label="Eliminar">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                  </button>
                  
                  </div>
                  
              </td>
            </tr>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$novedad->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body adm-confirmar-cuerpo"><span class="adm-confirmar__icono" aria-hidden="true"><i class="fa-solid fa-trash-can"></i></span>
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