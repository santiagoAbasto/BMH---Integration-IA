@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('content')
    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Historial']]])

    <section class="bmh-zona" aria-labelledby="bmh-historial-titulo">
        <x-osole::container.v1>
            <h1 id="bmh-historial-titulo" class="visually-hidden">Historial de pedidos</h1>

            @if (count($historial) > 0)
                <table class="bmh-docs">
                    <thead>
                        <tr>
                            <th scope="col"><span class="visually-hidden">Tipo</span></th>
                            <th scope="col">N° de pedido</th>
                            <th scope="col">Fecha de compra</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Importe</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historial as $pedido)
                            <tr>
                                <td class="bmh-docs__icono" aria-hidden="true">{{ \App\Support\IconoSitio::svg('documento') }}</td>
                                <td data-titulo="N° de pedido">{{ str_pad((string) $pedido->id, 11, '0', STR_PAD_LEFT) }}</td>
                                <td data-titulo="Fecha de compra">{{ \Illuminate\Support\Str::before((string) $pedido->fecha, ' ') }}</td>
                                <td data-titulo="Estado" class="bmh-docs__estado">{{ $pedido->estado_orden ?: $pedido->estado }}</td>
                                <td data-titulo="Importe">${{ $pedido->total_pedido }}</td>
                                <td>
                                    <div class="bmh-docs__acciones">
                                        <button type="button" class="bmh-docs__boton bmh-docs__boton--icono" data-repetir="{{ $pedido->id }}"
                                                aria-label="Volver a pedir el pedido {{ $pedido->id }}" title="Volver a pedir">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                        </button>
                                        <a class="bmh-docs__boton" href="{{ route('cliente.pedido', ['id' => $pedido->id]) }}">Ver online</a>
                                        <a class="bmh-docs__boton bmh-docs__boton--primario" href="{{ route('cliente.pedido', ['id' => $pedido->id, 'imprimir' => 1]) }}" target="_blank" rel="noopener">Descargar</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="bmh-zona__vacio">Todavía no hiciste ningún pedido. Cuando hagas uno, lo vas a ver acá.</p>
            @endif
        </x-osole::container.v1>
    </section>
@endsection

@section('script')
<script>
    (function () {
        // «Volver a pedir»: suma los productos del pedido al carrito (CarritoController@repetir_pedido).
        document.querySelectorAll('[data-repetir]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                boton.setAttribute('aria-busy', 'true');
                $.ajax({
                    url: "{{ route('repetir.pedido') }}",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', id: boton.dataset.repetir },
                    success: function () {
                        iziToast.success({ title: 'Productos agregados al carrito', position: 'bottomRight' });
                    },
                    error: function () {
                        iziToast.error({ title: 'No se pudo volver a pedir', message: 'Probá de nuevo en un momento.', position: 'bottomRight' });
                    },
                    complete: function () { boton.removeAttribute('aria-busy'); }
                });
            });
        });
    })();
</script>
@endsection
