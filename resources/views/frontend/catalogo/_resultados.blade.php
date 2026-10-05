@php
    $total = $resultado->total();
    $hayFiltros = $filtros->cantidadActivos() > 0;
@endphp

<div class="catalogo__cabecera">
    <div class="catalogo__encabezado">
        <h1 class="catalogo__titulo">{{ $titulo }}</h1>
        <p class="catalogo__total" role="status">
            <strong>{{ number_format($total, 0, ',', '.') }}</strong> {{ $total === 1 ? 'producto' : 'productos' }}
        </p>
    </div>

    {{-- Celular: los filtros se abren en un panel. --}}
    <button type="button" class="catalogo__abrir-filtros" data-catalogo-abrir-filtros aria-controls="catalogo-filtros" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        Filtros
        @if ($hayFiltros)
            <span class="filtros__contador">{{ $filtros->cantidadActivos() }}</span>
        @endif
    </button>
</div>

@if ($chips->isNotEmpty())
    <ul class="catalogo__chips" aria-label="Filtros aplicados">
        @foreach ($chips as $chip)
            <li>
                <a class="catalogo__chip" href="{{ $chip['url'] }}" data-catalogo-link aria-label="Quitar filtro {{ $chip['etiqueta'] }}">
                    <span>{{ $chip['etiqueta'] }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                </a>
            </li>
        @endforeach
    </ul>
@endif

@if ($total === 0)
    <div class="catalogo__vacio">
        <span class="catalogo__vacio-icono" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5M8.5 8.5l5 5M13.5 8.5l-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </span>
        @if ($hayFiltros)
            <h2>Ningún producto cumple todos los filtros</h2>
            <p>Probá sacando alguno de los filtros aplicados.</p>
        @else
            <h2>No encontramos resultados{{ $filtros->hayBusqueda() ? ' para «'.$filtros->q.'»' : '' }}</h2>
            <p>Revisá cómo está escrito, probá con menos palabras o buscá por el código BMH o una equivalencia.</p>
        @endif
        <div class="catalogo__vacio-acciones">
            @if ($hayFiltros)
                <a class="catalogo__boton catalogo__boton--lleno" data-catalogo-link href="{{ route($ruta, $filtros->query([
                    'estado' => null, 'marca' => null, 'vehiculo' => null, 'modelo' => null,
                    'equivalencia' => null, 'atributo' => null,
                ])) }}">Quitar filtros</a>
            @else
                <a class="catalogo__boton catalogo__boton--lleno" href="{{ route('contacto') }}">Consultanos</a>
            @endif
            <a class="catalogo__boton" href="{{ route('categorias') }}">Ver todas las categorías</a>
        </div>
    </div>
@else
    <div class="catalogo__lista" data-catalogo-lista>
        @include('frontend.catalogo._cards')
    </div>

    {{--
        Scroll infinito: cuando esto entra en pantalla se piden más cards.
        El link es el plan B (sin JS, o si falla la carga): pide la misma
        página con una tanda más.
    --}}
    <div class="catalogo__mas" data-catalogo-siguiente="{{ $siguiente }}" data-paginas="{{ $paginas }}" data-total="{{ $total }}">
        @if ($siguiente)
            <div class="catalogo__esqueletos" aria-hidden="true">
                <div class="catalogo__esqueleto"></div>
                <div class="catalogo__esqueleto"></div>
            </div>
            <a class="catalogo__boton" data-catalogo-cargar
               href="{{ route($ruta, $filtros->query() + ['paginas' => $paginas + 1]) }}">Cargar más productos</a>
        @else
            <p class="catalogo__fin">
                @if ($total > \App\Http\Controllers\CatalogoController::POR_PAGINA)
                    Viste los {{ number_format($total, 0, ',', '.') }} productos.
                @endif
            </p>
        @endif
    </div>
@endif
