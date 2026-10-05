{{--
    Sidebar de filtros. Es un form GET común: sin JS se aplica con el botón;
    con JS (public/js/catalogo.js) cada cambio se aplica al instante.

    Cada opción muestra cuántos productos quedarían al elegirla, contando el
    resto de los filtros ya elegidos (ResultadoCatalogo).
--}}
@php
    $titulo = fn (string $v) => mb_convert_case($v, MB_CASE_TITLE, 'UTF-8');

    /** [valor => cantidad] → opciones del select, sin perder la elegida aunque quede en 0. */
    $opciones = function (array $conteo, ?string $elegido, bool $ordenar = true) use ($titulo): array {
        if ($elegido !== null && !isset($conteo[$elegido])) {
            $conteo[$elegido] = 0;
        }
        if ($ordenar) {
            uksort($conteo, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
        }

        return collect($conteo)->map(fn ($n, $v) => ['texto' => $titulo((string) $v), 'cantidad' => $n])->all();
    };

    $opcionesCategoria = $categorias
        ->filter(fn ($c) => ($resultado->categorias[$c->id] ?? 0) > 0 || $c->id === $filtros->categoria)
        ->mapWithKeys(fn ($c) => [$c->id => ['texto' => $titulo($c->nombre), 'cantidad' => $resultado->categorias[$c->id] ?? 0]])
        ->all();

    $hayFiltros = $filtros->cantidadActivos() > 0;
@endphp

{{-- El orden por código (catalogo/_resultados) también es de este form, con form="catalogo-form". --}}
<form id="catalogo-form" class="filtros" method="GET" action="{{ route($ruta) }}" data-catalogo-form>
    @if ($filtros->hayBusqueda())
        <input type="hidden" name="q" value="{{ $filtros->q }}">
    @endif

    <div class="filtros__cabecera">
        <h2 class="filtros__encabezado">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Filtros
            @if ($hayFiltros)
                <span class="filtros__contador">{{ $filtros->cantidadActivos() }}</span>
            @endif
        </h2>
        @if ($hayFiltros)
            <a class="filtros__limpiar" href="{{ route($ruta, $filtros->query([
                'estado' => null, 'marca' => null, 'vehiculo' => null, 'modelo' => null,
                'equivalencia' => null, 'atributo' => null,
            ])) }}" data-catalogo-link>Limpiar</a>
        @endif
        <button type="button" class="filtros__cerrar" data-catalogo-cerrar-filtros aria-label="Cerrar filtros">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    </div>

    <div class="filtros__cuerpo">
        {{-- Línea de productos --}}
        <div class="filtros__grupo">
            <label class="filtros__titulo" for="filtro-categoria">Línea de productos</label>
            @include('frontend.catalogo._select', [
                'name' => 'categoria', 'id' => 'filtro-categoria',
                'vacio' => 'Todas las categorías', 'totalVacio' => $resultado->totalSinCategoria,
                'opciones' => $opcionesCategoria, 'seleccionado' => $filtros->categoria,
                'buscable' => 'Buscar categoría',
            ])
        </div>

        {{-- Estado --}}
        <div class="filtros__grupo" role="group" aria-labelledby="filtros-titulo-estado">
            <p class="filtros__titulo" id="filtros-titulo-estado">Estado</p>
            <div class="filtros__estados">
                @foreach (\App\Services\Catalogo\FiltrosCatalogo::ESTADOS as $valor => $estado)
                    @php $cantidad = $resultado->estados[$estado] ?? 0; @endphp
                    <label class="filtros__estado filtros__estado--{{ $valor }}">
                        <input type="checkbox" name="estado[]" value="{{ $valor }}"
                               @checked(in_array($estado, $filtros->estados, true))
                               @disabled($cantidad === 0 && !in_array($estado, $filtros->estados, true))>
                        <span class="filtros__estado-nombre">{{ $estado === \App\Services\Catalogo\FiltrosCatalogo::NUEVO ? 'Nuevo' : 'Reconstruido' }}</span>
                        <span class="filtros__cantidad">{{ number_format($cantidad, 0, ',', '.') }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Marca del repuesto --}}
        <div class="filtros__grupo">
            <label class="filtros__titulo" for="filtro-marca">Marca del repuesto</label>
            @include('frontend.catalogo._select', [
                'name' => 'marca', 'id' => 'filtro-marca', 'vacio' => 'Todas las marcas',
                'opciones' => $opciones($resultado->marcas, $filtros->marca),
                'seleccionado' => $filtros->marca, 'buscable' => 'Buscar marca',
            ])
        </div>

        {{-- Vehículo: sale de las aplicaciones de cada producto --}}
        <div class="filtros__grupo" role="group" aria-labelledby="filtros-titulo-vehiculo">
            <p class="filtros__titulo" id="filtros-titulo-vehiculo">Vehículo</p>
            <div class="filtros__pila">
                <label class="visually-hidden" for="filtro-vehiculo">Marca del vehículo</label>
                @include('frontend.catalogo._select', [
                    'name' => 'vehiculo', 'id' => 'filtro-vehiculo', 'vacio' => 'Marca del vehículo',
                    'opciones' => $opciones($resultado->vehiculos, $filtros->vehiculo),
                    'seleccionado' => $filtros->vehiculo, 'buscable' => 'Buscar marca de vehículo',
                ])
                <label class="visually-hidden" for="filtro-modelo">Modelo del vehículo</label>
                @include('frontend.catalogo._select', [
                    'name' => 'modelo', 'id' => 'filtro-modelo', 'vacio' => 'Modelo',
                    'opciones' => $opciones($resultado->modelos, $filtros->modelo),
                    'seleccionado' => $filtros->modelo, 'buscable' => 'Buscar modelo',
                    'deshabilitado' => $filtros->vehiculo === null, 'ayuda' => 'Elegí primero la marca del vehículo',
                ])
            </div>
        </div>

        {{-- Equivalencia --}}
        <div class="filtros__grupo">
            <label class="filtros__titulo" for="filtro-equivalencia">Equivalencia</label>
            <div class="filtros__texto">
                <input id="filtro-equivalencia" type="search" name="equivalencia" value="{{ $filtros->equivalencia }}"
                       placeholder="Código equivalente" maxlength="60" autocomplete="off" spellcheck="false"
                       enterkeyhint="search" data-catalogo-texto>
            </div>
            <p class="filtros__ayuda">GV, Dipra, PH, ZEN, ZM, Nosso…</p>
        </div>

        {{-- Medidas: sólo con una categoría elegida, que es la que las define --}}
        @if ($filtros->categoria !== null && $resultado->atributos !== [])
            <div class="filtros__grupo" role="group" aria-labelledby="filtros-titulo-medidas">
                <p class="filtros__titulo" id="filtros-titulo-medidas">Medidas y características</p>
                <div class="filtros__pila">
                    @foreach ($resultado->atributos as $columna => $atributo)
                        <label class="filtros__subtitulo" for="filtro-{{ $columna }}">{{ $titulo($atributo['etiqueta']) }}</label>
                        @include('frontend.catalogo._select', [
                            'name' => "atributo[{$columna}]", 'id' => "filtro-{$columna}", 'vacio' => 'Todas',
                            'opciones' => $opciones($atributo['opciones'], $filtros->atributos[$columna] ?? null, false),
                            'seleccionado' => $filtros->atributos[$columna] ?? null,
                            'buscable' => count($atributo['opciones']) > 10 ? 'Buscar valor' : null,
                        ])
                    @endforeach
                </div>
            </div>
        @elseif ($filtros->categoria === null)
            <p class="filtros__nota">Elegí una línea de productos para filtrar por medidas.</p>
        @endif

        <noscript>
            <button type="submit" class="filtros__aplicar">Aplicar filtros</button>
        </noscript>
    </div>

    {{-- Celular: el panel se cierra para ver lo filtrado. --}}
    <div class="filtros__pie">
        <button type="button" class="filtros__ver" data-catalogo-cerrar-filtros>
            Ver {{ number_format($resultado->total(), 0, ',', '.') }} {{ $resultado->total() === 1 ? 'producto' : 'productos' }}
        </button>
    </div>
</form>
