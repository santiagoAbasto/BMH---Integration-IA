{{--
    Características de un producto (crear y editar). Cada fila es nombre + valor (caracteristicas[ID]); las vacías
    no se guardan. SincronizarCaracteristicasProducto aplica el formato de cada una y devuelve los errores por campo.
    Lo maneja resources/js/admin/caracteristicas.js ([data-car-producto]).

    $producto: null al crear. $cargadas: colección de Caracteristica con pivot->valor (las del producto).
--}}
@php
    use App\Models\Caracteristica;

    $cargadas = collect($cargadas ?? []);
    $valores = $cargadas->mapWithKeys(fn ($c) => [$c->id => (string) $c->pivot?->valor]);

    // Después de un error de validación vuelven las filas que se habían mandado (con lo que se escribió).
    $viejos = old('caracteristicas');
    if (is_array($viejos)) {
        $ids = array_map('intval', array_keys($viejos));
        $orden = array_flip($ids);
        $filas = Caracteristica::query()->whereIn('id', $ids)->get()->sortBy(fn ($c) => $orden[$c->id] ?? 0)->values();
        $valores = collect($viejos)->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v]);
    } else {
        $filas = $cargadas;
    }
    $sumar = array_map('intval', (array) old('sumar_a_categoria', []));
@endphp
<div class="car-selector" data-car-producto
    data-url-buscar="{{ route('caracteristicas.buscar') }}"
    data-url-crear="{{ route('caracteristicas.store') }}"
    data-url-origenes="{{ route('caracteristicas.origenes') }}"
    data-url-categoria="{{ route('categoria.show', ':id') }}"
    data-producto-id="{{ $producto?->id ?? '' }}"
    data-select-categoria="select-categoria">
    <input type="hidden" name="caracteristicas_presente" value="1">

    <div class="car-selector__cabeza">
        <p class="mb-0" data-car-resumen-categoria aria-live="polite">Elegí la categoría arriba para ver sus características.</p>
        <button type="button" class="btn btn-primary btn-sm" data-car-usar-categoria hidden>Usar las de la categoría</button>
    </div>

    <div class="car-aviso" data-car-aviso-categoria role="status" hidden></div>

    <ul class="car-filas" data-car-filas aria-label="Características del producto">
        @foreach ($filas as $caracteristica)
            @php
                $id = 'car-valor-'.$caracteristica->id;
                $error = $errors->first('caracteristicas.'.$caracteristica->id);
            @endphp
            <li class="car-fila" data-id="{{ $caracteristica->id }}" data-nombre="{{ $caracteristica->nombre }}"
                data-mayusculas="{{ $caracteristica->mayusculas ? '1' : '0' }}" data-numerica="{{ $caracteristica->numerica ? '1' : '0' }}" data-unidad="{{ $caracteristica->unidad }}">
                <div class="car-fila__nombre">
                    <label for="{{ $id }}">{{ $caracteristica->nombre }}</label>
                    <small id="{{ $id }}-formato">{{ $caracteristica->descripcionFormato() }}</small>
                </div>
                <div class="car-fila__valor">
                    <input type="text" @class(['form-control', 'is-invalid' => $error]) id="{{ $id }}" name="caracteristicas[{{ $caracteristica->id }}]"
                        value="{{ $valores[$caracteristica->id] ?? '' }}" maxlength="255" aria-describedby="{{ $id }}-formato {{ $id }}-error"
                        @if ($caracteristica->numerica) inputmode="decimal" placeholder="Ej.: 12{{ $caracteristica->unidad }}" @endif
                        @if ($error) aria-invalid="true" @endif>
                    <p class="car-fila__previa" data-car-previa aria-live="polite"></p>
                    <div class="invalid-feedback d-block" id="{{ $id }}-error" data-car-error @unless ($error) hidden @endunless>{{ $error }}</div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm car-fila__quitar" aria-label="Quitar {{ $caracteristica->nombre }}" title="Quitar" data-car-quitar>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="car-fila__aviso" data-car-fuera hidden></div>
                @if (in_array($caracteristica->id, $sumar, true))
                    <input type="hidden" name="sumar_a_categoria[]" value="{{ $caracteristica->id }}">
                @endif
            </li>
        @endforeach
    </ul>
    <p class="car-chips__vacio" data-car-filas-vacio @if ($filas->isNotEmpty()) hidden @endif>
        Este producto todavía no tiene características. Usá las de su categoría, buscalas abajo o copialas de otro producto.
    </p>
    <p class="visually-hidden" data-car-anuncio aria-live="polite"></p>

    @include('backend.partials.caracteristicas.buscador', [
        'id' => 'car-producto-buscar',
        'etiqueta' => 'Agregar una característica',
        'placeholder' => 'Escribí para buscar: voltaje, diámetro, giro…',
        'ayuda' => 'Elegí con las flechas y Enter. Si no existe, podés crearla.',
    ])

    @include('backend.partials.caracteristicas.paneles', [
        'uid' => 'car-producto',
        'origenTitulo' => 'Copiar de otro producto o categoría',
        'origenAyuda' => 'Buscá un producto parecido y traé sus características (con sus valores, si querés) o las de una categoría.',
        'conSinUso' => false,
    ])
</div>
