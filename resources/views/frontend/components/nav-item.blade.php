@php
    // Link de la página actual, fuera de la home. Antes esto era un
    // `color:#000 !important` inline, que le ganaba a cualquier CSS y dejaba
    // el link activo en negro sobre el header de color al hacer scroll.
    $activo = Route::currentRouteName() !== 'home'
        && ((is_array($ruta) && in_array(Route::currentRouteName(), $ruta)) || Route::currentRouteName() == $ruta);
@endphp
<li class="nav-item seleccionable {{request()->routeIs($ruta) ? 'seleccionado' : ''}}">
    <a class="nav-link itemNavb under active {{ $activo ? 'nav-activo' : '' }}"
       style="position: relative;"
       href="{{ route(is_array($ruta) ? $ruta[0] : $ruta) }}">
        {{ $titulo }}
    </a>
</li>
