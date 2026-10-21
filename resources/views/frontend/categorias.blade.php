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
            <div class='row categorias-grilla'>
                @foreach($categorias as $categoria)
                    {{-- 6 por fila en pantallas grandes, 4 / 3 / 2 al achicarse. --}}
                    <div class='col-6 col-md-4 col-lg-3 col-xl-2' style='margin-bottom:24px;' data-aos="fade-up">
                        @include('components/categoria')
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    
@endsection
