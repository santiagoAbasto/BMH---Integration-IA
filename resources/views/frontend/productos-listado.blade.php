@foreach ($productos as $producto)
<div class="producto-cont {{ Route::currentRouteName() == 'filtroRodamientos' ? 'col-lg-3' : 'col-lg-4' }}" {{ Route::currentRouteName() == 'producto' ? 'data-aos="fade-up"' : '' }}>
    @include('frontend.components.productoRodamiento')
    
</div>
@endforeach