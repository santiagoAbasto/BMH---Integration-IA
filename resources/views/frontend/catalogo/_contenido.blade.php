{{-- Lo que se reemplaza al cambiar un filtro: migas, sidebar y resultados. --}}
<nav class="miga catalogo__miga" aria-label="Ruta de navegación">
    <a href="{{ route('home') }}">Inicio</a>
    <span class="px-2">/</span>
    <a href="{{ route('categorias') }}">Productos</a>
    @if ($filtros->hayBusqueda())
        <span class="px-2">/</span> Búsqueda
    @elseif ($categoria)
        <span class="px-2">/</span> {{ mb_convert_case($categoria->nombre, MB_CASE_TITLE, 'UTF-8') }}
    @endif
</nav>

<div class="catalogo__grilla">
    <div class="catalogo__fondo" data-catalogo-cerrar-filtros aria-hidden="true"></div>

    <aside id="catalogo-filtros" class="catalogo__sidebar" aria-label="Filtros">
        @include('frontend.catalogo._sidebar')
    </aside>

    <div class="catalogo__resultados" data-catalogo-resultados>
        @include('frontend.catalogo._resultados')
    </div>
</div>
