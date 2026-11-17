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
                {{-- Anti-bots: honeypot + token de tiempo (EnsureHumanFormSubmission) y rate limit `registro`. --}}
                <x-anti-bots id="registro" />

                <div class='row'>

                    <!-- username -->
                    <div class="mt-4 col-12">
                        <x-input-label class='form-label' for="username" :value="__('Nombre de usuario*')" />
                        <x-text-input id="username" class="form-control" type="text" name="username" :value="old('username')" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9._\-]*" title="Letras, números, punto, guion o guion bajo, sin espacios" required />
                        <small class="text-muted d-block mt-1">Sin espacios: letras, números, punto, guion o guion bajo.</small>
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
                        <x-text-input id="nombre" class="form-control" type="text" name="nombre" :value="old('nombre')" autocomplete="name" maxlength="120" required />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2"/>
                    </div>

                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="dni" :value="__('DNI / CUIT*')" />
                        <x-text-input id="dni" class="form-control" type="text" inputmode="numeric" name="dni" :value="old('dni')" autocomplete="off" maxlength="14" required />
                        <x-input-error :messages="$errors->get('dni')" class="mt-2" />
                    </div>

                    <!-- Email Address -->
                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="email" :value="__('Email*')" />
                        <x-text-input id="email" class="form-control" type="email" name="email" :value="old('email')" autocomplete="email" maxlength="120" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4 col-md-6">
                        <x-input-label class='form-label' for="celular" :value="__('Celular*')" />
                        <x-text-input id="celular" class="form-control" type="tel" inputmode="tel" name="celular" :value="old('celular')" autocomplete="tel" maxlength="20" placeholder="1144822609" required />
                        <x-input-error :messages="$errors->get('celular')" class="mt-2"/>
                    </div>

                    

                    <div class="mt-4 col-md-12">
                        <x-input-label class='form-label' for="direccion" :value="__('Dirección*')" />
                        <x-text-input id="direccion" class="form-control" type="text" name="direccion" :value="old('direccion')" autocomplete="street-address" maxlength="160" required />
                        <x-input-error :messages="$errors->get('direccion')" class="mt-2"/>
                    </div>

                    <div class="mt-4 col-md-4">
                        <label for="provincia" class="form-label">Provincia</label>
                        <select id='select-provincia' class="form-select" name="provincia" >
                            @foreach ($provincias as $provincia)
                                <option {{$provincia['id'] == '02' ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                            @endforeach
                            
                        </select>
                        <x-input-error :messages="$errors->get('provincia')" class="mt-2"/>
                    </div>
    
                    <div class="mt-4 col-md-4">
                        <label for="localidad" class="form-label">Localidad</label>
                        <select id='localidades' class="form-select" name="localidad" >
                            @foreach ($localidades as $localidad)
                                <option value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                            @endforeach
                            
                        </select>
                        <x-input-error :messages="$errors->get('localidad')" class="mt-2"/>
                    </div>
    
                    <div class="mt-4 col-md-4">
                        <x-input-label class='form-label' for="cp" :value="__('Código Postal*')" />
                        <x-text-input id="cp" class="form-control" type="text" name="cp" :value="old('cp')" autocomplete="postal-code" maxlength="10" required />
                        <x-input-error :messages="$errors->get('cp')" class="mt-2"/>
                    </div>
                </div>

                
                
                
                <button id='nosotros-mas' type="submit" class="mt-5 green-btn submit" style='float: right;'>{{ __('Registrarme') }}</button>
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

    // «Procesando» recién cuando el navegador da el formulario por válido (antes cambiaba aunque faltaran datos).
    document.getElementById('formulario').addEventListener('submit', function () {
        var boton = this.querySelector('[type=submit]');
        boton.disabled = true;
        boton.innerHTML = '<div class="loading-spinner"></div>  Procesando';
    });


</script>
@endsection