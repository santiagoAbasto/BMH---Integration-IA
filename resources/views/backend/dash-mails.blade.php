@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Mails y avisos</h1>
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

      <form id='formulario' action="{{route('mails.update')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        <label class='mt-4' for="registro" style='font-size:20px;font-weight:500;'>Registro</label>
        <div class='d-flex' style='align-items:center;'>
            <label for="registro_titulo" style='font-weight:300;width:110px;'>Asunto:</label>
            <input type="text" class='form-control mt-2 mb-2' name='registro_titulo' required value='{{$mails->registro_titulo}}'>
        </div>
        <textarea class='summernote-text' id="summernote1" name="registro">{!!$mails->registro!!}</textarea>

        <label class='mt-4' for="habilitado" style='font-size:20px;font-weight:500;'>Habilitación de cuenta</label>
        <div class='d-flex' style='align-items:center;'>
            <label for="habilitado_titulo" style='font-weight:300;width:110px;'>Asunto:</label>
            <input type="text" class='form-control mt-2 mb-2' name='habilitado_titulo' required value='{{$mails->habilitado_titulo}}'>
        </div>
        <textarea class='summernote-text' id="summernote2" name="habilitado">{!!$mails->habilitado!!}</textarea>

        <button type="submit" data-form-id='formulario' class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
      </form>
    </div>
  </div>
  
  
@endsection

@section('script')
<script>
  // Summernote
  $(document).ready(function() {
      $('.summernote-text').each(function() {
        
        // Inicializar Summernote para este editor
        $(this).summernote({
            placeholder: '',
            tabsize: 2,
            height: 200,
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
  });

</script>
@endsection