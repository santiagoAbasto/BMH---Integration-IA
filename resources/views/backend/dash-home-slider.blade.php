@extends('layouts.plantilla-back')

@section('styles')
<style>
  .hidden{
    display: none;
  }
</style>
@endsection

@section('content')
<h1 class='mb-4'>Slider</h1>
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

  {{-- <div class="mb-3" style='margin-left:50px;margin-right:50px;'>
    <form action="{{route('slider.texto')}}" method='POST'>
      @csrf
      @method('put')
      <div class='row d-flex flex-column'>
        <div class="col-lg-12">
          <label for="baner_texto" class="form-label">Texto</label>
          <textarea class='summernote-text' id="summernote1" name="slider_texto">{!!$nosotros_contenido->info!!}</textarea>
        </div>
        <div class="col-lg-12 mt-3">
          <label for="baner_texto" class="form-label">Texto 2</label>
          <textarea class='summernote-text' id="summernote1" name="slider_texto_2">{!!$nosotros_contenido->info_2!!}</textarea>
        </div>
      </div>
      <div class="row justify-content-end">
        <div class="col-lg-1 mt-3">
          <button type='submit' class='btn btn-primary'>Actualizar</button>


        </div>

      </div>
    </form>
    
    
  </div> --}}

  <div class="card">
    <div class="card-header">
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear-slider"><i class="fa-solid fa-plus"></i>  CREAR</button>

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
                <input type="hidden" name="sector" value="home-slider">
            
                
                <div class="row g-3 align-items-center">
                  <div class="col-2 ">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="text" class="form-control" name='orden' value='aa' required>
                  </div>
                
                  <div class="col-10">
                    <label for="imagenes" class="form-label">Agregar imagen o video <span class='recomendada'>(recomendada 1366x640 px)</span></label>
                      <input class="form-control" type="file" id="imagenes" name="imagenes[]" multiple required>
            
                  </div>
                  </div>
                  

                  <div class="col-12 mt-3">
                    <label for="baner_texto" class="form-label">Texto</label>
                    <textarea class='summernote-text' id="summernote1" name="baner_texto"></textarea>

                  </div>
                  {{-- <div class="col-12 mt-3">
                    <label for="baner_texto" class="form-label">Texto 2</label>
                    <textarea class='summernote-text' id="summernote1" name="baner_texto_2"></textarea>

                  
                  
                </div> --}}
            
            
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button  data-form-id="agregar" type="submit" class="btn btn-primary submit" >Agregar</button> 
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card-body">


      <table class="table table-striped">
        <thead>
          <tr>
            <th>Orden</th>
            <th>Thumbnail</th>
            <th>Texto</th>
            <th>Texto 2</th>
            <th>Tipo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($home_slider as $imagen)
            <tr>
              <td>{{$imagen->orden}}</td>
              <td><img src="{{asset('imagenes/'.($imagen->tipo == 'video' ? 'video-placeholder.png' : $imagen->path))}}" class="img-thumbnail" style="max-width: 100px;"></td>
              <td>{!!$imagen->baner_texto!!}</td>
              <td>{!!$imagen->baner_texto_2!!}</td>

              <td>{{$imagen->tipo == 'video' ? 'video' : 'imagen'}}</td>
              
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$imagen->id}}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <form action="{{ route('imagen.delete', ['id' => $imagen->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                </div>
                
              </td>
            </tr>
          @endforeach

        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Modales para ver las imágenes -->
  @foreach ($home_slider as $imagen)

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

          

              <div class='row'>
                <div class="mb-3 col-2">
                  <label for="orden" class="form-label">Orden</label>
                  <input type="text" class="form-control" name='orden' value='{{$imagen->orden}}' required>
                </div>
                <div class="mb-3 col-10">
                  <label for="imagen" class="form-label">Imagen <span class='recomendada'>(recomendada 1366x640 px)</span></label>
                  <input class="form-control preview" data-form-id="{{$imagen->tipo.$imagen->id}}" type="file" id="imagen" name='imagen'>
                </div>

          
              </div>

              <div class="row">
                <div class="mb-3">
                  <label for="baner_texto" class="form-label">Texto</label>
                  <textarea class='summernote-text' id="summernote1" name="baner_texto">{!!$imagen->baner_texto!!}</textarea>
  
                </div>
  
                {{-- <div class="mb-3">
                  <label for="baner_texto" class="form-label">Texto 2</label>
                  <textarea class='summernote-text' id="summernote1" name="baner_texto_2">{!!$imagen->baner_texto_2!!}</textarea>
  
                </div> --}}

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
  // Preview de imagenes
  function mostrarImagenSeleccionada(input) {
      var imagenId = $(input).data('form-id');
      console.log(imagenId)
      if (input.files && input.files[0]) {
          var lector = new FileReader();
          lector.onload = function (e) {

              // Que tipo de archivo se está subiendo?
              var tipoDeArchivo = e.target.result.split(';')[0].split(':')[1];
              if(!tipoDeArchivo.startsWith('image/') && !tipoDeArchivo.startsWith('video/')){
                alert('El archivo debe ser de tipo imagen o video')
              } else {
                if (tipoDeArchivo.startsWith('image/')) {
                  tipoDeArchivo = 'IMG'
                } else {
                  tipoDeArchivo = 'VIDEO'
                }

                elemento = document.getElementById(imagenId)
                console.log(elemento.tagName, tipoDeArchivo)
                if (elemento.tagName == tipoDeArchivo){ // Si ambos son iguales
                  elemento.src = e.target.result;
                  elemento.classList.remove('hidden')

                  if(tipoDeArchivo == 'IMG'){
                    elemento = document.getElementById(imagenId.replace(/imagen(\d+)/g, 'video$1'))
                  } else {
                    elemento = document.getElementById(imagenId.replace(/video(\d+)/g, 'imagen$1'))
                  }
                  elemento.classList.add('hidden')
                  // elemento.style.display = 'block';
                } else {
                  elemento.classList.add('hidden')
                  if(tipoDeArchivo == 'IMG'){
                    elemento = document.getElementById(imagenId.replace(/video(\d+)/g, 'imagen$1'))
                  } else {
                    elemento = document.getElementById(imagenId.replace(/imagen(\d+)/g, 'video$1'))
                  }
                  elemento.src = e.target.result;
                  elemento.classList.remove('hidden')
                  
                }
                
              }

              
          };
          // Lee el archivo de imagen como una URL de datos
          lector.readAsDataURL(input.files[0]);
      }
  }

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

</script>
@endsection