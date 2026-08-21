@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Clientes</h1>

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
    <div class="card-header d-flex justify-content-between">
      <div class="search-container">
        <input type="text" placeholder="Buscar..." class="search-input">
        <button id='buscador-productos' class="search-btn">
          <i class="fa fa-search"></i>
        </button>
      </div>
    </div>
    <div id='clientes-contenedor' class="card-body">
      @include('backend/dash-clientes-listado')
    </div>
    <div class='card-footer'>
      {{$clientes->links()}}
    </div>
  </div>

  

@endsection

@section('script')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // BUSCADOR
    $('.search-btn').on('click', function(e) {
      var valor = $('.search-input').val()
      $.ajax({
          url: "{{ route('cliente.buscar') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              valor: valor
          },
          success: function(response) {
              // console.log(response);
              $('#clientes-contenedor').html(response)
          },
          error: function(xhr) {
              console.error(xhr.responseText);
          }
      });
    })

    function togglePassword(event){
      var element = event.target
      var id = $(element).data('id')
        if($(element).is(':checked')){
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = false
            });
        } else {
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = true
            });
        }
    }

    function habilitar(event, id){
      if(event.target.innerText == 'Habilitar'){
        event.target.innerText = 'Deshabilitar'
      } else {
        event.target.innerText = 'Habilitar'
      }
      $.ajax({
          url: "{{ route('cliente.habilitar') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              id:id,
          },
          success: function(response) {
              // console.log(response);
              
          },
          error: function(xhr) {
              console.error(xhr.responseText);
          }
      });
    }
</script>
@endsection