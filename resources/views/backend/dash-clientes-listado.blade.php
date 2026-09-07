<table class="table table-striped" style='border: 1px solid #dddddd;'>
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Razón social</th>
        <th>DNI / CUIT</th>
        <th>Provincia</th>
        <th>Localidad</th>
        <th>Celular</th>
        <th>Email</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($clientes as $usuario)
        <tr>
            <td>{{(ucfirst($usuario->username))}}</td>
            <td>{{(ucfirst($usuario->name))}}</td>
            <td>{{(ucfirst($usuario->dni))}}</td>
            <td>{{(ucfirst($nombres[array_search($usuario->provincia, $referencia)]))}}</td>
            <td>{{(ucfirst($usuario->localidad))}}</td>
            <td>{{(ucfirst($usuario->celular))}}</td>
            <td>{{$usuario->email}}</td>
            <td>
            <div class='d-flex'>
                <button type="button" onclick='habilitar(event, {{$usuario->id}})' class="habilitar btn btn-warning btn-sm me-1">
                {{$usuario->habilitado ? 'Deshabilitar' : 'Habilitar'}}
                </button>
                <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#{{'usuario'.$usuario->id}}">
                <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#{{'eliminar'.$usuario->id}}">
                <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
            
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@foreach ($clientes as $usuario)
{{-- MODALES --}}
<div class="modal fade" id="{{'usuario'.$usuario->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <div class="modal-header">
        Editar usuario
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        <form id='form{{$usuario->id}}' method="POST" action="{{ route('cliente.update', ['id' => $usuario->id]) }}">
            @csrf
            @method('put')

            <!-- username -->
            <div class="mt-4 col-12">
                <x-input-label class='form-label' for="username" :value="__('Nombre de usuario*')" />
                <x-text-input id="username" class="form-control" type="text" name="username" value="{{$usuario->username}}" autocomplete="username" />
                <x-input-error :messages="$errors->get('username')" class="mt-2"/>
            </div>

            <div class='row mt-4'>
                <!-- Password -->
                <div class="col-6">
                    <x-input-label class='form-label' for="password" :value="__('Contraseña')" />

                    <div class="password-wrap">
                        <x-text-input id="password" class="form-control input-password contr{{$usuario->id}}"
                                        type="password"
                                        name="password"
                                            autocomplete="new-password" disabled/>

                        <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                </div>
    
                <!-- Confirm Password -->
                <div class="col-6">
                    <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />

                    <div class="password-wrap">
                        <x-text-input id="password_confirmation" class="form-control input-password contr{{$usuario->id}}"
                                        type="password"
                                        name="password_confirmation"  autocomplete="new-password" disabled/>

                        <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>
            
            <input data-id='{{$usuario->id}}' class='cambio-contr' type="checkbox" name='check' onclick='togglePassword(event)'>
            <label for="check">Cambiar contraseña</label>

            <div class='row'>
                <!-- Name -->
                <div class="mt-4 col-6">
                    <x-input-label class='form-label' for="nombre" :value="__('Nombre y apellido / Razón social*')" />
                    <x-text-input id="nombre" class="form-control" type="text" name="nombre" value="{{$usuario->name}}" autocomplete="nombre" />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2"/>
                </div>

                <div class="mt-4 col-6">
                    <x-input-label class='form-label' for="dni" :value="__('DNI / CUIT*')" />
                    <x-text-input id="dni" class="form-control" type="number" name="dni" value="{{$usuario->dni}}"  autocomplete="dni" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>

            <div class='row'>
                <!-- Email Address -->
                <div class="mt-4 col-6">
                    <x-input-label class='form-label' for="email" :value="__('Email*')" />
                    <x-text-input id="email" class="form-control" type="email" name="email" value="{{$usuario->email}}"  autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4 col-6">
                    <x-input-label class='form-label' for="celular" :value="__('Celular*')" />
                    <x-text-input id="celular" class="form-control" type="number" name="celular" value="{{$usuario->celular}}" autocomplete="celular" />
                    <x-input-error :messages="$errors->get('celular')" class="mt-2"/>
                </div>
            </div>

            

            <div class="mt-4 col-12">
                <x-input-label class='form-label' for="direccion" :value="__('Dirección*')" />
                <x-text-input id="direccion" class="form-control" type="text" name="direccion" value="{{$usuario->direccion}}" autocomplete="direccion" />
                <x-input-error :messages="$errors->get('direccion')" class="mt-2"/>
            </div>

            <div class='row mt-4'>
                <div class="col-4">
                    <label for="provincia" class="form-label">Provincia</label>
                    <select data-id='{{$usuario->id}}' class="form-select select-provincia" name="provincia" >
                        @foreach ($provincias as $provincia)
                            <option {{$provincia['id'] == $usuario->provincia ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                        @endforeach
                        
                    </select>
                </div>

                <div class="col-4">
                    <label for="localidad" class="form-label">Localidad</label>
                    <select id='localidades{{$usuario->id}}' class="form-select" name="localidad" >
                        @foreach ($localidades as $localidad)
                            <option {{$localidad['nombre'] == $usuario->localidad ? 'selected' : ''}} value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                        @endforeach
                        
                    </select>
                </div>

                <div class="col-4">
                    <x-input-label class='form-label' for="cp" :value="__('Código Postal*')" />
                    <x-text-input id="cp" class="form-control" type="text" name="cp" value="{{$usuario->cp}}" autocomplete="cp" />
                    <x-input-error :messages="$errors->get('cp')" class="mt-2"/>
                </div>
                <div class='col-4'>
                    <label for="descuento" class='form-label mt-4'>Descuento (%)</label>
                    <input type="number" name='descuento' class='form-control' value={{$usuario->descuento}} required>
                </div>
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
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body fs-4 d-flex justify-content-center">
        ¿Desea eliminar el cliente {{$usuario->username}}?
        </div>
        <div class="modal-footer d-flex justify-content-center">
        
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form action="{{ route('cliente.delete', ['id' => $usuario->id]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Eliminar</button>
        </form>
        </div>
    </div>
    </div>
</div>
@endforeach

<script>
    // ACTUALIZAR LOCALIDADES
    $('.select-provincia').on('change', function(e) {
        console.log('dkjdks')
        var id = $(this).data('id')
        var provinciaId = $(this).val();
        var url = 'https://apis.datos.gob.ar/georef/api/localidades?provincia=' + provinciaId + '&orden=nombre&max=1000';

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                // Limpiar y actualizar el segundo select con los datos obtenidos
                var select2 = $('#localidades' + id);
                select2.empty();

                $.each(data.localidades, function(index, localidad) {
                    select2.append($('<option>', { 
                        value: localidad.nombre, 
                        text: localidad.nombre 
                    }));
                });
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });

    });

    $('.submit').on('click', function() {
        var formId = $(this).data('form-id');
        var form = $('#' + formId);

        if (form[0].checkValidity()) {
            form.submit();
        } else {
            form[0].reportValidity();
        }
    });
</script>