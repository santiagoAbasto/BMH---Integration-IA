@extends('layouts.plantilla-back')

@section('styles')
<style>
    th{
        background-color:#254F70 !important;
        color:#fff;
        font-weight: 300;
    }
    .acciones{
        max-width: 50px;
    }
</style>
@endsection

@section('content')
<h1 class='mb-4'>Crear producto</h1>
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
  @if($errors->any())
      <div class="alert alert-danger" role="alert">
          @foreach($errors->all() as $error)
              <div>{{ $error }}</div>
          @endforeach
      </div>
  @endif

  <div class="card">
    <div class="card-header">
    </div>
    <div class="card-body">

  
        <form class='pb-3' id='actualizar' action="{{route('producto.store')}}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class='row'>
                <div class="col-1 mb-3">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="text" class="form-control" name='orden' value='aa' required>
                </div>
                <div class="col-2 mb-3">
                    <label for="orden" class="form-label">Codigo</label>
                    <input type="text" class="form-control" name='codigo' required>
                </div>


                <div class="col-5 mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" name='nombre' required>
                </div>
              
                <div class='col-4'>
                    <label class="form-label" for="categoria">Familia</label>
                    <select class='form-select' name="categoria" id='select-categoria'>
                        @foreach ($categorias as $categoria)
                            <option value={{$categoria->id}}>{{ucfirst($categoria->nombre)}}</option>
                        @endforeach
                    </select>
                </div>


                
                {{-- <div class="col-2 mb-3">
                    <label for="precio" class="form-label">Precio</label>
                    <input type="text" class="form-control" name='precio' required>
                </div>
                
                <div class="col-2 mb-3">
                    <label for="iva" class="form-label">Iva incluído</label>
                    <select class='form-select' name="iva">
                        <option selected value='1'>Sí</option>
                        <option value='0'>No</option>
                    </select>
                </div> --}}
            </div>
         
            <div class="row">
                {{-- <div class="col-3 mb-3">
                    <label for="precio" class="form-label">Precio venta</label>
                    <input type="text" class="form-control" name='precioV' >
                </div> --}}
                
                   <div class="col-lg-4 mb-3">
                    <label for="precio" class="form-label">Precio</label>
                    <input type="text" class="form-control" name='precio' >
                </div>
                
                <!--<div class="col-lg-4 mb-3">-->
                <!--    <label for="precio" class="form-label">Precio 2</label>-->
                <!--    <input type="text" class="form-control" name='precio' >-->
                <!--</div>-->
          
                <div class="col-lg-4 mb-3">
                    
                    <label for="nombre" class="form-label">Descuento %</label>
                    <input type="number" min="0" class="form-control" name='descuento'>
                </div>

            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label for="precio" class="form-label">Marca</label>
                    <input type="text" class="form-control" name='marca' >
                </div>
                <div class="col-lg-6 mb-3">
                    <label for="precio" class="form-label">Modelo</label>
                    <input type="text" class="form-control" name='modelo' >
                </div>
            </div>

            {{-- <div class="row">
                <div class="col-lg-2 mb-3">
                    <label for="orden" class="form-label">Diám. interno:</label>
                    <input type="text" class="form-control" name='diametroInterno' >
                </div>
              
                <div class="col-lg-2 mb-3">
                    <label for="precio" class="form-label">Diám. externo:</label>
                    <input type="text" class="form-control" name='diametroExterno' >
                </div>

                <div class="col-lg-2 mb-3">
                    <label for="precio" class="form-label">Ancho banda:</label>
                    <input type="text" class="form-control" name='anchoBanda' >
                </div>
                <div class="col-lg-3 mb-3">
                    <label for="precio" class="form-label">Tolerancia:</label>
                    <input type="text" class="form-control" name='tolerancia' >
                </div>

                <div class="col-lg-3 mb-3">
                    <label for="precio" class="form-label">Blindaje:</label>
                    <input type="text" class="form-control" name='blindaje' >
                </div>

            </div> --}}

            <div id="dynamic-inputs" class="row"
                 data-url-categoria="{{ route('categoria.show', ':id') }}" data-cargar-inicial="true"></div>





            <div class="row">
                <label for="precio" class="form-label">Estado:</label>

                <div class="col-lg-12">
                    <div class="row">
                        <div class="d-flex col-lg-2">
                            <input type="checkbox" style="height: 20px; width: 20px" name="nuevo" id="nuevo">
                            <p style="margin-left: 10px">Nuevo</p>

                        </div>
                
                        <div class="d-flex col-lg-2">
                            <input type="checkbox" style="height: 20px; width: 20px" name="reconstruido" id="reconstruido">
                            <p style="margin-left: 10px">Reconstruido</p>
                        </div>
                    </div>
                </div>
                

            </div>

            <label class='mt-3' for="descripcion">Descripción</label><br>
            <textarea class='summernote-text'  id="summernote1" name="descripcion"></textarea>

            @include('backend.partials.partes-relacionadas', [
                'productoActualId' => null,
                'partesRelacionadas' => $partesRelacionadas,
            ])

            @include('backend.partials.equivalencias', [
                'equivalencias' => collect(),
            ])

            @include('backend.partials.aplicaciones', [
                'aplicaciones' => collect(),
            ])

            {{-- <label class='mt-3' for="caracteristicas">Características</label><br>
            <textarea class='summernote-text'  id="summernote2" name="caracteristicas"></textarea> --}}


            {{-- <div class='col-12 mt-3'>
                <label for="medidas[]" class='form-label'>Medidas</label>
                <select class="js-example-tags form-control" multiple="multiple" name='medidas[]'>
                    @foreach ($medidas as $medida)
                        <option value="{{$medida->id}}">{{ucfirst($medida->descripcion)}}</option>
                    @endforeach
                </select>
            </div>

            <div class='col-12 mt-3'>
                <label for="repuestos[]" class='form-label'>Repuestos</label>
                <select class="js-example-tags form-control" multiple="multiple" name='repuestos[]'>
                    @foreach ($repuestos as $repuesto)
                        <option value="{{$repuesto->id}}">{{ucfirst($repuesto->descripcion)}}</option>
                    @endforeach
                </select>
            </div> --}}



            <div class="row">
                <div class="col-6 my-4">
                    <label for="imagenes" class="form-label">Añadir imágenes @include('backend.partials.foto-sugerida')</label>
                    <input class="form-control limit" type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" data-achicar>
                </div>
                {{-- <div class="col-6 my-4">
                    <label for="imagenes" class="form-label">Añadir ficha tecnica </label>
                    <input class="form-control limit" type="file" id="imagenes" name="ficha" accept="file/*" >
                </div> --}}
            </div>

            <button data-form-id="actualizar" type="submit" class="btn btn-success submit" style='float:right;'>Guardar</button>
            
        </form>
    </div>
  </div>

@endsection

@section('script')
    @include('backend.partials.achicar-imagenes')
    <script src="{{ asset('js/campos-producto.js') }}"></script>
<script>
    $(document).ready(function() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');

checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            checkboxes.forEach((cb) => {
                if (cb !== this) cb.checked = false;
            });
        }
    });
});

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
