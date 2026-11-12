@extends('layouts.plantilla-back')

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Contenido del sitio" title="Descargas" />

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
    <div class="card-header">
      <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success"><i class="fa-solid fa-plus"></i>  CREAR</button>

      {{-- MODAL CREAR --}}
      <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h2 class="modal-title">Crear descarga</h2>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <form class='pb-3' id='formulario-crear' action="{{route('descarga.store')}}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class='row'>
                    <div class="col-2 mb-3">
                        <label for="orden" class="form-label">Orden</label>
                        <input type="text" class="form-control" name='orden' value='aa' required>
                    </div>
                    <div class="col-10 mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name='nombre' required>
                    </div>
                    <div class="mb-3">
                      <label for="file" class="form-label">Archivo</label>
                      <input class="form-control preview" type="file" name='file' accept="file/*" required>
                    </div>
                    
                </div>

              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button  data-form-id="formulario-crear" type="submit" class="btn btn-primary submit" >Agregar</button> 
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Orden</th>
            <th>Nombre</th>
            <th>Archivo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($descargas as $descarga)
            <tr>
              <td>{{$descarga->orden}}</td>
              <td>{{ucfirst($descarga->nombre)}}</td>
              <td>{{$descarga->archivo}}</td>
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="{{'#editar'.$descarga->id}}" aria-label="Editar"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                  <form action="{{ route('descarga.delete', ['id' => $descarga->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$descarga->id}}" aria-label="Eliminar"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                  </form>
                </div>
                
              </td>
            </tr>

            
          @endforeach

        </tbody>
      </table>

  </div>
</div>

@foreach ($descargas as $descarga)
  {{-- MODAL EDITAR --}}
  <div class="modal fade" id="editar{{$descarga->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Editar descarga</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form id='agregar{{$descarga->id}}' class='mb-4 loading' action="{{route('descarga.update', ['id' => $descarga->id])}}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('put')    
            
            <div class="row g-3 align-items-center">
              <div class="col-2 ">
                <label for="orden" class="form-label">Orden</label>
                <input type="text" class="form-control" name='orden' value='{{$descarga->orden}}' required>
              </div>
              <div class="col-10">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name='nombre' value='{{$descarga->nombre}}' required>
              </div>
              <div class="my-3">
                <label for="file" class="form-label">Archivo</label>
                <input class="form-control preview" data-form-id="{{'imagen'.$descarga->id}}" type="file" id="imagen" name='file' accept="file/*">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button  data-form-id="agregar{{$descarga->id}}" type="submit" class="btn btn-primary submit" >Actualizar</button> 
        </div>
      </div>
    </div>
  </div>


@endforeach

@endsection
