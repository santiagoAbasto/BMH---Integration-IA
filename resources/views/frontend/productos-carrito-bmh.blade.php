

@foreach ($productos as $producto)
<div class="producto-cont col-lg-12" {{ Route::currentRouteName() == 'producto' ? 'data-aos="fade-up"' : '' }}>
    @include('frontend.components.productoBmh')
    
</div>

@endforeach


