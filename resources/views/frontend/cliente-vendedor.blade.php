@extends('layouts.plantilla-front')

@section('styles')
<style>
    .modal-body{
        padding: 30px;
    }
    .dropdown-menu{
        border-radius: 0px 0px 15px 15px;
        background: #FFF;
        box-shadow: 0px 1px 3px 0px rgba(0, 0, 0, 0.35);
        padding:0;
    }
    .dropdown-menu li{
        width:245px;
        border-bottom:1px solid #d4d4d4;
        padding: 0;
    }
    .dropdown-item:hover{
        background-color: rgba(78, 153, 212, 0.22);
    }
</style>
@endsection


@section('content')

    <section class='titulo'>
        <div class='container d-flex flex-column' >
            <div class='row'>
                <div class='miga'>
                <div>
                    {{ucfirst($cliente->name)}}
                </div>
                    
                </div>
            </div>
        </div>
    </section>

    <section style='padding-top:69px;padding-bottom:125px'>
        
        {{-- <h2 class='pb-4' style='text-align:center;'>Historial de compras</h2> --}}
        

        <div class='container'>

            <a href="{{route('vendedor.clientes')}}"><div class='mb-3'>< Volver</div></a>

            <table class="table table-striped" style='border: 1px solid #dddddd;'>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Estado de la compra</th>
                    <th>Estado de la orden</th>
                    <th>Importe</th>
                    <th style='text-align:center;'>Acciones</th>
                  </tr>
                </thead>
                <tbody id='productos-contenedor'>
        
                    @foreach ($pedidos as $pedido)
                    <tr>
                        <td>{{$pedido->id}}</td>
                        <td>{{$pedido->fecha}}</td>
                        <td style='{{$pedido->estado == 'Esperando pago' ? 'color:red;' : ''}}'>
                            <div class="btn-group">
                                <div id='estado{{$pedido->id}}' class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{$pedido->estado}}
                                </div>
                                <ul class="dropdown-menu" style='padding-left:8px;padding-right:8px;box-shadow: 0 2px 2px 0 rgba(0, 0, 0, .14), 0 3px 1px -2px rgba(0, 0, 0, .12), 0 1px 5px 0 rgba(0, 0, 0, .2);'>
                                    <div style='border-bottom: 1px solid #adb5bd;padding-bottom:8px;'>Cambiar a</div>
                                    <li style='padding-top:8px;'>
                                        @if($pedido->estado == 'Pago realizado')
                                        <a class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado(event)'>Esperando pago</a>
                                        @else
                                        <a class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado(event)'>Pago realizado</a>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <div id='estado-orden{{$pedido->id}}' class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{$pedido->estado_orden}}
                                </div>
                                <ul class="dropdown-menu" style='padding-left:8px;padding-right:8px;box-shadow: 0 2px 2px 0 rgba(0, 0, 0, .14), 0 3px 1px -2px rgba(0, 0, 0, .12), 0 1px 5px 0 rgba(0, 0, 0, .2);'>
                                    <div style='border-bottom: 1px solid #adb5bd;padding-bottom:8px;'>Cambiar a</div>
                                    <li style='padding-top:8px;'>
                                        @if($pedido->estado_orden != 'Pendiente')
                                        <a class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Pendiente</a>
                                        @endif
                                        @if($pedido->estado_orden != 'Procesado')
                                        <a class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Procesado</a>
                                        @endif
                                        @if($pedido->estado_orden != 'Cancelado')
                                        <a style='color:red;' class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Cancelado</a>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td>$ {{$pedido->total_pedido}}</td>
                        <td>
                        <div class='d-flex justify-content-center'>
                            <a href="{{route('vendedor.pedido.datos', ['id' => $pedido->id])}}"><button class="btn btn-primary btn-sm me-1"><i class="fa-regular fa-eye"></i></button></a>
                        </div>
                        
                        </td>
                    </tr>
                    @endforeach
        
                </tbody>
              </table>


        </div>
    </section>
    
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
                    document.getElementById('estado-orden' + id).innerText = nuevoEstado
                    event.target.innerText = anteriorEstado
                
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
        // if(nuevoEstado == 'Cancelado'){
        //     window.location.href = "{{route('ventas')}}"
        // }
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