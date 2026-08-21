@extends('layouts.plantilla-front')
{{-- 
@section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection --}}

@section('styles')
    <style>

.modal-dialog{
        max-width: 500px !important;
        width: 500px !important;
    }
        .cuadroT {
            color: #1C1C1C;
            font-family: "Montserrat";
            font-size: 17px;
            font-style: normal;
            font-weight: 500;
            line-height: normal;
            padding-top: 18px;
            padding-left: 17px
        }

        .divContent {
            height: 196px;
            border-radius: 10px;
            border: 1px solid #C4C4C4;
            background: linear-gradient(to bottom, #F5F5F5 56px, transparent 100px);

        }

        .btnAdjuntar {
            border-radius: 20px;
            background: var(--Verde, #236644);

            color: #FFF;
            font-family: Roboto;
            font-size: 18px;
            font-style: normal;
            font-weight: 400;
            line-height: normal;
            display: inline-flex;
            padding: 11px 32px;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .tabla .fila {
            border-bottom: 1px solid #d4d4d4;
            align-content: center;
            padding-top: 12.5px;
            padding-bottom: 12.5px;
            font-size: 15px;
        }

        .cabecera {
            background-color: #EFEFEF;
            height: 56px;
            font-weight: 300;
        }

        .producto-img {
            width: 58px !important;
            height: 59px !important;
            flex-shrink: 0;

        }

        /* .modal-dialog {
            max-width: 80vw;
        } */

        .modal-content {
            padding: 29px 40px 29px 40px;
    
        }

        .dropdown-menu {
            border-radius: 0px 0px 15px 15px;
            background: #FFF;
            box-shadow: 0px 1px 3px 0px rgba(0, 0, 0, 0.35);
            padding: 0;
        }

        .dropdown-menu li {
            width: 245px;
            border-bottom: 1px solid #d4d4d4;
            padding: 0;
        }

        .dropdown-item:hover {
            background-color: rgba(78, 153, 212, 0.22);
        }

        .cantidad {
            display: flex;
            justify-content: space-between;
            width: 99px !important;
            height: 38px;
            padding: 0px 16px 0px 16px;
            border-radius: 76px;
            border: 1px solid #D9D9D9;
            background: #FFF;
            align-items: center;
        }

        .cantidad div {
            height: auto !important;
        }

        .cantidad svg {
            cursor: pointer;
        }

        .descuentos {
            margin-top: 7px;
            color: #329943;
            font-size: 13px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
        }

        .porcentajes {
            color: #329943;
            text-align: right;
            font-size: 12px;
            font-style: normal;
            font-weight: 400;
            line-height: normal;
        }

        .cuadro-info {
            border-radius: 20px;
            border: 1px solid #DFDFDF;


            padding: 0px 0px 17px 0px;
        }


        textarea {
            color: #8C8C8C;
            font-family: 'Montserrat';
            font-size: 15px;
            font-style: normal;
            font-weight: 400;
            line-height: 142.549%;
            /* 21.382px */
            background: white;
            border: none;
            width: 100%;
            height: 136px;
            padding: 15px;
        }

        .detalle {
            font-size: 15px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
        }

        #archivo {
            /* Oculta el input original */
            display: none;
        }

        /* Estilo del botón personalizado */
        .custom-file-upload {
            border: none;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid #E5E5E5;
            width: 100%;
            height: 43px;
            padding: 13px 0px 13px 25px;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Estilo para cuando el botón se enfoca */
        .custom-file-upload:hover {
            background-color: #e9e9e9;
        }

        .enviar-pedido {
            width: 35%;
            max-height: 40px;
            font-size: 18px !important;
            border-radius: 10px !important;
            background: linear-gradient(to right, #0098DA 0%, #0098DA 100%) !important;
background-size: 0 100% !important;
background-repeat: no-repeat !important;
transition: background-size 0.5s ease, border-color 0.5s ease !important;
color: #0098DA !important;

        }

        .enviar-pedido:hover{

            border-color: #0098DA !important;
  background-size: 100% 100% !important;
  color: white !important;
        }

        .inicio {
            width: 284px;
        }

        .form-check-input:not(#cambiar-envio) {
            margin: 0px 6px 0px 0px;
            min-width: 18px;
            height: 18px;
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

        .form-check-input:disabled {
            background-color: #f0f0f0;
        }

        .pago-info {
            display: none;
        }

        .carrito-btn {
            color: #0098DA;
            font-family: 'Montserrat';
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: normal;
        }



        @media (max-width: 990px) {
            .buscador-cont {
                width: 100%;
            }

            /* .modal-dialog {
                max-width: none;
            } */

            .cabecera-medidas div,
            .dimension .fila {
                width: 25% !important;
            }

            .dimension .fila {
                font-size: 12px;
            }

            /* .modal-content {
                padding: 20px;
            } */

            .mobile .cabecera-medidas div,
            .mobile .dimension .fila {
                width: 20% !important;
            }

            .seguir-comprando {
                margin-right: 0;
            }

            .producto-img {
                margin-right: 10px;
            }
        }
    </style>
@endsection

@section('content')

    {{-- <div class="container"
        style="padding-top: 24px; color: #717171 !important; font-family: Roboto; font-size: 16px; font-style: normal; font-weight: 400; line-height: normal;">
        <a href="">Inicio  <span style="padding-left: 14px; padding-right:14px">/</span> <a href="">Carrito</a></a>

    </div> --}}

    {{-- <section class="sectionBo" style="margin-top: 26px">
        <div class="container" style="padding-top: 44px">

            <div class="d-flex flex-column justify-content-center align-items-center">
                <div class="bonificacionw">
       
                    <div class="row d-flex justify-content-between align-items-end" style="width: 100%;">
                        <div class="col-lg-6" style="padding-left: 0px">
                            <h4>BONIFICACIONES</h4>                            
    
                         
                        </div>
                     
                        
                    </div>

                    @foreach ($bonificaciones as $bonificacion)
                        <div class="row d-flex justify-content-between align-items-end"
                            style="width: 100%; border-bottom: 1px solid #E5E5E5; ">
                            <div class="col-lg-6" style="padding-left: 0px">
                                @if ($bonificacion->orden == 'gg')
                                    <span class="bonificacion">Más de
                                        ${{ number_format($bonificacion->desde, 0, '.', '.') }} </span>
                                @else
                                    <span class="bonificacion">Desde ${{ number_format($bonificacion->desde, 0, '.', '.') }}
                                        hasta ${{ number_format($bonificacion->hasta, 0, '.', '.') }} </span>
                                @endif
                            </div>
                            <div class="col-lg-6 d-flex justify-content-end" style="padding-right: 0px">
                                <span class="bonificacion">
                                    {{ number_format($bonificacion->porcentaje, 2, '.', '.') }}%</span>

                            </div>

                        </div>
                    @endforeach
                </div>

            </div>

        </div>


    </section> --}}


    <section style='padding-top:51px;padding-bottom:26px;'>
        <div class='container'>

            <div id='carrito-desplegado'>
                @include('frontend/components/carrito-productos')
            </div>
            <a href="{{ route('productos.clientes') }}">
                <div style='margin-top:26px;'>

                    <button style="width: 200px !important;" class="btn carrito-btn">Seguir comprando</button>
                </div>
            </a>
        </div>
    </section>

    <section>
        <div class='container'>
            <div class='row'>
                <div class='col-md-6'>
                    {{-- <div class='cuadro-info' style="height: 206px;">
                        <div class='cuadro-titulo'><p>Información importante</p></div>

                        <div style="padding-left: 24px">{!! $informacion->info !!}</div>
                    </div> --}}

                    <form id='pedido' action="{{ route('realizar.pedido') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf


                        <div class="divContent">
                            <div class="cuadroT" style='padding-bottom: 16px;'>
                                Observaciones</div>
                            <textarea name="mensaje" cols="30" rows="3"
                                placeholder="Días especiales de entrega, cambios de domicilio, expresos, requerimientos especiales en la mercadería, exenciones."></textarea>
                        </div>

                        <!-- <div class='cuadroT' style='padding-top:14px; padding-bottom:16px;'>Adjunta un archivo</div>
                            <input type="file" id="archivo" name="archivo" accept="file/*" />
                            <label for="archivo" class="custom-file-upload d-flex justify-content-between">
                                <div>Seleccionar archivo</div>
                                {{-- <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28"
                                fill="none">
                                <path
                                    d="M13 22H8.5C6.98333 22 5.68733 21.475 4.612 20.425C3.53667 19.375 2.99933 18.0917 3 16.575C3 15.275 3.39167 14.1167 4.175 13.1C4.95833 12.0833 5.98333 11.4333 7.25 11.15C7.66667 9.61667 8.5 8.375 9.75 7.425C11 6.475 12.4167 6 14 6C15.95 6 17.6043 6.67933 18.963 8.038C20.3217 9.39667 21.0007 11.0507 21 13C22.15 13.1333 23.1043 13.6293 23.863 14.488C24.6217 15.3467 25.0007 16.3507 25 17.5C25 18.75 24.5623 19.8127 23.687 20.688C22.8117 21.5633 21.7493 22.0007 20.5 22H15V14.85L16.6 16.4L18 15L14 11L10 15L11.4 16.4L13 14.85V22Z"
                                    fill="#DC1B23" />
                            </svg> --}}
                                <button class="btn btnAdjuntar">Adjuntar</button>
                            </label>
                            {{-- <input id='descuento-input' type="text" hidden name='descuento_pago'
                            value='{{ session('tipo_pago') == 'Efectivo o transferencia' ? $informacion->descuento_efectivo : '0' }}'
                            required> --}}
                            <input id='envio-input' type="text" hidden name='tipo_envio' value='{{ session('tipo_envio') }}'
                                required> -->

                        {{-- <input id='comprador-input' type="text" hidden name='comprador' value='{{(Auth::guard('web')->user()->rol == 'vendedor' && count($clientes) > 0) ? $clientes[0]->id : Auth::guard('web')->user()->id}}' required> --}}
                    </form>


                </div>
                <div class='col-md-6'>
                    <div id='carrito-total'>
                        @include('frontend/components/carrito-pedido')
                    </div>

                    <div class='d-flex justify-content-end'
                        style='align-items:flex-end;margin-top:40px;margin-bottom:150px;'>

                        @if (Auth::guard('web')->user()->rol == 'vendedor')
                            <div style='width:50%;'>
                                <label for="cliente">Seleccionar cliente</label>
                                <select class='form-select' name="cliente" id="select-cliente" style='border-radius:50px;'>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}">{{ $cliente->username }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- @if (Auth::guard('web')->user()->rol == 'vendedor' && count($clientes) == 0)
                    <button type="button" class="enviar-pedido green-btn" onclick="notificar('No tienes clientes asociados')">Enviar pedido</button>
                    @else --}}
                        <button type="button" class="enviar-pedido green-btn"
                            onclick="{{ count(Cart::content()) > 0 ? 'enviar_pedido(event)' : 'notificar("El carrito esta vacío")' }}">Realizar
                            pedido</button>
                        {{-- @endif --}}
                    </div>
                </div>

            </div>

        </div>
    </section>

    @if ($aviso)
        {{-- MODAL --}}
        <div class="modal fade" id="aviso" aria-hidden="true" aria-labelledby="exampleModalToggleLabel2" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg" style='width:828px;'>
                <div class="modal-content">
                    <div class="modal-body" style='overflow:hidden;text-align:center;'>
                        <div
                            style='
            font-size: 32px;
            font-style: normal;
            font-weight: 500;
            line-height: normal;'>
                            {{ $informacion->pedido_titulo }}</div>
                        <div
                            style='
            font-size: 24px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
            margin-top:23px;margin-bottom:37px;'>
                            {!! $informacion->pedido !!}</div>
                        <a href="{{ route('productos.home') }}"><button class='green-btn inicio'>Ir al inicio</button></a>
                    </div>
                </div>
            </div>
        </div>
    @endif


@endsection

@section('script')
    <script>
        $(document).ready(function() {
            document.getElementById("archivo").addEventListener("change", function() {
                var archivo = this.files[0];
                // Aquí puedes agregar lógica para trabajar con el archivo seleccionado
                document.querySelector('.custom-file-upload').innerText = archivo.name
                // console.log("Archivo seleccionado:", archivo.name);
            });

            $('#select-cliente').change(function() {
                document.getElementById('comprador-input').value = $(this).val()
            })


        })

        function enviar_pedido(e) {

            e.target.innerHTML = '<div class="loading-spinner"></div>  Procensando';
            document.getElementById('pedido').submit()
            
               iziToast.success({
                    title: 'Pedido realizado con éxito',
                    backgroundColor: '#DAF6D3',
                    titleColor: '#479831',
                    iconColor: '#479831',
                    progressBar: false,
                    icon: 'fa-solid fa-square-check',
                    position: 'bottomRight',
                });
                
            //si hay checkbox de envio habilitar
            // var checked = false

            // document.querySelectorAll('.envio-opcion').forEach(element => {
            //     if (element.checked) {
            //         checked = true
            //     }
            // });
            // if (checked) {
            //     e.target.innerHTML = '<div class="loading-spinner"></div>  Procensando pedido';
            //     document.getElementById('pedido').submit()
            // } else {
            //     notificar('Elige una forma de envio')
            // }

        }

        $(document).ready(function() {
            $('#aviso').modal('show');
        })

        function notificar(texto) {
            iziToast.warning({
                title: texto,
                backgroundColor: '#254F70',
                titleColor: '#fff',
                progressBar: false,
                position: 'bottomRight',
            });
        }
    </script>


    <script>
        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";
    </script>
@endsection
