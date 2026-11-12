@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('metadatos')
    @php $metadatos = App\Models\Metadatos::query()->first(); @endphp
    <meta name='keyword' content='{{ $metadatos?->keyword ?? '' }}'>
    <meta name='descripcion' content='{{ $metadatos?->descripcion ?? '' }}'>
@endsection

@section('content')
    @php
        use App\Support\IconoSitio;
        use App\Support\TextoAdmin;

        // Textos del admin (Nosotros); los títulos son los del Figma.
        $tarjetas = [
            ['icono' => 'mision', 'titulo' => 'Misión', 'texto' => TextoAdmin::html($nosotros_contenido?->mision)],
            ['icono' => 'objetivos', 'titulo' => 'Objetivos', 'texto' => TextoAdmin::html($nosotros_contenido?->vision)],
            ['icono' => 'servicios', 'titulo' => 'Servicios', 'texto' => TextoAdmin::html($nosotros_contenido?->valores)],
        ];
        $foto = $nosotros_portada?->path ? asset('imagenes/'.$nosotros_portada->path) : asset('img/rediseno/planta-bmh.webp');
    @endphp

    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Nosotros']]])

    <x-osole::info-split.v1
        class="bmh-nosotros-historia"
        title="Compromiso y dedicación, todos los días"
        :description="TextoAdmin::html($nosotros_contenido?->info)"
        :image="['src' => $foto, 'alt' => 'Planta de BMH']"
        :heading-level="1"
    />

    <section class="bmh-nosotros-valores" aria-labelledby="bmh-nosotros-valores-titulo">
        <x-osole::container.v1>
            <h2 id="bmh-nosotros-valores-titulo" class="bmh-seccion-titulo">Lo que nos mueve</h2>
            <ul class="bmh-grilla-valores" role="list">
                @foreach ($tarjetas as $tarjeta)
                    @continue($tarjeta['texto'] === null)
                    <li>
                        <x-osole::feature-card.v1 :icon="IconoSitio::svg($tarjeta['icono'])" :title="$tarjeta['titulo']" :description="$tarjeta['texto']" />
                    </li>
                @endforeach
            </ul>
        </x-osole::container.v1>
    </section>
@endsection
