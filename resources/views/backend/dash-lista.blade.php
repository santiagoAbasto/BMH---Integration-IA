@extends('layouts.plantilla-back')

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Carrito" title="Lista de precios" description="El archivo de la lista de precios que descargan los clientes." />

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
      <div class="adm-card-titulo">
        <h2>Archivos</h2>
        <p>Editá cada lista para reemplazar el archivo o cambiarle el nombre.</p>
      </div>
    </div>
    <div class="card-body">
    <table class="table">
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
                <div class='adm-acciones'>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="{{'#editar'.$lista->id}}" aria-label="Editar"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                    {{-- <form action="{{ route('descarga.delete', ['id' => $lista->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$lista->id}}" aria-label="Eliminar"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                    </form> --}}
                </div>
                
                </td>
            </tr>
            @endforeach

        </tbody>
    </table>
    </div>
  </div>

    @foreach ($listas as $lista)

{{-- MODAL EDITAR --}}
<div class="modal fade" id="editar{{$lista->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
    <div class="modal-header">
        <h2 class="modal-title">Editar lista</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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
