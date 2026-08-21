@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('content')

{{-- <section class='titulo'>
    <div class='container d-flex flex-column miga'>
        <div><p class="migaText">Productos</p></div>
    </div>
</section> --}}

    <section style='padding-top:78px;padding-bottom:82px;'>
        <div class='container'>            
            <div class='row'>
                @foreach($categorias as $categoria)
                    <div class='col-lg-3' style='margin-bottom:24px;' data-aos="fade-up">
                        @include('components/categoria')
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    
@endsection
