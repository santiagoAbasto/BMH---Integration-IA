@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection

@section('content')

    <section class='titulo'>
        <div class='container d-flex flex-column' >
            <div class='row'>
                <div class='miga'>
                    <a href="{{route('home')}}">Inicio</a> / Clientes
                </div>
            </div>
        </div>
    </section>

    <section style='padding-top:68px;padding-bottom:170px;'>
        <div class='container'>
            <div class='row' style='padding-bottom:190px;'>
                        @foreach($clientes as $cliente)
                        <div  class=" imagen-galeria col-6 col-lg-2 my-2 overflow-hidden d-flex flex-column justify-content-center" style='max-height:400px; padding-left:12px;padding-right:12px;'>
                            <div class=' cliente d-flex flex-column justify-content-center border border-2' style='height:182px; background-image: url("{{asset('imagenes/'.$cliente->logo_file)}}"); background-size:cover; '>
                                {{-- <img src="{{asset('imagenes/'.$cliente->logo_file)}}"  alt="Service 1" class="" style='max-width:100%;'> --}}
                            </div>
                            
                        </div>
                        @endforeach
                    

            </div>

        </div>
    </section>
        
@endsection