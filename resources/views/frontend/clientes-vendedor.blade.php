@extends('layouts.plantilla-front')

@section('styles')
<style>
    .modal-body{
        padding: 30px;
    }
    .select2{
        width: 30% !important;
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
                    Clientes
                </div>
                    
                </div>
            </div>
        </div>
    </section>

    <section style='padding-top:69px;padding-bottom:125px'>
        
        {{-- <h2 class='pb-4' style='text-align:center;'>Historial de compras</h2> --}}

        

        <div class='container'>

            <div class='mb-4'>
                <form action="{{route('vendedor.clientes.asociar', ['id' => Auth::guard('web')->user()->id])}}" method="POST">
                    @csrf
                    @method('put')
                    <label for="clientes[]" class='form-label'>Usuarios</label>
                    <select class="js-example-tags form-control" multiple="multiple" name='clientes[]' required>
                        @foreach ($usuarios as $usuario)
                            <option value="{{$usuario->id}}">{{$usuario->username}}</option>
                        @endforeach
                    </select>
                    <button type='submit' class='btn btn-primary btn-sm'>Añadir</button>
                </form>
                
            </div>
            

            @if(count($clientes) > 0)
            
            <label class='mb-3' for="">Mis clientes</label>
            <table class="table table-striped" style='border: 1px solid #dddddd;'>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>DNI/CUIT</th>
                    <th>Email</th>
                    <th>Celular</th>
                    <th  style='text-align:end;'>Pago pendiente</th>
                    <th style='text-align:center;'>Acciones</th>
                  </tr>
                </thead>
                <tbody id='productos-contenedor'>
        
                    @foreach ($clientes as $cliente)
                    <tr>
                        <td>{{$cliente->id}}</td>
                        <td>{{$cliente->name}}</td>
                        <td>{{$cliente->username}}</td>
                        <td>{{$cliente->dni}}</td>
                        <td>{{$cliente->email}}</td>
                        <td>{{$cliente->celular}}</td>
                        <td style='text-align:end;'>$ {{$cliente->pago_pendiente()}}</td>
                        <td>
                        <div class='d-flex justify-content-center'>
                            <a href="{{route('vendedor.cliente', ['id' => $cliente->id])}}"><button class="btn btn-primary btn-sm me-1"><i class="fa-regular fa-eye"></i></button></a>
                            <form action="{{route('vendedor.clientes.desasociar', ['id' => $cliente->id])}}" method="post">
                                @csrf
                                @method('delete')
                                <button type='submit' class="btn btn-danger btn-sm me-1"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                            
                        </div>
                        
                        </td>
                    </tr>
                    @endforeach
        
                </tbody>
              </table>

            @else

            <div style='text-align:center;'>No tienes clientes asociados.</div>
            
            @endif
        </div>
    </section>
    
    
@endsection
