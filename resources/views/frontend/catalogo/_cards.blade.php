{{-- Una tanda de cards; también es lo que devuelve el scroll infinito. --}}
@foreach ($productos as $producto)
    <div class="producto-cont catalogo__item">
        @include('frontend.components.productoBmh', ['sinRecursosCard' => $sinRecursosCard ?? false])
    </div>
@endforeach
