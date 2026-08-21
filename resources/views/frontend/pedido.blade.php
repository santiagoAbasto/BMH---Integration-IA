@extends('layouts.plantilla-front')

{{-- @section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection --}}

@section('styles')
<style>
    .envio-info{
        display: none;
    }
    .envio-info img{
        width:100% !important;
        height:auto !important;
    }
    .pago-info{
        display: none;
    }
    .pago-info img{
        width:100% !important;
        height:auto !important;
    }
    .mostrar-info{
        display: block;
    }
    #facturacion label{
        height: 50px;
        display:flex;
        flex-direction: column;
        justify-content: center;
    }
    #facturacion input:not([type=checkbox]), #facturacion select{
        border: 1px solid var(--Gris-linea, #E5E5E5)!important;
        border-radius: 25px;
        height: 50px;
        align-content: center;
        padding-left: 21px;
        padding-right: 21px;
        margin-bottom: 24px;
    }
    #facturacion input::placeholder{
        /* color:#000; */
    }
    #facturacion textarea{
        height: 236px;
        border: 1px solid var(--Gris-linea, #E5E5E5)!important;
        border-radius: 25px;
        padding: 21px;
        margin-top: 40px;
    }
    
    .mi-pedido{
        border-radius: 18px;
        border: 1px solid #DFDFDF;
    }
    .pedido-titulo{
        border-radius: 18px 18px 0px 0px;
        background: #EFEFEF;
        height: 52px;
        padding-left:32px;
        align-content: center;
        font-size: 20px;
        font-weight: 500;
    }
    .pedido-info{
        padding:32px 32px 24px 32px;
        color: var(--tipografia, #1E1E1E);
        font-size: 15px;
        font-style: normal;
        font-weight: 300;
        line-height: 140%;
    }
    #cambiar-envio {
        margin: 0px 6px 0px 0px;
        min-width: 18px;
        height: 18px;
        border-radius: 0% !important;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 20px;
        height: 20px;
        border: 1px solid #DEDEDE;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }

    #cambiar-envio:checked {
        border: 1px solid #DF0E15;
        background-color: white;
    }

    #cambiar-envio:checked::after {
        content: '';
        background-image: url('{{ asset('imagenes/iconos/tick.png') }}');

        background-size: cover;
        background-position: center;
        width: 24px; /* Ajusta el tamaño según sea necesario */
        height: 24px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    #cambiar-envio:disabled{
        background-color: #f0f0f0;
    }

    .form-check-input:not(#cambiar-envio) {
        margin: 0px 6px 0px 0px;
        min-width: 18px;
        height: 18px;
        /* border-radius: 0% !important; */
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 20px;
        height: 20px;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
        background-image: url('{{ asset('imagenes/iconos/frame.svg') }}');
        background-size: cover;
        background-position: center;
    }

    .form-check-input:checked {
        background-color: white;
        background-image: url('{{ asset('imagenes/iconos/ellipse.svg') }}');

    }

    .form-check-input:checked::after {
        content: '';
        background-image: url('{{ asset('imagenes/iconos/ellipse.svg') }}');
        background-size: cover;
        background-position: center;
        width: 20px;
        height: 20px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .form-check-input:disabled{
        background-color: #f0f0f0;
    }
    .subtitulo{
        color: var(--tipografia, #1E1E1E);
        font-size: 15px;
        font-style: normal;
        font-weight: 500;
        line-height: normal;
    }
    .carrito-total-opcion{
        padding-bottom:16px;
    }
</style>
@endsection

@section('content')

    <?php
    use App\Models\Carrito;
    ?>

    @include('frontend/components/miga-baner', ['titulo' => 'Carrito'])

    <section style='padding-top:58px;padding-bottom:100px;'>
        <div class='container'>
            <div class='row'>
                <div class='col-lg-8'>
                    <form id='facturacion'>
                        <div style='margin-bottom:29px;'>
                            <div>
                                <h2 class='carrito-titulo border-bottom border-1' style='padding-bottom:8px;margin-bottom:36px;'>Detalle de facturación</h2>
                                <div class='row'>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="name">Nombre y apellido / Razón social*</label> --}}
                                        <input id="nombre" class="form-control" type="text" name="name" value="{{isset($user) ? $user->name : ''}}" placeholder="Nombre y apellido*" autocomplete="nombre" required />
                                    </div>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="dni">DNI / CUIT*</label> --}}
                                        <input id="dni" class="form-control" type="number" name="dni" value="{{isset($user) ? $user->dni : ''}}" placeholder="DNI*" autocomplete="dni" required/>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="email">Email*</label> --}}
                                        <input id="email" class="form-control" type="email" name="email" value="{{isset($user) ? $user->email : ''}}" placeholder="E-mail*" autocomplete="email" required/>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="celular">Celular*</label> --}}
                                        <input id="celular" class="form-control" type="number" name="celular" value="{{isset($user) ? $user->celular : ''}}" placeholder="Celular*" autocomplete="celular" required/>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="direccion">Dirección*</label> --}}
                                        <input id="direccion" class="form-control" type="text" name="direccion" value="{{isset($user) ? $user->direccion : ''}}" placeholder="Dirección*" autocomplete="direccion" required/>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label for="provincia" class="form-label">Provincia</label> --}}
                                        <select id='select-provincia' class="form-select" name="provincia" required>
                                            @foreach ($provincias as $provincia)
                                                <option {{$provincia['id'] == '02' ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label for="localidad" class="form-label">Localidad</label> --}}
                                        <select id='localidades' class="form-select" name="localidad" required>
                                            @foreach ($localidades as $localidad)
                                                <option value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        {{-- <label class='form-label' for="cp">Código Postal*</label> --}}
                                        <input id="cp" class="form-control" type="text" name="cp" value="{{isset($user) ? $user->cp : ''}}" placeholder="Código postal*" autocomplete="cp" required/>
                                    </div>
                                    <div>
                                        <div class="col-12 d-flex" style='align-items:center;'>
                                            <input value='1' id='cambiar-envio' class='form-check-input' type="checkbox" name='diferente'>
                                            <label for="diferente">¿Enviar a una dirección diferente?</label>
                                        </div>
                                        <div id='cambio-inputs' class="row" style='display:none;margin-top:36px;'>
                                            <div class="col-6">
                                                {{-- <label class='form-label' for="direccion2">Dirección*</label> --}}
                                                <input id='direccion2' class="form-control" type="text" name="direccion2" placeholder="Dirección*" autocomplete="direccion2"/>
                                            </div>
                                            
                                            <div class="col-6">
                                                {{-- <label for="provincia2" class="form-label">Provincia</label> --}}
                                                <select id='select-provincia2' class="form-select" name="provincia2">
                                                    @foreach ($provincias as $provincia)
                                                        <option {{$provincia['id'] == '02' ? 'selected' : ''}} value="{{$provincia['id']}}">{{$provincia['nombre']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                {{-- <label for="localidad2" class="form-label">Localidad</label> --}}
                                                <select id='localidades2' class="form-select" name="localidad2">
                                                    @if(isset($user) && $datos->cp_envio != $user->cp && $datos->tipo_envio != 'Envíos a todo el país')
                                                        <option selected value="{{$loc}}">{{$loc}}</option>
                                                    @endif
                                                    @foreach ($localidades as $localidad)
                                                        <option value="{{$localidad['nombre']}}">{{$localidad['nombre']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                {{-- <label class='form-label' for="cp2">Código Postal*</label> --}}
                                                <input id="cp2" class="form-control" type="number" name="cp2" placeholder="Código postal*" autocomplete="cp" value='{{isset($user) && $datos->cp_envio != $user->cp && $datos->tipo_envio != 'Envíos a todo el país' ? $datos->cp_envio : ''}}'/>
                                            </div>
                                        </div>
                                    </div>
                                        
                                    <div class="col-12">
                                        <textarea class="form-control" id="notas" rows="8" placeholder="¿Querés dejarnos una nota?" name='notas'></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <form id='formulario' action="{{route('realizar.pedido', ['seccion' => 'public'])}}" method='POST'>
                        @csrf
                        <input id='envio-input' hidden type="text" name='tipo_envio' value='' required>
                        <input id='pago-input' hidden type="text" value='' name='tipo_pago' required>
                        <input id='descuento-input' hidden type="text" value='0' name='descuento_pago' required>
                        {{-- <input id='subtotal-input' hidden type="text" value='{{Cart::subtotal(0, ',', '')}}' name='subtotal_pedido' required> --}}
                        {{-- <input id='total-input' hidden type="text" name='total_pedido' value='{{floatval(Carrito::subtotal_final()) + $datos->costo_envio}}' required> --}}
                        {{-- <input id='descuento-cliente-input' hidden type="number" value='{{Auth::guard('web')->check() ? Auth::guard('web')->user()->descuento : 0}}' name='descuento_cliente' required> --}}
                        <div class='d-flex justify-content-between'>
                            <div>* campos obligatorios</div>
                            <button id='btn-comprar' {{Cart::count() > 0 ? '' : 'disabled'}} class='green-btn' data-tipo='' type='button' style='height:45px;width:288px;margin-top:11px;margin-bottom:27px;font-size:16px;
                                font-weight: 400;'>
                                    Finalizar compra
                            </button>
                        </div>
                        
                        <div id='mensaje-submit' style='padding-bottom:20px;color:#DF0E15;display:none;'></div>
                    </form>
                </div>
                <div id='carrito-total' class='col-lg-4'>
                    <div class='mi-pedido'>
                        <div class='pedido-titulo'>
                            Mi pedido
                        </div>
                    
                        <div class='pedido-info'>
                            <div class='subtitulo' style='padding-bottom:23px;'>
                                Detalle
                            </div>
                            @foreach(Cart::content() as $item)
                            <div class='d-flex' style='padding-bottom:17px;'>
                                <div style='padding-right:10px;'>x{{$item->qty}}</div>
                                <div>{{$item->name}}</div>
                            </div>
                            @endforeach
                            <div class='subtitulo' style='border-top:1px solid #dfdfdf;padding-top:17px;padding-bottom:16px;margin-top:13px'>
                                Forma de pago
                            </div>
                            <fieldset>
                            <div class='d-flex w-100 justify-content-between carrito-total-opcion'>
                                <div class='d-flex' style='align-items:flex-start;'>
                                    <input class="form-check-input pago-opcion" type="radio" data-tipo='Efectivo o transferencia' data-descuento='{{$informacion->descuento_efectivo == null ? 0 : $informacion->descuento_efectivo}}' name="pago" >
                                    <div class=''>Efectivo o transferencia bancaria (-{{$informacion->descuento_efectivo}}% adic.)</div>
                                </div>
                                <div>{{$informacion->desc_mp == null ? '' : '-'.$informacion->desc_mp.'%'}}</div>
                            </div>
                            <div class='pago-info'>
                                {!!$informacion->info_efectivo!!}
                            </div>
                            <div class='d-flex w-100 justify-content-between carrito-total-opcion'>
                                <div class='d-flex' style='align-items:flex-start;'>
                                    <input class="form-check-input pago-opcion" type="radio" data-tipo='Mercado Pago' data-descuento="0" name="pago" >
                                    <div class=''>Mercado Pago</div>
                                </div>
                                <div>{{$informacion->desc_mp == null ? '' : '-'.$informacion->desc_mp.'%'}}</div>
                            </div>
                            <div class='pago-info'>
                                {!!$informacion->info_mp!!}
                            </div>
                            </fieldset>
                            <div id='mensaje-pago' style='color:#DF0E15;display:none;text-align:center;'>Selecciona un método de pago</div>
                            <div class='subtitulo' style='border-top:1px solid #dfdfdf;padding-top:17px;padding-bottom:16px;margin-top:13px'>
                                Envío
                            </div>
                            <fieldset>
                            <div class='d-flex w-100 justify-content-between carrito-total-opcion'>
                                <div class='d-flex' style='align-items:flex-start;'>
                                    <input class="form-check-input envio-opcion" type="radio" data-tipo='Retiro en fabrica' name="envio" >
                                    <div class=''>Retiro en fábrica</div>
                                </div>
                                <div>{{$informacion->desc_mp == null ? '' : '-'.$informacion->desc_mp.'%'}}</div>
                            </div>
                            <div class='envio-info'>
                                {!!$informacion->info_retiro!!}
                            </div>
                            <div class='d-flex w-100 justify-content-between carrito-total-opcion'>
                                <div class='d-flex' style='align-items:flex-start;'>
                                    <input class="form-check-input envio-opcion" type="radio" data-tipo='A convenir' name="envio" >
                                    <div class=''>A convenir</div>
                                </div>
                                <div>{{$informacion->desc_mp == null ? '' : '-'.$informacion->desc_mp.'%'}}</div>
                            </div>
                            <div class='envio-info'>
                                {!!$informacion->info_convenir!!}
                            </div>
                            </fieldset>
                            <div id='mensaje-envio' style='color:#DF0E15;display:none;text-align:center;'>Selecciona un tipo de envío</div>
                            <div id='carrito-detalle'>
                                @include('frontend/components/carrito-detalle', ['descuento' => 0])
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
    </section>

</div>
    
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {

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
        document.getElementById('select-provincia2').addEventListener('change', function() {
                
            var provinciaId = this.value;
            var url = 'https://apis.datos.gob.ar/georef/api/localidades?provincia=' + provinciaId + '&orden=nombre&max=1000';

            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    // Limpiar y actualizar el segundo select con los datos obtenidos
                    var select2 = $('#localidades2');
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

        // DIRECCION DIFERENTE
        document.getElementById('cambiar-envio').addEventListener('click', (event) => {
            var element = event.target
            if(element.checked){
                document.querySelectorAll('#cambio-inputs input').forEach((e) => {
                    e.value = ''
                })
                $('#cambio-inputs').slideDown()
            } else {
                $('#cambio-inputs').slideUp()
                document.querySelectorAll('#cambio-inputs input').forEach((e) => {
                    e.value = ''
                })
            }
        })

        // ENVIO INFO
        $('.envio-opcion').on('click', function() {
            document.querySelectorAll('.envio-info').forEach(element => {
                if ($(element).is(':visible')) {
                    $(element).slideUp();
                }
            });
            var envioInfo = $(this).closest('.carrito-total-opcion').next('.envio-info')
            envioInfo.slideDown()

            document.getElementById('envio-input').value = this.getAttribute('data-tipo')
        })
        // PAGO INFO
        $('.pago-opcion').on('click', function() {
            document.querySelectorAll('.pago-info').forEach(element => {
                if ($(element).is(':visible')) {
                    $(element).slideUp();
                }
            });
            var pagoInfo = $(this).closest('.carrito-total-opcion').next('.pago-info')
            pagoInfo.slideDown()

            document.getElementById('pago-input').value = this.getAttribute('data-tipo')
            document.getElementById('descuento-input').value = this.getAttribute('data-descuento')

            actualizarCosto(this.getAttribute('data-descuento'))
        })
    })

    function actualizarCosto(descuento){
        console.log(descuento)
        $.ajax({
            url: '{{route("actualizar.total.pedido")}}',
            type: 'GET',
            data: {
                descuento: descuento,
            },
            success: function(response) {
                // Limpiar y actualizar el segundo select con los datos obtenidos
                console.log(response)
                document.getElementById('carrito-detalle').innerHTML = response
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }

    $('#btn-comprar').click(function(event){
        event.preventDefault()

        const form = document.getElementById('facturacion');
        const formData = new FormData(form);
        const formObj = {};
        formData.forEach((value, key) => {
            formObj[key] = value;
        });
        const form2 = document.getElementById('formulario');
        const formData2 = new FormData(form2);
        var mensaje = document.getElementById('mensaje-submit')
        var mensajecp = document.getElementById('mensaje-cp')

        var facturacion_check = false
        if(form.checkValidity()){

            mensaje.style.display = 'none'
            facturacion_check = true
            if(document.getElementById('cambiar-envio').checked){
                if(document.getElementById('direccion2').value == '' || document.getElementById('cp2').value == ''){
                    facturacion_check = false
                    mensaje.innerText = 'Hay datos obligatorios sin completar'
                    mensaje.style.display = 'block'
                } else {
                    mensaje.style.display = 'none'
                    facturacion_check = true
                }
            }

            
        } else {
            mensaje.innerText = 'Hay datos obligatorios sin completar'
            mensaje.style.display = 'block'
        }

        var pago = false
        document.querySelectorAll('.pago-opcion').forEach(input => {
            if(input.checked){
                pago = true
            }
        })
        if(!pago){
            document.getElementById('mensaje-pago').style.display = 'block'
        } else {
            document.getElementById('mensaje-pago').style.display = 'none'
        }

        var envio = false
        document.querySelectorAll('.envio-opcion').forEach(input => {
            if(input.checked){
                envio = true
            }
        })
        if(!envio){
            document.getElementById('mensaje-envio').style.display = 'block'
        } else {
            document.getElementById('mensaje-envio').style.display = 'none'
        }

        // var checked = document.getElementById('terminos-input').checked
        // if(!checked){
        //     document.getElementById('mensaje').style.display = 'block'
        // } else {
        //     document.getElementById('mensaje').style.display = 'none'
        // }
        
        if(facturacion_check && pago && envio){

            //combinar formularios
            const combinedFormData = new FormData();
            for (const [key, value] of formData.entries()) {
                combinedFormData.append(key, value);
            }
            for (const [key, value] of formData2.entries()) {
                combinedFormData.append(key, value);
            }
            //guardar datos en sesión
            $.ajax({
                url: "{{route('realizar.pedido', ['seccion' => 'public'])}}", 
                type: 'POST',
                processData: false, // Necesario para enviar `FormData`
                contentType: false, // Necesario para enviar `FormData`
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: combinedFormData,
                
                success: function(response) {
                    console.log('Datos enviados con éxito:', response);
                    window.location.href = "{{route('carrito.publico', ['aviso' => true])}}"
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al enviar los datos:', textStatus, errorThrown);
                }
            });
        }
        
        
    })
</script>
@endsection