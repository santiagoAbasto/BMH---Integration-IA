@extends('layouts.plantilla-back')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/biblioteca-imagenes.css') }}?v=1">
@endsection

@php
    $resumen = $reporte['resumen'];
    $aplicado = $resultado !== null;
    $hayCambios = count($reporte['cambios']) > 0;
    $iconos = ['error' => 'fa-circle-xmark', 'advertencia' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'];
    $nombres = ['error' => 'Error', 'advertencia' => 'Advertencia', 'info' => 'Información'];
    $precio = fn ($valor) => $valor === null ? '—' : '$ '.number_format((float) $valor, 2, ',', '.');
@endphp

@section('content')
<div class="bi">
    <a class="bi-volver" href="{{ route('dashboard.productos.excel.importar') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Subir otro Excel</a>

    <header class="bi-header">
        <div>
            <p class="bi-eyebrow">Importar Excel de productos</p>
            <h1 class="bi-title">{{ $aplicado ? 'Cambios aplicados' : 'Revisá antes de aplicar' }}</h1>
            <p class="bi-subtitle"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> {{ $reporte['archivo'] }} · {{ number_format($reporte['filas_leidas'], 0, ',', '.') }} filas leídas</p>
        </div>
    </header>

    <ol class="bi-pasos" aria-label="Pasos para importar">
        <li class="bi-paso is-hecho"><div><strong>Descargá el Excel</strong><span>Con los datos actuales.</span></div></li>
        <li class="bi-paso is-hecho"><div><strong>Completá Precio e Imágenes</strong><span>Con los códigos de la biblioteca.</span></div></li>
        <li class="bi-paso {{ $aplicado ? 'is-hecho' : 'is-actual' }}"><div><strong>{{ $aplicado ? 'Aplicado' : 'Revisá y confirmá' }}</strong><span>{{ $aplicado ? 'Los cambios ya están en la web.' : 'Todavía no se cambió nada.' }}</span></div></li>
    </ol>

    {{-- Estado general --}}
    @if ($reporte['fatal'])
        <div class="bi-cartel bi-cartel-error" role="alert">
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
            <div><strong>No se puede importar este archivo</strong>{{ $reporte['fatal'] }}
                <div style="margin-top: 10px"><a class="bi-btn bi-btn-chico" href="{{ route('dashboard.productos.excel.importar') }}">Subir otro archivo</a></div>
            </div>
        </div>
    @elseif ($aplicado)
        <div class="bi-cartel bi-cartel-exito" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <div><strong>Listo: los cambios ya están en la web ({{ $resultado['fecha'] }})</strong>
                Se actualizaron {{ $resultado['precios'] }} {{ $resultado['precios'] === 1 ? 'precio' : 'precios' }} y las imágenes de {{ $resultado['imagenes'] }} {{ $resultado['imagenes'] === 1 ? 'producto' : 'productos' }}.
                @if ($resumen['errores'] > 0) Las {{ $resumen['errores'] }} filas con error no se aplicaron: corregilas y volvé a subir el Excel. @endif
            </div>
        </div>
    @elseif (! $hayCambios)
        <div class="bi-cartel bi-cartel-info" role="status">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div><strong>No hay nada para cambiar</strong>Los precios y las imágenes del Excel ya son iguales a los de la web{{ $resumen['errores'] ? ', o las filas tienen errores (abajo están los detalles)' : '' }}.</div>
        </div>
    @else
        <div class="bi-cartel bi-cartel-info" role="status">
            <i class="fa-solid fa-eye" aria-hidden="true"></i>
            <div><strong>Vista previa: todavía no se cambió nada</strong>
                Revisá los cambios y los avisos. Cuando esté bien, tocá <strong>«Aplicar cambios»</strong> al final de la página.
                @if ($resumen['errores'] > 0) Las filas con error se saltean: el resto se aplica igual. @endif
            </div>
        </div>
    @endif

    @unless ($reporte['fatal'])
        {{-- Resumen --}}
        <div class="bi-resumen">
            <div class="bi-numero is-exito"><b>{{ number_format($resumen['productos'], 0, ',', '.') }}</b><span>productos {{ $aplicado ? 'actualizados' : 'con cambios' }}</span></div>
            <div class="bi-numero"><b>{{ number_format($resumen['precios'], 0, ',', '.') }}</b><span>precios {{ $aplicado ? 'cambiados' : 'a cambiar' }}</span></div>
            <div class="bi-numero"><b>{{ number_format($resumen['imagenes'], 0, ',', '.') }}</b><span>productos con imágenes {{ $aplicado ? 'cambiadas' : 'a cambiar' }}</span></div>
            <div class="bi-numero {{ $resumen['errores'] ? 'is-error' : '' }}"><b>{{ $resumen['errores'] }}</b><span>{{ $resumen['errores'] === 1 ? 'fila con error' : 'filas con error' }} (no se aplican)</span></div>
            <div class="bi-numero {{ $resumen['advertencias'] ? 'is-advertencia' : '' }}"><b>{{ $resumen['advertencias'] }}</b><span>advertencias</span></div>
            <div class="bi-numero"><b>{{ number_format($resumen['sin_cambios'], 0, ',', '.') }}</b><span>productos sin cambios</span></div>
        </div>

        <details class="bi-detalles">
            <summary>Recordá cómo se aplican los cambios</summary>
            <div>
                <table class="bi-reglas-tabla">
                    <tr><th scope="row"><span class="bi-chip-columna">Precio</span></th><td><ul><li>Vacío: no se cambia. Un número: reemplaza el precio.</li></ul></td></tr>
                    <tr><th scope="row"><span class="bi-chip-columna is-imagenes">Imágenes</span></th><td><ul>
                        <li>Los códigos <strong>reemplazan todas las fotos</strong> del producto; la <strong>primera es la principal</strong>.</li>
                        <li>Vacío: <strong>no se tocan</strong>. Un guion (-): <strong>se sacan todas</strong>.</li>
                        <li>Si un código no existe, las fotos de ese producto no cambian. Los archivos <strong>nunca se borran</strong>.</li>
                    </ul></td></tr>
                    <tr><th scope="row"><span class="bi-chip-columna">Nombre</span> <span class="bi-chip-columna">Categoría</span></th><td><ul><li>Son de referencia: no se modifican.</li></ul></td></tr>
                </table>
            </div>
        </details>

        {{-- Avisos por fila --}}
        @if ($reporte['avisos'])
            <section class="bi-card" aria-labelledby="titulo-avisos">
                <div class="bi-card-cuerpo">
                    <h2 class="bi-card-titulo" id="titulo-avisos">Avisos por fila</h2>
                    <p class="bi-card-sub" style="margin-bottom: 12px">
                        <span style="color:#B42318"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Error</span>: esa fila (o esa columna) no se aplica.
                        <span style="color:#8A5A00; margin-left: 10px"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Advertencia</span>: revisalo, puede no ser lo que querías.
                        <span style="color:#00699A; margin-left: 10px"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Información</span>: sólo para que lo sepas.
                    </p>
                    <div class="bi-filtro-avisos" role="group" aria-label="Filtrar avisos">
                        <button type="button" class="bi-btn bi-btn-chico" data-filtro-aviso="todos" aria-pressed="true">Todos ({{ count($reporte['avisos']) }})</button>
                        @foreach (['error' => $resumen['errores'], 'advertencia' => $resumen['advertencias'], 'info' => $resumen['infos']] as $nivel => $cantidad)
                            @if ($cantidad)
                                <button type="button" class="bi-btn bi-btn-chico" data-filtro-aviso="{{ $nivel }}" aria-pressed="false"><i class="fa-solid {{ $iconos[$nivel] }}" aria-hidden="true"></i> {{ $nombres[$nivel] }}{{ $cantidad === 1 ? '' : ($nivel === 'error' ? 'es' : 's') }} ({{ $cantidad }})</button>
                            @endif
                        @endforeach
                    </div>
                    <ul class="bi-avisos" id="lista-avisos">
                        @foreach ($reporte['avisos'] as $aviso)
                            <li class="bi-aviso is-{{ $aviso['nivel'] }}" data-nivel="{{ $aviso['nivel'] }}">
                                <i class="fa-solid {{ $iconos[$aviso['nivel']] }}" aria-label="{{ $nombres[$aviso['nivel']] }}"></i>
                                <div class="bi-aviso-donde"><b>Fila {{ $aviso['fila'] }}</b>{{ $aviso['codigo'] }}@if ($aviso['columna']) · {{ $aviso['columna'] }}@endif</div>
                                <div>{{ $aviso['mensaje'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        {{-- Cambios --}}
        @if ($hayCambios)
            <section class="bi-card" aria-labelledby="titulo-cambios">
                <div class="bi-card-cuerpo">
                    <h2 class="bi-card-titulo" id="titulo-cambios">{{ $aplicado ? 'Lo que cambió' : 'Lo que va a cambiar' }} ({{ count($reporte['cambios']) }} {{ count($reporte['cambios']) === 1 ? 'producto' : 'productos' }})</h2>
                    <p class="bi-card-sub" style="margin-bottom: 12px">Sólo aparecen los productos que cambian. En las imágenes, lo tachado/gris es lo de antes y lo verde lo nuevo.</p>
                    <div class="bi-tabla-cont">
                        <table class="bi-tabla">
                            <thead>
                                <tr><th class="bi-col-fila">Fila</th><th class="bi-col-producto">Producto</th><th class="bi-col-precio">Precio</th><th>Imágenes</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($reporte['cambios'] as $cambio)
                                    <tr>
                                        <td class="bi-col-fila">{{ $cambio['fila'] }}</td>
                                        <td class="bi-producto"><b>{{ $cambio['codigo'] }}</b><span>{{ \Illuminate\Support\Str::limit($cambio['nombre'], 70) }}</span></td>
                                        <td>
                                            @if ($cambio['precio'])
                                                <span class="bi-antes">{{ $precio($cambio['precio']['antes']) }}</span><span class="bi-flecha" aria-hidden="true">→</span><span class="bi-despues">{{ $precio($cambio['precio']['despues']) }}</span>
                                            @else
                                                <span style="color:#8A96A0">Sin cambios</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($cambio['imagenes'])
                                                <div class="bi-fotos">
                                                    <div class="bi-fotos-grupo is-antes" aria-label="Antes">
                                                        @forelse ($cambio['imagenes']['antes'] as $i => $codigo)
                                                            <span class="bi-mini {{ $i === 0 ? 'bi-principal' : '' }}">@if ($urls[$codigo] ?? '')<img src="{{ $urls[$codigo] }}" alt="" loading="lazy">@endif<span title="{{ $codigo }}">{{ $codigo }}</span></span>
                                                        @empty
                                                            <span style="color:#8A96A0; font-size: 12.5px; padding: 6px">Sin fotos</span>
                                                        @endforelse
                                                    </div>
                                                    <span class="bi-flecha" aria-hidden="true">→</span>
                                                    @if ($cambio['imagenes']['quitar'])
                                                        <span class="bi-sin-fotos"><i class="fa-solid fa-ban" aria-hidden="true"></i> Se quitan todas las fotos</span>
                                                    @else
                                                        <div class="bi-fotos-grupo is-despues" aria-label="Después">
                                                            @foreach ($cambio['imagenes']['despues'] as $i => $codigo)
                                                                <span class="bi-mini {{ $i === 0 ? 'bi-principal' : '' }}">@if ($urls[$codigo] ?? '')<img src="{{ $urls[$codigo] }}" alt="" loading="lazy">@endif<span title="{{ $codigo }}">{{ $codigo }}</span></span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span style="color:#8A96A0">Sin cambios</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        {{-- Confirmar --}}
        @if (! $aplicado && $hayCambios)
            <form method="POST" action="{{ route('dashboard.productos.excel.confirmar', $token) }}" class="bi-confirmacion" id="form-confirmar">
                @csrf
                <p>
                    Se van a actualizar <strong>{{ $resumen['productos'] }} {{ $resumen['productos'] === 1 ? 'producto' : 'productos' }}</strong>
                    ({{ $resumen['precios'] }} {{ $resumen['precios'] === 1 ? 'precio' : 'precios' }}, imágenes de {{ $resumen['imagenes'] }}).
                    @if ($resumen['errores']) Las {{ $resumen['errores'] }} filas con error se saltean. @endif
                </p>
                <div class="bi-actions">
                    <a class="bi-btn" href="{{ route('dashboard.productos.excel.importar') }}">Cancelar</a>
                    <button type="submit" class="bi-btn bi-btn-exito" id="boton-aplicar"><i class="fa-solid fa-check" aria-hidden="true"></i> Aplicar cambios</button>
                </div>
            </form>
        @elseif ($aplicado)
            <div class="bi-actions" style="margin-top: 18px">
                <a class="bi-btn" href="{{ route('dashboard.productos') }}">Ir a Productos</a>
                <a class="bi-btn" href="{{ route('dashboard.imagenes') }}">Biblioteca de imágenes</a>
                <a class="bi-btn bi-btn-primario" href="{{ route('dashboard.productos.excel.importar') }}">Importar otro Excel</a>
            </div>
        @endif
    @endunless
</div>
@endsection

@section('script')
<script>
    (function () {
        var botones = document.querySelectorAll('[data-filtro-aviso]');
        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var nivel = boton.dataset.filtroAviso;
                botones.forEach(function (b) { b.setAttribute('aria-pressed', b === boton ? 'true' : 'false'); b.classList.toggle('bi-btn-primario', b === boton); });
                document.querySelectorAll('#lista-avisos [data-nivel]').forEach(function (item) {
                    item.hidden = nivel !== 'todos' && item.dataset.nivel !== nivel;
                });
            });
        });
        if (botones[0]) botones[0].classList.add('bi-btn-primario');

        var form = document.getElementById('form-confirmar');
        if (form) {
            form.addEventListener('submit', function () {
                var boton = document.getElementById('boton-aplicar');
                boton.disabled = true;
                boton.innerHTML = '<span class="bi-girando" aria-hidden="true"></span> Aplicando…';
            });
        }
    })();
</script>
@endsection
