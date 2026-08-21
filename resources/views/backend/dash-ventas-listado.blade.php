@foreach ($pedidos as $pedido)
<tr>
    <td>{{$pedido->id}}</td>
    <td>{{$pedido->nombre}}</td>
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
    {{-- <td>{{$pedido->tipo_pago}}</td> --}}
    {{-- <td>{{$pedido->tipo_envio}}</td> --}}
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
        <a href="{{route('pedido.datos', ['id' => $pedido->id])}}"><button class="btn btn-primary btn-sm me-1"><i class="fa-regular fa-eye"></i></button></a>
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$pedido->id}}">
            <i class="fa-solid fa-trash-can"></i>
        </button>

        {{-- MODAL ELIMINAR --}}
        <div class="modal fade" id="{{'eliminar'.$pedido->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body fs-4 d-flex justify-content-center">
                  ¿Desea eliminar el pedido #{{$pedido->id}}?
                </div>
                <div class="modal-footer d-flex justify-content-center">
                  <form action="{{ route('pedido.delete', ['id' => $pedido->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style='height:auto;'>Eliminar</button>
                  </form>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style='height:auto;'>Cancelar</button>
                </div>
              </div>
            </div>
        </div>
    </div>
    
    </td>
</tr>
@endforeach