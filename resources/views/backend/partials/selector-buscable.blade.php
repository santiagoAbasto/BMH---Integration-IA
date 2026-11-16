{{--
    Selector con búsqueda (resources/js/admin/selector-buscable.js). Se escribe y filtra con debounce; flechas y
    Enter para elegir. Lo elegido viaja en el campo oculto `$name`.

    $id, $name, $label, $placeholder, $ayuda (opcional)
    $url: busca en el servidor (?q=) · $opciones: [{valor, titulo, codigo?, detalle?, meta?, extra?}] para filtrar acá
    $valor: el elegido al cargar (sólo con $opciones) · $requerido: sin elegir no se manda el formulario
--}}
@php
    $requerido ??= true;
    $valor ??= '';
    $local = ! isset($url);
@endphp
<div class="adm-selector" data-selector-buscable @isset($url) data-url="{{ $url }}" @endisset
    data-mensaje-requerido="{{ $mensajeRequerido ?? 'Elegí una opción de la lista.' }}">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" value="{{ $valor }}" data-selector-valor @if ($requerido) data-requerido @endif>

    {{-- Lo elegido: la ficha y el botón para cambiarlo. --}}
    <div class="adm-selector__elegido">
        <div class="adm-selector__ficha" data-selector-ficha></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-selector-limpiar>
            <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Cambiar
        </button>
    </div>

    {{-- El buscador. --}}
    <div class="adm-selector__caja">
        <svg class="adm-selector__lupa" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="text" id="{{ $id }}" class="form-control adm-selector__input" placeholder="{{ $placeholder }}" data-selector-input
            aria-describedby="{{ $id }}-estado{{ isset($ayuda) ? ' '.$id.'-ayuda' : '' }}">
        <span class="adm-selector__cargando" aria-hidden="true"></span>
    </div>
    <ul class="adm-selector__lista" data-selector-lista hidden></ul>
    <p class="adm-selector__estado" id="{{ $id }}-estado" data-selector-estado role="status" aria-live="polite"></p>
    @isset($ayuda)
        <p class="form-text mt-1" id="{{ $id }}-ayuda">{{ $ayuda }}</p>
    @endisset

    @if ($local)
        <script type="application/json" data-selector-opciones>@json($opciones)</script>
    @endif
</div>
