@extends('layouts.plantilla-front')

{{-- @section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection --}}

@section('styles')
<style>
    .descargas{
        width: 1016px;
    }
    .descarga{
        background-color: #F4F4F4;
        margin-bottom: 24px;
        align-items: center;
        height: 217px;
        border-radius: 8px;
    }
    .descarga img{
        height: 152px;
        padding: 10px;
        width:152px;
        margin-left: 62px;
        margin-right: 51px;
    }
    .descarga-nombre{
        font-family: Poppins;
        font-size: 32px;
        font-style: normal;
        font-weight: 600;
        line-height: normal;
        margin-bottom:17px;
    }
    .tituloDescarga{
        color: #000;
font-family: Montserrat;
font-size: 32px;
font-style: normal;
font-weight: 600;
line-height: normal;
    }
    .descripcionDescarga{
        color: #000;
font-family: Montserrat;
font-size: 16px;
font-style: normal;
font-weight: 400;
line-height: normal;
margin-top: 34px
    }
    #visualizar{
      /* color:#fff;
      background-color: var(--azuloscuro);
      border-color: var(--azuloscuro); */
      width: 100%
    }

    #visualizar:hover{
      color: #000;
      background-color: transparent;
    }
    .green-btn{
        padding:12px 42px 12px 42px;
    }
    .green-btn-inverse:not(.zona-cliente-btn){
        padding:12px 42px 12px 42px;
        color: #000;
    }
    .green-btn-inverse:not(.zona-cliente-btn):hover{
        color: #fff;
    }
    .descarga-btns{
        padding-right:60px;
        align-content:center;
    }
    .dropdown-menu{
        border-radius: 0px 0px 15px 15px;
        background: #FFF;
        box-shadow: 0px 1px 3px 0px rgba(0, 0, 0, 0.35);
        padding:0;
    }
    .dropdown-menu li{
        width:245px;
        border-bottom:1px solid #d4d4d4;
        padding: 0;
    }
    .dropdown-item:hover{
        background-color: rgba(78, 153, 212, 0.22);
    }

    .dow-btn{
        display: flex;
width: 129px;
height: 40px;
padding: 10px;
justify-content: center;
align-items: center;
gap: 10px;
flex-shrink: 0;
border-radius: 8px;
background: #FFF;
color: #0098DA;
font-family: Montserrat;
font-size: 16px;
font-style: normal;
font-weight: 600;
line-height: normal;
    }

    .dow-btn:hover{
        background: var(--Verde, #0098DA);
        color: white;
        border: 1px solid #0098DA

    }

    .ver-btn{
        display: flex;
width: 129px;
height: 40px;
padding: 10px;
justify-content: center;
align-items: center;
gap: 10px;
flex-shrink: 0;
border-radius: 8px;
background: #0098DA;
color: #FFF;
font-family: 'Montserrat';
font-size: 16px;
font-style: normal;
font-weight: 600;
line-height: normal;
    }

    .ver-btn:hover{
        border: 1px solid #0098DA;
        background: #FFF;
        color: #0098DA;

        
    }



    @media (max-width: 990px){
        .descargas{
            width: auto;
            
            max-width: 100%;
        }
        .descarga{
            margin-left: 20px;
            margin-right: 20px;
            height: auto;
        }
        .descarga-nombre{
            font-size: 20px;
            padding-top: 20px;
            margin-bottom: 12px;
        }
        .descarga-btns{
            padding: 20px 30px 10px 30px;
        }
        .descarga img{
            height: 100px;
            padding: 10px;
            width:100px;
            margin:0;
        }
        .descarga-nombre{
            width: auto;
        }
        .green-btn:not(.zona-cliente-btn){
            padding-left: 0;
            padding-right: 0;
        }
    }
</style>
@endsection


@section('content')

{{-- <div class="container"
style="padding-top: 24px; color: #717171 !important; font-family: Roboto; font-size: 16px; font-style: normal; font-weight: 400; line-height: normal;">
<a href="">Inicio  <span style="padding-left: 14px; padding-right:14px">/</span> <a href="">Lista de precios</a></a>

</div> --}}
{{-- <section class='d-flex justify-content-center' style='padding-top:58px;padding-bottom:104px;'>
    <div class='descargas'>

        <div class='descarga'>
            <div class='row h-100'>
                <div class='col-3' style='align-content:center;'>
                    <img src="imagenes/exc.png" alt="">
                </div>
                <div class='col-9 col-lg-6' style='align-content:center;'>
                    <div class='descarga-nombre'>{{ucfirst($lista->nombre)}}</div>
                    <div>Descargá nuestra lista de precios actualizada</div>
                </div>
                
                <div class='col-lg-3 descarga-btns'>
                    <div class='row h-100' style='align-content:center;'>
                        <div class='col-6 col-lg-12' style='margin-bottom:14px;'>
                            <button id='visualizar' class='green-btn' onclick="visualizar_pdf(event)" data-pdf='archivos/{{$lista->path}}'>Visualizar</button>
                        </div>
                        <div class='col-6 col-lg-12'>
                            <a href="{{asset('archivos/'.$lista->path)}}" download="{{$lista->archivo}}"><button style='width:100%;' class='green-btn-inverse'>Descargar</button></a>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>

    
     --}}
</section>

<section style="padding-top: 50px; padding-bottom: 126px">
    <div class="container">

        <div class="row">
    
            @foreach ($listas as $lista)
            <div class="col-lg-12 listaPrecioDiv" style="height: 193px; border-radius: 8px; background: #F3F3F3; margin-top: 36px; padding-top: 50px">
                <div class="row d-flex">
                    <div class="col-lg-2" style="padding-left: 32px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="107" height="107" viewBox="0 0 107 107" fill="none">
                            <path d="M56.584 8.67274V39.1094C56.584 39.3301 56.6717 39.5418 56.8277 39.6978C56.9838 39.8539 57.1954 39.9415 57.4161 39.9415H87.8528C87.935 39.9416 88.0154 39.9173 88.0838 39.8717C88.1522 39.8262 88.2056 39.7613 88.2372 39.6854C88.2688 39.6095 88.2772 39.526 88.2614 39.4453C88.2455 39.3646 88.2062 39.2905 88.1482 39.2322L57.2934 8.37733C57.2351 8.31938 57.1609 8.27999 57.0802 8.26415C56.9995 8.2483 56.916 8.25671 56.8401 8.28832C56.7642 8.31992 56.6994 8.3733 56.6538 8.44172C56.6082 8.51014 56.5839 8.59052 56.584 8.67274Z" fill="#0098DA"/>
                            <path d="M51.5914 46.5985C51.15 46.5985 50.7267 46.4232 50.4146 46.1111C50.1025 45.799 49.9272 45.3757 49.9272 44.9343V6.65698H19.1389C18.4769 6.65698 17.8419 6.91999 17.3737 7.38814C16.9056 7.8563 16.6426 8.49126 16.6426 9.15333V97.3575C16.6426 98.0196 16.9056 98.6546 17.3737 99.1227C17.8419 99.5909 18.4769 99.8539 19.1389 99.8539H87.3724C88.0344 99.8539 88.6694 99.5909 89.1376 99.1227C89.6057 98.6546 89.8687 98.0196 89.8687 97.3575V46.5985H51.5914ZM73.2264 79.8831H33.2849V73.2262H73.2264V79.8831ZM73.2264 63.2408H33.2849V56.5839H73.2264V63.2408Z" fill="#0098DA"/>
                          </svg>
                    </div>
    
                    <div class="col-lg-8 d-flex flex-column">
                       <span class="tituloDescarga"> {{ucfirst($lista->nombre)}}</span>
                        <span class="descripcionDescarga">Descargá nuestra lista de precios actualizada</span>
    
                    </div>
    
                    <div class="col-lg-2 d-flex flex-column align-items-center botonesLista">
                        <button onclick="visualizar_pdf(event)" data-pdf='archivos/{{$lista->path}}' class="btn ver-btn">Ver online</button>
                        <a style="padding-top: 32px" href="{{asset('archivos/'.$lista->path)}}" download="{{$lista->archivo}}"><button class="btn dow-btn">Descargar</button> </a>
                    </div>
    
                </div>
        
            </div>
            
            
            
            @endforeach
        </div>
    </div>

</section>

    
@endsection

@section('script')
<script>

    function visualizar_pdf(event){
        var pdfURL = event.target.getAttribute('data-pdf') // Reemplaza 'ruta/al/archivo.pdf' con la URL de tu archivo PDF
        window.open(pdfURL, '_blank');
    }
</script>
@endsection