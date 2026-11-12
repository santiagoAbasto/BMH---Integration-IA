@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@php
    // Texto con algo para mostrar (el editor del admin deja «<p><br></p>» cuando está vacío).
    $conTexto = fn (?string $html) => trim(strip_tags((string) $html)) !== '' ? new \Illuminate\Support\HtmlString($html) : null;

    // Hero: cada imagen o video del slider del admin; el texto del Figma si el slide no trae uno.
    $slides = [];
    foreach ($home_slider as $slide) {
        $slides[] = [
            'title' => $conTexto($slide->baner_texto) ?? 'Calidad que mantiene tu mundo en movimiento',
            'subtitle' => $conTexto($slide->baner_texto_2) ?? 'Rotores, estatores, inducidos y más de 5.200 repuestos eléctricos para el automotor. Calidad de fábrica, con entrega en todo el país.',
            'buttons' => [['label' => 'Ver productos', 'href' => route('categorias')]],
            'media' => $slide->tipo === 'imagen'
                ? ['src' => asset('imagenes/'.$slide->path)]
                : ['type' => 'video', 'src' => asset('imagenes/'.$slide->path), 'poster' => $slide->path_mobile ? asset('imagenes/'.$slide->path_mobile) : null],
        ];
    }

    $marcas = [
        ['src' => asset('img/rediseno/marca-pars.png'), 'alt' => 'Pars', 'width' => 183, 'height' => 98],
        ['src' => asset('img/rediseno/marca-divaio.png'), 'alt' => 'Divaio', 'width' => 183, 'height' => 98],
        ['src' => asset('img/rediseno/marca-prestolite.png'), 'alt' => 'Prestolite', 'width' => 183, 'height' => 98],
        ['src' => asset('img/rediseno/marca-ep.png'), 'alt' => 'EP', 'width' => 183, 'height' => 98],
    ];

    $nosotrosTitulo = trim((string) $nosotros_slider?->baner_texto);
    $nosotrosTexto = $conTexto($nosotros_slider?->baner_texto_2);
    if (mb_strlen($nosotrosTitulo) < 10) {
        $nosotrosTitulo = 'Más de 55 años fabricando calidad para el automotor';
    }
    if ($nosotrosTexto === null || mb_strlen(trim(strip_tags($nosotrosTexto->toHtml()))) < 20) {
        $nosotrosTexto = 'B.M.H Bobinajes San Francisco S.R.L. se dedica a la elaboración y reparación de rotores y estatores para alternador e inducidos para motores de arranque. En 2017 inauguramos nuestra planta de 800 m², equipada con maquinaria automatizada de última generación.';
    }
@endphp

@section('content')
    <x-osole::hero.v1 class="bmh-home-hero" :slides="$slides" :heading-level="1" />

    <x-osole::logo-strip.v1 class="bmh-home-marcas" title="Trabajamos con" :items="$marcas" :heading-level="2" />

    <section class="bmh-home-rubros" aria-labelledby="bmh-home-rubros-titulo">
        <x-osole::container.v1>
            <h2 id="bmh-home-rubros-titulo" class="visually-hidden">Nuestros rubros</h2>
            <ul class="bmh-grilla-rubros" role="list">
                @foreach ($categorias as $categoria)
                    <li>
                        <x-osole::category-card.v1
                            :name="$categoria->nombre"
                            :href="route('productos', ['categoria' => $categoria->id])"
                            :image="$categoria->portada ? asset('imagenes/'.$categoria->portada) : null"
                        />
                    </li>
                @endforeach
            </ul>
        </x-osole::container.v1>
    </section>

    <x-osole::info-split.v1
        class="bmh-home-nosotros"
        :title="$nosotrosTitulo"
        :description="$nosotrosTexto"
        :image="['src' => $nosotros_slider?->path ? asset('imagenes/'.$nosotros_slider->path) : asset('img/rediseno/planta-bmh.webp'), 'alt' => 'Planta de BMH']"
        media-position="start"
        button-label="Conocé más"
        :button-href="route('nosotros')"
    />

    @if ($novedades->isNotEmpty())
        <section class="bmh-home-novedades" aria-labelledby="bmh-home-novedades-titulo">
            <x-osole::container.v1>
                <div class="bmh-seccion-encabezado">
                    <h2 id="bmh-home-novedades-titulo" class="bmh-seccion-titulo">Novedades</h2>
                    <a href="{{ route('novedades') }}" class="bmh-btn bmh-btn--oscuro">Ver todas</a>
                </div>
                <ul class="bmh-grilla-novedades" role="list">
                    @foreach ($novedades as $novedad)
                        <li>
                            <x-osole::post-card.v1
                                :title="$novedad->titulo"
                                :href="route('novedad', ['id' => $novedad->id])"
                                :image="$novedad->portada ? asset('imagenes/'.$novedad->portada) : null"
                                :category="$novedad->etiqueta"
                                :description="$novedad->epigrafe"
                            />
                        </li>
                    @endforeach
                </ul>
            </x-osole::container.v1>
        </section>
    @endif

    {{-- MODAL REGISTRO --}}
    @if ($registro)
        @php $mail = App\Models\Mail::find(1); @endphp
        <div class="modal fade" id="registro" aria-hidden="true" aria-labelledby="registro-titulo" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bmh-modal-registro">
                    <div class="modal-body">
                        <h2 id="registro-titulo" class="bmh-modal-registro__titulo">{{ $mail?->registro_titulo }}</h2>
                        <div class="bmh-modal-registro__texto">{!! $mail?->registro !!}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
