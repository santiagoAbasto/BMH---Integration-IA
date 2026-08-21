@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('styles')
<style>
    .descargas{
        width: 1016px;
    }
    .descarga{
        background-color: #F4F4F4;
        margin-bottom: 24px;
        align-items: center;
        height: 173px;
        border-radius: 8px;
    }
    .descarga img{
        height: 152px;
        padding: 10px;
        width:152px;
        margin-left: 62px;
    }
    .descarga-nombre{
        font-size: 24px;
        font-weight: 400;
        line-height: normal;
        width: 338px;
    }
    #visualizar{
      color:#fff;
      background-color: var(--azuloscuro);
      border-color: var(--azuloscuro);
      width: 100%
    }

    #visualizar:hover{
      color: #000;
      border-color:#000;
      background-color: transparent;
    }
    .green-btn{
        padding:12px 42px 12px 42px;
    }
    .descarga-btns{
        padding-right:36px;
        align-content:center;
    }
    @media (max-width: 990px){
        .descargas{
            width: auto;
        }
        .descarga{
            margin-left: 20px;
            margin-right: 20px;
        }
        .descarga-btns{
            padding-left: 20px;
            padding-right: 20px;
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
        .green-btn{
            padding-left: 0;
            padding-right: 0;
        }
    }
</style>
@endsection


@section('content')

<section class='titulo'>
    <div class='container d-flex flex-column miga'>
        <div>Descargas</div>
    </div>
</section>

<section class='d-flex justify-content-center' style='padding-top:50px;padding-bottom:57px;'>
    <div class='descargas'>
        @foreach($descargas as $descarga)
            <div class='descarga'>
                <div class='row h-100'>
                    <div class='col-3' style='align-content:center;'>
                        <img src="imagenes/doc.png" alt="">
                    </div>
                    <div class='col-9 col-lg-6' style='align-content:center;'>
                        <div class='descarga-nombre'>{{ucfirst($descarga->nombre)}}</div>
                    </div>
                    
                    <div class='col-lg-3 descarga-btns'>
                        <div class='row'>
                            <div class='col-6 col-lg-12' style='margin-bottom:14px;'>
                                <button id='visualizar' class='green-btn' onclick="visualizar_pdf(event)" data-pdf='archivos/{{$descarga->path}}'>Visualizar</button>
                            </div>
                            <div class='col-6 col-lg-12'>
                                <a href="{{asset('archivos/'.$descarga->path)}}" download="{{$descarga->archivo}}"><button style='width:100%;' class='green-btn'>Descargar</button></a>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        @endforeach
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