@extends('layouts.plantilla-back')

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Administración" title="Usuarios" description="Las personas con acceso a este panel y su rol." />

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
  @if($errors->any())
      <div class="alert alert-danger" role="alert">
          <div>
              <strong>No se guardaron los cambios.</strong>
              <ul class="mt-1">
                  @foreach($errors->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
      </div>
  @endif

  <div class="card">
    <div class="card-header">
      Editar información
    </div>
    <div class="card-body">
  
      <table class="table table-striped">
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
                  <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{'usuario'.$usuario->id}}">
                    Editar
                  </button>
                  <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$usuario->id}}">
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
                    <h2 class="modal-title">Editar usuario</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                    <form id='{{'form'.$usuario->id}}'  action="{{route('usuario.update', ['id' => $usuario->id])}}" method="post" enctype="multipart/form-data">
                      @csrf
                      @method('put')

                      <div class="row">
                        <!-- username -->
                        <div class="col-sm-8">
                            <x-input-label class='form-label' for="username-{{ $usuario->id }}" :value="__('Usuario')" />
                            <x-text-input id="username-{{ $usuario->id }}" class="form-control" type="text" name="username" value="{{$usuario->username}}"  autocomplete="username" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                      
                        <div class="col-sm-4 mt-3 mt-sm-0">
                            <label for="rol-{{ $usuario->id }}" class="form-label">Rol</label>
                            <select class="form-select" id="rol-{{ $usuario->id }}" name="rol">
                                <option {{$usuario->rol == 'usuario' ? 'selected' : ''}} value="usuario">Usuario</option>
                                <option {{$usuario->rol == 'administrador' ? 'selected' : ''}} value="administrador">Administrador</option>
                            </select>
                        </div>
                      </div>

                      <!-- Email Address -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="email-{{ $usuario->id }}" :value="__('Email')" />
                          <x-text-input id="email-{{ $usuario->id }}" class="form-control" type="email" name="email"  value="{{$usuario->email}}"  autocomplete="username" />
                          <x-input-error :messages="$errors->get('email')" class="mt-2" />
                      </div>

                      <!-- Password -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="password-{{ $usuario->id }}" :value="__('Contraseña')" />

                          <div class="password-wrap">
                          <x-text-input id="password-{{ $usuario->id }}" class="form-control"
                                          type="password"
                                          name="password"
                                              autocomplete="new-password"/>

                          <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                              <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                          </button>
                          </div>

                          <p class="form-text mb-0">Para guardar hay que escribir una contraseña (puede ser la misma de siempre).</p>
                          <x-input-error :messages="$errors->get('password')" class="mt-2" />
                      </div>

                      <!-- Confirm Password -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="password_confirmation-{{ $usuario->id }}" :value="__('Confirmar contraseña')" />

                          <div class="password-wrap">
                          <x-text-input id="password_confirmation-{{ $usuario->id }}" class="form-control"
                                          type="password"
                                          name="password_confirmation"  autocomplete="new-password" />

                          <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                              <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                          </button>
                          </div>

                          <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                      </div>
                      
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button data-form-id="{{'form'.$usuario->id}}" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
                  </div>
                </div>
              </div>
            </div>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$usuario->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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