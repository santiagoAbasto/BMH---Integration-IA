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
    $descuentoCliente = $clienteLogueado ? (int) $cliente->descuento : 0;
    $tienePartes = $producto->partesRelacionadas->isNotEmpty();
    $tieneEquivalencias = $producto->equivalencias->isNotEmpty();
    $tieneAplicaciones = $producto->aplicaciones->isNotEmpty();
    $galeriaUrls = method_exists($producto, 'galeriaUrls') ? $producto->galeriaUrls() : [];
    if (empty($galeriaUrls)) {
        $galeriaUrls = $portadaUrl ? [$portadaUrl] : [asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg')];
    }
    $mostrarThumbs = count($galeriaUrls) > 1;
@endphp

<div class="pbmh-card" data-pbmh data-agg-url="{{ route('carrito.agregar') }}" data-csrf="{{ csrf_token() }}">

    {{-- ==================== Parte superior ==================== --}}
    <div class="pbmh-top">
        <div class="pbmh-gallery">
            @if ($mostrarThumbs)
            <div class="pbmh-thumbs" aria-label="Vista previa de imágenes">
                @foreach ($galeriaUrls as $idx => $thumbUrl)
                    <button type="button" class="pbmh-thumb-btn {{ $idx === 0 ? 'activo' : '' }}" data-src="{{ $thumbUrl }}" aria-label="Imagen {{ $idx + 1 }}">
                        <img src="{{ $thumbUrl }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
            @endif
            <div class="pbmh-imgbox">
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
                    <div class="pbmh-precio-fila">
                        <span class="pbmh-precio-label">Precio Lista:</span>
                        <span class="pbmh-precio-valor">${{ number_format($producto->precio(), 2, ',', '.') }}</span>
                    </div>
                    <div class="pbmh-precio-fila">
                        <span class="pbmh-precio-label">Precio reventa:</span>
                        <span class="pbmh-precio-valor">${{ $producto->precio_reventa() }}</span>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="17" viewBox="0 0 15 17" fill="none">
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
                            <a href="{{ route('producto', ['id' => $parte->id]) }}" aria-label="Ver {{ $parte->nombre }}">
                                <img class="pbmh-thumb"
                                    src="{{ $parte->portadaUrl() ?? asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}"
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 1.5v9M1.5 6h9"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="17.5" cy="20" r="1.4"/><path d="M2.5 3.5h2.2l2.5 11.3a1.7 1.7 0 0 0 1.7 1.3h7.9a1.7 1.7 0 0 0 1.7-1.3l1.8-8.3H6"/></svg>
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

@once
<style>
    .pbmh-card { background:#fff; border:1px solid #E7E9EC; border-radius:10px; margin-bottom:18px;
        overflow:hidden; font-family:'Roboto',sans-serif; }
    .pbmh-top { display:flex; gap:22px; padding:22px 24px 18px; }
    .pbmh-gallery { display:flex; gap:10px; flex:0 0 auto; align-self:flex-start; }
    .pbmh-thumbs { display:flex; flex-direction:column; gap:8px; width:90px; max-height:295px; overflow-y:auto; overflow-x:hidden; scrollbar-width:thin; scrollbar-color:#E8EBEF transparent; padding-right:2px; }
    .pbmh-thumbs::-webkit-scrollbar { width:3px; height:3px; }
    .pbmh-thumbs::-webkit-scrollbar-track { background:transparent; }
    .pbmh-thumbs::-webkit-scrollbar-thumb { background:#E8EBEF; border-radius:10px; }
    .pbmh-thumbs::-webkit-scrollbar-thumb:hover { background:#D1D6DE; }
    .pbmh-thumb-btn { width:90px; height:82px; border:1px solid #E7E9EC; border-radius:6px; background:#fff; padding:4px; cursor:pointer; display:flex; align-items:center; justify-content:center; overflow:hidden; transition:border-color .15s, box-shadow .15s; flex-shrink:0; }
    .pbmh-thumb-btn:hover { border-color:#B8C0CC; }
    .pbmh-thumb-btn.activo { border-color:#0098DA; border-width:2px; box-shadow:0 0 0 1px rgba(0,152,218,.15); }
    .pbmh-thumb-btn img { width:100%; height:100%; object-fit:contain; display:block; }
    .pbmh-imgbox { flex:0 0 340px; align-self:flex-start; }
    .pbmh-imgbox img { width:340px; height:295px; object-fit:contain; display:block; }
    .pbmh-body { flex:1; min-width:0; display:flex; flex-direction:column; }
    .pbmh-headrow { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
    .pbmh-titulos { display:flex; flex-direction:column; gap:3px; min-width:0; }
    .pbmh-codigo { font-size:13px; font-weight:700; color:#1F2430; letter-spacing:.02em; }
    .pbmh-nombre { font-size:15px; font-weight:700; color:#1F2430; line-height:1.3;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .pbmh-cars { margin-top:10px; display:flex; flex-direction:column; gap:2px;
        max-height:132px; overflow-y:auto; padding-right:6px; }
    .pbmh-cars::-webkit-scrollbar { width:6px; }
    .pbmh-cars::-webkit-scrollbar-thumb { background:#D9DDE3; border-radius:3px; }
    .pbmh-cars::-webkit-scrollbar-track { background:transparent; }
    .pbmh-car { display:flex; gap:6px; font-size:11px; line-height:1.5; }
    .pbmh-car-label { color:#9AA0A8; letter-spacing:.03em; white-space:nowrap; }
    .pbmh-car-valor { color:#3A3F47; font-weight:500; }
    .pbmh-precios { margin-top:12px; display:flex; flex-direction:column; gap:4px; max-width:320px; }
    .pbmh-precio-fila { display:flex; justify-content:space-between; font-size:13px; color:#3A3F47; }
    .pbmh-precio-valor { font-weight:500; color:#1F2430; }
    .pbmh-actions { margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:14px; }
    .pbmh-stepper { display:inline-flex; align-items:center; border:1px solid #D9DDE3; border-radius:8px;
        overflow:hidden; background:#fff; }
    .pbmh-step { width:30px; height:32px; border:none; background:none; font-size:16px; color:#3A3F47;
        cursor:pointer; line-height:1; }
    .pbmh-step:hover { background:#F3F5F7; }
    .pbmh-qty { min-width:30px; text-align:center; font-size:13px; font-weight:600; color:#1F2430; }
    .pbmh-cart-btn { display:inline-flex; align-items:center; gap:9px; border:1.5px solid #0098DA;
        color:#0098DA; background:#fff; border-radius:8px; padding:9px 22px; font-size:12px;
        font-weight:600; letter-spacing:.05em; cursor:pointer; transition:all .15s; }
    .pbmh-cart-btn:hover { background:#0098DA; color:#fff; }
    .pbmh-cart-btn:hover svg path { fill:#fff; }
    .pbmh-tabs { display:flex; gap:12px; flex-wrap:wrap; padding:16px 24px; border-top:1px solid #EDEFF2; }
    .pbmh-tab { border:1.5px solid #E2E6EB; background:#F6F8FA; color:#1F2430; cursor:pointer;
        font-family:inherit; font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
        display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:10px;
        transition:background .18s, color .18s, border-color .18s, box-shadow .18s, transform .12s; }
    .pbmh-tab:hover { border-color:#0098DA; color:#0098DA; background:#fff; }
    .pbmh-tab:active { transform:translateY(1px); }
    .pbmh-tab.activa { background:#0098DA; border-color:#0098DA; color:#fff;
        box-shadow:0 8px 18px rgba(0,152,218,.22); }
    .pbmh-caret { font-size:10px; transition:transform .2s ease; }
    .pbmh-tab.activa .pbmh-caret { transform:rotate(180deg); }

    /* Despliegue animado (max-height + opacidad; el contenido queda oculto cuando está cerrado) */
    .pbmh-panel { overflow:hidden; max-height:0; opacity:0; transition:max-height .34s ease, opacity .26s ease; }
    .pbmh-panel.abierto { opacity:1; }
    .pbmh-panel-inner { overflow-x:auto; padding:0 24px 22px;
        scrollbar-width:thin; scrollbar-color:#E8EBEF transparent; }
    .pbmh-panel-inner::-webkit-scrollbar { height:3px; }
    .pbmh-panel-inner::-webkit-scrollbar-track { background:transparent; }
    .pbmh-panel-inner::-webkit-scrollbar-thumb { background:#E8EBEF; border-radius:10px; }
    .pbmh-panel-inner::-webkit-scrollbar-thumb:hover { background:#D1D6DE; }
    .pbmh-tabla { width:100%; border-collapse:separate; border-spacing:0; }
    .pbmh-tabla thead tr { background:#111315; color:#fff; }
    .pbmh-tabla th { font-size:12px; font-weight:500; text-align:left; padding:11px 14px; }
    .pbmh-tabla thead th:first-child { border-top-left-radius:4px; }
    .pbmh-tabla thead th:last-child { border-top-right-radius:4px; }
    .pbmh-tabla td { padding:10px 14px; border-bottom:1px solid #F0F2F4; font-size:12px;
        color:#3A3F47; vertical-align:middle; }
    .pbmh-tabla tbody tr:hover { background:#FAFBFC; }
    .pbmh-col-img { width:64px; position:relative; }
    .pbmh-thumb { width:48px; height:44px; object-fit:contain; display:block; }
    .pbmh-celda-cod { font-weight:600; color:#1F2430; white-space:nowrap; }
    .pbmh-celda-desc { display:flex; flex-direction:column; gap:2px; min-width:180px; }
    .pbmh-celda-cod a { color:inherit; text-decoration:none; }
    .pbmh-celda-cod a:hover { color:#0098DA; }
    .pbmh-celda-desc a { display:flex; flex-direction:column; gap:2px; color:inherit; text-decoration:none; }
    .pbmh-celda-desc a:hover { color:#0098DA; }
    .pbmh-col-img a { display:block; }
    .pbmh-num { white-space:nowrap; }
    .pbmh-stepper-sm .pbmh-qty { min-width:24px; padding:0 2px 0 10px; font-size:13px; text-align:left; }
    .pbmh-steps { display:flex; flex-direction:column; padding:0 7px 0 2px; }
    .pbmh-stepper-sm .pbmh-step { width:16px; height:14px; display:flex; align-items:center; justify-content:center;
        border:none; background:none; padding:0; color:#5A6169; cursor:pointer; }
    .pbmh-stepper-sm .pbmh-step:hover { background:none; color:#0098DA; }
    .pbmh-stepper-sm .pbmh-step svg { display:block; }
    .pbmh-total { font-weight:600; color:#1F2430; }
    .pbmh-mini-cart { width:38px; height:38px; border-radius:10px; border:1.5px solid #0098DA;
        background:#fff; color:#0098DA; cursor:pointer; display:inline-flex; align-items:center;
        justify-content:center; gap:3px; padding:0; transition:all .15s; }
    .pbmh-mini-cart svg { display:block; flex-shrink:0; }
    .pbmh-mini-cart:hover { background:#0098DA; color:#fff; }
    .pbmh-consultar { display:inline-flex; align-items:center; justify-content:center; border-radius:10px;
        background:#0098DA; color:#fff; border:1px solid #0098DA; font-family:'Montserrat',sans-serif;
        font-size:13px; font-weight:600; letter-spacing:.02em; padding:9px 22px; text-decoration:none;
        transition:all .15s; cursor:pointer; white-space:nowrap; }
    .pbmh-consultar:hover { background:#fff; color:#0098DA; }
    .pbmh-consultar-sm { padding:7px 14px; font-size:11px; }
    .pbmh-vacio { font-size:12px; color:#9AA0A8; padding:14px 0 2px; margin:0; }
    @media (max-width: 991px) {
        .pbmh-top { flex-direction:column; }
        .pbmh-gallery { width:100%; flex-direction:column; }
        .pbmh-thumbs { flex-direction:row; width:100%; max-height:none; overflow-x:auto; overflow-y:hidden; padding-bottom:4px; padding-right:0; }
        .pbmh-thumb-btn { flex:0 0 90px; }
        .pbmh-imgbox { flex:none; width:100%; }
        .pbmh-imgbox img { width:100%; height:auto; max-height:420px; }
        .pbmh-tabs { gap:20px; flex-wrap:wrap; }
        .pbmh-panel-inner .pbmh-tabla { min-width:620px; }
    }
    /* Lupa zoom - preview flotante */
    #pbmh-zoom { position:fixed; display:none; width:460px; height:460px; background:#fff; border:1px solid #E7E9EC;
        border-radius:10px; box-shadow:0 14px 36px rgba(16,24,40,.16); z-index:1060; pointer-events:none;
        background-repeat:no-repeat; background-position:center; overflow:hidden; }
    .pbmh-imgbox { position:relative; overflow:visible; }
    .pbmh-lens { position:absolute; display:none; border:1px solid rgba(0,152,218,.35);
        background:rgba(0,152,218,.08); pointer-events:none; border-radius:6px; z-index:2; }
    @media (max-width: 991px) { #pbmh-zoom, .pbmh-lens { display:none !important; } }
</style>
@endonce

@once
<script>
(function () {
    // Lupa zoom para las imágenes de las cards (hover -> preview flotante al costado)
    if (!window.__pbmhZoomInit) {
        window.__pbmhZoomInit = true;
        var preview = document.createElement('div');
        preview.id = 'pbmh-zoom';
        document.body.appendChild(preview);
        var lens = document.createElement('div');
        lens.className = 'pbmh-lens';
        var activeImg = null, activeBox = null, zoom = 2.4;
        function showZoom(box, img) {
            if (window.innerWidth < 992) return;
            var src = img.currentSrc || img.src;
            if (!src || src.includes('WhatsApp-Image')) return;
            activeImg = img; activeBox = box;
            preview.style.backgroundImage = 'url("' + src.replace(/"/g, '&quot;') + '")';
            if (img.naturalWidth) {
                var cw0 = box.clientWidth || img.clientWidth || 48;
                var base = img.naturalWidth / cw0 * 1.35;
                var maxScale = cw0 < 120 ? 10 : (cw0 < 300 ? 6 : 4.2);
                var scale = Math.min(maxScale, Math.max(3.0, base));
                zoom = scale;
                preview.style.backgroundSize = (cw0 * scale) + 'px ' + (box.clientHeight * scale) + 'px';
            } else {
                zoom = 3.6;
                preview.style.backgroundSize = '360%';
            }
            if (!box.contains(lens)) box.appendChild(lens);
            lens.style.display = 'block';
            preview.style.display = 'block';
        }
        function hideZoom() {
            preview.style.display = 'none';
            lens.style.display = 'none';
            activeImg = null; activeBox = null;
        }
        function moveZoom(e) {
            if (!activeImg || !activeBox) return;
            var rect = activeBox.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var cw = rect.width, ch = rect.height;
            var pw = preview.offsetWidth || 460, ph = preview.offsetHeight || 460;
            var lensW = pw / zoom, lensH = ph / zoom;
            // Para miniaturas muy pequeñas el lens debe ser menor (más zoom útil) y caber en el contenedor
            var frac = cw < 120 ? 0.42 : (cw < 300 ? 0.5 : 0.58);
            lensW = Math.min(lensW, cw * frac);
            lensH = Math.min(lensH, ch * frac);
            lensW = Math.max(lensW, 22);
            lensH = Math.max(lensH, 22);
            lens.style.width = lensW + 'px';
            lens.style.height = lensH + 'px';
            var lx = x - lensW / 2, ly = y - lensH / 2;
            lx = Math.max(0, Math.min(lx, cw - lensW));
            ly = Math.max(0, Math.min(ly, ch - lensH));
            lens.style.left = lx + 'px';
            lens.style.top = ly + 'px';
            var xPct = (cw - lensW) > 0 ? (lx / (cw - lensW)) * 100 : (x / cw) * 100;
            var yPct = (ch - lensH) > 0 ? (ly / (ch - lensH)) * 100 : (y / ch) * 100;
            preview.style.backgroundPosition = xPct + '% ' + yPct + '%';
            var left = rect.right + 32;
            var top = rect.top + (ch / 2) - (ph / 2);
            if (left + pw > window.innerWidth - 12) left = rect.left - pw - 32;
            top = Math.max(12, Math.min(top, window.innerHeight - ph - 12));
            preview.style.left = left + 'px';
            preview.style.top = top + 'px';
        }
        function initZoomBoxes() {
            document.querySelectorAll('.pbmh-imgbox').forEach(function (box) {
                if (box.dataset.pbmhZoomAttached) return;
                box.dataset.pbmhZoomAttached = '1';
                var img = box.querySelector('img');
                if (!img) return;
                box.addEventListener('mouseenter', function () { showZoom(box, img); });
                box.addEventListener('mousemove', moveZoom);
                box.addEventListener('mouseleave', hideZoom);
            });
            // También las miniaturas de Partes relacionadas (48px) — mismo zoom preparado para imagen pequeña
            document.querySelectorAll('.pbmh-thumb').forEach(function (img) {
                var cell = img.closest('.pbmh-col-img') || img.closest('td') || img.parentElement;
                if (!cell || cell.dataset.pbmhZoomAttachedThumb) return;
                cell.dataset.pbmhZoomAttachedThumb = '1';
                cell.style.position = 'relative';
                cell.addEventListener('mouseenter', function () { showZoom(cell, img); });
                cell.addEventListener('mousemove', moveZoom);
                cell.addEventListener('mouseleave', hideZoom);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initZoomBoxes);
        } else {
            initZoomBoxes();
        }
        // Por si las filas de partes se abren después (panel oculto al inicio), reintentar al abrir tabs
        document.addEventListener('click', function (e) {
            if (e.target.closest('.pbmh-tab')) setTimeout(initZoomBoxes, 80);
        });
    }
})();
</script>
@endonce

@once
<script>
(function () {
    // Delegación global: una sola suscripción para todas las cards.
    if (window.__pbmhDelegado) return;
    window.__pbmhDelegado = true;

    function formatear(n) {
        return '$' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function toastOk() {
        if (window.iziToast) {
            iziToast.success({
                title: 'Producto agregado al carrito',
                backgroundColor: '#DAF6D3', titleColor: '#479831', iconColor: '#479831',
                progressBar: false, position: 'bottomRight', timeout: 2500,
            });
        }
    }

    document.addEventListener('click', function (ev) {
        var card = ev.target.closest('[data-pbmh]');
        if (!card) return;

        // ---- Galería: cambiar imagen principal desde las previews (thumbs verticales) ----
        var thumbBtn = ev.target.closest('.pbmh-thumb-btn');
        if (thumbBtn) {
            var gallery = thumbBtn.closest('.pbmh-gallery');
            var mainImg = gallery ? gallery.querySelector('.pbmh-imgbox img') : null;
            if (mainImg && thumbBtn.dataset.src) {
                // Evitar recargar si ya es la activa
                if (mainImg.getAttribute('src') !== thumbBtn.dataset.src && mainImg.src !== thumbBtn.dataset.src) {
                    mainImg.src = thumbBtn.dataset.src;
                }
                gallery.querySelectorAll('.pbmh-thumb-btn').forEach(function (b) { b.classList.remove('activo'); });
                thumbBtn.classList.add('activo');
            }
            return;
        }

        // ---- Steppers (+/-) ----
        var stepBtn = ev.target.closest('[data-step]');
        if (stepBtn) {
            var qtyEl = stepBtn.closest('.pbmh-stepper').querySelector('[data-qty]');
            var valor = Math.max(1, parseInt(qtyEl.textContent, 10) + parseInt(stepBtn.dataset.step, 10));
            qtyEl.textContent = valor;

            // Si la fila tiene total, recalcularlo.
            var fila = stepBtn.closest('tr');
            if (fila && fila.dataset.precio) {
                fila.querySelector('[data-total]').textContent = formatear(valor * parseFloat(fila.dataset.precio));
            }
            return;
        }

        // ---- Tabs desplegables (uno abierto a la vez, animado) ----
        var tab = ev.target.closest('.pbmh-tab');
        if (tab) {
            var nombre = tab.dataset.tab;
            var abrir = !tab.classList.contains('activa');
            // Cerrar el panel que esté abierto (animado).
            card.querySelectorAll('.pbmh-panel').forEach(function (p) {
                if (p.classList.contains('abierto')) {
                    p.style.maxHeight = p.scrollHeight + 'px';
                    void p.offsetHeight; // fuerza reflow para que la transición a 0 se vea
                    p.classList.remove('abierto');
                    p.style.maxHeight = '0px';
                }
            });
            card.querySelectorAll('.pbmh-tab').forEach(function (t) { t.classList.remove('activa'); });
            if (abrir) {
                tab.classList.add('activa');
                var panel = card.querySelector('[data-panel="' + nombre + '"]');
                panel.style.maxHeight = panel.scrollHeight + 'px';
                panel.classList.add('abierto');
                panel.addEventListener('transitionend', function te(e) {
                    if (e.propertyName === 'max-height' && panel.classList.contains('abierto')) {
                        panel.style.maxHeight = 'none';
                    }
                }, { once: true });
            }
            return;
        }

        // ---- Sumar al carrito (card principal y filas de partes) ----
        var addBtn = ev.target.closest('[data-add]');
        if (addBtn) {
            var grupo = addBtn.closest('.pbmh-actions') || addBtn.closest('tr');
            var qty = grupo ? parseInt(grupo.querySelector('[data-qty]').textContent, 10) || 1 : 1;
            var body = new URLSearchParams();
            body.append('producto_id', addBtn.dataset.productoId);
            body.append('precio', addBtn.dataset.precio);
            body.append('qty', qty);

            fetch(card.dataset.aggUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': card.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            })
                .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(function () { toastOk(); })
                .catch(function (e) { console.error('carrito', e); });
        }
    });
})();
</script>
@endonce
