{{--
    Header del sitio (rediseño 2026): osole::header-catalog.v1 con las variantes del Figma.

      · Home: transparente sobre el hero, links en mayúsculas y "Nosotros | Contacto" arriba.
      · Páginas internas: barra oscura con Nosotros, Productos, Novedades y Contacto.
      · Zona de Clientes: Productos, Carrito, Historial, Lista de precios, Mis datos y la cuenta.

    Lo dinámico sale de la base como antes: teléfono, mail y redes (Contacto), logos
    (LogosSitio), categorías del menú (MenuProductosComposer) y el buscador
    (layouts.partials.buscador-header).
--}}
@php
    use App\Models\Contacto;
    use App\Services\LogosSitio;

    $contacto ??= Contacto::actual();
    $ruta = Route::currentRouteName();
    $enHome = $ruta === 'home';
    $cliente = Auth::guard('web')->user();
    $zonaPrivada = isset($zonaclientes) && $cliente !== null;
    $conAsesor = app(\App\Services\Ai\AdvisorBootstrap::class)->shouldRender();
    $icono = fn (string $nombre) => \App\Support\IconoSitio::svg($nombre);

    $datosBarra = [];
    if ($telHref = $contacto->telHref()) {
        $datosBarra[] = ['text' => $contacto->tel, 'href' => $telHref, 'icon' => $icono('telefono')];
    }
    if (filled($contacto->mail)) {
        $datosBarra[] = ['text' => $contacto->mail, 'href' => 'mailto:'.$contacto->mail, 'icon' => $icono('mail')];
    }

    $redes = [];
    foreach ($contacto->redesBarra() as $red => $url) {
        $redes[] = ['label' => Contacto::REDES_BARRA[$red].' de BMH', 'href' => $url, 'icon' => $icono($red)];
    }

    // Misma barra en todas las páginas (como la Home): datos y redes a la izquierda, secciones a la derecha.
    $linksBarra = [
        ['label' => 'Nosotros', 'href' => route('nosotros'), 'active' => $ruta === 'nosotros'],
        ['label' => 'Contacto', 'href' => route('contacto'), 'active' => $ruta === 'contacto'],
    ];

    $enProductos = in_array($ruta, ['categorias', 'productos', 'producto', 'search', 'productos.clientes', 'productos.home'], true);
    $categoriaActual = $ruta === 'productos' ? (int) request()->query('categoria') : null;
    $itemsMenu = array_map(fn (array $c) => [
        'label' => $c['nombre'],
        'href' => route('productos', ['categoria' => $c['id']]),
        'image' => $c['imagen'] ? asset($c['imagen']) : null,
        'meta' => number_format($c['cantidad'], 0, ',', '.').($c['cantidad'] === 1 ? ' producto' : ' productos'),
        'active' => $categoriaActual === $c['id'],
    ], $categoriasMenu);

    $logo = $logosSitio->url(LogosSitio::HEADER_TRANSPARENTE);
@endphp

<x-osole::header-catalog.v1
    id="site-header"
    :class="\Illuminate\Support\Arr::toCssClasses(['bmh-header', 'bmh-header--home' => $enHome, 'bmh-header--privada' => $zonaPrivada, 'bmh-header--con-asesor' => $conAsesor])"
    :home-href="$zonaPrivada ? route('productos.clientes') : route('home')"
    home-label="BMH Distribuidora, ir al inicio"
    :topbar-items="$datosBarra"
    :social-links="$redes"
    :topbar-links="$linksBarra"
    topbar-layout="split"
    :overlay="$enHome"
    :scrolled-after="$enHome ? 40 : 0"
    collapse-at="xl"
    menu-label="Menú"
>
    <x-slot:logo>
        <img src="{{ $logo }}" alt="" width="198" height="64">
    </x-slot:logo>

    <x-slot:search>
        @include('layouts.partials.buscador-header')
    </x-slot:search>

    <x-slot:nav>
        @if ($zonaPrivada)
            <x-osole::header-catalog.v1.dropdown label="Productos" :href="route('productos.clientes')" :active="$enProductos" panel-id="menu-productos-panel">
                <x-osole::header-catalog.v1.mega title="Líneas de productos" all-label="Ver todas las categorías" :all-href="route('productos.clientes')" :items="$itemsMenu" />
            </x-osole::header-catalog.v1.dropdown>
            <x-osole::header-catalog.v1.link :href="route('carrito')" :active="$ruta === 'carrito'">Carrito</x-osole::header-catalog.v1.link>
            @if ($cliente->rol === 'cliente')
                <x-osole::header-catalog.v1.link :href="route('cliente.historial')" :active="in_array($ruta, ['cliente.historial', 'cliente.pedido'], true)">Historial</x-osole::header-catalog.v1.link>
            @endif
            <x-osole::header-catalog.v1.link :href="route('lista')" :active="$ruta === 'lista'">Lista de precios</x-osole::header-catalog.v1.link>
            <x-osole::header-catalog.v1.link :href="route('cliente.datos')" :active="$ruta === 'cliente.datos'">Mis datos</x-osole::header-catalog.v1.link>
            @if ($cliente->rol === 'vendedor' && Route::has('vendedor.clientes'))
                <x-osole::header-catalog.v1.link :href="route('vendedor.clientes')" :active="$ruta === 'vendedor.clientes'">Clientes</x-osole::header-catalog.v1.link>
            @endif
        @else
            {{-- Los mismos links que la Home en todas las páginas públicas (Nosotros y Contacto van arriba). --}}
            <x-osole::header-catalog.v1.dropdown label="Productos" :href="route('categorias')" :active="$enProductos" panel-id="menu-productos-panel">
                <x-osole::header-catalog.v1.mega title="Líneas de productos" all-label="Ver todas las categorías" :all-href="route('categorias')" :items="$itemsMenu" />
            </x-osole::header-catalog.v1.dropdown>
            <x-osole::header-catalog.v1.link :href="route('ofertas')" :active="$ruta === 'ofertas'">Ofertas</x-osole::header-catalog.v1.link>
            <x-osole::header-catalog.v1.link :href="route('novedades')" :active="in_array($ruta, ['novedades', 'novedad'], true)">Novedades</x-osole::header-catalog.v1.link>
        @endif

        {{-- Asesor IA: sólo para los clientes de la beta (AssistantAccess). --}}
        @if ($conAsesor)
            <li class="osole-header-catalog-v1__item">
                <a href="#" class="osole-header-catalog-v1__link bmh-asesor-link" data-bmh-advisor>
                    <span class="bmh-asesor-punto" aria-hidden="true"></span>Asistente virtual
                </a>
            </li>
        @endif
    </x-slot:nav>

    <x-slot:actions>
        @if ($cliente)
            <x-osole::header-catalog.v1.button popovertarget="bmh-cuenta" class="bmh-cuenta-boton">
                <x-slot:icon>{{ $icono('usuario') }}</x-slot:icon>
                {{ \Illuminate\Support\Str::limit($cliente->name ?: $cliente->username, 18) }}
            </x-osole::header-catalog.v1.button>
        @else
            <x-osole::header-catalog.v1.button popovertarget="bmh-ingreso" class="bmh-zona-boton">
                <x-slot:icon>{{ $icono('usuario') }}</x-slot:icon>
                Zona Clientes
            </x-osole::header-catalog.v1.button>
        @endif
    </x-slot:actions>
</x-osole::header-catalog.v1>

{{-- Ingreso a la Zona de Clientes y menú de la cuenta: popovers nativos (Esc y tocar afuera los cierran). --}}
@if ($cliente)
    <div id="bmh-cuenta" class="bmh-popover bmh-cuenta" popover>
        <p class="bmh-cuenta__nombre">{{ $cliente->name ?: $cliente->username }}</p>
        <ul class="bmh-cuenta__opciones">
            @if ($zonaPrivada)
                <li><a href="{{ route('home') }}">Ir al sitio</a></li>
            @else
                <li><a href="{{ route('productos.clientes') }}">Ir a la Zona de Clientes</a></li>
            @endif
            <li><a href="{{ route('cliente.datos') }}">Mis datos</a></li>
            <li>
                <form method="POST" action="{{ route('logout', ['ventana' => 'home']) }}">
                    @csrf
                    <button type="submit" class="bmh-cuenta__salir">Cerrar sesión</button>
                </form>
            </li>
        </ul>
    </div>
@else
    <div id="bmh-ingreso" class="bmh-popover bmh-ingreso" popover>
        @include('frontend.form-login')
    </div>
@endif
