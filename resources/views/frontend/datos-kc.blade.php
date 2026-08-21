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
        <div>Datos KC</div>
    </div>
</section>

<section  style='padding-top:200px;padding-bottom:200px;'>

    <div class='pb-3' style='text-align:center;'>
        <a href="{{asset('archivos/'.$modelo->path)}}" download=""><button class='btn btn-warning'>Descargar modelo</button></a>
        <a href="{{asset('archivos/'.$datos->path)}}" download><button class='btn btn-warning'>Descargar actual</button></a>
    </div>
            
            
    <div class='d-flex justify-content-center'>
        <form class='pb-3' id='formulario-crear' action="{{route('datos.update')}}" method="POST" enctype="multipart/form-data">
            @csrf
    
            <div class="mb-3">
                <label for="file" class="form-label">Archivo</label>
                <input class="form-control preview" type="file" name='file' accept="file/*" required>
            </div>
    
            <button type="submit" class='btn btn-primary'>Subir</button>
    
        </form>
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