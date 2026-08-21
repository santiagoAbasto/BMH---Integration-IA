@extends('layouts.plantilla-back')

@section('content')
<h1 class='mb-4'>Zonas postales</h1>
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
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear-slider"><i class="fa-solid fa-plus"></i>  CREAR</button>

      {{-- MODAL CREAR --}}
      <div class="modal fade" id="crear-slider" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              Crear zona postal
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id='agregar' class='mb-4 loading' action="{{route('zona.store')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class='row'>
                    <div class="col-9">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name='nombre' maxlength="255" required>
                    </div>
                    <div class="col-3">
                        <label for="costo" class="form-label">Costo</label>
                        <input type="number" class="form-control" name='costo' required>
                    </div>
                </div>
                
              </form>
            </div>
            <div class="modal-footer">
              <button  data-form-id="agregar" type="submit" class="btn btn-primary submit" >Añadir</button> 
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">


      <table class="table table-striped">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Costo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($zonas as $zona)
            <tr>
              <td>{{$zona->nombre}}</td>
              <td>{{$zona->costo}}</td>
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$zona->id}}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$zona->id}}"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                
              </td>
            </tr>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$zona->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body fs-4 d-flex justify-content-center" style='text-align:center;'>
                      ¿Desea eliminar la zona postal {{$zona->nombre}}?<br>Los códigos postales asociados quedarán sin costo asignado.
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <form action="{{route('zona.delete', ['id' => $zona->id])}}" method="POST">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$zona->id}}">Eliminar</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
          @endforeach

        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Modales para editar -->
  @foreach ($zonas as $zona)
    <div class="modal fade" id="{{'editar'.$zona->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
          <div class="modal-header">
            Editar zona postal
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id='{{'form'.$zona->id}}'  action="{{route('zona.update', ['id' => $zona->id])}}" method="post" enctype="multipart/form-data">
              @csrf
              @method('put')
                <div class='row'>
                  <div class="mb-3 col-8">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" name='nombre' value='{{$zona->nombre}}' maxlength="255" required>
                  </div>
                  <div class="mb-3 col-4">
                    <label for="costo" class="form-label">Costo</label>
                    <input type="number" class="form-control" name='costo' value='{{$zona->costo}}' required>
                  </div>
                </div>
                

            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button  data-form-id="{{'form'.$zona->id}}" type="submit" class="btn btn-primary submit">Actualizar</button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
  
@endsection
