@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Usuarios</h1>

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
      Editar información
    </div>
    <div class="card-body">
  
      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($editar_usuarios as $usuario)
            <tr>

              <td>{{$usuario->username}}</td>
              <td>{{$usuario->email}}</td>
              <td>{{$usuario->rol}}</td>
              <td>
                <div class='d-flex'>
                  <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'usuario'.$usuario->id}}">
                    Editar
                  </button>
                  <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$usuario->id}}">
                    Eliminar
                  </button>
                </div>
                
              </td>
            </tr>

            {{-- MODALES --}}
            <div class="modal fade" id="{{'usuario'.$usuario->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    Editar usuario
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id='{{'form'.$usuario->id}}'  action="{{route('usuario.update', ['id' => $usuario->id])}}" method="post" enctype="multipart/form-data">
                      @csrf
                      @method('put')

                      <div class="row">
                        <!-- username -->
                        <div class="col-9">
                            <x-input-label class='form-label' for="username" :value="__('Usuario')" />
                            <x-text-input id="username" class="form-control" type="text" name="username" value="{{$usuario->username}}"  autocomplete="username" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                      
                        <div class="col-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" name="rol">
                                <option {{$usuario->rol == 'usuario' ? 'selected' : ''}} value="usuario">Usuario</option>
                                <option {{$usuario->rol == 'administrador' ? 'selected' : ''}} value="administrador">Administrador</option>
                            </select>
                        </div>
                      </div>

                      <!-- Email Address -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="email" :value="__('Email')" />
                          <x-text-input id="email" class="form-control" type="email" name="email"  value="{{$usuario->email}}"  autocomplete="username" />
                          <x-input-error :messages="$errors->get('email')" class="mt-2" />
                      </div>

                      <!-- Password -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="password" :value="__('Nueva contraseña')" />

                          <x-text-input id="password" class="form-control"
                                          type="password"
                                          name="password"
                                              autocomplete="new-password"/>

                          <x-input-error :messages="$errors->get('password')" class="mt-2" />
                      </div>

                      <!-- Confirm Password -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />

                          <x-text-input id="password_confirmation" class="form-control"
                                          type="password"
                                          name="password_confirmation"  autocomplete="new-password" />

                          <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                      </div>
                      
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button data-form-id="{{'form'.$usuario->id}}" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$usuario->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body fs-4 d-flex justify-content-center">
                    ¿Desea eliminar el usuario {{$usuario->username}}?
                  </div>
                  <div class="modal-footer d-flex justify-content-center">
                    <form action="{{ route('usuario.delete', ['id' => $usuario->id]) }}" method="POST">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
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