@extends('layouts.plantilla-back')

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Ventas" title="Clientes" description="Altas, descuentos y acceso a la Zona de Clientes." />

  @if(session('success'))
      <div class="alert alert-success" role="status">
          {{ session('success') }}
      </div>
  @endif
  @if(session('warning'))
      <div class="alert alert-danger" role="alert">
          {{ session('warning') }}
      </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <div class="search-container">
        <input type="search" placeholder="Buscar..." class="search-input" aria-label="Buscar">
        <button id='buscador-productos' class="search-btn">
          <i class="fa fa-search"></i>
        </button>
      </div>
    </div>
    <div id='clientes-contenedor' class="card-body">
      @include('backend/dash-clientes-listado')
    </div>
    <div class='card-footer'>
      {{ $clientes->links('pagination::bootstrap-5') }}
    </div>
  </div>

  

@endsection

@section('script')
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