{{-- Lo que se reemplaza al cambiar un filtro: migas, sidebar y resultados. --}}
{{-- Migas del rediseño (breadcrumb.v1): en /ofertas, «Ofertas»; si no, «Productos» y la categoría o la búsqueda. --}}
@php
    $migas = $filtros->soloOfertas
        ? [['label' => 'Ofertas', 'href' => $categoria || $filtros->hayBusqueda() ? route('ofertas') : null]]
        : [['label' => 'Productos', 'href' => route('categorias')]];
    if ($filtros->hayBusqueda()) {
        $migas[] = ['label' => 'Búsqueda'];
    } elseif ($categoria) {
        $migas[] = ['label' => mb_convert_case($categoria->nombre, MB_CASE_TITLE, 'UTF-8')];
    }
@endphp
<div class="catalogo__miga bmh-migas bmh-migas--catalogo">
    <x-osole::breadcrumb.v1 :items="array_merge([['label' => 'Inicio', 'href' => route('home')]], $migas)" />
</div>

<div class="catalogo__grilla">
    <div class="catalogo__fondo" data-catalogo-cerrar-filtros aria-hidden="true"></div>

    <aside id="catalogo-filtros" class="catalogo__sidebar" aria-label="Filtros">
        @include('frontend.catalogo._sidebar')
    </aside>

    <div class="catalogo__resultados" data-catalogo-resultados>
        @include('frontend.catalogo._resultados')
    </div>
</div>
