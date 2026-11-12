@foreach ($pedidos as $pedido)
<tr>
    <td class="adm-celda-numero">#{{$pedido->id}}</td>
    <td class="fw-semibold">{{$pedido->nombre}}</td>
    <td class="text-nowrap">{{$pedido->fecha}}</td>
    <td>
        <div class="btn-group">
            <button id='estado{{$pedido->id}}' class="dropdown-toggle adm-estado {{ $pedido->estado == 'Esperando pago' ? 'adm-estado--alerta' : 'adm-estado--ok' }}" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">{{$pedido->estado}}</button>
            <ul class="dropdown-menu">
                <li><h3 class="dropdown-header">Cambiar a</h3></li>
                <li>
                    @if($pedido->estado == 'Pago realizado')
                    <button type="button" class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado(event)'>Esperando pago</button>
                    @else
                    <button type="button" class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado(event)'>Pago realizado</button>
                    @endif
                </li>
            </ul>
        </div>
    </td>
    {{-- <td>{{$pedido->tipo_pago}}</td> --}}
    {{-- <td>{{$pedido->tipo_envio}}</td> --}}
    <td>
        <div class="btn-group">
            <button id='estado-orden{{$pedido->id}}' class="dropdown-toggle adm-estado {{ ['Pendiente' => 'adm-estado--alerta', 'Procesado' => 'adm-estado--ok', 'Cancelado' => 'adm-estado--peligro'][$pedido->estado_orden] ?? '' }}" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">{{$pedido->estado_orden}}</button>
            <ul class="dropdown-menu">
                <li><h3 class="dropdown-header">Cambiar a</h3></li>
                <li>
                    @if($pedido->estado_orden != 'Pendiente')
                    <button type="button" class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Pendiente</button>
                    @endif
                    @if($pedido->estado_orden != 'Procesado')
                    <button type="button" class='dropdown-item modificar-estado' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Procesado</button>
                    @endif
                    @if($pedido->estado_orden != 'Cancelado')
                    <button type="button" class='dropdown-item modificar-estado text-danger' data-id='{{$pedido->id}}' onclick='modificar_estado_orden(event)'>Cancelado</button>
                    @endif
                </li>
            </ul>
        </div>
    </td>
    <td class="adm-celda-importe">$ {{$pedido->total_pedido}}</td>
    <td>
    <div class='adm-acciones'>
        <a href="{{route('pedido.datos', ['id' => $pedido->id])}}" class="btn btn-outline-primary btn-sm">Ver pedido<span class="visually-hidden"> #{{ $pedido->id }}</span></a>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$pedido->id}}" aria-label="Eliminar el pedido #{{ $pedido->id }}">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
        </button>

        {{-- MODAL ELIMINAR --}}
        <div class="modal fade" id="{{'eliminar'.$pedido->id}}" tabindex="-1" aria-label="Eliminar el pedido #{{ $pedido->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body adm-confirmar-cuerpo"><span class="adm-confirmar__icono" aria-hidden="true"><i class="fa-solid fa-trash-can"></i></span>
                  ¿Eliminar el pedido #{{$pedido->id}}?
                </div>
                <div class="modal-footer d-flex justify-content-center">
                  <form action="{{ route('pedido.delete', ['id' => $pedido->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                  </form>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
              </div>
            </div>
        </div>
    </div>

    </td>
</tr>
@endforeach