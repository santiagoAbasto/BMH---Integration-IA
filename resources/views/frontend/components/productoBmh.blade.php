{{--
    Card horizontal de producto para la Zona de Clientes (diseño Figma).
    Se incluye dentro de frontend/productos-carrito-bmh (una vez por producto).

    Requiere en el controlador: $producto con eager load de
    portadaImagen, productCaracteristicas.caracteristica y partesRelacionadas.
--}}
@php
    $portadaUrl = $producto->portadaUrl();
    $clienteLogueado = Auth::guard('web')->check();
    $cliente = $clienteLogueado ? Auth::guard('web')->user() : null;
    $descuentoCliente = $clienteLogueado ? (float) ($cliente->descuento ?? 0) : 0;
    $precioListaF = number_format($producto->precio(), 2, ',', '.');
    $precioConDescF = number_format($producto->precio_unitario_descontado(), 2, ',', '.');
    $margenReventa = $clienteLogueado
        ? (float) $cliente->margenReventaParaCategoria($producto->categoria_id)
        : 0.0;
    $tieneMargenReventa = abs($margenReventa) > 0.00001;
    $precioReventaF = $tieneMargenReventa ? $producto->precio_reventa() : $precioListaF;
    $tienePartes = $producto->partesRelacionadas->isNotEmpty();
    $tieneEquivalencias = $producto->equivalencias->isNotEmpty();
    $tieneAplicaciones = $producto->aplicaciones->isNotEmpty();
    $galeriaUrls = method_exists($producto, 'galeriaUrls') ? $producto->galeriaUrls() : [];
    if (empty($galeriaUrls)) {
        $galeriaUrls = [$portadaUrl];
    }
    $mostrarThumbs = count($galeriaUrls) > 1;
@endphp

<div class="pbmh-card" data-pbmh data-agg-url="{{ route('carrito.agregar') }}" data-csrf="{{ csrf_token() }}">

    {{-- ==================== Parte superior ==================== --}}
    <div class="pbmh-top">
        <div class="pbmh-gallery" @include('frontend.components.galeria-datos', ['productoGaleria' => $producto, 'fotosGaleria' => $galeriaUrls])>
            @if ($mostrarThumbs)
            <div class="pbmh-thumbs" aria-label="Vista previa de imágenes">
                @foreach ($galeriaUrls as $idx => $thumbUrl)
                    <button type="button" class="pbmh-thumb-btn {{ $idx === 0 ? 'activo' : '' }}" data-src="{{ $thumbUrl }}" aria-label="Imagen {{ $idx + 1 }}">
                        <img src="{{ $thumbUrl }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
            @endif
            <div class="pbmh-imgbox" data-gallery-open role="button" tabindex="0" aria-haspopup="dialog" aria-label="Ver fotos de {{ $producto->nombre }}">
                <img src="{{ $galeriaUrls[0] }}" alt="{{ $producto->nombre }}" loading="lazy">
            </div>
        </div>

        <div class="pbmh-body">
            <div class="pbmh-headrow">
                <div class="pbmh-titulos">
                    <span class="pbmh-codigo">{{ $producto->codigo }}</span>
                    <span class="pbmh-nombre">{{ $producto->nombre }}</span>
                </div>
            </div>

            <div class="pbmh-cars">
                @if ($producto->marca)
                    <div class="pbmh-car">
                        <span class="pbmh-car-label">Marca:</span>
                        <span class="pbmh-car-valor">{{ $producto->marca }}</span>
                    </div>
                @endif
                @if ($producto->modelo)
                    <div class="pbmh-car">
                        <span class="pbmh-car-label">Modelo:</span>
                        <span class="pbmh-car-valor">{{ $producto->modelo }}</span>
                    </div>
                @endif

                @for ($i = 1; $i <= 78; $i++)
                    @php $columna = 'columna_' . $i; @endphp
                    @if (!empty($producto->$columna) && $producto->categoria && !empty($producto->categoria->$columna))
                        <div class="pbmh-car">
                            <span class="pbmh-car-label">{{ mb_strtoupper($producto->categoria->$columna) }}:</span>
                            <span class="pbmh-car-valor">{{ $producto->$columna }}</span>
                        </div>
                    @endif
                @endfor

                @foreach ($producto->productCaracteristicas as $caracteristica)
                    @continue(blank($caracteristica->valor))
                    <div class="pbmh-car">
                        <span class="pbmh-car-label">{{ mb_strtoupper($caracteristica->caracteristica->nombre ?? '') }}:</span>
                        <span class="pbmh-car-valor">{{ $caracteristica->valor }}</span>
                    </div>
                @endforeach
            </div>

            @if ($clienteLogueado)
                <div class="pbmh-precios">
                    <div class="pbmh-precio-fila" data-sensitive-fields>
                        <span class="pbmh-precio-label">Precio Lista:</span>
                        <span class="pbmh-precio-valor">${{ $precioListaF }}</span>
                    </div>
                    @if ($descuentoCliente > 0)
                        <div class="pbmh-precio-fila" data-sensitive-fields>
                            <span class="pbmh-precio-label">Descuento cliente:</span>
                            <span class="pbmh-precio-valor">{{ $descuentoCliente }}%</span>
                        </div>
                    @endif
                    @if ($descuentoCliente > 0)
                        <div class="pbmh-precio-fila" data-sensitive-fields>
                            <span class="pbmh-precio-label">Precio con descuento:</span>
                            <span class="pbmh-precio-valor">${{ $precioConDescF }}</span>
                        </div>
                    @endif
                    <div class="pbmh-precio-fila pbmh-precio-reventa" data-reventa-row>
                        <span class="pbmh-precio-label" data-reventa-label>Precio reventa:</span>
                        <span class="pbmh-reventa-control">
                            <span class="pbmh-precio-valor pbmh-reventa-valor" data-reventa-value>${{ $precioReventaF }}</span>
                            <button type="button" class="pbmh-reventa-toggle" data-reventa-toggle
                                aria-label="Ocultar precios y campos" aria-pressed="true" title="Ocultar precios y campos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M2.1 12s3.4-6 9.9-6 9.9 6 9.9 6-3.4 6-9.9 6-9.9-6-9.9-6Z"></path>
                                    <circle cx="12" cy="12" r="2.6"></circle>
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>

                <div class="pbmh-actions">
                    <div class="pbmh-stepper">
                        <button type="button" class="pbmh-step" data-step="-1" aria-label="Restar">&minus;</button>
                        <span class="pbmh-qty" data-qty>1</span>
                        <button type="button" class="pbmh-step" data-step="1" aria-label="Sumar">+</button>
                    </div>
                    <button type="button" class="pbmh-cart-btn"
                        data-add data-producto-id="{{ $producto->id }}"
                        data-precio="{{ number_format($producto->precio_unitario_descontado(), 2, '.', '') }}">
                        SUMAR AL CARRITO
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="19" viewBox="0 0 15 17" fill="none">
                            <path d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z" fill="#0098DA"/>
                        </svg>
                    </button>
                </div>
            @else
                <div class="pbmh-actions pbmh-actions-consultar">
                    <a href="{{ route('contacto', ['producto' => $producto->nombre]) }}" class="pbmh-consultar">CONSULTAR</a>
                </div>
            @endif
        </div>
    </div>

    {{-- ==================== Desplegables ==================== --}}
    @if ($tienePartes || $tieneEquivalencias || $tieneAplicaciones)
    <div class="pbmh-tabs">
        @if ($tienePartes)
            <button type="button" class="pbmh-tab" data-tab="partes">
                Partes relacionadas <span class="pbmh-caret">&#9662;</span>
            </button>
        @endif
        @if ($tieneEquivalencias)
            <button type="button" class="pbmh-tab" data-tab="equivalencias">
                Equivalencias <span class="pbmh-caret">&#9662;</span>
            </button>
        @endif
        @if ($tieneAplicaciones)
            <button type="button" class="pbmh-tab" data-tab="aplicaciones">
                Aplicaciones <span class="pbmh-caret">&#9662;</span>
            </button>
        @endif
    </div>

    @if ($tienePartes)
    <div class="pbmh-panel" data-panel="partes">
        <div class="pbmh-panel-inner">
        <table class="pbmh-tabla">
            <thead>
                <tr>
                    <th class="pbmh-col-img"></th>
                    <th>Código</th>
                    <th>Descripción</th>
                    @if ($clienteLogueado)
                        <th>Precio</th>
                        <th>Descuento</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th></th>
                    @else
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($producto->partesRelacionadas as $parte)
                    @php
                        $parteLista = (float) $parte->precio;
                        $parteDesc = $parteLista * (1 - ((float) $parte->descuento / 100)) * (1 - ($descuentoCliente / 100));
                    @endphp
                    <tr data-precio="{{ number_format($parteDesc, 2, '.', '') }}">
                        <td class="pbmh-col-img">
                            <a href="{{ route('producto', ['id' => $parte->id]) }}" data-gallery-open aria-haspopup="dialog" aria-label="Ver fotos de {{ $parte->nombre }}"
                               @include('frontend.components.galeria-datos', ['productoGaleria' => $parte])>
                                <img class="pbmh-thumb"
                                    src="{{ $parte->portadaUrl() }}"
                                    alt="" loading="lazy">
                            </a>
                        </td>
                        <td class="pbmh-celda-cod">
                            <a href="{{ route('producto', ['id' => $parte->id]) }}">{{ $parte->codigo }}</a>
                        </td>
                        <td class="pbmh-celda-desc">
                            <a href="{{ route('producto', ['id' => $parte->id]) }}">
                                <span>{{ $parte->nombre }}</span>
                                <span>Medidas</span>
                            </a>
                        </td>
                        @if ($clienteLogueado)
                            <td class="pbmh-num">${{ number_format($parteLista, 2, ',', '.') }}</td>
                            <td class="pbmh-num">${{ number_format($parteDesc, 2, ',', '.') }}</td>
                            <td>
                                <div class="pbmh-stepper pbmh-stepper-sm">
                                    <span class="pbmh-qty" data-qty>1</span>
                                    <span class="pbmh-steps">
                                        <button type="button" class="pbmh-step" data-step="1" aria-label="Sumar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 5l4-4 4 4"/></svg>
                                        </button>
                                        <button type="button" class="pbmh-step" data-step="-1" aria-label="Restar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l4 4 4-4"/></svg>
                                        </button>
                                    </span>
                                </div>
                            </td>
                            <td class="pbmh-num pbmh-total" data-total>${{ number_format($parteDesc, 2, ',', '.') }}</td>
                            <td>
                                <button type="button" class="pbmh-mini-cart" data-add
                                    data-producto-id="{{ $parte->id }}"
                                    data-precio="{{ number_format($parteDesc, 2, '.', '') }}"
                                    title="Sumar al carrito" aria-label="Sumar al carrito">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 1.5v9M1.5 6h9"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="17.5" cy="20" r="1.4"/><path d="M2.5 3.5h2.2l2.5 11.3a1.7 1.7 0 0 0 1.7 1.3h7.9a1.7 1.7 0 0 0 1.7-1.3l1.8-8.3H6"/></svg>
                                </button>
                            </td>
                        @else
                            <td>
                                <a href="{{ route('contacto', ['producto' => $parte->nombre]) }}" class="pbmh-consultar pbmh-consultar-sm">CONSULTAR</a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    @if ($tieneEquivalencias)
    <div class="pbmh-panel" data-panel="equivalencias">
        <div class="pbmh-panel-inner">
        @include('frontend.components.equivalencias-tabla', ['equivalencias' => $producto->equivalencias])
        </div>
    </div>
    @endif

    @if ($tieneAplicaciones)
    <div class="pbmh-panel" data-panel="aplicaciones">
        <div class="pbmh-panel-inner">
        @include('frontend.components.aplicaciones-tabla', ['aplicaciones' => $producto->aplicaciones])
        </div>
    </div>
    @endif
    @endif
</div>

{{--
    CSS y JS de la card, una sola vez por página. El catálogo los incluye él
    mismo y le pasa `sinRecursosCard` a las cards, porque las que llegan por
    scroll o al filtrar se renderizan en otro pedido y repetirían todo.
--}}
@unless ($sinRecursosCard ?? false)
    @once
        @include('frontend.components.productoBmh-recursos')
    @endonce
@endunless
