<div class="table-responsive">

    <table class="table">
        <thead style="border: 1px solid #D4D4D4 !important;
background: #EBEBEB !important; height: 60px !important;">
            <tr class="contenido">
                <th scope="col"></th>
                <th scope="col">Artículo</th>
                <th scope="col" style="text-align: start">Descripción</th>
                <th scope="col">P. lista</th>
                @if (Auth::guard('web')->user()->descuento > 0)
                    <th scope="col">Descuento</th>
                @endif
                <!--<th scope="col">P.Venta</th>-->
                <th scope="col" style="text-align: start; width: 150px">Cantidad</th>
                <th scope="col" style="text-align: end; width: 100px">Subtotal</th>
                <th scope="col" style="width: 20px"></th>


            </tr>
        </thead>
        <tbody>
            @foreach ($cart as $item)
                {{-- Un producto borrado del admin no rompe el carrito (CarritoCompartido lo saca). --}}
                @php($producto = App\Models\Producto::find($item->id))
                @continue(! $producto)
                <tr class="contenidoTablaTwo">

                    <td style="cursor: pointer">
                        @if ($producto->portada() && $producto->portada()->path)
                            <div class='producto-img'
                                onclick="abrirModalProducto(
                '{{ $producto->nombre }}', 
                '{{ asset('imagenes/' . $producto->portada()->path) }}', 
                '{{ $producto->descripcion }}',
                                                    '{{ $producto->codigo }}',

                {{ json_encode($producto->fichaCaracteristicas()) }}
            )"
                                style="background-image:url({{ asset('imagenes/' . $producto->portada()->path) }});background-size:contain;background-position:center;background-repeat:no-repeat;">
                            </div>
                        @else
                            <div class='producto-img'
                                onclick="abrirModalProducto(
                '{{ $producto->nombre }}', 
                '{{ App\Models\Producto::imagenPredeterminadaUrl() }}',
                '{{ $producto->descripcion }}',
                                                    '{{ $producto->codigo }}',

                {{ json_encode($producto->fichaCaracteristicas()) }}
            )"
                                style="background-image:url({{ App\Models\Producto::imagenPredeterminadaUrl() }});background-size:contain;background-position:center;background-repeat:no-repeat;">
                            </div>
                        @endif
                    </td>
                    <td style="padding-top: 25px; text-align: start !important">
                        {{ $producto->codigo }}

                    </td>

                    <td style="padding-top: 25px; text-align: start !important">
                        <span class="textoProductoLink">
                            {{ ucfirst($producto->nombre) }}
                        </span>
                    </td>

                    <td style="padding-top: 25px; text-align: end !important">
                        ${{ number_format($producto->precio(), 2, ',', '.') }}

                    </td>

                    @if (Auth::guard('web')->user()->descuento > 0)
                        <td class="descuentoTexto" style="padding-top: 25px; text-align: end !important">
                            -{{ Auth::guard('web')->user()->descuento }}%

                        </td>
                    @endif



                    <!--<td style="padding-top: 25px; text-align: end !important">-->
                    <!--    ${{ $producto->precio_reventa() }}-->

                    <!--</td>-->


                    <td style="padding-top: 18px;">
                        <div class='cantidad' style="width: 88px !important;">
                            <div>
                                <span class="addC"
                                    onclick="carrito_restar('{{ $item->rowId }}', 1, 'private', '{{ $item->qty }}')">-</span>
                            </div>
                            <div class='cantidad-contador{{ $producto->id }}'
                                data-cantidad={{ $producto->unidadVenta }}
                                data-subtotal1={{ $producto->precio() * $producto->unidadVenta }} style='width:auto;'>
                                {{ $item->qty }}</div>
                            <div>
                                <span class="addC"
                                    onclick="carrito_sumar('{{ $item->rowId }}', 1, 'private' )">+</span>

                            </div>

                        </div>
                    </td>



                    <td style="padding-top: 25px; text-align: end !important">
                        <div class='fila col-1 monitor subtotal{{ $producto->id }}'
                            style='text-align:end; width: 100%;'>

                            ${{ number_format($item->subtotal, 2, ',', '.') }}



                        </div>
                    </td>

                    <td style="padding-top: 18px;">
                        <div class='fila col-lg-1' style='text-align:center;'>
                            @include('frontend.components.remove-btn', ['seccion' => 'private'])
                        </div>
                    </td>

                </tr>
            @endforeach

        </tbody>
    </table>

</div>

@include('frontend.components.modalCarrito')

@section('script')
    <script>
        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";
    </script>
    <script>
        function abrirModalProducto(nombre, imagen, descripcion, codigo, caracteristicas) {
            $('#productoModalLabel').text(nombre);
            $('#productoModalImg').css('background-image', `url('${imagen}')`);
            $('#productoModalDescripcion').html(descripcion);
            $('#codigo').text(codigo);

            // Las características que declara la categoría del producto, en su orden: «NOMBRE: valor».
            const lista = document.getElementById('productoModalCaracteristicas');
            lista.replaceChildren();
            (caracteristicas || []).forEach(({ nombre, valor }) => {
                const fila = document.createElement('div');
                const etiqueta = document.createElement('span');
                etiqueta.className = 'nR';
                etiqueta.style.paddingLeft = '0';
                etiqueta.textContent = `${nombre}: `;
                const dato = document.createElement('span');
                dato.className = 'infoR';
                dato.textContent = valor;
                fila.append(etiqueta, dato);
                lista.append(fila);
            });

            bmhAbrirModal('productoModal');
        }

        function cerrarModal() {
            bmhCerrarModal('productoModal');

        }
    </script>
@endsection


<script>
    // El carrito del servidor manda: se guarda en la base por cliente
    // (CarritoCompartido) y es el mismo en todos sus dispositivos. La copia
    // de localStorage es de cada navegador y podía mostrar uno viejo.
    window.cart = @json($cart);
</script>
