{{--
    Tabla(s) de equivalencias de un producto, para la ficha (show) y las
    cards del listado. Es la misma pieza en ambos lados.

    Espera: $equivalencias  iterable  filas del producto (App\Models\Equivalencia)

    Una o dos columnas de tablas idénticas: la segunda aparece solo si la
    primera no alcanza (evitar scroll); con una sola, conserva las mismas
    proporciones. Header negro con radio superior de 4px.
--}}
@php
    $equivLista = collect($equivalencias)->sortBy('orden')->values();
    $equivMaxPorColumna = 12;
    $equivColumnas = $equivLista->isEmpty()
        ? collect()
        : ($equivLista->count() > $equivMaxPorColumna
            ? $equivLista->chunk((int) ceil($equivLista->count() / 2))
            : collect([$equivLista]));
@endphp
@if ($equivColumnas->isNotEmpty())
    <div class="pbmh-equiv-grid">
        @foreach ($equivColumnas as $equivColumna)
            <table class="pbmh-tabla pbmh-tabla-equiv">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Código</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equivColumna as $equivalencia)
                        <tr>
                            <td>{{ $equivalencia->nombre ?: '—' }}</td>
                            <td class="pbmh-celda-cod">{{ $equivalencia->valor }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
@else
    <p class="pbmh-vacio">No hay equivalencias cargadas para este producto.</p>
@endif

@once
<style>
    {{-- El radio superior del header y border-collapse:separate vienen del
         estilo base .pbmh-tabla (card y show). Acá solo lo específico. --}}
    .pbmh-equiv-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:0 26px; align-items:start; }
    .pbmh-tabla-equiv th { width:45%; }
    .pbmh-tabla-equiv th:last-child { width:55%; }
    .pbmh-tabla-equiv tbody tr:nth-child(odd) { background:#F5F6F7; }
    .pbmh-tabla-equiv tbody tr:hover { background:#EDF5FB; }
    @media (max-width: 991px) {
        .pbmh-equiv-grid { grid-template-columns:1fr; gap:22px 0; }
    }
</style>
@endonce
