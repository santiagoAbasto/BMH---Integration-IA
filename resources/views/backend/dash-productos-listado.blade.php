@forelse ($productos as $producto)
    <tr class="producto-row {{ $producto->oferta ? 'producto-row--oferta' : '' }}" role="row" data-producto-codigo="{{ $producto->codigo }}">
        <td class="producto-orden" role="cell"><span class="producto-label" aria-hidden="true">Orden</span> {{ $producto->orden }}</td>
        <td class="producto-codigo" role="cell">{{ $producto->codigo }}</td>
        <td class="producto-portada" role="cell">
            @if($producto->portadaImagen?->path)
                <img src="{{ asset('imagenes/'.$producto->portadaImagen->path) }}" alt="" width="72" height="72" loading="lazy" decoding="async">
            @else
                <span class="producto-sin-imagen"><i class="fa-regular fa-image" aria-hidden="true"></i><span>Sin imagen</span></span>
            @endif
        </td>
        <td class="producto-nombre" role="cell">{{ $producto->nombre }}</td>
        <td class="producto-categoria" role="cell"><span class="producto-label" aria-hidden="true">Categoría</span> {{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
        <td class="producto-destacada" role="cell">
            <label class="adm-switch producto-featured" for="producto{{ $producto->id }}">
                <input class="categoriaCheckbox" type="checkbox" role="switch" data-id="{{ $producto->id }}" id="producto{{ $producto->id }}" name="destacada" value="1" @checked($producto->destacada) aria-checked="{{ $producto->destacada ? 'true' : 'false' }}">
                <span class="producto-featured-text">Destacado</span>
                <span class="visually-hidden">: {{ $producto->codigo }}</span>
            </label>
        </td>
        <td class="producto-oferta" role="cell">
            {{-- Oferta con alcance: sin oferta, en el sitio y la Zona de Clientes, o sólo en la Zona de Clientes. --}}
            @php($alcance = $producto->oferta?->value ?? '')
            <label class="visually-hidden" for="oferta{{ $producto->id }}">Oferta de {{ $producto->codigo }}</label>
            <select class="form-select form-select-sm producto-oferta-select js-producto-oferta" id="oferta{{ $producto->id }}"
                data-producto="{{ $producto->id }}" data-codigo="{{ $producto->codigo }}" data-valor="{{ $alcance }}" data-anterior="{{ $alcance }}">
                <option value="" @selected($alcance === '')>Sin oferta</option>
                @foreach (\App\Enums\AlcanceOferta::cases() as $opcion)
                    <option value="{{ $opcion->value }}" @selected($alcance === $opcion->value)>{{ $opcion === \App\Enums\AlcanceOferta::Publica ? 'Para todos' : 'Sólo clientes' }}</option>
                @endforeach
            </select>
            @if ($producto->porcentajeOferta())
                <small class="producto-oferta-descuento">−{{ $producto->porcentajeOferta() }}% · ${{ number_format($producto->precioOferta(), 2, ',', '.') }}</small>
            @endif
        </td>
        <td class="producto-acciones" role="cell">
            <div class="producto-actions">
                <a href="{{ route('producto.edit', ['id' => $producto->id]) }}" class="btn btn-sm btn-outline-primary" aria-label="Editar producto {{ $producto->codigo }}">
                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i><span class="producto-action-text">Editar</span>
                </a>
                <form action="{{ route('producto.delete', ['id' => $producto->id]) }}" method="POST" class="form-eliminar-producto" data-nombre="{{ $producto->nombre }}" data-codigo="{{ $producto->codigo }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Eliminar producto {{ $producto->codigo }}">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="producto-action-text">Eliminar</span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr class="productos-empty-row" role="row">
        <td colspan="8" role="cell">
            <div class="productos-empty">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <strong>No hay productos para mostrar</strong>
                <span>Probá con otro código, cambiá la categoría o sacá el filtro «Sólo ofertas».</span>
            </div>
        </td>
    </tr>
@endforelse
