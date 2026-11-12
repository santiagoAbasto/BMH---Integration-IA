@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('content')
    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Lista de precios']]])

    <section class="bmh-zona" aria-labelledby="bmh-listas-titulo">
        <x-osole::container.v1>
            <h1 id="bmh-listas-titulo" class="visually-hidden">Listas de precios</h1>

            <table class="bmh-docs">
                <thead>
                    <tr>
                        <th scope="col"><span class="visually-hidden">Tipo</span></th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Formato</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($listas as $lista)
                        @php $extension = strtoupper(pathinfo((string) ($lista->archivo ?: $lista->path), PATHINFO_EXTENSION)); @endphp
                        <tr>
                            <td class="bmh-docs__icono" aria-hidden="true">{{ \App\Support\IconoSitio::svg('documento') }}</td>
                            <td data-titulo="Nombre">{{ ucfirst($lista->nombre) }}</td>
                            <td data-titulo="Formato">{{ $lista->formato ?: $extension }}</td>
                            <td>
                                <div class="bmh-docs__acciones">
                                    <a class="bmh-docs__boton" href="{{ asset('archivos/'.$lista->path) }}" target="_blank" rel="noopener">Ver online</a>
                                    <a class="bmh-docs__boton bmh-docs__boton--primario" href="{{ asset('archivos/'.$lista->path) }}" download="{{ $lista->archivo }}">Descargar</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Todos los productos en un único Excel de una sola hoja, generado en el momento (ListaPreciosCompleta). --}}
                    <tr>
                        <td class="bmh-docs__icono" aria-hidden="true">{{ \App\Support\IconoSitio::svg('documento') }}</td>
                        <td data-titulo="Nombre">Lista completa - Todos los productos (un único Excel con los precios actuales)</td>
                        <td data-titulo="Formato">Excel</td>
                        <td>
                            <div class="bmh-docs__acciones">
                                <a class="bmh-docs__boton bmh-docs__boton--primario" href="{{ route('lista.completa') }}">Descargar</a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-osole::container.v1>
    </section>
@endsection
