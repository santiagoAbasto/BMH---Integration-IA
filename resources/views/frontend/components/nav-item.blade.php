
<li class="nav-item seleccionable {{request()->routeIs($ruta) ? 'seleccionado' : ''}}">
    {{-- <a class="nav-link above active categorias-nav" href="{{route('categorias')}}">Categorías</a> --}}

    <a class="nav-link itemNavb under active" 
    style="position: relative; 
           {{ (Route::currentRouteName() !== 'home' && ((is_array($ruta) && in_array(Route::currentRouteName(), $ruta)) || Route::currentRouteName() == $ruta)) 
               ? 'color: #000 !important; font-weight: 600' 
               : '' }}" 
    href="{{ route(is_array($ruta) ? $ruta[0] : $ruta) }}">
    {{ $titulo }}
</a>

 
      
    
    
</li>