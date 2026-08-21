@extends('layouts.plantilla-front')

{{-- @section('styles')
<link rel="stylesheet" href="css/nosotros.css">
@endsection --}}

@section('styles')
<style>
    .form-control{
        border-radius: 12px !important
    }
    .form-select{
        border-radius: 12px !important

    }
</style>
@endsection
@section('content')

    <section style='padding-top:69px;padding-bottom:125px' data-aos='fade-up'>
        <div class='container d-flex flex-column' style='align-items:center;'>

            <h2 class='pb-4' style='text-align:left;'>Crear cuenta</h2>

            <form id='formulario' method="POST" action="{{ route('crear.usuario', ['rol' => 'cliente']) }}" style='max-width:734px;'>
                @csrf

                <div class='row'>

                    <!-- username -->
                    <div class="mt-4 col-12">
                        <x-input-label class='form-label' for="username" :value="__('Nombre de usuario*')" />
                        <x-text-input id="username" class="form-control" type="text" name="username" :value="old('username')" autocomplete="username" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2"/>
                    </div>

                    <!-- Password -->
                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="password" :value="__('Contraseña')" />
        
                        <x-text-input id="password" class="form-control"
                                        type="password"
                                        name="password"
                                            autocomplete="new-password" />
        
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
        
                    <!-- Confirm Password -->
                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />
        
                        <x-text-input id="password_confirmation" class="form-control"
                                        type="password"
                                        name="password_confirmation"  autocomplete="new-password" />
        
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <!-- Name -->
                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="nombre" :value="__('Nombre y apellido / Razón social*')" />
                        <x-text-input id="nombre" class="form-control" type="text" name="nombre" :value="old('nombre')" autocomplete="nombre" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2"/>
                    </div>

                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="dni" :value="__('DNI / CUIT*')" />
                        <x-text-input id="dni" class="form-control" type="number" name="dni" :value="old('dni')"  autocomplete="dni" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Email Address -->
                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="email" :value="__('Email*')" />
                        <x-text-input id="email" class="form-control" type="email" name="email" :value="old('email')"  autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="celular" :value="__('Celular*')" />
                        <x-text-input id="celular" class="form-control" type="number" name="celular" :value="old('celular')" autocomplete="celular" />
                        <x-input-error :messages="$errors->get('celular')" class="mt-2"/>
                    </div>

                    

                    <div class="mt-4 col-md-12">
                        <x-input-label class='form-label' for="direccion" :value="__('Dirección*')" />
                        <x-text-input id="direccion" class="form-control" type="text" name="direccion" :value="old('direccion')" autocomplete="direccion" />
                        <x-input-error :messages="$errors->get('direccion')" class="mt-2"/>
                    </div>

                    <div class="mt-4 col-md-4">
                        <label for="provincia" class="form-label">Provincia</label>
                        <select id='select-provincia' class="form-select" name="provincia" >
                            @foreach ($provincias as $provincia)
                                <option {{$provincia['id'] == '02' ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                            @endforeach
                            
                        </select>
                    </div>
    
                    <div class="mt-4 col-md-4">
                        <label for="localidad" class="form-label">Localidad</label>
                        <select id='localidades' class="form-select" name="localidad" >
                            @foreach ($localidades as $localidad)
                                <option value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                            @endforeach
                            
                        </select>
                    </div>
    
                    <div class="mt-4 col-md-4">
                        <x-input-label class='form-label' for="cp" :value="__('Código Postal*')" />
                        <x-text-input id="cp" class="form-control" type="text" name="cp" :value="old('cp')" autocomplete="cp" />
                        <x-input-error :messages="$errors->get('cp')" class="mt-2"/>
                    </div>
                </div>

                
                
                
                <button id='nosotros-mas' type="submit" onclick='toggleSpinner(event)' class="mt-5 green-btn submit" style='float: right;'>{{ __('Registrarme') }}</button>
            </form>
            
                
        </div>
    </section>
    
@endsection

@section('script')

<script>

    // ACTUALIZAR LOCALIDADES
    document.getElementById('select-provincia').addEventListener('change', function() {
        
        var provinciaId = this.value;
        var url = 'https://apis.datos.gob.ar/georef/api/localidades?provincia=' + provinciaId + '&orden=nombre&max=1000';

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                // Limpiar y actualizar el segundo select con los datos obtenidos
                var select2 = $('#localidades');
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

    function toggleSpinner(event){
        event.target.innerHTML = '<div class="loading-spinner"></div>  Procesando';
    }


</script>
@endsection