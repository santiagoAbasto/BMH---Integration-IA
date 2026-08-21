@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Carrito</h1>
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

      <form id='formulario' action="{{route('carrito.informacion.update')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')
        
        {{-- <div class='row'>
          <div class='col-4'>
            <label class='mt-4' for="desc_mp">Descuento Mercado Pago (%)</label>
            <input class='form-control mt-2' type="number" name="desc_mp" {{isset($informacion->desc_mp) ? 'value='.$informacion->desc_mp : ''}}>
          </div>
          
          <div class='col-4'>
            <label class='mt-4' for="desc_tb">Descuento Transferencia Bancaria (%)</label>
            <input class='form-control mt-2' type="number" name="desc_tb" {{isset($informacion->desc_tb) ? 'value='.$informacion->desc_tb : ''}}>
          </div>
          
          <div class='col-4'>
            <label class='mt-4' for="desc_lo">Descuento Pago en Local (%)</label>
            <input class='form-control mt-2' type="number" name="desc_lo" {{isset($informacion->desc_lo) ? 'value='.$informacion->desc_lo : ''}}>
          </div>
          
        </div> --}}

        {{-- <label class='mt-4' for="info_efectivo">Info pago en efectivo o transferencia bancaria</label><br> --}}
        {{-- <div class='d-flex' style='align-items:center;'>
          <label for="descuento_efectivo" style='font-weight:300;width:150px;'>Descuento (%):</label>
          <input type="text" class='form-control mt-2 mb-2' name='descuento_efectivo' required value='{{$informacion->descuento_efectivo}}'>
        </div>
        <textarea class='summernote-text'  id="summernote2" name="info_efectivo">{!!$informacion->info_efectivo!!}</textarea> --}}

        {{-- <label class='mt-4' for="info_mp">Info pago por Mercado Pago</label><br>
        <textarea class='summernote-text'  id="summernote2" name="info_mp">{!!$informacion->info_mp!!}</textarea> --}}

        {{-- <label class='mt-2' for="info_retiro">Info retiro cliente</label><br>
        <textarea class='summernote-text'  id="summernote2" name="info_retiro">{!!$informacion->info_retiro!!}</textarea>

        <label class='mt-4' for="info_empresa">Info Reparto Iptsa</label><br>
        <textarea class='summernote-text'  id="summernote2" name="info_empresa">{!!$informacion->info_empresa!!}</textarea>

        <label class='mt-4' for="info_convenir">Info envío a convenir</label><br>
        <textarea class='summernote-text'  id="summernote2" name="info_convenir">{!!$informacion->info_convenir!!}</textarea>        

        <label class='mt-4' for="info">Informacion importante</label><br>
        <textarea class='summernote-text' id="summernote1" name="info">{!!$informacion->info!!}</textarea> --}}

        <label class='mt-4' for="habilitado" style='font-size:20px;font-weight:500;'>Pedido realizado</label>
        <div class='d-flex' style='align-items:center;'>
            <label for="pedido_titulo" style='font-weight:300;width:110px;'>Título:</label>
            <input type="text" class='form-control mt-2 mb-2' name='pedido_titulo' required value='{{$informacion->pedido_titulo}}'>
        </div>
        <textarea class='summernote-text' id="summernote2" name="pedido">{!!$informacion->pedido!!}</textarea>

        

        

        {{-- <label class='mt-4' for="texto_tb">Texto que aparece cuando se tiene que procesar el pago por transferencia bancaria</label><br>
        <textarea class='summernote-text'  id="summernote8" name="texto_tb">{!!$informacion->texto_tb!!}</textarea> --}}
{{-- 
        <label class='mt-4' for="terminos">Términos y condiciones de uso</label><br>
        <label for="terminos_detalle" style='font-weight:300;width:110px;padding-top:20px;'>Aclaración:</label>
        <textarea class='summernote-text'  id="summernote10" name="terminos_detalle">{!!$informacion->terminos_detalle!!}</textarea>
        <label for="terminos" style='font-weight:300;width:200px;'>Términos y condiciones</label>
        <textarea class='summernote-text'  id="summernote9" name="terminos">{!!$informacion->terminos!!}</textarea> --}}

        <button type="submit" data-form-id='formulario' class="btn btn-primary submit mt-3" style='float:right;'>Actualizar</button>
      </form>
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