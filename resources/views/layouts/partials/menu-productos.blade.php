{{--
    «Productos» del header con su menú desplegable de categorías
    (computadora). El link lleva a /categorias (o al inicio de la Zona de
    Clientes); cada categoría, a su listado.

    Parámetros opcionales (los usa la navegación de la Zona de Clientes):
      href, claseLink, activo

    Las categorías vienen de App\View\Composers\MenuProductosComposer. Las
    imágenes se piden recién la primera vez que se abre el menú
    (public/js/menu-productos.js): el header está en todas las páginas.
--}}
@php
    $rutaActual = Route::currentRouteName();
    $activo = $activo ?? ($rutaActual !== 'home' && in_array($rutaActual, ['categorias', 'productos', 'producto', 'search'], true));
    $href = $href ?? route('categorias');
    $claseLink = $claseLink ?? 'nav-link itemNavb under active';
    $categoriaActual = $rutaActual === 'productos' ? (int) request()->query('categoria') : null;
    $totalProductos = array_sum(array_column($categoriasMenu, 'cantidad'));
@endphp
<li class="nav-item seleccionable menu-productos {{ $activo ? 'seleccionado' : '' }}" data-menu-productos>
    <a class="{{ $claseLink }} menu-productos__link {{ $activo ? 'nav-activo' : '' }}"
       style="position: relative;"
       href="{{ $href }}"
       aria-haspopup="true" aria-expanded="false" aria-controls="menu-productos-panel">
        Productos
        <svg class="menu-productos__flecha" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

    <div id="menu-productos-panel" class="menu-productos__panel">
        <div class="menu-productos__interior">
            <div class="menu-productos__cabecera">
                <div>
                    <p class="menu-productos__titulo">Líneas de productos</p>
                    <p class="menu-productos__subtitulo">
                        {{ count($categoriasMenu) }} categorías · {{ number_format($totalProductos, 0, ',', '.') }} productos
                    </p>
                </div>
                <a class="menu-productos__todas" href="{{ route('categorias') }}">
                    Ver todas las categorías
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <ul class="menu-productos__grilla">
                @foreach ($categoriasMenu as $categoriaMenu)
                    <li>
                        <a class="menu-productos__item {{ $categoriaActual === $categoriaMenu['id'] ? 'is-actual' : '' }}"
                           href="{{ route('productos', ['categoria' => $categoriaMenu['id']]) }}"
                           @if ($categoriaActual === $categoriaMenu['id']) aria-current="page" @endif>
                            <span class="menu-productos__imagen">
                                @if ($categoriaMenu['imagen'])
                                    <img data-src="{{ asset($categoriaMenu['imagen']) }}" alt="" width="64" height="64" decoding="async">
                                @else
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="menu-productos__texto">
                                <span class="menu-productos__nombre">{{ $categoriaMenu['nombre'] }}</span>
                                <span class="menu-productos__cantidad">
                                    {{ number_format($categoriaMenu['cantidad'], 0, ',', '.') }} {{ $categoriaMenu['cantidad'] === 1 ? 'producto' : 'productos' }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</li>
