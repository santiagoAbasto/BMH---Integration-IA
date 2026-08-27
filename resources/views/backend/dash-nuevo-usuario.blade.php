@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Crear usuario</h1>

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

  <div class="card" style='margin-right:400px;margin-left:400px;'>
    <div class="card-header">
    </div>
    <div class="card-body">

        <form method="POST" id='formulario' action="{{ route('crear.usuario') }}">
            @csrf
            <!-- Name -->
            <div>
                <x-text-input hidden id="name" class="form-control" type="text" name="name" value="alberto"  autofocus autocomplete="name" />
            </div>

            <div class='row'>
                <!-- username -->
                <div class="col-8">
                    <x-input-label class='form-label' for="username" :value="__('Usuario')" />
                    <x-text-input id="username" class="form-control" type="text" name="username" :value="old('username')"  autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div class="col-4">
                    <label for="rol" class="form-label">Rol</label>
                    <select id="rol-select" class="form-select" name="rol">
                        <option selected value="usuario">Usuario</option>
                        <option value="administrador">Administrador</option>
                        <option value="clienteadm">Cliente</option>

                    </select>
                </div>
            </div>
            

            <!-- Email Address -->
       

            <div class="mt-4 col-12" id="descuento-container" style="display: none;">
                <x-input-label class='form-label' for="descuento" :value="__('Descuento')" />
                <x-text-input id="descuento" class="form-control" type="number" name="descuento" value="0" />
                <x-input-error :messages="$errors->get('descuento')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4 col-21">
                <x-input-label class='form-label' for="password" :value="__('Contraseña')" />

                <div class="password-wrap">
                <x-text-input id="password" class="form-control"
                                type="password"
                                name="password"
                                    autocomplete="new-password" />

                <button type="button" class="btn-toggle-password" onclick="toggleClientePasswordVisibility(this)" aria-label="Mostrar u ocultar contraseña">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4 col-12">
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

            <button  type="submit" data-form-id='formulario' class="mt-4 btn btn-primary submit" style='float: right;'>{{ __('Crear') }}</button>
        </form>
    </div>
  </div>
  

@endsection



@section('script')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rolSelect = document.getElementById('rol-select');
        const descuentoContainer = document.getElementById('descuento-container');

        // Función para mostrar/ocultar el campo email y agregar el campo descuento
        function toggleFields() {
            if (rolSelect.value === 'clienteadm') {
                // Si selecciona "clienteadm", ocultar email y mostrar descuento
                descuentoContainer.style.display = 'block';
            } else {
                // Para otros roles, mostrar email y ocultar descuento
                descuentoContainer.style.display = 'none';
            }
        }

        // Ejecutar la función al cargar la página para aplicar el estado inicial
        toggleFields();

        // Ejecutar la función cuando cambie la selección del rol
        rolSelect.addEventListener('change', toggleFields);
    });
</script>
@endsection
