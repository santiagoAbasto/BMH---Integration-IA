{{--
    Paneles plegables del selector de categorías y de la sección del producto:
    «Crear una característica nueva», «Traer de otra categoría o producto» y (opcional) «Características sin usar».
    $uid: prefijo único para los ids (puede haber varios formularios en la página).
    $origenTitulo / $origenAyuda: textos del panel «traer»; $conSinUso: mostrar el panel de las sin usar.
--}}
<details class="car-panel" data-car-alta>
    <summary>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Crear una característica nueva
        <small>si no está en la lista</small>
    </summary>
    <div class="car-panel__cuerpo car-alta">
        <div>
            <label class="form-label" for="{{ $uid }}-alta-nombre">Nombre</label>
            <input type="text" class="form-control" id="{{ $uid }}-alta-nombre" maxlength="255" placeholder="Ej.: AMPERAJE" data-car-alta-nombre>
        </div>
        <fieldset class="car-alta__tipo">
            <legend class="form-label">¿Qué se carga?</legend>
            <label><input type="radio" class="form-check-input" name="{{ $uid }}-alta-tipo" value="texto" checked data-car-alta-tipo> Texto</label>
            <label><input type="radio" class="form-check-input" name="{{ $uid }}-alta-tipo" value="numero" data-car-alta-tipo> Número</label>
            <label>Unidad <input type="text" class="form-control form-control-sm" maxlength="10" placeholder="V, MM, A…" aria-label="Unidad (opcional)" data-car-alta-unidad></label>
        </fieldset>
        <div class="car-acciones">
            <button type="button" class="btn btn-primary btn-sm" data-car-alta-crear>Crear y agregar</button>
        </div>
        <p class="car-estado" data-car-alta-estado aria-live="polite">En mayúsculas, como todas. Si es un número, la unidad se agrega sola al guardar.</p>
    </div>
</details>

<details class="car-panel" data-car-origen>
    <summary>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 17l4 4 4-4M12 12v9"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg>
        {{ $origenTitulo }}
    </summary>
    <div class="car-panel__cuerpo">
        <p class="car-panel__ayuda">{{ $origenAyuda }}</p>
        <fieldset class="car-origen__tipos">
            <legend class="visually-hidden">Traer de</legend>
            <label><input type="radio" class="form-check-input" name="{{ $uid }}-origen-tipo" value="categoria" checked data-car-origen-tipo> Una categoría</label>
            <label><input type="radio" class="form-check-input" name="{{ $uid }}-origen-tipo" value="producto" data-car-origen-tipo> Un producto</label>
        </fieldset>
        @include('backend.partials.caracteristicas.buscador', [
            'id' => $uid.'-origen-buscar',
            'etiqueta' => 'Buscar',
            'placeholder' => 'Nombre de la categoría…',
            'atributo' => 'data-car-origen-buscar',
            'atributoEstado' => 'data-car-origen-estado',
        ])
        <div data-car-origen-detalle></div>
    </div>
</details>

@if ($conSinUso ?? false)
    <details class="car-panel" data-car-sin-uso>
        <summary>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
            Características sin usar
            <small>no las tiene ninguna categoría ni producto</small>
        </summary>
        <div class="car-panel__cuerpo">
            <p class="car-estado" data-car-sin-uso-estado aria-live="polite">Cargando…</p>
            <ul class="car-sueltas" data-car-sin-uso-lista></ul>
        </div>
    </details>
@endif
