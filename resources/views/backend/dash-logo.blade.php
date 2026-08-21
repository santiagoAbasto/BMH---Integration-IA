@extends('layouts.plantilla-back')

@section('content')

    <h1 class='mb-4'>CONTENIDO</h1>

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
      Editar contenido
    </div>
    <div class="card-body">

      <form id='formulario' action="{{route('logo.update')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        <label class='mt-2 mb-4 form-label' for="" style='font-size:20px;font-weight:500;'>Logos</label><br>
        <div class='row'>
          <div class='col-6'>
            <div class="mb-3">
              <label for="imagen" class="form-label">Principal <span class='recomendada'>(recomendada 360x90 px)</span></label>
              <input class="form-control preview" data-form-id="{{'imagen'.$logo->id}}" type="file" id="imagen" name='imagen' accept="image/*">
            </div>
            <div class='d-flex justify-content-center' style='max-height:50vh;background-color:#d7d7d7;'>
                <img id="{{'imagen'.$logo->id}}" src="{{asset('imagenes/'.$logo->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
            </div>
          </div>
          <div class='col-6'>
            <div class="mb-3">
              <label for="imagen2" class="form-label">Secundario <span class='recomendada'>(recomendada 330x170 px)</span></label>
              <input class="form-control preview" data-form-id="{{'imagen'.$logo2->id}}" type="file" id="imagen2" name='imagen2' accept="image/*">
            </div>
            <div class='d-flex justify-content-center' style='max-height:50vh;'>
              <img id="{{'imagen'.$logo2->id}}" src="{{asset('imagenes/'.$logo2->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
            </div>
          </div>
        </div>

        

        

          {{-- <hr>
          <label class='mt-2 mb-4 form-label' for="" style='font-size:20px;font-weight:500;'>Baner</label><br>
        
        <div class="mb-3">
          <label for="baner_texto" class="form-label">Texto del baner</label>
          <input class="form-control" type="text" name='baner_texto' value='{{$baner->baner_texto}}'>
        </div>

        <div class="mb-3">
          <label for="baner" class="form-label">Imagen o video<span class='recomendada'> (recomendada 1366x730 px)</span></label>
          <input class="form-control" type="file" name='baner' accept="file/*">
        </div> --}}

        <hr>
        <label class='mt-2 mb-4 form-label' for="" style='font-size:20px;font-weight:500;'>Empresa</label><br>

        <div class="card">
          <!--<div class="card-header">-->
            <!--<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear-slider"><i class="fa-solid fa-plus"></i>  CREAR</button>-->
          <!--</div>-->
          <div class="card-body">
      
      
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Orden</th>
                  <th>Thumbnail</th>
                  <th>Tipo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
      
                  <tr>
                    <td>{{$nosotros_slider->orden}}</td>
                    <td><img src="{{asset('imagenes/'.($nosotros_slider->tipo == 'video' ? 'video-placeholder.png' : $nosotros_slider->path))}}" class="img-thumbnail" style="max-width: 100px;"></td>
                    <td>{{$nosotros_slider->tipo == 'video' ? 'video' : 'imagen'}}</td>
                    
                    <td>
                      <div class='d-flex'>
                        <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$nosotros_slider->id}}">
                          <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm delete-slider" data-id='{{$nosotros_slider->id}}'><i class="fa-solid fa-trash-can"></i></button>

                      </div>
                      
                    </td>
                  </tr>
      
              </tbody>
            </table>
          </div>
        </div>
        
        <label class='mt-3' for="titulo">Título</label><br>
        <input type="text" name="titulo" required class='form-control' value='{{$nosotros_slider->baner_texto}}'>

        <label class='mt-3' for="info">Descripción</label><br>
        <textarea class='summernote-text' id="summernote1" name="info">{!!$nosotros_slider->baner_texto_2 !!}</textarea>

        {{-- <hr class='mt-4'>
        <label class='mt-2 mb-4 form-label' for="" style='font-size:20px;font-weight:500;'>Kovea</label><br>
        <label for="imagen_kovea" class="form-label">Principal <span class='recomendada'>(recomendada 360x90 px)</span></label>
        <input class="form-control preview" data-form-id="{{'imagen'.$logo->id}}" type="file" id="imagen" name='imagen_kovea' accept="image/*">

        <div class='d-flex justify-content-center' style='max-height:50vh;'>
            <img id="{{'imagen'.$nosotros_contenido->imagen_file_kovea}}" src="{{asset('imagenes/'.$nosotros_contenido->imagen_file_kovea)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
        </div>
        <label class='mt-3' for="titulo_kovea">Título</label><br>
        <input type="text" name="titulo_kovea" required class='form-control' value='{{$nosotros_contenido->titulo_kovea}}'>

        <label class='mt-3' for="texto_kovea">Descripción</label><br>
        <textarea class='summernote-text' id="summernote1" name="texto_kovea">{!!$nosotros_contenido->texto_kovea!!}</textarea> --}}

        <button type="submit" data-form-id='formulario' class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
      </form>
    </div>
  </div>

  {{-- MODAL CREAR --}}
  <div class="modal fade" id="crear-slider" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          Crear slider
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id='agregar' class='mb-4 loading' action="{{route('imagen.store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="sector" value="nosotros-slider">
        
            
            <div class="row g-3 align-items-center">
              <div class="col-2 ">
                <label for="orden" class="form-label">Orden</label>
                <input type="text" class="form-control" name='orden' value='aa' required>
              </div>
              
              <div class="col-10">
                <label for="imagenes" class="form-label">Agregar imagen o video <span class='recomendada'>(recomendada 790x650 px)</span></label>
                  <input class="form-control" type="file" id="imagenes" name="imagenes[]" multiple required>
        
              </div>
              
              
            </div>
        
        
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button  data-form-id="agregar" type="submit" class="btn btn-primary submit" >Agregar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modales para ver las imágenes -->
      
    {{-- MODALES --}}
    <div class="modal fade" id="{{'editar'.$nosotros_slider->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            Editar slider
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id='{{'form'.$nosotros_slider->id}}'  action="{{route('slider.update',['id'=>$nosotros_slider->id])}}" method="post" enctype="multipart/form-data">
              @csrf
              @method('put')


              <div class="mb-3">
                <label for="orden" class="form-label">Orden</label>
                <input type="text" class="form-control" name='orden' value='{{$nosotros_slider->orden}}' required>
              </div>

              

              <div class="mb-3">
                <label for="imagen" class="form-label">Imagen <span class='recomendada'>(recomendada 790x650 px)</span></label>
                <input class="form-control preview" data-form-id="{{$nosotros_slider->tipo.$nosotros_slider->id}}" type="file" id="imagen" name='imagen'>
              </div>

              {{-- PREVIEW --}}
              <div class='d-flex justify-content-center' style='max-height:50vh;'>

                <img class='{{$nosotros_slider->tipo=='imagen' ? '' : 'hidden'}}' id="{{'imagen'.$nosotros_slider->id}}" src="{{asset('imagenes/'.$nosotros_slider->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
                {{-- <video class='{{$imagen->tipo=='video' ? '' : 'hidden'}}' id="{{'video'.$imagen->id}}" src="{{asset('imagenes/'.$imagen->path)}}" controls style="max-width: 100%;" ></video> --}}
                
              </div>
              

            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button  data-form-id="{{'form'.$nosotros_slider->id}}" type="submit" class="btn btn-primary submit">Actualizar</button>
          </div>
        </div>
      </div>
    </div>
  
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
          height: 120,
          toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            // ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            // ['table', ['table']],
            // ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
          ]
      });
    });

    $('.delete-slider').click(function() {
      
      var imagenId = $(this).data('id');
      $.ajax({
        url: "{{ route('imagen.delete') }}",
        type: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}',
            id: imagenId
        },
        success: function(response) {
            // Manejo de respuesta exitosa
            console.log(response.mensaje);
            location.reload()
        },
        error: function(xhr) {
            // Manejo de error
            console.error(xhr.responseText);
            location.reload()
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

