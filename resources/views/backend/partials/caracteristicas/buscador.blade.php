{{--
    Buscador de características (combobox accesible; lo maneja resources/js/admin/caracteristicas.js).
    $id, $etiqueta, $placeholder; $atributo y $atributoEstado: los data-* que busca el JS (por defecto el buscador
    principal); $ayuda opcional.
--}}
@php
    $atributo ??= 'data-car-buscar';
    $atributoEstado ??= 'data-car-buscar-estado';
@endphp
<div class="car-buscador">
    <label class="form-label" for="{{ $id }}">{{ $etiqueta }}</label>
    <div class="car-buscador__caja">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" class="form-control" id="{{ $id }}" placeholder="{{ $placeholder }}" {{ $atributo }} aria-describedby="{{ $id }}-estado">
    </div>
    <p class="car-estado" id="{{ $id }}-estado" {{ $atributoEstado }} aria-live="polite">{{ $ayuda ?? '' }}</p>
</div>
