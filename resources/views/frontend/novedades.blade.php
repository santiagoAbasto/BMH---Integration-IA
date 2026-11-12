@extends('layouts.plantilla-front')

@section('metadatos')
    @php $meta = $metadatos->first(); @endphp
    <meta name='keyword' content='{{ $meta?->keyword ?? '' }}'>
    <meta name='descripcion' content='{{ $meta?->descripcion ?? '' }}'>
@endsection

@section('content')
    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Novedades']]])

    <section class="bmh-seccion-novedades" aria-labelledby="bmh-novedades-titulo">
        <x-osole::container.v1>
            <h1 id="bmh-novedades-titulo" class="visually-hidden">Novedades</h1>
            @if ($novedades->isEmpty())
                <p class="bmh-vacio">Todavía no hay novedades publicadas.</p>
            @else
                <ul class="bmh-grilla-novedades" role="list">
                    @foreach ($novedades as $novedad)
                        <li>
                            <x-osole::post-card.v1
                                :title="$novedad->titulo"
                                :href="route('novedad', ['id' => $novedad->id])"
                                :image="$novedad->portada ? asset('imagenes/'.$novedad->portada) : null"
                                :category="$novedad->etiqueta"
                                :description="$novedad->epigrafe"
                                :heading-level="2"
                            />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-osole::container.v1>
    </section>
@endsection
