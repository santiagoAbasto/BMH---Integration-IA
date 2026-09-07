{{--
    Tabla(s) de aplicaciones de un producto, para la ficha (show) y las
    cards del listado. Es la misma pieza en ambos lados.

    Espera: $aplicaciones  iterable  filas del producto (App\Models\Aplicacion)

    Una o dos columnas de tablas idénticas: la segunda aparece solo si la
    primera no alcanza (evitar scroll); con una sola, conserva las mismas
    proporciones. Header negro con radio superior de 4px.
--}}
@php
    $aplicLista = collect($aplicaciones)->values();
    $aplicMaxPorColumna = 12;
    $aplicColumnas = $aplicLista->isEmpty()
        ? collect()
        : ($aplicLista->count() > $aplicMaxPorColumna
            ? $aplicLista->chunk((int) ceil($aplicLista->count() / 2))
            : collect([$aplicLista]));
@endphp
@if ($aplicColumnas->isNotEmpty())
    <div class="pbmh-aplic-grid">
        @foreach ($aplicColumnas as $aplicColumna)
            <table class="pbmh-tabla pbmh-tabla-aplic">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Modelo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aplicColumna as $aplicacion)
                        <tr>
                            <td>{{ $aplicacion->nombre ? mb_strtoupper($aplicacion->nombre) : '—' }}</td>
                            <td class="pbmh-celda-cod">{{ mb_strtoupper((string) $aplicacion->valor) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
@else
    <p class="pbmh-vacio">No hay aplicaciones cargadas para este producto.</p>
@endif

@once
<style>
    {{-- El radio superior del header y border-collapse:separate vienen del
         estilo base .pbmh-tabla (card y show). Acá solo lo específico. --}}
    .pbmh-aplic-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:0 26px; align-items:start; }
    .pbmh-tabla-aplic th { width:45%; }
    .pbmh-tabla-aplic th:last-child { width:55%; }
    .pbmh-tabla-aplic tbody tr:nth-child(odd) { background:#F5F6F7; }
    .pbmh-tabla-aplic tbody tr:hover { background:#EDF5FB; }
    @media (max-width: 991px) {
        .pbmh-aplic-grid { grid-template-columns:1fr; gap:22px 0; }
    }
</style>
@endonce
