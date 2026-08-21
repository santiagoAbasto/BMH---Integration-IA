@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Caracteristicas</h1>

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
      Caracteristica
    </div>
    <div class="card-body">
      <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success mt-2 mb-2"><i
        class="fa-solid fa-plus"></i> CREAR</button> 

           {{-- MODAL CREAR --}}
           <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        Crear caracteristica
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class='pb-3' id='formulario-crear' action="{{ route('caracteristicas.store') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class='row'>
                         
                                <div class="col-10 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name='nombre' required>
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button data-form-id="formulario-crear" type="submit"
                            class="btn btn-primary submit">Agregar</button>
                    </div>
                </div>
            </div>
        </div>
        
        
      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
              <th>Orden</th>
            <th>Nombre</th>
        
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($caracteristicas as $dato)
            <tr>
              <td>{{$dato->orden}}</td>

              <td>{{$dato->nombre}}</td>
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'dato'.$dato->id}}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <form action="{{ route('caracteristicas.delete', ['id' => $dato->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" ><i class="fa-solid fa-trash-can"></i></button>
                </form>
                </div>
                
              </td>
            </tr>

            {{-- MODALES --}}
            <div class="modal fade" id="{{'dato'.$dato->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <h3>{{ucfirst($dato->seccion)}}</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id='{{'form'.$dato->id}}'  action="{{route('caracteristicas.update', ['id' => $dato->id])}}" method="post" enctype="multipart/form-data">
                      @csrf
                      @method('put')
                      
                           <div class="mb-3">
                        <label for="keyword" class="form-label">Orden</label>
                        <input type="text" class="form-control" value='{{$dato->orden}}' name='orden'>
                      </div>
                    
                      <div class="mb-3">
                        <label for="keyword" class="form-label">Nombre</label>
                        <input type="text" class="form-control" value='{{$dato->nombre}}' name='nombre'>
                      </div>
                
                      
                      
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button  data-form-id="{{'form'.$dato->id}}" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>

          @endforeach

        </tbody>
      </table>
    </div>
  </div>

  

@endsection