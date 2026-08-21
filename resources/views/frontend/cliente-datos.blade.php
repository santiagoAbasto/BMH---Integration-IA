@extends('layouts.plantilla-front')

@section('styles')
{{-- <link rel="stylesheet" href="css/nosotros.css"> --}}

<style>
    input{
        height: 44px;
        border: 1px solid #EBEBEB;
        border-radius: 4px;
        width: 100%;
        padding-left: 10px

    }
    input::placeholder{
        color: #414141;
font-family: 'Montserrat';
font-size: 13px;
font-style: normal;
font-weight: 400;
line-height: normal;

}

    .btn-guardar{
        display: flex;
width: 392px;
height: 39px;
padding: 10px 32px;
justify-content: center;
align-items: center;
gap: 10px;
flex-shrink: 0;
border-radius: 10px;
background: #0098DA;
color: #FFF;
font-family: Montserrat;
font-size: 16px;
font-style: normal;
font-weight: 400;
line-height: normal;
    }

    .btn-guardar:hover{
        background: white;
        color: #0098DA;
        border: 1px solid #0098DA
    }
</style>
@endsection


@section('content')
<section style="padding-top: 50px; padding-bottom: 366px">

    <div class="container">
        <form method="POST" action="{{route('clienteD.update')}}">
            @csrf
            <input type="text" name="cliente_id" value="{{$usuario->id}}" style="display: none">

            <div class="row datosClient" >
    
                <div class="col-lg-4">
                    <label for="">Reventa</label>
                    <input type="number"  name="incrementoReventa" placeholder="Incremento reventa %" value="{{$usuario->reventa}}">
        
                </div>
                <div class="col-lg-4">
                    <label for="">Direccion</label>
                    <input type="text" name="direccionEntrega" placeholder="Dirección de entrega" value="{{$usuario->direccion}}">
        
                </div>
        
                <div class="col-lg-4">
                    <label for="">Localidad</label>
                    <input type="text" name="localidadEntregar" placeholder="Localidad de entrega" value="{{$usuario->localidad}}" >
        
                </div>
            </div>
    
            <div class="row datosClient" style="padding-top: 24px">
    
                <div class="col-lg-4">
                    <label for="">Celular</label>
                    <input type="text" name="telefono" placeholder="Teléfono" value="{{$usuario->celular}}">
        
                </div>
                <div class="col-lg-4">
                    <label for="">Email</label>
                    <input type="text" name="mail" placeholder="Mail" value="{{$usuario->email}}">
        
                </div>
        
                <div class="col-lg-4">
                    <label for="">Transporte</label>
                    <input type="text" name="transporte" placeholder="Transporte" value="{{$usuario->transporte}}">
        
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-end" style="padding-top: 50px">
                    <button type="submit" class="btn btn-guardar">Guardar</button>
    
                </div>
    
            </div>

        </form>

    </div>
</section>
    {{-- <section style='padding-top:69px;padding-bottom:125px' data-aos='fade-up'>
        <div class='container'>

            <h2 class='pb-4' style='text-align:center;'>Mis datos</h2>

            <form id='form{{$usuario->id}}' method="POST" action="{{ route('cliente.update', ['id' => $usuario->id]) }}" class='border border-1' style='padding:40px;'>
                @csrf
                @method('put')

                <!-- username -->
                <div class="col-12">
                    <x-input-label class='form-label' for="username" :value="__('Nombre de usuario*')" />
                    <x-text-input disabled id="username" class="form-control" type="text" name="username" value="{{$usuario->username}}" autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2"/>
                </div>

                <div class='row mt-4' style='display:none;'>
                    <!-- Password -->
                    <div class="col-6">
                        <x-input-label class='form-label' for="password" :value="__('Contraseña')" />
        
                        <x-text-input id="password" class="form-control input-password contr{{$usuario->id}}"
                                        type="password"
                                        name="password"
                                            autocomplete="new-password" disabled/>
        
                        <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                    </div>
        
                    <!-- Confirm Password -->
                    <div class="col-6">
                        <x-input-label class='form-label' for="password_confirmation" :value="__('Confirmar contraseña')" />
        
                        <x-text-input id="password_confirmation" class="form-control input-password contr{{$usuario->id}}"
                                        type="password"
                                        name="password_confirmation"  autocomplete="new-password" disabled/>
        
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>
                
                <input disabled data-id='{{$usuario->id}}' class='cambio-contr' type="checkbox" name='check' style='display:none;'>
                <label for="check"  style='display:none;'>Cambiar contraseña</label>

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
                        <select id='select-provincia' class="form-select" name="provincia" >
                            @foreach ($provincias as $provincia)
                                <option {{$provincia['id'] == $usuario->provincia ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                            @endforeach
                            
                        </select>
                    </div>
    
                    <div class="col-4">
                        <label for="localidad" class="form-label">Localidad</label>
                        <select id='localidades' class="form-select" name="localidad" >
                            <option selected value="{{$usuario->localidad}}">{{$usuario->localidad}}</option>
                            @foreach ($localidades as $localidad)
                                
                                <option value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                            @endforeach
                            
                        </select>
                    </div>
    
                    <div class="col-4">
                        <x-input-label class='form-label' for="cp" :value="__('Código Postal*')" />
                        <x-text-input id="cp" class="form-control" type="text" name="cp" value="{{$usuario->cp}}" autocomplete="cp" />
                        <x-input-error :messages="$errors->get('cp')" class="mt-2"/>
                    </div>
                </div>
                <div class='d-flex justify-content-end mt-4'>
                    <button data-form-id="{{'form'.$usuario->id}}" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
                </div>
                
            </form>
            
                
        </div>
    </section> --}}
    
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


    $('.cambio-contr').on('click', function(){
        var id = $(this).data('id')
        if($(this).is(':checked')){
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = false
            });
        } else {
            document.querySelectorAll('.contr' + id).forEach(element => {
                element.disabled = true
            });
        }
        
        
    })
</script>
@endsection