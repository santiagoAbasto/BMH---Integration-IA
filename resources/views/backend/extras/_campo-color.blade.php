{{--
    Campo de color: muestra + hex sincronizados, "volver al original" y aviso
    de contraste. El JS de _script.blade.php lo engancha por data-*.

    @param string      $campo      nombre del input (columna de `apariencia`)
    @param string      $etiqueta
    @param string      $parte      qué elementos de la vista previa resalta (data-parte)
    @param string|null $contraste  campo contra el que se mide el contraste
    @param bool        $hover      si enfocarlo muestra el estado "al pasar el mouse"
--}}
@php
    $valor = strtoupper((string) old($campo, $apariencia->{$campo}));
    $original = \App\Models\Apariencia::DEFAULTS[$campo];
@endphp
<div class="ext-color {{ $errors->has($campo) ? 'is-invalido' : '' }}"
     data-campo="{{ $campo }}"
     data-original="{{ $original }}"
     data-parte="{{ $parte }}"
     @isset($contraste) data-contraste="{{ $contraste }}" @endisset
     @if (!empty($hover)) data-hover="1" @endif>
  <label for="{{ $campo }}">{{ $etiqueta }}</label>
  <div class="ext-color__control">
    <input type="color" class="ext-color__swatch" value="{{ strtolower($valor) }}" aria-label="Elegir color: {{ $etiqueta }}" tabindex="-1">
    <input type="text" class="form-control form-control-sm ext-color__hex" id="{{ $campo }}" name="{{ $campo }}"
           value="{{ $valor }}" maxlength="7" spellcheck="false" autocomplete="off" required
           pattern="#[0-9A-Fa-f]{6}" title="Formato #RRGGBB">
    <button type="button" class="ext-color__reset" title="Volver al color original ({{ $original }})" {{ $valor === $original ? 'hidden' : '' }}>
      <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Original
    </button>
  </div>
  <p class="ext-color__aviso" hidden></p>
  @error($campo)
    <p class="ext-color__error">{{ $message }}</p>
  @enderror
</div>
