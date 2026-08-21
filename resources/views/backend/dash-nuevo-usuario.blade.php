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

                <x-text-input id="password" class="form-control"
                                type="password"
                                name="password"
                                    autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4 col-12">
                <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />

                <x-text-input id="password_confirmation" class="form-control"
                                type="password"
                                name="password_confirmation"  autocomplete="new-password" />

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
