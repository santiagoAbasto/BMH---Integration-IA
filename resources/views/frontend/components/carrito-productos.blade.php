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
                <tr class="contenidoTablaTwo">
                    <?php
                    $producto = App\Models\Producto::find($item->id);
                    ?>

                    <td style="cursor: pointer">
                        @if ($producto->portada() && $producto->portada()->path)
                            <div class='producto-img'
                                onclick="abrirModalProducto(
                '{{ $producto->nombre }}', 
                '{{ asset('imagenes/' . $producto->portada()->path) }}', 
                '{{ $producto->descripcion }}',
                                                    '{{ $producto->codigo }}',

                {{ json_encode([
                    $producto->columna_1,
                    $producto->columna_2,
                    $producto->columna_3,
                    $producto->columna_4,
                    $producto->columna_5,
                    $producto->columna_6,
                    $producto->columna_7,
                    $producto->columna_8,
                    $producto->columna_9,
                    $producto->columna_10,
                    $producto->columna_11,
                    $producto->columna_12,
                    $producto->columna_13,
                    $producto->columna_14,
                    $producto->columna_15,
                    $producto->columna_16,
                    $producto->columna_17,
                    $producto->columna_18,
                    $producto->columna_19,
                    $producto->columna_20,
                    $producto->columna_21,
                    $producto->columna_22,
                    $producto->columna_23,
                    $producto->columna_24,
                    $producto->columna_25,
                    $producto->columna_26,
                    $producto->columna_27,
                    $producto->columna_28,
                    $producto->columna_29,
                    $producto->columna_30,
                    $producto->columna_31,
                    $producto->columna_32,
                    $producto->columna_33,
                    $producto->columna_34,
                    $producto->columna_35,
                    $producto->columna_36,
                    $producto->columna_37,
                    $producto->columna_38,
                    $producto->columna_39,
                    $producto->columna_40,
                    $producto->columna_41,
                    $producto->columna_42,
                    $producto->columna_43,
                    $producto->columna_44,
                    $producto->columna_45,
                    $producto->columna_46,
                    $producto->columna_47,
                    $producto->columna_48,
                    $producto->columna_49,
                    $producto->columna_50,
                    $producto->columna_51,
                    $producto->columna_52,
                    $producto->columna_53,
                    $producto->columna_54,
                    $producto->columna_55,
                    $producto->columna_56,
                    $producto->columna_57,
                    $producto->columna_58,
                    $producto->columna_59,
                    $producto->columna_60,
                    $producto->columna_61,
                    $producto->columna_62,
                    $producto->columna_63,
                    $producto->columna_64,
                    $producto->columna_65,
                    $producto->columna_66,
                    $producto->columna_67,
                    $producto->columna_68,
                    $producto->columna_69,
                    $producto->columna_70,
                    $producto->columna_71,
                    $producto->columna_72,
                    $producto->columna_73,
                    $producto->columna_74,
                    $producto->columna_75,
                    $producto->columna_76,
                    $producto->columna_77,
                    $producto->columna_78,
                ]) }},
                {{ json_encode([
                    $producto->categoria()->first()->columna_1,
                    $producto->categoria()->first()->columna_2,
                    $producto->categoria()->first()->columna_3,
                    $producto->categoria()->first()->columna_4,
                    $producto->categoria()->first()->columna_5,
                    $producto->categoria()->first()->columna_6,
                    $producto->categoria()->first()->columna_7,
                    $producto->categoria()->first()->columna_8,
                    $producto->categoria()->first()->columna_9,
                    $producto->categoria()->first()->columna_10,
                    $producto->categoria()->first()->columna_11,
                    $producto->categoria()->first()->columna_12,
                    $producto->categoria()->first()->columna_13,
                    $producto->categoria()->first()->columna_14,
                    $producto->categoria()->first()->columna_15,
                    $producto->categoria()->first()->columna_16,
                    $producto->categoria()->first()->columna_17,
                    $producto->categoria()->first()->columna_18,
                    $producto->categoria()->first()->columna_19,
                    $producto->categoria()->first()->columna_20,
                    $producto->categoria()->first()->columna_21,
                    $producto->categoria()->first()->columna_22,
                    $producto->categoria()->first()->columna_23,
                    $producto->categoria()->first()->columna_24,
                    $producto->categoria()->first()->columna_25,
                    $producto->categoria()->first()->columna_26,
                    $producto->categoria()->first()->columna_27,
                    $producto->categoria()->first()->columna_28,
                    $producto->categoria()->first()->columna_29,
                    $producto->categoria()->first()->columna_30,
                    $producto->categoria()->first()->columna_31,
                    $producto->categoria()->first()->columna_32,
                    $producto->categoria()->first()->columna_33,
                    $producto->categoria()->first()->columna_34,
                    $producto->categoria()->first()->columna_35,
                    $producto->categoria()->first()->columna_36,
                    $producto->categoria()->first()->columna_37,
                    $producto->categoria()->first()->columna_38,
                    $producto->categoria()->first()->columna_39,
                    $producto->categoria()->first()->columna_40,
                    $producto->categoria()->first()->columna_41,
                    $producto->categoria()->first()->columna_42,
                    $producto->categoria()->first()->columna_43,
                    $producto->categoria()->first()->columna_44,
                    $producto->categoria()->first()->columna_45,
                    $producto->categoria()->first()->columna_46,
                    $producto->categoria()->first()->columna_47,
                    $producto->categoria()->first()->columna_48,
                    $producto->categoria()->first()->columna_49,
                    $producto->categoria()->first()->columna_50,
                    $producto->categoria()->first()->columna_51,
                    $producto->categoria()->first()->columna_52,
                    $producto->categoria()->first()->columna_53,
                    $producto->categoria()->first()->columna_54,
                    $producto->categoria()->first()->columna_55,
                    $producto->categoria()->first()->columna_56,
                    $producto->categoria()->first()->columna_57,
                    $producto->categoria()->first()->columna_58,
                    $producto->categoria()->first()->columna_59,
                    $producto->categoria()->first()->columna_60,
                    $producto->categoria()->first()->columna_61,
                    $producto->categoria()->first()->columna_62,
                    $producto->categoria()->first()->columna_63,
                    $producto->categoria()->first()->columna_64,
                    $producto->categoria()->first()->columna_65,
                    $producto->categoria()->first()->columna_66,
                    $producto->categoria()->first()->columna_67,
                    $producto->categoria()->first()->columna_68,
                    $producto->categoria()->first()->columna_69,
                    $producto->categoria()->first()->columna_70,
                    $producto->categoria()->first()->columna_71,
                    $producto->categoria()->first()->columna_72,
                    $producto->categoria()->first()->columna_73,
                    $producto->categoria()->first()->columna_74,
                    $producto->categoria()->first()->columna_75,
                    $producto->categoria()->first()->columna_76,
                    $producto->categoria()->first()->columna_77,
                    $producto->categoria()->first()->columna_78,
                ]) }}
            )"
                                style="background-image:url({{ asset('imagenes/' . $producto->portada()->path) }});background-size:contain;background-position:center;background-repeat:no-repeat;">
                            </div>
                        @else
                            <div class='producto-img'
                                onclick="abrirModalProducto(
                '{{ $producto->nombre }}', 
                '{{ asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}', 
                '{{ $producto->descripcion }}',
                                                    '{{ $producto->codigo }}',

                {{ json_encode([
                    $producto->columna_1,
                    $producto->columna_2,
                    $producto->columna_3,
                    $producto->columna_4,
                    $producto->columna_5,
                    $producto->columna_6,
                    $producto->columna_7,
                    $producto->columna_8,
                    $producto->columna_9,
                    $producto->columna_10,
                    $producto->columna_11,
                    $producto->columna_12,
                    $producto->columna_13,
                    $producto->columna_14,
                    $producto->columna_15,
                    $producto->columna_16,
                    $producto->columna_17,
                    $producto->columna_18,
                    $producto->columna_19,
                    $producto->columna_20,
                    $producto->columna_21,
                    $producto->columna_22,
                    $producto->columna_23,
                    $producto->columna_24,
                    $producto->columna_25,
                    $producto->columna_26,
                    $producto->columna_27,
                    $producto->columna_28,
                    $producto->columna_29,
                    $producto->columna_30,
                    $producto->columna_31,
                    $producto->columna_32,
                    $producto->columna_33,
                    $producto->columna_34,
                    $producto->columna_35,
                    $producto->columna_36,
                    $producto->columna_37,
                    $producto->columna_38,
                    $producto->columna_39,
                    $producto->columna_40,
                    $producto->columna_41,
                    $producto->columna_42,
                    $producto->columna_43,
                    $producto->columna_44,
                    $producto->columna_45,
                    $producto->columna_46,
                    $producto->columna_47,
                    $producto->columna_48,
                    $producto->columna_49,
                    $producto->columna_50,
                    $producto->columna_51,
                    $producto->columna_52,
                    $producto->columna_53,
                    $producto->columna_54,
                    $producto->columna_55,
                    $producto->columna_56,
                    $producto->columna_57,
                    $producto->columna_58,
                    $producto->columna_59,
                    $producto->columna_60,
                    $producto->columna_61,
                    $producto->columna_62,
                    $producto->columna_63,
                    $producto->columna_64,
                    $producto->columna_65,
                    $producto->columna_66,
                    $producto->columna_67,
                    $producto->columna_68,
                    $producto->columna_69,
                    $producto->columna_70,
                    $producto->columna_71,
                    $producto->columna_72,
                    $producto->columna_73,
                    $producto->columna_74,
                    $producto->columna_75,
                    $producto->columna_76,
                    $producto->columna_77,
                    $producto->columna_78,
                ]) }},
                {{ json_encode([
                    $producto->categoria()->first()->columna_1,
                    $producto->categoria()->first()->columna_2,
                    $producto->categoria()->first()->columna_3,
                    $producto->categoria()->first()->columna_4,
                    $producto->categoria()->first()->columna_5,
                    $producto->categoria()->first()->columna_6,
                    $producto->categoria()->first()->columna_7,
                    $producto->categoria()->first()->columna_8,
                    $producto->categoria()->first()->columna_9,
                    $producto->categoria()->first()->columna_10,
                    $producto->categoria()->first()->columna_11,
                    $producto->categoria()->first()->columna_12,
                    $producto->categoria()->first()->columna_13,
                    $producto->categoria()->first()->columna_14,
                    $producto->categoria()->first()->columna_15,
                    $producto->categoria()->first()->columna_16,
                    $producto->categoria()->first()->columna_17,
                    $producto->categoria()->first()->columna_18,
                    $producto->categoria()->first()->columna_19,
                    $producto->categoria()->first()->columna_20,
                    $producto->categoria()->first()->columna_21,
                    $producto->categoria()->first()->columna_22,
                    $producto->categoria()->first()->columna_23,
                    $producto->categoria()->first()->columna_24,
                    $producto->categoria()->first()->columna_25,
                    $producto->categoria()->first()->columna_26,
                    $producto->categoria()->first()->columna_27,
                    $producto->categoria()->first()->columna_28,
                    $producto->categoria()->first()->columna_29,
                    $producto->categoria()->first()->columna_30,
                    $producto->categoria()->first()->columna_31,
                    $producto->categoria()->first()->columna_32,
                    $producto->categoria()->first()->columna_33,
                    $producto->categoria()->first()->columna_34,
                    $producto->categoria()->first()->columna_35,
                    $producto->categoria()->first()->columna_36,
                    $producto->categoria()->first()->columna_37,
                    $producto->categoria()->first()->columna_38,
                    $producto->categoria()->first()->columna_39,
                    $producto->categoria()->first()->columna_40,
                    $producto->categoria()->first()->columna_41,
                    $producto->categoria()->first()->columna_42,
                    $producto->categoria()->first()->columna_43,
                    $producto->categoria()->first()->columna_44,
                    $producto->categoria()->first()->columna_45,
                    $producto->categoria()->first()->columna_46,
                    $producto->categoria()->first()->columna_47,
                    $producto->categoria()->first()->columna_48,
                    $producto->categoria()->first()->columna_49,
                    $producto->categoria()->first()->columna_50,
                    $producto->categoria()->first()->columna_51,
                    $producto->categoria()->first()->columna_52,
                    $producto->categoria()->first()->columna_53,
                    $producto->categoria()->first()->columna_54,
                    $producto->categoria()->first()->columna_55,
                    $producto->categoria()->first()->columna_56,
                    $producto->categoria()->first()->columna_57,
                    $producto->categoria()->first()->columna_58,
                    $producto->categoria()->first()->columna_59,
                    $producto->categoria()->first()->columna_60,
                    $producto->categoria()->first()->columna_61,
                    $producto->categoria()->first()->columna_62,
                    $producto->categoria()->first()->columna_63,
                    $producto->categoria()->first()->columna_64,
                    $producto->categoria()->first()->columna_65,
                    $producto->categoria()->first()->columna_66,
                    $producto->categoria()->first()->columna_67,
                    $producto->categoria()->first()->columna_68,
                    $producto->categoria()->first()->columna_69,
                    $producto->categoria()->first()->columna_70,
                    $producto->categoria()->first()->columna_71,
                    $producto->categoria()->first()->columna_72,
                    $producto->categoria()->first()->columna_73,
                    $producto->categoria()->first()->columna_74,
                    $producto->categoria()->first()->columna_75,
                    $producto->categoria()->first()->columna_76,
                    $producto->categoria()->first()->columna_77,
                    $producto->categoria()->first()->columna_78,
                ]) }}
            )"
                                style="background-image:url({{ asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }});background-size:contain;background-position:center;background-repeat:no-repeat;">
                            </div>
                        @endif
                    </td>
                    <td style="padding-top: 25px; text-align: start !important">
                        {{ $producto->codigo }}

                    </td>

                    <td style="padding-top: 25px; text-align: start !important">
                        <a class="textoProductoLink" href="{{ route('producto', ['id' => $producto->id]) }}">
                            {{ ucfirst($producto->nombre) }}
                        </a>
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
        function abrirModalProducto(nombre, imagen, descripcion, codigo, categoriaColumnas, productoColumnas) {
            $('#productoModalLabel').text(nombre);
            $('#productoModalImg').css('background-image', `url('${imagen}')`);
            $('#productoModalDescripcion').html(descripcion);
            $('#codigo').text(codigo);

            for (let i = 0; i < 38; i++) {
                const categoriaColumna = categoriaColumnas[i] || '';
                const productoColumna = productoColumnas[i] || '';

                const divColumna = document.getElementById(`divColumna${i + 1}`);
                const spanCategoria = divColumna.querySelector(`#categoriaColumna${i + 1}`);
                const spanProducto = divColumna.querySelector(`#productoColumna${i + 1}`);

                spanCategoria.innerText = categoriaColumna ? `${categoriaColumna}` : '';
                spanProducto.innerText = productoColumna ? `${productoColumna}:` : '';

                // Mostrar el div solo si hay datos para la columna
                divColumna.style.display = (categoriaColumna && productoColumna) ? 'block' : 'none';

            }


            $('#productoModal').modal('show');
        }

        function cerrarModal() {
            $('#productoModal').modal('hide');

        }
    </script>
@endsection


<script>
    let storedCart = localStorage.getItem('carrito');
    if (storedCart) {
        try {
            window.cart = JSON.parse(storedCart);
        } catch (e) {
            console.error('Error parsing cart:', e);
            window.cart = @json($cart); // Si hay error, usa el carrito de Laravel
        }
    } else {
        window.cart = @json($cart);
    }
</script>

<?php
// Sobrescribir $cart con lo que viene de JS
$cart = "<script>document.write(JSON.stringify(window.cart))</script>";
?>
