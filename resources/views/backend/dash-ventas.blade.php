@extends('layouts.plantilla-back')

@section('styles')

@endsection

@section('content')

  <x-osole::admin-page-header.v1 eyebrow="Ventas" title="Pedidos" description="Los pedidos que hacen los clientes desde la web, del más nuevo al más viejo." />

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
  <p class="adm-aviso-pedidos" role="status">
    <span class="adm-topbar__pulso" aria-hidden="true"></span>
    <strong>{{ $nuevosPedidos }} {{ $nuevosPedidos === 1 ? 'pedido nuevo' : 'pedidos nuevos' }}</strong> en las últimas 24 horas.
  </p>
@endif


<div class="card">

    <div class="card-header">
      <div class="search-container" role="search">
        <label for="pedidos-busqueda" class="visually-hidden">Buscar pedidos</label>
        <i class="fa fa-search search-container__icono" aria-hidden="true"></i>
        <input type="search" id="pedidos-busqueda" placeholder="Buscar por número, cliente, CUIT, estado o fecha" class="search-input" autocomplete="off">
        <button id='buscador-productos' class="search-btn" type="button" aria-label="Buscar">
          <i class="fa fa-search" aria-hidden="true"></i>
        </button>
      </div>
      <p class="adm-ayuda-tabla mb-0">Tocá el estado de un pedido para cambiarlo.</p>
    </div>
    <div class="card-body">

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>N.º</th>
            <th>Razón social</th>
            <th>Fecha</th>
            <th>Estado de la compra</th>
            {{-- <th>Tipo de pago</th> --}}
            {{-- <th>Método de envío</th> --}}
            <th>Estado de la orden</th>
            <th>Importe</th>
            <th><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody id='productos-contenedor'>

          @include('backend/dash-ventas-listado')

        </tbody>
      </table>

    </div>
    <div class='card-footer'>
        {{ $pedidos->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection

@section('script')
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