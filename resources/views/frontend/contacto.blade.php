@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('styles')
<style>

    .form-label{
        color: #414141;
font-family: "Montserrat";
font-size: 16px;
font-style: normal;
font-weight: 500;
line-height: normal;
    }

    textarea::placeholder{
        color: #414141;
font-family: "Montserrat";
font-size: 16px;
font-style: normal;
font-weight: 500;
line-height: normal;
    }
    input::placeholder{
        color: #414141;
font-family: "Montserrat";
font-size: 16px;
font-style: normal;
font-weight: 500;
line-height: normal;
    }
    #formulario .form-control, #formulario .form-select{
        min-height: 44px;
        border-radius: 12px;
border: 1px solid #EBEBEB;
        color: #000;
        font-size: 13px;
        font-style: normal;
        font-weight: 400;
        line-height: normal;
        padding-left: 10px;
        padding-right: 25px;
        margin-bottom: 28px;
    }
    #formulario .form-control::placeholder{
        color: #000;
    }
    #formulario textarea{
        padding-top:10px !important;
        border-radius: 12px;
        border: 1px solid #EBEBEB;
        height: 100%;
    }
    iframe{
        width: 100%;
        height:566px;
    }
    .info , #formulario{
        font-size: 16px;
        font-weight: 300;
        line-height: 160%; /* 25.6px */
    }
    @media (max-width: 990px){
        .enviar{
            width: auto;
        }
    }
</style>
@endsection


@section('content')


    <section style='padding-top:72px;padding-bottom:60px' data-aos='fade-up'>
        <div class='container'>
            <div class='row'>
                <div class='col-12 col-lg-4 mb-4 info'>
                    <div class="informacionOne" style='padding-bottom:36px; '>Por cualquier consulta no dude en escribirnos y le responderemos a la brevedad</div>
                    @if(isset($contacto->direccion))
                    <div class="informacionC">

                        <a class='d-flex' href="https://maps.app.goo.gl/bvt6zSHzT8txo6uV7" target="_blank">
                            <div class='pe-2'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25" fill="none">
                                    <path d="M20.8327 10.4167C20.8327 16.6667 12.4993 22.9167 12.4993 22.9167C12.4993 22.9167 4.16602 16.6667 4.16602 10.4167C4.16602 8.20657 5.04399 6.08695 6.60679 4.52415C8.1696 2.96135 10.2892 2.08337 12.4993 2.08337C14.7095 2.08337 16.8291 2.96135 18.3919 4.52415C19.9547 6.08695 20.8327 8.20657 20.8327 10.4167Z" stroke="#009FE0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12.5 13.5417C14.2259 13.5417 15.625 12.1426 15.625 10.4167C15.625 8.6908 14.2259 7.29169 12.5 7.29169C10.7741 7.29169 9.375 8.6908 9.375 10.4167C9.375 12.1426 10.7741 13.5417 12.5 13.5417Z" stroke="#009FE0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                  </svg>
                            </div>
                            <div style="width: 281px;">
                                {{$contacto->direccion}}
                            </div>
                        </a>
                    </div>

                    @endif
            
                    @if(isset($contacto->tel))
                        <div  class='d-flex informacionC'>
                            <div class='pe-2'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 23 23" fill="none">
                                    <path d="M21.3445 17.4565C20.5429 16.6488 18.6013 15.4701 17.6593 14.995C16.4326 14.3771 16.3317 14.3267 15.3675 15.043C14.7243 15.5211 14.2967 15.9481 13.5441 15.7876C12.7914 15.6271 11.1558 14.7219 9.72358 13.2943C8.2914 11.8667 7.33376 10.1836 7.17272 9.43344C7.01169 8.68327 7.44583 8.26074 7.91935 7.61608C8.58672 6.70741 8.53624 6.55596 7.96579 5.32925C7.52105 4.37514 6.30797 2.45178 5.49723 1.65416C4.62994 0.797482 4.62994 0.948927 4.07111 1.18114C3.61615 1.37257 3.17968 1.60525 2.76716 1.87628C1.95945 2.41291 1.51117 2.85866 1.19767 3.52856C0.88418 4.19845 0.743336 5.76895 2.36229 8.71003C3.98125 11.6511 5.1171 13.155 7.46804 15.4994C9.81899 17.8437 11.6268 19.1043 14.2695 20.5864C17.5387 22.4174 18.7927 22.0605 19.4646 21.7475C20.1365 21.4345 20.5843 20.9903 21.1219 20.1826C21.3936 19.7707 21.6268 19.3348 21.8186 18.8801C22.0513 18.3233 22.2027 18.3233 21.3445 17.4565Z" stroke="#009FE0" stroke-width="2" stroke-miterlimit="10"/>
                                  </svg>
                            </div>
                            <div>
                                {{$contacto->tel}}
                            </div>
                        </div>
                    @endif

                    @if(isset($contacto->mail))
                    <div class="informacionC">
                        <a class='d-flex' href="mailto:{{$contacto->mail}}" target="_blank">
                            <div class='pe-2'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25" fill="none">
                                    <path d="M20.834 4.16675H4.16732C3.01672 4.16675 2.08398 5.09949 2.08398 6.25008V18.7501C2.08398 19.9007 3.01672 20.8334 4.16732 20.8334H20.834C21.9846 20.8334 22.9173 19.9007 22.9173 18.7501V6.25008C22.9173 5.09949 21.9846 4.16675 20.834 4.16675Z" stroke="#009FE0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.9173 7.29175L13.5736 13.2292C13.252 13.4307 12.8801 13.5376 12.5007 13.5376C12.1212 13.5376 11.7493 13.4307 11.4277 13.2292L2.08398 7.29175" stroke="#009FE0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                  </svg>
                            </div>
                            <div>
                                {{$contacto->mail}}
                            </div>
                        </a>

                    </div>
                @endif
              
                    @if(isset($contacto->whatsapp))
                    <div class="informacionC">

                        <a class='d-flex' href="https://wa.me/{{preg_replace("/[^0-9]/", "", $contacto->whatsapp)}}" target="_blank">
                            <div class='pe-2'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M7.25399 18.494L7.97799 18.917C9.19893 19.6291 10.5876 20.0029 12.001 20C13.5832 20 15.13 19.5308 16.4456 18.6518C17.7611 17.7727 18.7865 16.5233 19.392 15.0615C19.9975 13.5997 20.156 11.9911 19.8473 10.4393C19.5386 8.88743 18.7767 7.46197 17.6578 6.34315C16.539 5.22433 15.1136 4.4624 13.5617 4.15372C12.0099 3.84504 10.4013 4.00346 8.93952 4.60896C7.47771 5.21447 6.22828 6.23984 5.34923 7.55544C4.47018 8.87103 4.00099 10.4177 4.00099 12C3.99807 13.4139 4.37226 14.8029 5.08499 16.024L5.50699 16.748L4.85399 19.149L7.25399 18.494ZM2.00499 22L3.35699 17.032C2.46608 15.5049 1.99804 13.768 2.00099 12C2.00099 6.477 6.47799 2 12.001 2C17.524 2 22.001 6.477 22.001 12C22.001 17.523 17.524 22 12.001 22C10.2338 22.0029 8.49765 21.5352 6.97099 20.645L2.00499 22ZM8.39199 7.308C8.52599 7.298 8.66099 7.298 8.79499 7.304C8.84899 7.308 8.90299 7.314 8.95699 7.32C9.11599 7.338 9.29099 7.435 9.34999 7.569C9.64799 8.245 9.93799 8.926 10.218 9.609C10.28 9.761 10.243 9.956 10.125 10.146C10.065 10.243 9.97099 10.379 9.86199 10.518C9.74899 10.663 9.50599 10.929 9.50599 10.929C9.50599 10.929 9.40699 11.047 9.44499 11.194C9.45899 11.25 9.50499 11.331 9.54699 11.399L9.60599 11.494C9.86199 11.921 10.206 12.354 10.626 12.762C10.746 12.878 10.863 12.997 10.989 13.108C11.457 13.521 11.987 13.858 12.559 14.108L12.564 14.11C12.649 14.147 12.692 14.167 12.816 14.22C12.878 14.246 12.942 14.268 13.007 14.286C13.0742 14.3031 13.1449 14.2999 13.2102 14.2767C13.2756 14.2536 13.3326 14.2116 13.374 14.156C14.098 13.279 14.164 13.222 14.17 13.222V13.224C14.2203 13.1771 14.28 13.1415 14.3452 13.1196C14.4104 13.0977 14.4796 13.09 14.548 13.097C14.608 13.101 14.669 13.112 14.725 13.137C15.256 13.38 16.125 13.759 16.125 13.759L16.707 14.02C16.805 14.067 16.894 14.178 16.897 14.285C16.901 14.352 16.907 14.46 16.884 14.658C16.852 14.917 16.774 15.228 16.696 15.391C16.6425 15.5022 16.5716 15.6042 16.486 15.693C16.3851 15.7989 16.2746 15.8953 16.156 15.981C16.074 16.043 16.031 16.071 16.031 16.071C15.9066 16.1499 15.7788 16.2233 15.648 16.291C15.3905 16.4278 15.1062 16.5063 14.815 16.521C14.63 16.531 14.445 16.545 14.259 16.535C14.251 16.535 13.691 16.448 13.691 16.448C12.2692 16.074 10.9544 15.3735 9.85099 14.402C9.62499 14.203 9.41499 13.989 9.20099 13.776C8.31299 12.891 7.63999 11.936 7.23099 11.034C7.0227 10.5915 6.91024 10.11 6.90099 9.621C6.89723 9.01375 7.09605 8.42257 7.46599 7.941C7.53899 7.847 7.60799 7.749 7.72699 7.636C7.85299 7.516 7.93399 7.452 8.02099 7.408C8.13666 7.35003 8.26285 7.31602 8.39199 7.308Z" fill="#009FE0"/>
                                  </svg>                            </div>
                            <div>
                                {{$contacto->whatsapp}}
                            </div>
                        </a>
                    </div>
                    @endif
                </div>
                <div class='col-12 col-lg-8'>
                    <form id='formulario' action="{{route('enviar.mail')}}" method='POST'  enctype="multipart/form-data">
                        @csrf

                        <input type='hidden' name='g-recaptcha-response' id='g-recaptcha-response'>

                        <div class='row' >
                            <div class='col-md-6'>
                                <div class="">
                                    {{-- <label for="nombre" class="form-label">Nombre y apellido *</label> --}}
                                    <input type="text" placeholder="Nombre y Apellido*" class="form-control"  name='nombre' required value='{{Auth::guard('web')->check() ? Auth::guard('web')->user()->name : ''}}'>
                                </div>
                                <div class="">
                                    {{-- <label for="telefono" class="form-label">Teléfono</label> --}}
                                    <input type="text" placeholder="Celular*" class="form-control"  name='telefono' required value='{{Auth::guard('web')->check() ? Auth::guard('web')->user()->celular : ''}}'>
                                </div>
                            </div>
                            
                            <div class='col-md-6 d-flex flex-column justify-content-between'>
                                <div>
                                    <div class="">
                                        {{-- <label for="mail" class="form-label">E-Mail *</label> --}}
                                        <input type="text" placeholder="Email*"  class="form-control"  name='mail' required value='{{Auth::guard('web')->check() ? Auth::guard('web')->user()->email : ''}}'>
                                    </div>
                                    <div class="">
                                        {{-- <label for="empresa" class="form-label">Empresa</label> --}}
                                        <input type="text" placeholder="Empresa" class="form-control"  name='empresa'>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="row" style="padding-right: 0px !important">

                                <div class="col-lg-6">
                                    {{-- <label for="mensaje" class="form-label">Mensaje *</label> --}}
                                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="6" placeholder="Mensaje" name='mensaje' required>{{$producto == null ? '' : '¡Hola!, quisiera consultar sobre el producto '.$producto.'.'}}</textarea>
                                </div>
    
    
                                
                                
                                <div class='col-lg-6 d-flex flex-column align-items-start justify-content-end' style='position:relative;align-items:center;'>
                                    <div style='text-align:start;color: #414141; padding-bottom:10px;
    font-family: "Montserrat";
    font-size: 16px;
    font-style: normal;
    font-weight: 500;
    line-height: normal;;'>* Campos obligatorios</div>
                                    
                                    <button type="submit" class="enviar green-btn {{session('success') ? 'success' : ''}}{{session('warning') ? 'failure' : ''}}"
                                    onclick='onClick(event)'>Enviar Consulta</button>
                                    <div style='position:absolute;bottom:-40px;width:100%;display:flex;justify-content:center;'><div class='result'></div></div>
                                </div>
                            </div>

                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </section>
    
    <section>
        <div class='container-fluid' style='padding:0; height: 566px !important;'>
            {{-- <div class="mapouter"><div class="gmap_canvas"><iframe width="100%" height="560" id="gmap_canvas" src="https://maps.google.com/maps?q=Mariano+Moreno+4085%2C+B1872GJS+Sarand%C3%AD%2C+Provincia+de+Buenos+Aires.+Argentina&t=&z=15&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe><br><style>.mapouter{position: relative;text-align: right;height: 560px;width:100%;}</style><a href="https://www.mapembed.net">google satellite maps zoom</a><style>.gmap_canvas{overflow: hidden;background: none !important;height: 560px;width: 100%;}</style></div></div> --}}
            {{-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3280.9685624054864!2d-58.50611369999999!3d-34.6807429!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcc927abcf85cd%3A0xa9858c1982236dfd!2sCnel.%20Dom%C3%ADnguez%20370%2C%20B1751BAH%20Villa%20Madero%2C%20Provincia%20de%20Buenos%20Aires!5e0!3m2!1ses!2sar!4v1713966398368!5m2!1ses!2sar" width="100%" height="528" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe> --}}
            {!!$contacto->iframe!!}
        </div>
    </section>
    
@endsection

@section('script')
<script src="https://www.google.com/recaptcha/api.js"></script>

<script>

    window.onload = function(){
        
        var btns = document.querySelectorAll('.success');
        btns.forEach(function(btn) {
            console.log('exito')
            btn.innerText = '¡Gracias por contactarnos!';
            btn.classList.add('green-btn');
            btn.classList.remove('green-btn-inverse');
            iziToast.success({
              title: '¡Gracias por contactarnos!',
              message:'Nos comunicaremos en la brevedad',
              backgroundColor: '#00F710',
              timeout: 7000
            });
        });
        
        var btns = document.querySelectorAll('.failure');
        btns.forEach(function(btn) {
            mensajes = document.querySelectorAll('.result')
            mensajes.forEach(function(msj) {
                msj.innerText = 'Ha ocurrido un error. Inténtelo de nuevo.'
                msj.style.color = 'red';
            })
            
            btn.classList.add('green-btn')
            btn.classList.remove('green-btn-inverse')
        });
        
    }
    
    // $(document).ready(function() {
    //     $('#formulario').submit(function(event) {
    //         console.log('sdfdsf')
    //         event.preventDefault(); // Evita que el formulario se envíe de forma predeterminada
            
    //         var boton = $(this).find('button[type="submit"]');
    //         boton.text('Enviando...');
    //         boton.addClass('green-btn')
    //         boton.removeClass('green-btn-inverse')

    //         var formData = $(this).serialize(); // Serializa los datos del formulario
            
    //         $.ajax({
    //             url: "{{ route('enviar.mail') }}",
    //             type: 'POST',
    //             data: formData,
    //             success: function(response) {
    //                 // Manejo de respuesta exitosa
    //                 console.log(response.mensaje);
    //                 alert('¡El formulario se envió correctamente!');
    //                 boton.text('Enviar');
    //                 boton.addClass('green-btn-inverse')
    //                 boton.removeClass('green-btn')
    //             },
    //             error: function(xhr) {
    //                 // Manejo de error
    //                 console.error(xhr.responseText);
    //                 alert('Hubo un error al enviar el formulario. Por favor, inténtalo de nuevo.');
    //                 boton.text('Enviar de nuevo');
    //                 boton.addClass('green-btn-inverse')
    //                 boton.removeClass('green-btn')
    //             }
    //         });

            
    //     });
    // });

    // function onSubmit(token) {
    //     document.getElementById("formulario").submit();
    // }

    function onClick(e) {
        e.preventDefault();
        
        grecaptcha.ready(function() {
           grecaptcha.execute('6LfjNywqAAAAAKlfuav604AgIuv_qsZLXYtLqlPw', {action: 'submit'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
              form = document.getElementById('formulario')
              if(form.checkValidity()){
                var boton = e.target;
                boton.innerText = 'Enviando...';
                boton.classList.add('green-btn')
                boton.classList.remove('green-btn-inverse')
                form.submit()
              } else {
                mensajes = document.querySelectorAll('.result')
                mensajes.forEach(function(msj) {
                    msj.innerText = 'Hay campos obligatorios sin completar'
                    msj.style.color = 'red';
                })
              }
              
           });
        });
      }

</script>
@endsection