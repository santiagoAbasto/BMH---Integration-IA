{{--
    Características de una categoría (modal crear/editar categoría). Las elegidas viajan como caracteristicas[]
    (CategoriaController hace el sync). Lo maneja resources/js/admin/caracteristicas.js ([data-car-selector]).
    $uid: único por modal; $seleccionadas: colección de Caracteristica ya elegidas; $categoriaId: null al crear.
--}}
@php($seleccionadas = collect($seleccionadas ?? []))
<section class="car-selector" data-car-selector
    data-url-buscar="{{ route('caracteristicas.buscar') }}"
    data-url-crear="{{ route('caracteristicas.store') }}"
    data-url-origenes="{{ route('caracteristicas.origenes') }}"
    data-url-sin-uso="{{ route('caracteristicas.sin-uso') }}"
    data-categoria-id="{{ $categoriaId ?? '' }}"
    aria-labelledby="{{ $uid }}-titulo">
    <div class="car-selector__cabeza">
        <div>
            <h3 id="{{ $uid }}-titulo">Características de la ficha</h3>
            <p>Los datos técnicos que se cargan en los productos de esta categoría y se muestran en el sitio.</p>
        </div>
        <span class="adm-chip" data-car-contador>{{ $seleccionadas->count() ? $seleccionadas->count().' '.($seleccionadas->count() === 1 ? 'elegida' : 'elegidas') : 'ninguna elegida' }}</span>
    </div>

    <ul class="car-chips" data-car-chips aria-label="Características elegidas">
        @foreach ($seleccionadas as $caracteristica)
            <li class="car-chip" data-nombre="{{ $caracteristica->nombre }}">
                <span>{{ $caracteristica->nombre }}</span>
                <input type="hidden" name="caracteristicas[]" value="{{ $caracteristica->id }}">
                <button type="button" aria-label="Quitar {{ $caracteristica->nombre }}" title="Quitar">×</button>
            </li>
        @endforeach
    </ul>
    <p class="car-chips__vacio" data-car-chips-vacio @if ($seleccionadas->isNotEmpty()) hidden @endif>
        Todavía no elegiste ninguna. Buscalas abajo, traelas de otra categoría o creá una nueva.
    </p>
    <p class="visually-hidden" data-car-anuncio aria-live="polite"></p>

    @include('backend.partials.caracteristicas.buscador', [
        'id' => $uid.'-buscar',
        'etiqueta' => 'Agregar una característica',
        'placeholder' => 'Escribí para buscar: voltaje, diámetro, giro…',
        'ayuda' => 'Elegí con las flechas y Enter. Si no existe, podés crearla.',
    ])

    @include('backend.partials.caracteristicas.paneles', [
        'uid' => $uid,
        'origenTitulo' => 'Traer las de otra categoría o producto',
        'origenAyuda' => 'Buscá una categoría o un producto, mirá qué características tiene y agregá las que quieras.',
        'conSinUso' => true,
    ])
</section>
