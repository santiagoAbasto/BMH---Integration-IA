@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Metadatos</h1>

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
      Editar metadatos
    </div>
    <div class="card-body">
  
      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Sección</th>
            <th>Keyword</th>
            <th>Descripción</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($metadatos as $dato)
            <tr>

              <td>{{$dato->seccion}}</td>
              <td>{{$dato->keyword}}</td>
              <td>{{$dato->descripcion}}</td>
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'dato'.$dato->id}}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
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
                    <form id='{{'form'.$dato->id}}'  action="{{route('metadatos.update', ['id' => $dato->id])}}" method="post" enctype="multipart/form-data">
                      @csrf
                      @method('put')
                    
                      <div class="mb-3">
                        <label for="keyword" class="form-label">Keyword</label>
                        <input type="text" class="form-control" value='{{$dato->keyword}}' name='keyword'>
                      </div>
                      <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripcion</label>
                        <input type="text" class="form-control" value='{{$dato->descripcion}}' name='descripcion'>
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