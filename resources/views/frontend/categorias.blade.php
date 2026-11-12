@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('content')
    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Productos']]])

    <section class="bmh-seccion-categorias" aria-label="Categorías de productos">
        <x-osole::container.v1>
            <h1 class="visually-hidden">Productos</h1>
            @include('frontend.partials.grilla-categorias')
        </x-osole::container.v1>
    </section>
@endsection
