@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('content')

    @include('frontend.components.miga-baner', ['titulo' => 'Ofertas'])

    <section style='padding-top:51px;padding-bottom:82px;'>
        <div class='container'>            
            <div class='row'>
                @foreach($productos as $producto)
                    <div class='col-md-3' style='margin-bottom:24px;'>
                        @include('frontend/components/producto')
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    
@endsection
