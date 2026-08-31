@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Anuncio</h1>
  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif


      <form id='formulario' action="{{route('anuncio.update')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        {{-- <div class="mb-3">
          <label for="imagen" class="form-label">Imagen <span class='recomendada'>(recomendada 600x400 px)</span></label>
          <input class="form-control preview" data-form-id="{{'imagen'.$nosotros_contenido->id}}" type="file" id="imagen" name='imagen' accept="image/*">
        </div>
        <div class='d-flex justify-content-center' style='max-height:50vh;'>
          <img id="{{'imagen'.$nosotros_contenido->id}}" src="{{asset('imagenes/'.$nosotros_contenido->imagen_file)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">

        </div> --}}
        <div class='d-flex justify-content-between' style='align-items:center;'>
            <label class='my-3' for="contenido">Contenido</label>
            <small class="text-muted">Medida sugerida para la imagen: 700x500 px.</small>
            <div>
                <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault" {{$anuncio->mostrar ? 'checked' : ''}}>
                <label class="form-check-label" for="flexSwitchCheckDefault">Mostrar</label>
            </div>
                
            </div>
        </div>
        
        <textarea class='summernote-text' id="summernote1" name="contenido">{!!$anuncio->contenido!!}</textarea>

        <button type="submit" data-form-id='formulario' class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
      </form>
  
  
@endsection

@section('script')
<script>
  // Check validity summernote
  document.getElementById('formulario').addEventListener('submit', function(event) {
        var summernoteFields = document.querySelectorAll('.summernote-text');

        for (var i = 0; i < summernoteFields.length; i++) {
            var content = $(summernoteFields[i]).summernote('code').trim(); // Obtener el contenido y eliminar espacios en blanco al inicio y al final
            if (!content) {
                alert('Por favor, complete todos los campos');
                event.preventDefault(); // Detener el envío del formulario
                return;
            }
        }
    });

  // Summernote
  $(document).ready(function() {
      $('.summernote-text').each(function() {
        
        // Inicializar Summernote para este editor
        $(this).summernote({
            placeholder: '',
            tabsize: 2,
            height: 600,
            toolbar: [
              ['style', ['style']],
              ['font', ['bold', 'underline', 'clear']],
              // ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph']],
              // ['table', ['table']],
              ['insert', ['link', 'picture', 'video']],
              ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });

    $('#flexSwitchCheckDefault').change(function() {
        var estado = $(this).is(':checked');
        console.log(estado)

        $.ajax({
          url: "{{ route('anuncio.mostrar') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              destacada: estado
          },
          success: function(response) {
              // Manejo de respuesta exitosa
            //   console.log(response.mensaje);
          },
          error: function(xhr) {
              // Manejo de error
              console.error(xhr.responseText);
          }
        });
      });
  });

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
</script>
@endsection
