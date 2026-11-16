{{--
    Filas «ETIQUETA: valor» de un producto: Marca, Modelo y después las características que declara su categoría, en el
    orden de cada característica (admin → Características). Las usan la tarjeta del catálogo (productoBmh) y la ficha del
    producto, así se ven iguales. Estilos: .pbmh-car en resources/css/sitio/paginas.css.

    $producto; $caracteristicas opcional (si ya se calcularon, Producto::fichaCaracteristicas()).
--}}
@php($caracteristicas ??= $producto->fichaCaracteristicas())
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
@foreach ($caracteristicas as $caracteristica)
    <div class="pbmh-car">
        <span class="pbmh-car-label">{{ $caracteristica['nombre'] }}:</span>
        <span class="pbmh-car-valor">{{ $caracteristica['valor'] }}</span>
    </div>
@endforeach
