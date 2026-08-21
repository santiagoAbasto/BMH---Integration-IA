@extends('layouts.plantilla-back')

@section('styles')
<style>
    table .btn{
        height: 31px;
    }
</style>
@endsection

@section('content')

  <h1 class='mb-4'>Pedidos</h1>

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

  @if($nuevosPedidos > 0)
  <div class="mb-3 text-end">
    <span class="badge bg-primary" style="font-size: 16px; padding: 10px 20px; border-radius: 20px;">
      Pedidos en las últimas 24 hs: {{ $nuevosPedidos }}
    </span>
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
    <div class="card-body">

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>ID</th>
            <th>Razón social</th>
            <th>Fecha</th>
            <th>Estado de la compra</th>
            {{-- <th>Tipo de pago</th> --}}
            {{-- <th>Método de envío</th> --}}
            <th>Estado de la orden</th>
            <th>Importe</th>
            <th style='text-align:center;'>Acciones</th>
          </tr>
        </thead>
        <tbody id='productos-contenedor'>

          @include('backend/dash-ventas-listado')

        </tbody>
      </table>

    </div>
    <div class='card-footer'>
        {{$pedidos->links()}}
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.tailwindcss.com"></script>
<script>

    // Estado de la compra
    function modificar_estado(event){
        var anteriorEstado = document.getElementById('estado' + event.target.getAttribute('data-id')).innerText
        var nuevoEstado = event.target.innerText
        var id = event.target.getAttribute('data-id')
        $.ajax({
            url: "{{ route('update.estado') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                estado: nuevoEstado
            },
            success: function(response) {
                // console.log(response.mensaje);
                document.getElementById('estado' + id).innerText = nuevoEstado
                event.target.innerText = anteriorEstado
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
    }

    function modificar_estado_orden(event){
        var anteriorEstado = document.getElementById('estado-orden' + event.target.getAttribute('data-id')).innerText
        var nuevoEstado = event.target.innerText
        var id = event.target.getAttribute('data-id')
        $.ajax({
            url: "{{ route('update.estado.orden') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                estado: nuevoEstado
            },
            success: function(response) {
                // console.log(response.mensaje);
                if(nuevoEstado != 'Cancelado'){
                    document.getElementById('estado-orden' + id).innerText = nuevoEstado
                    event.target.innerText = anteriorEstado
                }
                
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
        if(nuevoEstado == 'Cancelado'){
            window.location.href = "{{route('ventas')}}"
        }
    }

    // Destacada ajax
    $(document).ready(function() {


        // BUSCADOR
        $('.search-input').on('input', function(e) {
            var valor = $('.search-input').val()
            $.ajax({
                url: "{{ route('dashboard.buscar.pedido') }}",
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
    });

</script>

@endsection