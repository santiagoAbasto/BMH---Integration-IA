<div class="table-responsive">

    <table class="table">
        <thead style="border: 1px solid #D4D4D4 !important;background: #EBEBEB !important; height: 60px !important;">
            <tr class="contenido">
                <th scope="col"></th>
                <th scope="col">Artículo</th>
                <th scope="col" style="text-align: start">Descripción</th>
                <th scope="col">P. lista</th>
                @if (Auth::guard('web')->user()->descuento > 0)
                    <th scope="col">Descuento</th>
                @endif
                {{-- <th scope="col">P.Neto</th> --}}
                <th scope="col">P.Venta</th>
                <th scope="col">Cantidad</th>
                <th scope="col" style="width: 150px; text-align: end">Subtotal</th>
                <th scope="col" style="width: 100px"></th>


            </tr>
        </thead>
        <tbody>
            @foreach ($productos as $producto)
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
                            -{{ $producto->descuento }}%

                        </td>
                    @endif


                    {{-- <td style="padding-top: 25px; text-align: end !important">
                        ${{ $producto->precio_neto() }}

                    </td> --}}

                    <td class="reventa{{ $producto->id }}" style="padding-top: 25px; text-align: end !important">
                        ${{ $producto->precio_reventa() }}

                    </td>

                    <td style="padding-top: 18px; padding-left: 35px">
                        <div class='cantidad' style="width: 80px !important;">
                            <div>
                                <span class="addC" onclick="sumar_restar('restar', '{{ $producto->id }}')">-</span>
                            </div>
                            <div class='cantidad-contador{{ $producto->id }}' style='width:auto;'>1</div>
                            <div>
                                <span class="addC" onclick="sumar_restar('sumar', '{{ $producto->id }}')">+</span>

                            </div>

                        </div>
                    </td>

                    <td style="padding-top: 25px; text-align: end !important">
                        <div class='fila col-1 monitor subtotal{{ $producto->id }}'
                            style='text-align:end; width: 100%;'>
                            <div>
                                $
                                {{ $producto->precio_unitario_descontado_format() }}</div>
                        </div>
                    </td>

                    <td style="padding-top: 18px;">
                        @include('frontend.components.add-btn')

                    </td>

                </tr>
            @endforeach

        </tbody>
    </table>

</div>


@include('frontend.components.modalCarrito')




@include('frontend.components.paginacion', ['productos' => $productos])

@section('script')
    <script>
        function sumar_restar(tipo, id) {

            var cantidad = document.querySelector('.cantidad-contador' + id)
            if (tipo == 'sumar') {
                var resultado = parseInt(cantidad.innerText) + 1
            } else {
                var resultado = parseInt(cantidad.innerText) - 1
            }
            if (resultado > 0) {
                cantidad.innerText = resultado
                $.ajax({
                    url: "{{ route('actualizar.subtotal') }}",
                    type: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        cantidad: resultado,
                    },
                    success: function(response) {
                        console.log(response)
                        document.querySelector('.subtotal' + id).innerText = '$' + response.total

                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

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


        var precioVisible = true;



















        $('.vistaPublica').on('click', () => {
            let preciosVenta = document.querySelectorAll('td[class^="reventa"]');
            var encabezadoPrecioVenta = document.querySelectorAll('thead th');




            encabezadoPrecioVenta.forEach(function(th) {
                if (th.innerText.trim() === 'P.Venta') {
                    th.style.display = precioVisible ? 'none' : 'table-cell';
                }
            });

            preciosVenta.forEach(function(precio) {
                if (precioVisible) {
                    precio.style.display = 'none'; // Ocultar
                } else {
                    precio.style.display = 'table-cell'; // Mostrar
                }
            });

            if (precioVisible) {
                $('#vistaP').val('OFF') // Ocultar
            } else {
                $('#vistaP').val('ON') // Ocultar
            }


            precioVisible = !precioVisible





        })



        var marcas = @json($marcas);
        var marcaSelect = document.getElementById('marca');
        var modeloSelect = document.getElementById('modelo');

        if (marcaSelect && modeloSelect) marcaSelect.addEventListener('change', function() {
            modeloSelect.disabled = false;


            var marcaSeleccionada = marcaSelect.value;

            // Limpiar modelos anteriores
            modeloSelect.innerHTML = '<option value="">Selecciona un modelo</option>';

            if (marcaSeleccionada && marcas[marcaSeleccionada]) {
                var modelos = marcas[marcaSeleccionada];
                for (var modelo of modelos) {
                    var option = document.createElement('option');
                    option.value = modelo;
                    option.textContent = modelo;
                    modeloSelect.appendChild(option);
                }
            }
        });



        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";
    </script>
@endsection
