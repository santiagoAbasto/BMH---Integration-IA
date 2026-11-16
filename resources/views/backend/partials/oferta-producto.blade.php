{{--
    Oferta del producto (crear y editar): sin oferta, en el sitio y la Zona de Clientes, o sólo en la Zona de Clientes.
    Viaja como `oferta` = '' | publica | clientes (App\Enums\AlcanceOferta). $actual: el valor guardado.
--}}
@php
    $actual = (string) old('oferta', $actual ?? '');
    $actual = \App\Enums\AlcanceOferta::desdeFormulario($actual)?->value ?? '';
    $opciones = ['' => ['Sin oferta', 'No aparece en Ofertas.']]
        + collect(\App\Enums\AlcanceOferta::cases())->mapWithKeys(fn ($o) => [$o->value => [$o->etiqueta(), $o->ayuda()]])->all();
@endphp
<fieldset class="adm-oferta">
    <legend class="form-label">Oferta</legend>
    <div class="adm-oferta__opciones">
        @foreach ($opciones as $valor => [$titulo, $ayuda])
            <label @class(['adm-oferta__opcion', 'adm-oferta__opcion--activa' => $valor !== '']) for="oferta-{{ $valor ?: 'no' }}">
                <input type="radio" class="form-check-input" name="oferta" id="oferta-{{ $valor ?: 'no' }}" value="{{ $valor }}" @checked($actual === $valor)>
                <span>
                    <strong>{{ $titulo }}</strong>
                    <small>{{ $ayuda }}</small>
                </span>
            </label>
        @endforeach
    </div>
    <p class="producto-oferta-ayuda" id="oferta-ayuda">No cambia el precio ni el descuento: sólo dónde se muestra como oferta.</p>
</fieldset>
