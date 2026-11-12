@extends('layouts.plantilla-back')

@php
    // Resumen para el inicio del panel: solo lecturas (conteos y los últimos pedidos).
    $admin = Auth::guard('admin')->user();
    $desde = \Carbon\Carbon::now()->subDay();
    $pedidosRecientes = \App\Models\Pedido::where('created_at', '>=', $desde)->count();
    $pedidosDelMes = \App\Models\Pedido::where('created_at', '>=', now()->startOfMonth())->count();
    $totalPedidos = \App\Models\Pedido::count();
    $totalProductos = \App\Models\Producto::count();
    $productosOferta = \App\Models\Producto::where('oferta', true)->count();
    $totalClientes = \App\Models\User::where('rol', 'cliente')->count();
    $ultimosPedidos = \App\Models\Pedido::orderByDesc('id')->take(6)->get(['id', 'nombre', 'fecha', 'estado', 'estado_orden', 'total_pedido']);
    $numero = fn (int $n) => number_format($n, 0, ',', '.');
    $hora = (int) now()->format('G');
    $saludo = $hora < 13 ? 'Buen día' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
@endphp

@section('content')

<x-osole::admin-page-header.v1
    eyebrow="Panel de BMH"
    :title="$saludo.($admin?->name ? ', '.$admin->name : '')"
    description="Un resumen de lo que pasa en el sitio y accesos directos a lo que más se usa."
>
    <x-slot:actions>
        <a href="{{ route('ventas') }}" class="btn btn-secondary"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Ver pedidos</a>
        <a href="{{ route('producto.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Crear producto</a>
    </x-slot:actions>
</x-osole::admin-page-header.v1>

<div class="adm-pila">
    <x-osole::admin-stats.v1
        label="Resumen del sitio"
        :items="[
            ['label' => 'Pedidos nuevos', 'value' => $numero($pedidosRecientes), 'hint' => $pedidosRecientes ? 'En las últimas 24 horas' : 'Ninguno en las últimas 24 horas', 'href' => route('ventas'), 'icon' => 'cart', 'status' => $pedidosRecientes ? 'success' : null],
            ['label' => 'Pedidos de '.now()->locale('es')->translatedFormat('F'), 'value' => $numero($pedidosDelMes), 'hint' => $numero($totalPedidos).' en total', 'href' => route('ventas'), 'icon' => 'chart'],
            ['label' => 'Productos', 'value' => $numero($totalProductos), 'hint' => $productosOferta ? $numero($productosOferta).' en oferta' : 'Ninguno en oferta', 'href' => route('dashboard.productos'), 'icon' => 'box'],
            ['label' => 'Clientes', 'value' => $numero($totalClientes), 'hint' => 'Con acceso a la Zona de Clientes', 'href' => route('dashboard.clientes'), 'icon' => 'users'],
        ]"
    />

    <div class="adm-inicio">
        <section class="card adm-inicio__pedidos" aria-labelledby="inicio-pedidos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="inicio-pedidos">Últimos pedidos</h2>
                    <p>Los más nuevos primero.</p>
                </div>
                <a href="{{ route('ventas') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            @if ($ultimosPedidos->isEmpty())
                <div class="adm-vacio">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <strong>Todavía no hay pedidos</strong>
                    <p>Cuando un cliente haga un pedido desde la web, aparece acá.</p>
                </div>
            @else
                <ul class="adm-inicio__lista" role="list">
                    @foreach ($ultimosPedidos as $pedido)
                        <li>
                            <a href="{{ route('pedido.datos', ['id' => $pedido->id]) }}" class="adm-inicio__pedido">
                                <span class="adm-inicio__numero">#{{ $pedido->id }}</span>
                                <span class="adm-inicio__cliente">
                                    <strong>{{ $pedido->nombre }}</strong>
                                    <small>{{ $pedido->fecha }}</small>
                                </span>
                                <span class="adm-chip {{ ['Pendiente' => 'adm-chip--alerta', 'Procesado' => 'adm-chip--ok', 'Cancelado' => 'adm-chip--peligro'][$pedido->estado_orden] ?? '' }}">{{ $pedido->estado_orden }}</span>
                                <span class="adm-inicio__total">$ {{ $pedido->total_pedido }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card adm-inicio__atajos" aria-labelledby="inicio-atajos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="inicio-atajos">Accesos rápidos</h2>
                    <p>Lo que más se usa del panel.</p>
                </div>
            </div>
            <div class="card-body">
                <ul class="adm-atajos" role="list">
                    <li><a href="{{ route('dashboard.productos') }}"><i class="fa-solid fa-box" aria-hidden="true"></i><span>Productos<small>Buscar, editar y ofertas</small></span></a></li>
                    <li><a href="{{ route('dashboard.productos.excel.importar') }}"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i><span>Importar Excel<small>Precios e imágenes en lote</small></span></a></li>
                    <li><a href="{{ route('dashboard.imagenes') }}"><i class="fa-solid fa-images" aria-hidden="true"></i><span>Imágenes<small>Subir y asignar por código</small></span></a></li>
                    <li><a href="{{ route('dashboard.categorias') }}"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Categorías<small>Rubros del catálogo</small></span></a></li>
                    <li><a href="{{ route('dashboard.lista') }}"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i><span>Lista de precios<small>El archivo para descargar</small></span></a></li>
                    <li><a href="{{ route('dashboard.home-slider') }}"><i class="fa-solid fa-panorama" aria-hidden="true"></i><span>Slider del home<small>Las imágenes de portada</small></span></a></li>
                    <li><a href="{{ route('dashboard.novedades') }}"><i class="fa-regular fa-newspaper" aria-hidden="true"></i><span>Novedades<small>Publicar y destacar</small></span></a></li>
                    <li><a href="{{ route('dashboard.clientes') }}"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span>Clientes<small>Altas, descuentos y accesos</small></span></a></li>
                </ul>
            </div>
        </section>
    </div>
</div>

@endsection
