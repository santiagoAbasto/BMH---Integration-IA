@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Códigos Postales</h1>
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

  
<form id='crear' class='mb-4 loading' action="{{route('cp.update')}}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('put')
    
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="search-container" style='width:500px;'>
                <input type="text" placeholder="Buscar..." class="search-input">
                <button disabled id='buscador-productos' class="search-btn">
                    <i class="fa fa-search"></i>
                </button>
            </div>
            
            
        </div>
    
        <div class="card-body" style='max-height:65vh;overflow:scroll;overflow-y:scroll;'>

            <label for="" class='mb-2'>Seleccione uno o mas códigos</label>
            <table class="table table-striped">
            <thead>
                <tr>
                    <th></th>
                    {{-- <th><input id='select-all' class='todos' type="checkbox"></th> --}}
                    <th>Código</th>
                    <th>Provincia</th>
                    <th>Localidad</th>
                    <th>Zona</th>
                </tr>
            </thead>
            <tbody id='productos-contenedor'>
                @include('backend/dash-codigos-postales-listado')
            </tbody>
            </table>

        </div>
        <div class="card-footer d-flex justify-content-end" style='height:57px;align-items:center;text-align:center;'>

            <label for="descuento" class="form-label me-2">Asignar zona postal</label>
            <select name="zona" class='form-select me-2' style='max-width:150px;'>
                @foreach ($zonas as $zona)
                    <option value="{{$zona->nombre}}">{{$zona->nombre}}</option>
                @endforeach
                <option value="">Vacío</option>
            </select>

            <button data-form-id="crear" type="submit" class="btn btn-primary submit" >Aplicar</button> 
        </div>
    </div>
    


</form>
  
@endsection

@section('script')
<script>
    // BUSCADOR
    $('.search-input').on('input', function(e) {
        var valor = $('.search-input').val()
        $.ajax({
            url: "{{ route('buscar.cp') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                valor: valor,
            },
            success: function(response) {
                // console.log(response);
                $('#productos-contenedor').html(response)
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
    })

    // SELECCIONAR PRODUCTOS
    function seleccionar_producto(element){
        var formulario = document.getElementById('crear');
        var id = element.getAttribute('data-id')
        if(!element.classList.contains('seleccionado')){
            element.classList.add('seleccionado')
            element.checked = true  
            // Crear el nuevo elemento input
            var nuevoInput = document.createElement('input');
            nuevoInput.type = 'text';
            nuevoInput.name = 'codigos[]';
            nuevoInput.value = id;
            nuevoInput.style.display = 'none'
            nuevoInput.id = 'oferta' + id
            nuevoInput.classList.add('input-oferta')

            formulario.appendChild(nuevoInput);
        } else {
            element.checked = false  
            element.classList.remove('seleccionado')
            document.getElementById('oferta' + id).remove()
        }
    }

    $('#select-all').click(function(){
        if($(this).hasClass('todos')){
            document.querySelectorAll('.seleccionable').forEach(element => {
                if(element.checked == false){
                    seleccionar_producto(element)
                }
            });
            $(this).removeClass('todos')
        } else {
            $(this).addClass('todos')
            document.querySelectorAll('.seleccionable').forEach(element => {
                if(element.checked == true){
                    seleccionar_producto(element)
                }
            });
        }
    })

    $('#crear').on('keypress', 'input', function(e) {
        if(e.which === 13) { // 13 es el código de la tecla "Enter"
        e.preventDefault(); // Evita que el formulario se envíe
        // Puedes hacer cualquier otra acción aquí si lo necesitas
        return false;
        }
    });

</script>
@endsection