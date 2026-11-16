@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('content')
    @php
        use App\Support\IconoSitio;
        use App\Support\TextoAdmin;

        // Textos del admin (Nosotros); sin título cargado, los del Figma.
        $contenido = $nosotros_contenido ?? new \App\Models\Nosotros();
        $tarjetas = [];
        foreach (\App\Models\Nosotros::TARJETAS as $campo => [$titulo, $icono]) {
            $tarjetas[] = ['icono' => $icono, 'titulo' => $titulo, 'texto' => TextoAdmin::html($contenido->{$campo})];
        }
        $foto = $nosotros_portada?->path ? asset('imagenes/'.$nosotros_portada->path) : asset('img/rediseno/planta-bmh.webp');
    @endphp

    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Nosotros']]])

    <x-osole::info-split.v1
        class="bmh-nosotros-historia"
        :title="$contenido->historiaTitulo()"
        :description="TextoAdmin::html($nosotros_contenido?->info)"
        :image="['src' => $foto, 'alt' => 'Planta de BMH']"
        :heading-level="1"
    />

    <section class="bmh-nosotros-valores" aria-labelledby="bmh-nosotros-valores-titulo">
        <x-osole::container.v1>
            <h2 id="bmh-nosotros-valores-titulo" class="bmh-seccion-titulo">{{ $contenido->valoresTitulo() }}</h2>
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
