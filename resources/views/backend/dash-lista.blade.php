@extends('layouts.plantilla-back')

@section('content')

  <h1 class='mb-4'>Lista de precios</h1>

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


    <table class="table" style='border: 1px solid #dddddd;'>
        <thead>
            <tr>
            <th>Nombre</th>
            <th>Archivo</th>
            <th>Acciones</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($listas as $lista)
                
            <tr>
                <td>{{ucfirst($lista->nombre)}}</td>
                <td>{{$lista->archivo}}</td>
                <td>
                <div class='d-flex'>
                    <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="{{'#editar'.$lista->id}}"><i class="fa-regular fa-pen-to-square"></i></button>
                    {{-- <form action="{{ route('descarga.delete', ['id' => $lista->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$lista->id}}"><i class="fa-solid fa-trash-can"></i></button>
                    </form> --}}
                </div>
                
                </td>
            </tr>
            @endforeach

        </tbody>
    </table>

    @foreach ($listas as $lista)

{{-- MODAL EDITAR --}}
<div class="modal fade" id="editar{{$lista->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
    <div class="modal-header">
        Editar lista
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <form id='agregar{{$lista->id}}' class='mb-4 loading' action="{{route('descarga.update', ['id' => $lista->id])}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')    
        
        <div class="row g-3 align-items-center">
            <div class="col-12">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" name='nombre' value='{{$lista->nombre}}' required>
            </div>
            <div class="my-3">
            <label for="file" class="form-label">Archivo</label>
            <input class="form-control preview" data-form-id="{{'imagen'.$lista->id}}" type="file" id="imagen" name='file' accept="file/*">
            </div>
        </div>
        </form>
    </div>
    <div class="modal-footer">
        
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button  data-form-id="agregar{{$lista->id}}" type="submit" class="btn btn-primary submit" >Actualizar</button> 
    </div>
    </div>
</div>
</div>
@endforeach


@endsection
