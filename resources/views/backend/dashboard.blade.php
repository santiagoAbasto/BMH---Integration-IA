@extends('layouts.plantilla-back')

{{-- Inicio del panel (como Conelec y Strand, orientado a BMH). Datos: App\Support\Panel\MetricasPanel; rango,
     indicadores, gráfico, tabla y rankings: resources/js/admin/panel.js. --}}
@php
    $admin = Auth::guard('admin')->user();
    $ahora = now(\App\Support\Panel\MetricasPanel::ZONA)->locale('es');
    $hora = (int) $ahora->format('G');
    $saludo = $hora < 12 ? 'Buen día' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
    $rango = $actividad['rango'];
    $suma = fn (array $diario, int $dias, int $corrimiento = 0) => array_sum(array_slice($diario, count($diario) - $dias - $corrimiento, $dias));
    $numero = fn ($n) => number_format((float) $n, 0, ',', '.');
    $pesos = fn ($n) => '$ '.number_format((float) $n, 0, ',', '.');
    $faltan = collect($estado)->reject(fn (array $item): bool => $item['ok'])->count();
    $colorPedidos = collect($actividad['series'])->firstWhere('key', 'pedidos')['color'];
@endphp

@section('content')
<div class="panel" data-panel>
    {{-- Saludo y accesos rápidos --}}
    <header class="panel-hero">
        <div class="panel-hero__texto">
            <p class="panel-hero__fecha">{{ ucfirst($ahora->isoFormat('dddd D [de] MMMM')) }}</p>
            <h1><span data-saludo>{{ $saludo }}</span>, {{ \Illuminate\Support\Str::before(trim((string) $admin?->name), ' ') ?: $admin?->name }}.</h1>
            <p>Esto es lo que pasa en BMH Distribuidora.</p>
        </div>
        <nav class="panel-hero__acciones" aria-label="Accesos rápidos">
            <a class="panel-hero__accion" href="{{ route('producto.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Producto</a>
            <a class="panel-hero__accion" href="{{ route('ventas') }}"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Pedidos</a>
            <a class="panel-hero__accion" href="{{ route('dashboard.productos', ['oferta' => 1]) }}"><i class="fa-solid fa-tag" aria-hidden="true"></i> Ofertas</a>
            <a class="panel-hero__accion" href="{{ route('novedad.crear') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Novedad</a>
            <a class="panel-hero__accion panel-hero__accion--fantasma" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver sitio<span class="visually-hidden"> (abre en otra pestaña)</span></a>
        </nav>
    </header>

    {{-- Lo que espera (no depende del rango) --}}
    @if ($pendientes !== [])
        <section class="panel-atencion" aria-label="Pendientes">
            <span class="panel-atencion__icono" aria-hidden="true"><i class="fa-regular fa-bell"></i></span>
            <p><strong>Te esperan</strong></p>
            <ul role="list">
                @foreach ($pendientes as $item)
                    <li><a href="{{ $item['url'] }}"><b>{{ $item['count'] }}</b> {{ $item['count'] === 1 ? $item['one'] : $item['label'] }}</a></li>
                @endforeach
            </ul>
        </section>
    @else
        <p class="panel-atencion panel-atencion--al-dia"><span class="panel-atencion__icono" aria-hidden="true"><i class="fa-solid fa-check"></i></span><span><strong>Todo al día.</strong> No hay pedidos, consultas ni clientes nuevos sin ver.</span></p>
    @endif

    {{-- Actividad: todo lo de esta sección responde al rango elegido --}}
    <section class="panel-region" aria-labelledby="panel-actividad">
        <div class="panel-region__cabeza">
            <div><p class="panel-eyebrow">Actividad</p><h2 id="panel-actividad">Lo que llega desde el sitio</h2></div>
            <div class="panel-rango" role="group" aria-label="Rango de fechas">
                @foreach ($actividad['rangos'] as $dias)
                    <button type="button" data-rango="{{ $dias }}" aria-pressed="{{ $dias === $rango ? 'true' : 'false' }}">{{ $dias }} días</button>
                @endforeach
            </div>
        </div>
        <p class="panel-rango__leyenda" data-rango-leyenda>Últimos {{ $rango }} días, comparados con los {{ $rango }} anteriores.</p>

        <div class="panel-kpis">
            @php
                $facturacion = $suma($actividad['facturacion'], $rango);
                $facturacionAntes = $suma($actividad['facturacion'], $rango, $rango);
            @endphp
            <a class="panel-kpi panel-kpi--plata" href="{{ route('ventas') }}" data-kpi="facturacion" style="--kpi-color: #0a0e14">
                <span class="panel-kpi__etiqueta">Facturación</span>
                <span class="panel-kpi__valor" data-kpi-valor>{{ $pesos($facturacion) }}</span>
                <span @class(['panel-kpi__delta', 'is-sube' => $facturacion > $facturacionAntes, 'is-baja' => $facturacion < $facturacionAntes]) data-kpi-delta>&nbsp;</span>
                <svg class="panel-kpi__mini" data-kpi-mini viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true" focusable="false"></svg>
            </a>
            @foreach ($actividad['series'] as $serie)
                @php
                    $total = $suma($serie['daily'], $rango);
                @endphp
                <a class="panel-kpi" href="{{ $serie['url'] }}" data-kpi="{{ $serie['key'] }}" style="--kpi-color: {{ $serie['color'] }}">
                    <span class="panel-kpi__etiqueta">{{ $serie['label'] }}</span>
                    <span class="panel-kpi__valor" data-kpi-valor>{{ $numero($total) }}</span>
                    <span class="panel-kpi__delta" data-kpi-delta>&nbsp;</span>
                    <svg class="panel-kpi__mini" data-kpi-mini viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true" focusable="false"></svg>
                </a>
            @endforeach
        </div>

        <div class="panel-actividad">
            <article class="panel-card panel-card--grafico">
                <header class="panel-card__cabeza">
                    <div><h3>Entradas por día</h3><p>Pedidos, clientes que se registran y consultas del formulario.</p></div>
                    <div class="panel-vista" role="group" aria-label="Ver como">
                        <button type="button" data-vista="grafico" aria-pressed="true">Gráfico</button>
                        <button type="button" data-vista="tabla" aria-pressed="false">Tabla</button>
                    </div>
                </header>
                <ul class="panel-leyenda" role="list">
                    @foreach ($actividad['series'] as $serie)
                        <li><span class="panel-leyenda__color" style="background: {{ $serie['color'] }}"></span>{{ $serie['short'] }}</li>
                    @endforeach
                </ul>
                <div class="panel-grafico" data-grafico>
                    <svg class="panel-grafico__svg" data-grafico-svg role="img" aria-label="Entradas por día"></svg>
                    <div class="panel-tooltip" data-grafico-tooltip role="status" hidden></div>
                </div>
                <div class="panel-tabla" data-grafico-tabla hidden></div>
            </article>

            <div class="panel-actividad__lado">
                <article class="panel-card">
                    <header class="panel-card__cabeza"><div><h3>Lo más pedido</h3><p>Productos con más unidades pedidas en el período.</p></div></header>
                    <div data-barras="productos" data-barras-color="{{ $colorPedidos }}" data-barras-vacio="Sin pedidos en este período."></div>
                </article>
                <article class="panel-card">
                    <header class="panel-card__cabeza"><div><h3>Líneas más pedidas</h3><p>Unidades pedidas por categoría.</p></div></header>
                    <div data-barras="categorias" data-barras-color="#1baf7a" data-barras-vacio="Sin pedidos en este período."></div>
                </article>
            </div>
        </div>
    </section>

    {{-- Tu sitio: no depende del rango --}}
    <section class="panel-region" aria-labelledby="panel-sitio">
        <div class="panel-region__cabeza"><div><p class="panel-eyebrow">Tu sitio</p><h2 id="panel-sitio">De un vistazo</h2></div></div>
        <div class="panel-grilla">
            <article class="panel-card">
                <header class="panel-card__cabeza">
                    <div><h3>Últimos pedidos</h3><p>Los nuevos, sin abrir, están marcados.</p></div>
                    <a class="panel-link" href="{{ route('ventas') }}">Ver todos</a>
                </header>
                @forelse ($pedidos as $pedido)
                    <a @class(['panel-fila', 'is-nuevo' => $pedido['nuevo']]) href="{{ $pedido['url'] }}">
                        <span class="panel-fila__avatar" aria-hidden="true">#{{ $pedido['id'] }}</span>
                        <span class="panel-fila__cuerpo">
                            <span class="panel-fila__arriba"><strong>{{ $pedido['nombre'] }}</strong><b class="panel-fila__importe">{{ $pesos($pedido['total']) }}</b></span>
                            <span class="panel-fila__abajo">
                                {{ $pedido['estado'] }} · {{ $pedido['pago'] }}
                                @if ($pedido['nuevo'])<span class="panel-fila__nuevo">Nuevo</span>@endif
                                @if ($pedido['fecha'])<time datetime="{{ $pedido['fecha']->toIso8601String() }}">{{ $pedido['fecha']->locale('es')->diffForHumans(short: true) }}</time>@endif
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="panel-vacio">Todavía no hay pedidos.</p>
                @endforelse
            </article>

            <article class="panel-card">
                <header class="panel-card__cabeza">
                    <div><h3>Consultas recientes</h3><p>Lo último que llegó por el formulario de contacto.</p></div>
                    <a class="panel-link" href="{{ route('dashboard.contacto.mensajes') }}">Bandeja</a>
                </header>
                @forelse ($consultas as $consulta)
                    <a @class(['panel-fila', 'is-nuevo' => $consulta['nuevo']]) href="{{ $consulta['url'] }}">
                        <span class="panel-fila__avatar panel-fila__avatar--consulta" aria-hidden="true">{{ mb_strtoupper(mb_substr($consulta['nombre'] ?: '?', 0, 1)) }}</span>
                        <span class="panel-fila__cuerpo">
                            <span class="panel-fila__arriba"><strong>{{ $consulta['nombre'] }}</strong>@if ($consulta['fecha'])<time datetime="{{ $consulta['fecha']->toIso8601String() }}">{{ $consulta['fecha']->locale('es')->diffForHumans(short: true) }}</time>@endif</span>
                            <span class="panel-fila__abajo">{{ $consulta['detalle'] }}@if ($consulta['nuevo'])<span class="panel-fila__nuevo">Sin leer</span>@endif</span>
                        </span>
                    </a>
                @empty
                    <p class="panel-vacio">Todavía no llegaron consultas desde el sitio.</p>
                @endforelse
            </article>

            <div class="panel-grilla__col">
                <article class="panel-card">
                    <header class="panel-card__cabeza">
                        <div><h3>Estado del sitio</h3><p>Lo que conviene tener completo.</p></div>
                        <span @class(['adm-chip', 'adm-chip--ok' => $faltan === 0, 'adm-chip--alerta' => $faltan > 0])>{{ $faltan === 0 ? 'Completo' : ($faltan === 1 ? '1 pendiente' : $faltan.' pendientes') }}</span>
                    </header>
                    <ul class="panel-checks" role="list">
                        @foreach ($estado as $check)
                            <li @class(['is-ok' => $check['ok']])>
                                <a href="{{ $check['url'] }}">
                                    <span class="panel-checks__marca" aria-hidden="true">{{ $check['ok'] ? '✓' : '!' }}</span>
                                    <span><strong>{{ $check['label'] }}<span class="visually-hidden">: {{ $check['ok'] ? 'listo' : 'pendiente' }}</span></strong><small>{{ $check['detail'] }}</small></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </article>

                <article class="panel-card">
                    <header class="panel-card__cabeza"><div><h3>Catálogo</h3><p>Lo cargado en el sitio.</p></div></header>
                    <ul class="panel-cuentas" role="list">
                        @foreach ($catalogo as $cuenta)
                            <li><a href="{{ $cuenta['url'] }}"><span>{{ $cuenta['label'] }}</span><b>{{ $numero($cuenta['count']) }}</b></a></li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <script type="application/json" data-panel-datos>@json($actividad)</script>
</div>
@endsection
