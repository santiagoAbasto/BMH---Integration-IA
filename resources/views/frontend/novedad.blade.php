@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('styles')
<style>

    .texto{
        color: #000;
font-family: "Montserrat";
font-size: 16px;
font-style: normal;
font-weight: 500;
line-height: 120%; /* 19.2px */
padding-top: 80px
    }

    #consultar {
            border-radius: 10px;
            background: #0098DA;
            border: none;
            color: white;
            width: 100%;
            height: 39px;
            color: #FFF;
font-family: 'Montserrat';
font-size: 16px;
font-style: normal;
font-weight: 600;
line-height: normal;

        }

        #consultar:hover {
            border-radius: 10px;
            background: white;
            border: 1px solid #0098DA;
            color: #0098DA;
            height: 39px;

        }

</style>
@endsection


@section('content')

    <section class="novedad" style='padding-top:49px;padding-bottom:101px;'>
        <div class="container d-flex flex-column justify-content-center align-items-center">

            {{-- <div class="d-flex justify-content-start align-items-center">
                <svg onclick="goBack()" style="margin-top: 0px; margin-bottom: 30px; cursor: pointer;" style="cursor: pointer" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 20L13.41 18.59L7.83 13L20 13L20 11L7.83 11L13.41 5.41L12 4L4 12L12 20Z" fill="#131313"/>
                  </svg>
                <h3 class='novedades-titulo' style='padding-top:5px; padding-bottom:30px; padding-left:10px;   width: 790px;'>{{$novedad->titulo}}</h3>

            </div> --}}
    
            <div class='novedadIN' style='background-image: url("imagenes/{{$novedad->portada}}"); background-size: cover; background-position: center;height:299px; width:100%; max-height:800px;'>
                  
            </div>
            
            {{-- <p class='etiqueta' style='padding-top:31px;'>{{$novedad->etiqueta}}</p>   --}}
            {{-- <h3 class='novedades-titulo' style='padding-top:31px;'>{{$novedad->titulo}}</h3> --}}
            
            <div class='texto' >{!!$novedad->texto!!}</div>
            <button onclick="goBack()" style='margin-top:24px;'  class='green-btn'>Volver</button>
                
   
        </div>
    </section>
    
@endsection

@section('script')
    <script>
        function goBack() {
        window.history.back();
        }
    </script>
@endsection