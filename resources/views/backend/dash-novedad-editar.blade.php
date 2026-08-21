@extends('layouts.plantilla-back')

@section('styles')
  <style>
    .seleccionada{
      border:4px solid #22BE4A;
      
    }
    .imagen-producto{
      position: relative;
    }
    .middle {
      transition: .2s ease;
      opacity: 0;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      -ms-transform: translate(-50%, -50%);
      text-align: center;
    }

    .imagen-producto:hover .image {
      opacity: 0.3;
    }

    .imagen-producto:hover .middle {
      opacity: 1;
    }

    .text {
      /* background-color: #04AA6D; */
      color: white;
      font-size: 16px;
      padding: 16px 32px;
    }
  </style>
  
@endsection

@section('content')
<h1 class='mb-4'>Editar novedad</h1>
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
    </div>
    <div class="card-body">

      <form class='pb-3' id='actualizar' action="{{route('novedad.update', ['id' => $novedad->id])}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class='row'>
            <div class="mb-3 col-9">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" name='titulo' value='{{$novedad->titulo}}' required>
            </div>

            <div class="col-3 mb-3">
                <label for="orden" class="form-label">Orden</label>
                <input type="text" class="form-control" name='orden' value='{{$novedad->orden}}' required>
            </div>
        </div>

        <div class='row'>
            <div class="mb-3 col-9">
                <label for="epigrafe" class="form-label">Epígrafe</label>
                <input type="text" class="form-control" name='epigrafe' value='{{$novedad->epigrafe}}' required>
            </div>

            <div class="col-3 mb-6">
                <label for="etiqueta" class="form-label">Etiqueta</label>
                <input type="text" class="form-control" name='etiqueta' value='{{$novedad->etiqueta}}' required>
            </div>
        </div>

        <div class="mb-3">
            <label for="texto">Texto</label><br>
            <textarea id="summernote" class='summernote-producto' name="texto">{!!$novedad->texto!!}</textarea>
        </div>

        <div class="mb-3">
            <label for="imagen" class="form-label">Imagen</label>
            <input class="form-control preview" data-form-id="{{'imagen'.$novedad->id}}" type="file" id="imagen" name='imagen' accept="image/*">
          </div>
          <div class='d-flex justify-content-center' style='max-height:50vh;'>
            <img id="{{'imagen'.$novedad->id}}" src="{{asset('imagenes/'.$novedad->portada)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">

          </div>

        <button  data-form-id="actualizar" type="submit" class="btn btn-primary submit" style='float:right;'>Actualizar</button>
        
    </form>
    </div>
  </div>
@endsection

@section('script')

<script>

  $(document).ready(function() {
      $('.ver-imagen').click(function(event) {
          document.querySelector('.visualizador').src='imagenes/' + event.target.getAttribute('data-path')
          event.stopPropagation()
          
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

    // Editores ricos

    $(document).ready(function() {
      $('.summernote-producto').each(function() {
        // Obtener el ID único del producto
        var idProducto = $(this).attr('id').replace('summernote', '');
        
        // Inicializar Summernote para este editor
        $(this).summernote({
            placeholder: '',
            tabsize: 2,
            height: 120,
            toolbar: [
              ['style', ['style']],
              ['font', ['bold', 'underline', 'clear']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph']],
              // ['table', ['table']],
              // ['insert', ['link', 'picture', 'video']],
              ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });

    // Ajax imagenes producto
    $(document).ready(function() {
    $('.eliminar').click(function() {
        console.log('aisd')
        var imagenId = $(this).data('id');
        $.ajax({
            url: "{{ route('imagen.delete') }}",
            type: 'delete',
            data: {
                _token: '{{ csrf_token() }}',
                id: imagenId,
                tipo:'producto'
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
});
</script>

@endsection