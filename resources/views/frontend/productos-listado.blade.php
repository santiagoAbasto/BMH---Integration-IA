@foreach ($productos as $producto)
<div class="producto-cont col-lg-12" {{ Route::currentRouteName() == 'producto' ? 'data-aos="fade-up"' : '' }}>
    @include('frontend.components.productoBmh')
</div>
@endforeach

@if ($productos instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="col-12 mt-4">
        {{ $productos->links() }}
    </div>
@endif
