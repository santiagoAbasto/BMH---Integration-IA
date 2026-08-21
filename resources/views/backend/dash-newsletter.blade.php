@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Envío masivo</h1>
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

<form id='formulario' action="{{route('newsletter.enviar')}}" method="get" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
      <label for="asunto">Asunto</label>
      <input class='form-control' type="text" name="asunto" id="" required>
    </div>

    <div class="mb-3">
      <label for="mensaje">Mensaje</label>
      <textarea class='summernote-text' name="mensaje" ></textarea>
    </div>

    <button {{count($newsletter) > 0 ? '' : 'disabled'}} data-form-id="formulario" type="submit" class="btn btn-success submit">Enviar a todos los clientes</button>
</form>
{{--   
  <table class="table table-striped mt-4" style='border: 1px solid #dddddd;'>
    <thead>
      <tr>
        <th>Mail</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>

      @foreach ($newsletter as $mail)
        <tr>

          <td>{{$mail->mail}}</td>
          <td>
            <div class='d-flex'>
              <form action="{{ route('newsletter.delete', ['id' => $mail->id]) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
              </form>
            </div>
            
          </td>
        </tr>
      @endforeach

    </tbody>
  </table>
 --}}

@endsection

@section('script')

<script>
  // Check validity summernote
  document.getElementById('formulario').addEventListener('submit', function(event) {
      var summernoteFields = document.querySelectorAll('.summernote-text');

      for (var i = 0; i < summernoteFields.length; i++) {
          var content = $(summernoteFields[i]).summernote('code').trim(); // Obtener el contenido y eliminar espacios en blanco al inicio y al final
          if (!content || content=='<p><br></p>') {
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
  
</script>

@endsection