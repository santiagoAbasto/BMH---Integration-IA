@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Impuestos</h1>
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
    <div class="card-header">
      {{-- <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear-slider"><i class="fa-solid fa-plus"></i>  CREAR</button> --}}

      {{-- MODAL CREAR --}}
      {{-- <div class="modal fade" id="crear-slider" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              Crear impuesto
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id='agregar' class='mb-4 loading' action="{{route('caracteristica.store')}}" method="POST" enctype="multipart/form-data">
                @csrf
            
                <div class="row g-3 align-items-center">
                  <div class="col-2">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="text" class="form-control" name='orden' value='zz' required>
                  </div>
                  <div class="col-10">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" name='nombre' required>
                  </div>
                </div>
            
            
              </form>
            </div>
            <div class="modal-footer">
              <button  data-form-id="agregar" type="submit" class="btn btn-primary submit" >Agregar</button> 
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </div>
        </div>
      </div> --}}
    </div>
    <div class="card-body">


      <table class="table table-striped">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Texto en producto</th>
            <th>Porcentaje</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($impuestos as $impuesto)
            <tr>
              <td>{{$impuesto->nombre}}</td>
              <td>{{$impuesto->texto}}</td>
              <td>{{$impuesto->porcentaje}} %</td>
              
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$impuesto->id}}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  {{-- <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$car->id}}"><i class="fa-solid fa-trash-can"></i></button> --}}
                </div>
                
              </td>
            </tr>

            {{-- MODAL ELIMINAR --}}
            {{-- <div class="modal fade" id="{{'eliminar'.$car->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body fs-4 d-flex justify-content-center" style='text-align:center;'>
                      ¿Desea eliminar la característica {{ucfirst($car->nombre)}}?<br>Se eliminará de todos los productos.
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <form action="{{ route('caracteristica.delete', ['id' => $car->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$car->id}}">Eliminar</button>
                        </form>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                  </div>
                </div>
              </div> --}}
          @endforeach

        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Modales para editar -->
  @foreach ($impuestos as $impuesto)

    <div class="modal fade" id="{{'editar'.$impuesto->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            Editar impuesto
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id='{{'form'.$impuesto->id}}'  action="{{route('impuesto.update',['id'=>$impuesto->id])}}" method="post" enctype="multipart/form-data">
              @csrf
              @method('put')

              <div class='row'>
                

                <div class="mb-3 col-5">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" name='nombre' value='{{$impuesto->nombre}}' required>
                </div>
                <div class="mb-3 col-5">
                    <label for="texto" class="form-label">Texto</label>
                    <input type="text" class="form-control" name='texto' value='{{$impuesto->texto}}' required>
                </div>
                <div class="mb-3 col-2">
                    <label for="porcentaje" class="form-label">Porcenaje</label>
                    <input type="number" class="form-control" name='porcentaje' value='{{$impuesto->porcentaje}}' required>
                </div>
              </div>
              

            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button  data-form-id="{{'form'.$impuesto->id}}" type="submit" class="btn btn-primary submit">Actualizar</button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
  
@endsection
