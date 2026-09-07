@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Vendedores</h1>

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
    <div class="card-header d-flex justify-content-between">
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crear"><i class="fa-solid fa-plus"></i>  CREAR</button>
      {{-- MODAL CREAR --}}
      <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
          <div class="modal-content">
            <div class="modal-header">
              Crear vendedor
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id='crear-vendedor'  action="{{route('crear.usuario', ['rol' => 'vendedor'])}}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="row">
                  <!-- nombre -->
                  <div class="col-6">
                    <x-input-label class='form-label' for="nombre" :value="__('Nombre')" />
                    <x-text-input id="nombre" class="form-control" type="text" name="nombre"  autocomplete="name" required/>
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>
                  <!-- username -->
                  <div class="col-6">
                      <x-input-label class='form-label' for="username" :value="__('Usuario')" />
                      <x-text-input id="username" class="form-control" type="text" name="username" autocomplete="username" required/>
                      <x-input-error :messages="$errors->get('username')" class="mt-2" />
                  </div>

                </div>

                <!-- Email Address -->
                <div class="mt-4">
                    <x-input-label class='form-label' for="email" :value="__('Email')" />
                    <x-text-input id="email" class="form-control" type="email" name="email" autocomplete="username" required/>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label class='form-label' for="password" :value="__('Contraseña')" />

                    <div class="password-wrap">
                    <x-text-input id="password" class="form-control"
                                    type="password"
                                    name="password"
                                        autocomplete="new-password" required/>

                    <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mt-4">
                    <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />

                    <div class="password-wrap">
                    <x-text-input id="password_confirmation" class="form-control"
                                    type="password"
                                    name="password_confirmation"  autocomplete="new-password" requried/>

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
              <button  data-form-id="crear-vendedor" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">
  
      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Email</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id='clientes-contenedor'>

            @foreach ($vendedores as $vendedor)
            <tr>
              <td>{{(ucfirst($vendedor->name))}}</td>
              <td>{{$vendedor->username}}</td>
              <td>{{$vendedor->email}}</td>
              <td>
                <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'editar'.$vendedor->id}}">
                <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$vendedor->id}}">
                <i class="fa-solid fa-trash-can"></i>
                </button>
              </td>
            </tr>

            {{-- MODALES --}}
            <div class="modal fade" id="{{'editar'.$vendedor->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    Editar vendedor
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id='{{'form'.$vendedor->id}}'  action="{{route('vendedor.update', ['id' => $vendedor->id])}}" method="post" enctype="multipart/form-data">
                      @csrf
                      @method('put')

                      <div class="row">
                        <!-- nombre -->
                        <div class="col-6">
                          <x-input-label class='form-label' for="nombre" :value="__('Nombre')" />
                          <x-text-input id="nombre" class="form-control" type="text" name="nombre" value="{{$vendedor->name}}"  autocomplete="name" required/>
                          <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                      </div>
                        <!-- username -->
                        <div class="col-6">
                            <x-input-label class='form-label' for="username" :value="__('Usuario')" />
                            <x-text-input id="username" class="form-control" type="text" name="username" value="{{$vendedor->username}}"  autocomplete="username" required/>
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                      </div>

                      <!-- Email Address -->
                      <div class="mt-4">
                          <x-input-label class='form-label' for="email" :value="__('Email')" />
                          <x-text-input id="email" class="form-control" type="email" name="email"  value="{{$vendedor->email}}"  autocomplete="username" required/>
                          <x-input-error :messages="$errors->get('email')" class="mt-2" />
                      </div>

                       <!-- Password -->
                       <div class="mt-4">
                           <x-input-label class='form-label' for="password" :value="__('Nueva contraseña')" />

                           <div class="password-wrap">
                           <x-text-input id="password" class="form-control"
                                           type="password"
                                           name="password"
                                               autocomplete="new-password"/>

                           <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                               <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                               <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                           </button>
                           </div>

                           <x-input-error :messages="$errors->get('password')" class="mt-2" />
                       </div>

                       <!-- Confirm Password -->
                       <div class="mt-4">
                           <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />

                           <div class="password-wrap">
                           <x-text-input id="password_confirmation" class="form-control"
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
                    <button  data-form-id="{{'form'.$vendedor->id}}" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>

            {{-- MODAL ELIMINAR --}}
            <div class="modal fade" id="{{'eliminar'.$vendedor->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body fs-4 d-flex justify-content-center">
                    ¿Desea eliminar el vendedor {{$vendedor->username}}?
                  </div>
                  <div class="modal-footer d-flex justify-content-center">
                    <form action="{{ route('vendedor.delete', ['id' => $vendedor->id]) }}" method="POST">
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
    <div class='card-footer'>
    </div>
  </div>

  

@endsection

@section('script')
<script>
    // BUSCADOR
    $('.search-btn').on('click', function(e) {
      var valor = $('.search-input').val()
      $.ajax({
          url: "{{ route('cliente.buscar') }}",
          type: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              valor: valor
          },
          success: function(response) {
              // console.log(response);
              $('#clientes-contenedor').html(response)
          },
          error: function(xhr) {
              console.error(xhr.responseText);
          }
      });
    })

    function togglePassword(event){
      var element = event.target
      var id = $(element).data('id')
        if($(element).is(':checked')){
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = false
            });
        } else {
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = true
            });
        }
    }
</script>
@endsection