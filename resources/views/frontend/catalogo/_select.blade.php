{{--
    Un filtro de opciones del sidebar. Es un <select> común (anda sin JS);
    public/js/catalogo.js lo convierte en el desplegable con contadores y,
    si hay muchas opciones, con un campo para filtrarlas.

    Parámetros:
      name, id, vacio (texto de «todas»), opciones [valor => [texto, cantidad]],
      seleccionado, totalVacio (opcional), buscable (placeholder o null),
      deshabilitado (opcional), ayuda (opcional, se muestra si está deshabilitado)
--}}
@php
    $seleccionado = $seleccionado ?? null;
    $deshabilitado = $deshabilitado ?? false;
@endphp
<select id="{{ $id }}" name="{{ $name }}" class="filtros__select" data-desplegable
        @if (!empty($buscable)) data-buscable="{{ $buscable }}" @endif
        @if ($deshabilitado && !empty($ayuda)) data-ayuda="{{ $ayuda }}" @endif
        @disabled($deshabilitado)>
    <option value="" data-texto="{{ $vacio }}" @if (isset($totalVacio)) data-cantidad="{{ $totalVacio }}" @endif @selected($seleccionado === null)>
        {{ $vacio }}@isset($totalVacio) ({{ number_format($totalVacio, 0, ',', '.') }})@endisset
    </option>
    @foreach ($opciones as $valor => $opcion)
        <option value="{{ $valor }}" data-texto="{{ $opcion['texto'] }}" data-cantidad="{{ $opcion['cantidad'] }}"
                @selected((string) $seleccionado === (string) $valor)>
            {{ $opcion['texto'] }} ({{ number_format($opcion['cantidad'], 0, ',', '.') }})
        </option>
    @endforeach
</select>
