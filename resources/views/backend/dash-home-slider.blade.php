@extends('layouts.plantilla-back')

@section('styles')
<style>
  .slider-admin{--slider-ink:#1f2a37;--slider-muted:#667085;--slider-line:#dfe6ee;--slider-blue:#0098da;--slider-sky:#eff9fd}
  .slider-admin__intro{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:24px}
  .slider-admin__intro h1{margin:0;color:var(--slider-ink)}
  .slider-admin__intro p{max-width:690px;margin:7px 0 0;color:var(--slider-muted);font-size:.94rem}
  .slider-admin .card{border:1px solid var(--slider-line);box-shadow:0 8px 24px rgba(31,42,55,.06)}
  .slider-admin .card-header{padding:16px 18px;background:#fff;border-bottom:1px solid var(--slider-line)}
  .slider-media-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:8px}
  .slider-media-card{border:1px solid var(--slider-line);border-radius:10px;padding:15px;background:#fff}
  .slider-media-card--mobile{background:linear-gradient(145deg,#fff 0%,var(--slider-sky) 100%);border-color:#c8e9f6}
  .slider-media-card__head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}
  .slider-media-card__title{display:block;color:var(--slider-ink);font-size:.93rem;font-weight:600}
  .slider-media-card__copy{display:block;margin-top:3px;color:var(--slider-muted);font-size:.76rem;line-height:1.45}
  .slider-format{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;background:#eef2f6;color:#425466;font-size:.7rem;font-weight:600;letter-spacing:.03em;white-space:nowrap}
  .slider-media-card--mobile .slider-format{background:#d9f3fd;color:#08749d}
  .slider-file-note{display:block;min-height:18px;margin-top:7px;color:var(--slider-muted);font-size:.74rem}
  .slider-file-note.is-warning{color:#a55700;font-weight:500}
  .slider-file-note.is-ok{color:#087a58;font-weight:500}
  .slider-preview{position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-top:12px;border:1px solid var(--slider-line);border-radius:8px;background:#f4f7fa}
  .slider-preview--desktop{aspect-ratio:16/7}
  .slider-preview--mobile{width:min(100%,170px);aspect-ratio:9/16;margin-left:auto;margin-right:auto}
  .slider-preview img,.slider-preview video{width:100%;height:100%;object-fit:cover}
  .slider-preview__empty{padding:12px;color:var(--slider-muted);font-size:.75rem;text-align:center}
  .slider-thumb{width:112px;height:52px;object-fit:cover;border:1px solid var(--slider-line);border-radius:5px;background:#f4f7fa}
  .slider-mobile-none{color:var(--slider-muted);font-size:.8rem}
  .slider-admin .table td{vertical-align:middle}
  @media (max-width:767px){.slider-admin__intro{align-items:flex-start;flex-direction:column}.slider-media-grid{grid-template-columns:1fr}.slider-preview--mobile{width:150px}}
</style>
@endsection

@section('content')
<div class="slider-admin">
  <div class="slider-admin__intro">
    <div>
      <h1>Slider de inicio</h1>
      <p>Cada slide puede tener una pieza horizontal para desktop y una versión vertical para mobile. Así el mensaje y la imagen principal conservan su encuadre en cualquier pantalla.</p>
    </div>
  </div>
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
  @if ($errors->any())
      <div class="alert alert-danger">No se guardó el slider. Revisá los archivos y volvé a intentarlo.</div>
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
              <form id='agregar' class='mb-4 loading' action="{{ route('home-slider.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-center">
                  <div class="col-md-3">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="text" class="form-control" name='orden' value="{{ old('orden', 'aa') }}" required>
                  </div>
                </div>

                <div class="slider-media-grid">
                  <section class="slider-media-card">
                    <div class="slider-media-card__head">
                      <div>
                        <label class="slider-media-card__title" for="desktop_image">Imagen desktop</label>
                        <span class="slider-media-card__copy">La imagen horizontal principal del slider.</span>
                      </div>
                      <span class="slider-format">1920 x 900</span>
                    </div>
                    <input class="form-control" type="file" id="desktop_image" name="desktop_image" accept="image/*,video/*" required data-slider-orientation="desktop" data-slider-note="crear-desktop-note" data-slider-preview="crear-desktop-preview">
                    <span id="crear-desktop-note" class="slider-file-note">Horizontal, relación aproximada 2:1. También admite video.</span>
                    @error('desktop_image') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                    <div id="crear-desktop-preview" class="slider-preview slider-preview--desktop"><span class="slider-preview__empty">Vista previa desktop</span></div>
                  </section>

                  <section class="slider-media-card slider-media-card--mobile">
                    <div class="slider-media-card__head">
                      <div>
                        <label class="slider-media-card__title" for="mobile_image">Imagen mobile <span class="text-muted fw-normal">opcional</span></label>
                        <span class="slider-media-card__copy">Se muestra en celulares y evita recortes del arte desktop.</span>
                      </div>
                      <span class="slider-format">1080 x 1920</span>
                    </div>
                    <input class="form-control" type="file" id="mobile_image" name="mobile_image" accept="image/*" data-slider-orientation="mobile" data-slider-note="crear-mobile-note" data-slider-preview="crear-mobile-preview">
                    <span id="crear-mobile-note" class="slider-file-note">Vertical, relación aproximada 9:16. Si no la cargás, se usa desktop.</span>
                    @error('mobile_image') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                    <div id="crear-mobile-preview" class="slider-preview slider-preview--mobile"><span class="slider-preview__empty">Vista previa mobile</span></div>
                  </section>
                </div>

                <div class="col-12 mt-3">
                  <label for="baner_texto" class="form-label">Texto</label>
                  <textarea class='summernote-text' id="summernote1" name="baner_texto">{!! old('baner_texto') !!}</textarea>
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
    </div>
    
    <div class="card-body">


      <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Orden</th>
            <th>Desktop</th>
            <th>Mobile</th>
            <th>Texto</th>
            <th>Tipo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($home_slider as $imagen)
            <tr>
              <td>{{$imagen->orden}}</td>
              <td><img src="{{asset('imagenes/'.($imagen->tipo == 'video' ? 'video-placeholder.png' : $imagen->path))}}" class="slider-thumb" alt="Vista desktop del slider {{ $imagen->id }}"></td>
              <td>
                @if ($imagen->path_mobile)
                  <img src="{{ asset('imagenes/'.$imagen->path_mobile) }}" class="slider-thumb" alt="Vista mobile del slider {{ $imagen->id }}">
                @else
                  <span class="slider-mobile-none">Usa desktop</span>
                @endif
              </td>
              <td>{!!$imagen->baner_texto!!}</td>

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
            <form id='{{'form'.$imagen->id}}' action="{{ route('home-slider.update', $imagen) }}" method="post" enctype="multipart/form-data">
              @csrf
              @method('put')

          

              <div class='row'>
                <div class="mb-3 col-md-3">
                  <label for="orden" class="form-label">Orden</label>
                  <input type="text" class="form-control" name='orden' value='{{$imagen->orden}}' required>
                </div>
              </div>

              <div class="slider-media-grid">
                <section class="slider-media-card">
                  <div class="slider-media-card__head">
                    <div>
                      <label class="slider-media-card__title" for="desktop_image_{{ $imagen->id }}">Imagen desktop</label>
                      <span class="slider-media-card__copy">Dejá este campo vacío para conservar el archivo actual.</span>
                    </div>
                    <span class="slider-format">1920 x 900</span>
                  </div>
                  <input class="form-control" type="file" id="desktop_image_{{ $imagen->id }}" name="desktop_image" accept="image/*,video/*" data-slider-orientation="desktop" data-slider-note="editar-desktop-note-{{ $imagen->id }}" data-slider-preview="editar-desktop-preview-{{ $imagen->id }}">
                  <span id="editar-desktop-note-{{ $imagen->id }}" class="slider-file-note">Horizontal, relación aproximada 2:1. También admite video.</span>
                  <div id="editar-desktop-preview-{{ $imagen->id }}" class="slider-preview slider-preview--desktop">
                    @if ($imagen->tipo === 'video')
                      <video src="{{ asset('imagenes/'.$imagen->path) }}" muted controls></video>
                    @else
                      <img src="{{ asset('imagenes/'.$imagen->path) }}" alt="Imagen desktop actual">
                    @endif
                  </div>
                </section>

                <section class="slider-media-card slider-media-card--mobile">
                  <div class="slider-media-card__head">
                    <div>
                      <label class="slider-media-card__title" for="mobile_image_{{ $imagen->id }}">Imagen mobile <span class="text-muted fw-normal">opcional</span></label>
                      <span class="slider-media-card__copy">Dejá este campo vacío para mantener la configuración actual.</span>
                    </div>
                    <span class="slider-format">1080 x 1920</span>
                  </div>
                  <input class="form-control" type="file" id="mobile_image_{{ $imagen->id }}" name="mobile_image" accept="image/*" data-slider-orientation="mobile" data-slider-note="editar-mobile-note-{{ $imagen->id }}" data-slider-preview="editar-mobile-preview-{{ $imagen->id }}">
                  <span id="editar-mobile-note-{{ $imagen->id }}" class="slider-file-note">Vertical, relación aproximada 9:16.</span>
                  <div id="editar-mobile-preview-{{ $imagen->id }}" class="slider-preview slider-preview--mobile">
                    @if ($imagen->path_mobile)
                      <img src="{{ asset('imagenes/'.$imagen->path_mobile) }}" alt="Imagen mobile actual">
                    @else
                      <span class="slider-preview__empty">Sin imagen mobile. El sitio usa la versión desktop.</span>
                    @endif
                  </div>
                </section>
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
  
</div>
@endsection

@section('script')
<script>
  document.querySelectorAll('[data-slider-orientation]').forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      var note = document.getElementById(input.dataset.sliderNote);
      var preview = document.getElementById(input.dataset.sliderPreview);
      if (!file || !note) return;

      note.className = 'slider-file-note is-ok';
      note.textContent = 'Seleccionado: ' + file.name;

      if (!file.type.startsWith('image/')) {
        if (preview) preview.textContent = 'Video seleccionado: ' + file.name;
        return;
      }

      var image = new Image();
      image.onload = function () {
        if (preview) preview.innerHTML = '<img src="' + image.src + '" alt="Vista previa del archivo seleccionado">';
        var isVertical = image.naturalHeight > image.naturalWidth;
        var expectedVertical = input.dataset.sliderOrientation === 'mobile';
        if (isVertical !== expectedVertical) {
          note.className = 'slider-file-note is-warning';
          note.textContent = expectedVertical
            ? 'Esta imagen es horizontal. Para mobile se recomienda una composición vertical.'
            : 'Esta imagen es vertical. Para desktop se recomienda una composición horizontal.';
        }
        URL.revokeObjectURL(image.src);
      };
      image.src = URL.createObjectURL(file);
    });
  });

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
