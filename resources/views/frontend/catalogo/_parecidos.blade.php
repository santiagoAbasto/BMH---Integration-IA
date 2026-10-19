{{--
    Nada cumple todo lo pedido: un aviso corto y, debajo, los productos más
    parecidos (CatalogoController::parecidos). El aviso dice qué se dejó de
    lado para encontrarlos, así se entiende por qué aparecen.
--}}
@php
    $mostrados = $parecidos['productos']->count();
@endphp

<div class="catalogo__aviso" role="status">
    <span class="catalogo__aviso-icono" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </span>

    <div class="catalogo__aviso-texto">
        <p class="catalogo__aviso-titulo"><strong>Sin coincidencias exactas.</strong> Estos son los más parecidos a tu búsqueda.</p>

        @if ($parecidos['termino'] !== null)
            <p>Buscamos «<strong>{{ $parecidos['termino'] }}</strong>» en lugar de «{{ $filtros->q }}».</p>
        @elseif ($parecidos['parcial'])
            <p>Tienen parte de las palabras que buscaste.</p>
        @endif

        @if ($parecidos['sin'] !== [])
            <p class="catalogo__aviso-sin">
                <span>No cumplen:</span>
                @foreach ($parecidos['sin'] as $etiqueta)
                    <span class="catalogo__aviso-filtro">{{ $etiqueta }}</span>
                @endforeach
            </p>
        @endif
    </div>

    @if ($parecidos['url'] !== null && $parecidos['total'] > $mostrados)
        <a class="catalogo__aviso-accion" href="{{ $parecidos['url'] }}" data-catalogo-link>
            Ver los {{ number_format($parecidos['total'], 0, ',', '.') }}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    @endif
</div>

<div class="catalogo__lista">
    @include('frontend.catalogo._cards', ['productos' => $parecidos['productos']])
</div>
