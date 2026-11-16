{{--
    Oferta del producto (crear y editar): sin oferta, para todos (sitio y Zona de Clientes) o sólo Zona de Clientes, y
    el descuento opcional de la oferta: % sobre el precio de lista o precio de oferta. Viaja como `oferta`
    ('' | publica | clientes), `oferta_tipo` (porcentaje | precio) y `oferta_valor`. La vista previa lee el precio de
    lista del mismo formulario (#precio).

    $actual: alcance guardado; $tipo y $valor: el descuento guardado (null al crear).
--}}
@php
    use App\Models\Producto;

    $actual = \App\Enums\AlcanceOferta::desdeFormulario((string) old('oferta', $actual ?? ''))?->value ?? '';
    $tipo = old('oferta_tipo', $tipo ?? null) ?: Producto::OFERTA_PORCENTAJE;
    $valor = old('oferta_valor', isset($valor) && $valor !== null ? rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.') : '');
    $opciones = ['' => ['Sin oferta', 'No aparece en Ofertas.']]
        + collect(\App\Enums\AlcanceOferta::cases())->mapWithKeys(fn ($o) => [$o->value => [$o->etiqueta(), $o->ayuda()]])->all();
@endphp
<fieldset class="adm-oferta" data-oferta>
    <legend class="form-label">Oferta</legend>
    <div class="adm-oferta__opciones">
        @foreach ($opciones as $opcion => [$titulo, $ayuda])
            <label @class(['adm-oferta__opcion', 'adm-oferta__opcion--activa' => $opcion !== '']) for="oferta-{{ $opcion ?: 'no' }}">
                <input type="radio" class="form-check-input" name="oferta" id="oferta-{{ $opcion ?: 'no' }}" value="{{ $opcion }}" @checked($actual === $opcion)>
                <span>
                    <strong>{{ $titulo }}</strong>
                    <small>{{ $ayuda }}</small>
                </span>
            </label>
        @endforeach
    </div>

    <div class="adm-oferta__descuento" data-oferta-descuento @if ($actual === '') hidden @endif>
        <p class="adm-oferta__titulo">Descuento de la oferta <span>(opcional)</span></p>
        <div class="adm-oferta__fila">
            <div class="adm-oferta__tipos" role="radiogroup" aria-label="Tipo de descuento">
                <label><input type="radio" class="form-check-input" name="oferta_tipo" value="{{ Producto::OFERTA_PORCENTAJE }}" @checked($tipo === Producto::OFERTA_PORCENTAJE)> Porcentaje</label>
                <label><input type="radio" class="form-check-input" name="oferta_tipo" value="{{ Producto::OFERTA_PRECIO }}" @checked($tipo === Producto::OFERTA_PRECIO)> Precio de oferta</label>
            </div>
            <div class="input-group adm-oferta__valor">
                <span class="input-group-text" data-oferta-prefijo @if ($tipo !== Producto::OFERTA_PRECIO) hidden @endif>$</span>
                <input type="text" class="form-control @error('oferta_valor') is-invalid @enderror" name="oferta_valor" id="oferta-valor" value="{{ $valor }}" inputmode="decimal" autocomplete="off"
                    aria-label="Valor del descuento" aria-describedby="oferta-previa oferta-valor-error" placeholder="{{ $tipo === Producto::OFERTA_PRECIO ? 'Ej.: 25000' : 'Ej.: 15' }}">
                <span class="input-group-text" data-oferta-sufijo @if ($tipo === Producto::OFERTA_PRECIO) hidden @endif>%</span>
            </div>
        </div>
        @error('oferta_valor')
            <div class="invalid-feedback d-block" id="oferta-valor-error">{{ $message }}</div>
        @enderror
        <p class="adm-oferta__previa" id="oferta-previa" data-oferta-previa aria-live="polite"></p>
    </div>
    <p class="producto-oferta-ayuda" id="oferta-ayuda">El descuento se aplica sobre el precio de lista y sólo lo ven los clientes con sesión; los descuentos de cada cliente se suman igual. Sin descuento, el producto se muestra en Ofertas con su precio de siempre.</p>
</fieldset>

<script>
    (() => {
        const caja = document.currentScript.previousElementSibling;
        const descuento = caja.querySelector('[data-oferta-descuento]');
        const valor = caja.querySelector('[name="oferta_valor"]');
        const previa = caja.querySelector('[data-oferta-previa]');
        const prefijo = caja.querySelector('[data-oferta-prefijo]');
        const sufijo = caja.querySelector('[data-oferta-sufijo]');
        const lista = document.getElementById('precio');
        const numero = (texto) => parseFloat(String(texto ?? '').trim().replace(/\./g, (m, i, s) => (s.indexOf(',') > -1 ? '' : m)).replace(',', '.'));
        const pesos = (n) => '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const actualizar = () => {
            const alcance = caja.querySelector('[name="oferta"]:checked')?.value ?? '';
            const tipo = caja.querySelector('[name="oferta_tipo"]:checked')?.value ?? 'porcentaje';
            descuento.hidden = alcance === '';
            prefijo.hidden = tipo !== 'precio';
            sufijo.hidden = tipo === 'precio';
            valor.placeholder = tipo === 'precio' ? 'Ej.: 25000' : 'Ej.: 15';

            const base = numero(lista?.value);
            const v = numero(valor.value);
            if (!valor.value.trim()) {
                previa.textContent = 'Sin descuento: se muestra en Ofertas con su precio de lista.';
                previa.classList.remove('is-error');
                return;
            }
            if (!(base > 0) || !(v > 0)) {
                previa.textContent = !(base > 0) ? 'Cargá el precio de lista para ver cómo queda.' : 'Escribí un número mayor que cero.';
                previa.classList.toggle('is-error', base > 0);
                return;
            }
            const oferta = tipo === 'precio' ? v : base * (1 - v / 100);
            const porcentaje = Math.round((1 - oferta / base) * 100);
            const malo = tipo === 'precio' ? v >= base : v >= 100;
            previa.classList.toggle('is-error', malo);
            previa.textContent = malo
                ? (tipo === 'precio' ? 'El precio de oferta tiene que ser menor que el de lista (' + pesos(base) + ').' : 'El descuento tiene que ser menor que 100%.')
                : 'Lista ' + pesos(base) + ' → en oferta ' + pesos(oferta) + ' (−' + porcentaje + '%).';
        };

        caja.addEventListener('change', actualizar);
        valor.addEventListener('input', actualizar);
        lista?.addEventListener('input', actualizar);
        actualizar();
    })();
</script>
