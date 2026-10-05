@extends('layouts.plantilla-front')

{{--
    Catálogo con filtros (CatalogoController): /productos?categoria=… y
    /buscar?q=… son esta misma pantalla.

    Todo lo que cambia al filtrar vive en catalogo/_contenido, que
    public/js/catalogo.js reemplaza sin recargar la página.
--}}

@section('metadatos')
    @if ($filtros->hayBusqueda())
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="keyword" content="{{ App\Models\Metadatos::first()?->keyword }}">
        <meta name="descripcion" content="{{ App\Models\Metadatos::first()?->descripcion }}">
    @endif
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/catalogo.css') }}?v=2">
@endsection

@section('content')
    {{-- CSS y JS de las cards, una sola vez: las que llegan después vienen sin. --}}
    @include('frontend.components.productoBmh-recursos')

    <section class="catalogo" data-catalogo>
        <div class="container" data-catalogo-contenido>
            @include('frontend.catalogo._contenido')
        </div>
    </section>
@endsection

@section('script')
    <script src="{{ asset('js/catalogo.js') }}?v=3"></script>
@endsection
