{{--
    Buscador del header: un solo campo para código, marca, modelo,
    equivalencia, rubro o medida (App\Services\BuscadorCatalogo).

    Sin JS es un form GET común a /buscar. public/js/buscador-header.js le
    agrega las sugerencias mientras se escribe, la navegación con teclado y,
    en celular, lo abre a pantalla completa desde la lupa del header.

    Los ejemplos del panel de ayuda son productos y datos reales del catálogo;
    si alguno deja de existir, sólo hay que cambiarlo acá.
--}}
@php
    $ejemplosBusqueda = [
        ['etiqueta' => 'Código BMH', 'valor' => 'REGR1635'],
        ['etiqueta' => 'Equivalencia', 'valor' => 'P-2371C'],
        ['etiqueta' => 'Marca', 'valor' => 'Bosch'],
        ['etiqueta' => 'Modelo', 'valor' => 'Renault'],
        ['etiqueta' => 'Rubro', 'valor' => 'Alternadores'],
        ['etiqueta' => 'Medida o atributo', 'valor' => 'rotor 24v'],
    ];
@endphp
<form id="buscador-header" class="buscador-header" action="{{ route('search') }}" method="GET" role="search"
      aria-label="Buscar en el catálogo"
      data-sugerencias-url="{{ route('search.sugerencias') }}"
      data-minimo="{{ \App\Services\BuscadorCatalogo::MINIMO_CARACTERES }}">
  <div class="buscador-header__barra">
    <button type="button" class="buscador-header__volver" data-buscador-cerrar aria-label="Cerrar la búsqueda">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <svg class="buscador-header__lupa" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>

    <label for="buscador-header-input" class="visually-hidden">Buscar por código, marca, modelo, equivalencia, rubro o medida</label>
    <input id="buscador-header-input" class="buscador-header__input" type="search" name="q"
           value="{{ request()->routeIs('search') ? ($termino ?? '') : '' }}"
           placeholder="Buscá por código, marca, modelo, equivalencia o medida"
           {{-- Variantes más cortas: el JS usa la más larga que entra sin cortarse. --}}
           data-placeholders='["Buscá por código, marca, modelo, equivalencia o medida","Código, marca, modelo, equivalencia o medida","Código, marca, modelo o equivalencia","Código, marca o modelo","Código o marca","Buscar"]'
           maxlength="{{ \App\Http\Requests\BuscarRequest::MAXIMO_CARACTERES }}"
           autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search"
           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="buscador-header-resultados"
           aria-describedby="buscador-header-ayuda">

    <span class="buscador-header__spinner" aria-hidden="true"></span>
    <button type="button" class="buscador-header__limpiar" data-buscador-limpiar aria-label="Borrar lo escrito" hidden>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
    </button>
    {{-- Enter ya envía; el botón queda para lectores de pantalla y teclado. --}}
    <button type="submit" class="buscador-header__enviar visually-hidden-focusable">Buscar</button>
  </div>

  <div class="buscador-header__panel" data-buscador-panel hidden>
    {{-- Ayuda: se muestra al enfocar el campo vacío. --}}
    <div class="buscador-header__ayuda" data-buscador-ayuda>
      <div class="buscador-header__recientes" data-buscador-recientes hidden>
        <div class="buscador-header__titulo">
          Búsquedas recientes
          <button type="button" class="buscador-header__borrar-recientes" data-buscador-borrar-recientes>Borrar</button>
        </div>
        <ul class="buscador-header__lista-recientes" data-buscador-lista-recientes></ul>
      </div>

      <div class="buscador-header__titulo">Podés buscar por</div>
      <ul class="buscador-header__ejemplos">
        @foreach ($ejemplosBusqueda as $ejemplo)
          <li>
            <button type="button" class="buscador-header__ejemplo" data-buscador-ejemplo="{{ $ejemplo['valor'] }}">
              <span class="buscador-header__ejemplo-tipo">{{ $ejemplo['etiqueta'] }}</span>
              <span class="buscador-header__ejemplo-valor">{{ $ejemplo['valor'] }}</span>
            </button>
          </li>
        @endforeach
      </ul>
      <p class="buscador-header__tip">También podés combinar: <strong>rotor bosch 24v</strong>.</p>
    </div>

    {{-- Resultados: los arma el JS. --}}
    <div id="buscador-header-resultados" class="buscador-header__resultados" role="listbox" aria-label="Sugerencias" data-buscador-resultados hidden></div>
  </div>

  <p id="buscador-header-ayuda" class="visually-hidden">Escribí al menos {{ \App\Services\BuscadorCatalogo::MINIMO_CARACTERES }} caracteres para ver sugerencias. Usá las flechas para recorrerlas y Enter para abrir una.</p>
  <p class="visually-hidden" role="status" aria-live="polite" data-buscador-estado></p>
</form>
