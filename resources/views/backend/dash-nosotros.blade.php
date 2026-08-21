@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Nosotros</h1>
  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif

  <div class="card">
    <div class="card-header">
      Editar contenido
    </div>
    <div class="card-body">

      <form id='formulario' action="{{route('nosotros.update')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        <div class="mb-3">
          <label for="portada" class="form-label">Imagen o video<span class='recomendada'>(recomendada 600x600 px)</span></label>
          <input class="form-control preview" data-form-id="{{'imagen'.$nosotros_contenido->id}}" type="file" id="imagen" name='portada' accept="file/*">
        </div>
        {{-- PREVIEW --}}
        <div class='d-flex justify-content-center' style='max-height:50vh;'>

          @if ($nosotros_portada->tipo == 'imagen')
          <img  id="{{'imagen'.$nosotros_portada->id}}" src="{{asset('imagenes/'.$nosotros_portada->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
          @else
          <video id="{{'video'.$nosotros_portada->id}}" src="{{asset('imagenes/'.$nosotros_portada->path)}}" controls style="max-width: 100%;" ></video>
            
          @endif
          
        </div>        

        <label class='mt-3' for="info">Descripción</label><br>
        <textarea class='summernote-text' id="summernote1" name="info">{!!$nosotros_contenido->info!!}</textarea>

        {{-- <label class='mt-3' for="info">Procesos</label><br>

        <div class="card" style="margin-left: 0px !important; margin-right: 0px !important">
          <div class="card-header">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear-slider"><i class="fa-solid fa-plus"></i>  CREAR</button>
          </div>
          <div class="card-body">
      
      
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Orden</th>
                  <th>Imagen</th>
                  <th>Titulo</th>
                  <th>Descripcion</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
      
                @foreach ($nosotros_procesos as $imagen)
                  <tr>
                    <td>{{$imagen->orden}}</td>
                    <td><img src="{{asset('imagenes/'.($imagen->tipo == 'video' ? 'video-placeholder.png' : $imagen->path))}}" class="img-thumbnail" style="max-width: 100px;"></td>
                    <td>{!!$imagen->baner_texto !!}</td>
                    <td>{!!$imagen->baner_texto_2!!}</td>

                    
                    <td>
                      <div class='d-flex'>
                        <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$imagen->id}}">
                          <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm delete-slider" data-id='{{$imagen->id}}'><i class="fa-solid fa-trash-can"></i></button>

                      </div>
                      
                    </td>
                  </tr>
                @endforeach
      
              </tbody>
            </table>
          </div>
        </div> --}}

        <label class='mt-3' for="mision">Misión</label><br>
        <textarea class='summernote-text'  id="summernote2" name="mision">{!!$nosotros_contenido->mision!!}</textarea>

        <label class='mt-3' for="vision">Objetivos</label><br>
        <textarea class='summernote-text'  id="summernote3" name="vision">{!!$nosotros_contenido->vision!!}</textarea>

        <label class='mt-3' for="valores">servicios</label><br>
        <textarea class='summernote-text'  id="summernote4" name="valores">{!!$nosotros_contenido->valores!!}</textarea>

        {{-- <div class="my-3">
          <label for="baner" class="form-label">Baner <span class='recomendada'>(recomendada 1366x650 px)</span></label>
          <input class="form-control preview" data-form-id="{{'imagen'.$nosotros_baner->id}}" type="file" id="imagen" name='baner' accept="image/*">
        </div>
        <div class='d-flex justify-content-center' style='max-height:50vh;'>
          <img id="{{'imagen'.$nosotros_baner->id}}" src="{{asset('imagenes/'.$nosotros_baner->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
        </div>

        <label class='mt-3' for="titulo_baner" class="form-label">Título en baner</label>
        <input type="text" name='titulo_baner' class='form-control' value='{{$nosotros_contenido->titulo_baner}}'>

        <label class='mt-3' for="texto_baner">Texto en baner</label><br>
        <textarea class='summernote-text'  id="summernote5" name="texto_baner">{!!$nosotros_contenido->texto_baner!!}</textarea>
         --}}

        <button type="submit" data-form-id='formulario' class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
      </form>
    </div>
  </div>

    {{-- MODAL CREAR --}}
    <div class="modal fade" id="crear-slider" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            Crear proceso
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id='agregar' class='mb-4 loading' action="{{route('imagen.store')}}" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="sector" value="nosotros-procesos">
          
              
              <div class="row g-3 align-items-center">
                <div class="col-2 ">
                  <label for="orden" class="form-label">Orden</label>
                  <input type="text" class="form-control" name='orden' value='aa' required>
                </div>
                
                <div class="col-10">
                  <label for="imagenes" class="form-label">Agregar imagen o video <span class='recomendada'>(recomendada 790x650 px)</span></label>
                    <input class="form-control" type="file" id="imagenes" name="imagenes[]" multiple required>
          
                </div>
                <div class="col-12 mt-3">
                  <label for="baner_texto" class="form-label">Titulo</label>
                  <textarea class='summernote-text' id="summernote1" name="baner_texto"></textarea>
    
                </div>
    
                <div class="col-12 mt-3">
                  <label for="baner_texto" class="form-label">Descripcion</label>
                  <textarea class='summernote-text' id="summernote1" name="baner_texto_2"></textarea>
    
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
  @foreach ($nosotros_procesos as $imagen)
      
  {{-- MODALES --}}
  <div class="modal fade" id="{{'editar'.$imagen->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          Editar slider
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id='{{'form'.$imagen->id}}'  action="{{route('slider.update',['id'=>$imagen->id])}}" method="post" enctype="multipart/form-data">
            @csrf
            @method('put')


            <div class="mb-3">
              <label for="orden" class="form-label">Orden</label>
              <input type="text" class="form-control" name='orden' value='{{$imagen->orden}}' required>
            </div>

            

            <div class="mb-3">
              <label for="imagen" class="form-label">Imagen <span class='recomendada'>(recomendada 790x650 px)</span></label>
              <input class="form-control preview" data-form-id="{{$imagen->tipo.$imagen->id}}" type="file" id="imagen" name='imagen'>
            </div>

            <div class="col-12 mt-3">
              <label for="baner_texto" class="form-label">Titulo</label>
              <textarea class='summernote-text' id="summernote1" name="baner_texto">{!! $imagen->baner_texto !!}</textarea>

            </div>

            <div class="col-12 mt-3">
              <label for="baner_texto" class="form-label">Descripcion</label>
              <textarea class='summernote-text' id="summernote1" name="baner_texto_2">{!! $imagen->baner_texto_2 !!}</textarea>

            </div>

       

            {{-- PREVIEW --}}
            <div class='d-flex justify-content-center' style='max-height:50vh;'>

              @if ($imagen->tipo=='imagen')

              <img  id="{{'imagen'.$imagen->id}}" src="{{asset('imagenes/'.$imagen->path)}}" alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
              @else
              <video  id="{{'video'.$imagen->id}}" src="{{asset('imagenes/'.$imagen->path)}}" controls style="max-width: 100%;" ></video>
                
              @endif

              
            </div>
            

          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button  data-form-id="{{'form'.$imagen->id}}" type="submit" class="btn btn-primary submit">Actualizar</button>
        </div>
      </div>
    </div>
  </div>
@endforeach


  
  
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