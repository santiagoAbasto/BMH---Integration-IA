<div class='productoBmh cursor-pointer' style="cursor: pointer" onclick="window.location='{{ route('producto', ['id' => $producto->id]) }}'">

    <div class="p-4 hoverGradient col-lg-4">

        @if ($producto->portada() && $producto->portada()->path)
            <div class='producto-portada'
                style='position: relative; background-image: url("{{ asset('imagenes/' . $producto->portada()->path) }}"); background-size: cover; background-position: center; background-repeat: no-repeat; height: 70% !important;'>
                <div class="overlayThree">
                    <svg xmlns="http://www.w3.org/2000/svg" width="39" height="37" viewBox="0 0 39 37" fill="none">
                        <rect width="39" height="37" fill="#FCFCFC" fill-opacity="0.8" />
                        <path
                            d="M29.6781 26.8921L24.9175 22.1315C26.0637 20.6057 26.6824 18.7485 26.6803 16.8402C26.6803 11.9657 22.7146 8 17.8402 8C12.9657 8 9 11.9657 9 16.8402C9 21.7146 12.9657 25.6803 17.8402 25.6803C19.7485 25.6824 21.6057 25.0637 23.1315 23.9175L27.8921 28.6781C28.1331 28.8936 28.4474 29.0085 28.7705 28.9995C29.0936 28.9905 29.401 28.8581 29.6295 28.6295C29.8581 28.401 29.9905 28.0936 29.9995 27.7705C30.0085 27.4474 29.8936 27.1331 29.6781 26.8921ZM11.5258 16.8402C11.5258 15.5913 11.8961 14.3705 12.5899 13.3321C13.2838 12.2937 14.2699 11.4843 15.4237 11.0064C16.5775 10.5285 17.8472 10.4034 19.072 10.6471C20.2969 10.8907 21.422 11.4921 22.3051 12.3752C23.1882 13.2583 23.7896 14.3834 24.0332 15.6083C24.2769 16.8332 24.1518 18.1028 23.6739 19.2566C23.196 20.4104 22.3867 21.3966 21.3483 22.0904C20.3099 22.7842 19.089 23.1546 17.8402 23.1546C16.1661 23.1526 14.5612 22.4866 13.3774 21.3029C12.1937 20.1192 11.5278 18.5142 11.5258 16.8402Z"
                            fill="#6B6B6B" />
                    </svg>
                </div>
            </div>
        @else
            <div class='producto-portada'
                style='position: relative; background-image: url("{{ asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}"); background-size: cover; background-position: center; background-repeat: no-repeat;'>
                <div class="overlayThree">
                    <svg xmlns="http://www.w3.org/2000/svg" width="39" height="37" viewBox="0 0 39 37"
                        fill="none">
                        <rect width="39" height="37" fill="#FCFCFC" fill-opacity="0.8" />
                        <path
                            d="M29.6781 26.8921L24.9175 22.1315C26.0637 20.6057 26.6824 18.7485 26.6803 16.8402C26.6803 11.9657 22.7146 8 17.8402 8C12.9657 8 9 11.9657 9 16.8402C9 21.7146 12.9657 25.6803 17.8402 25.6803C19.7485 25.6824 21.6057 25.0637 23.1315 23.9175L27.8921 28.6781C28.1331 28.8936 28.4474 29.0085 28.7705 28.9995C29.0936 28.9905 29.401 28.8581 29.6295 28.6295C29.8581 28.401 29.9905 28.0936 29.9995 27.7705C30.0085 27.4474 29.8936 27.1331 29.6781 26.8921ZM11.5258 16.8402C11.5258 15.5913 11.8961 14.3705 12.5899 13.3321C13.2838 12.2937 14.2699 11.4843 15.4237 11.0064C16.5775 10.5285 17.8472 10.4034 19.072 10.6471C20.2969 10.8907 21.422 11.4921 22.3051 12.3752C23.1882 13.2583 23.7896 14.3834 24.0332 15.6083C24.2769 16.8332 24.1518 18.1028 23.6739 19.2566C23.196 20.4104 22.3867 21.3966 21.3483 22.0904C20.3099 22.7842 19.089 23.1546 17.8402 23.1546C16.1661 23.1526 14.5612 22.4866 13.3774 21.3029C12.1937 20.1192 11.5278 18.5142 11.5258 16.8402Z"
                            fill="#6B6B6B" />
                    </svg>
                </div>
            </div>
        @endif
    </div>
    <div style="background: transparent !important"
        class='producto-texto-bmh d-flex flex-column justify-content-between col-lg-8'>

        <div class="d-flex flex-column">
            <div class="d-flex justify-content-between">
                <div class="col-lg-6">
                    <span class="textoTitulo"> {{ $producto->codigo }}</span>
                </div>
                <div class="col-lg-6 text-end" style="padding-right: 10px; color: #0098DA">
                    <a href="{{ route('producto', ['id' => $producto->id]) }}">Ver producto</a>

                </div>

            </div>
            <span class="textoTitulo">{{ $producto->nombre }}</span>
        </div>


        {{-- @if ($producto->estado == 0)
            <div class="d-flex justify-content-start p-2">
             
                <div class="d-flex justify-content-start col-lg-6" >
                    <label class="textoCodigoDown">Código:</label>
                    <span class="textoCodigo"> {{ $producto->codigo }}</span>
                </div>
            
    
       
    
            </div>
         
            @else
            <div class="d-flex justify-content-between p-2">
                <div class="col-lg-6">
                    @if ($producto->estado == 1)
                    <span class="textoNuevo" style="color: #ABD430">Nuevo</span>
                    @elseif ($producto->estado == 2)
                    <span class="textoNuevo">Reconstruido</span>
                    @endif
                </div>
                <div class="d-flex justify-content-start col-lg-6" style="padding-right: 14px">
                    <label class="textoCodigoDown">Código:</label>
                    <span class="textoCodigo"> {{ $producto->codigo }}</span>
    
    
                </div>
       
    
            </div>
            @endif --}}


        <div class="row mt-3">
            <div class="col-lg-12" style="max-height: 200px; overflow-y: auto;">
                @for ($i = 1; $i <= 78; $i++)
                    @php
                        $columna = "columna_$i";
                        $backgroundClass = 'background-color: #E2EAFF !important;';

                    @endphp

                    @if ($producto->$columna)
                        @if ($producto->categoria()->first()->$columna)
                            <div class="d-flex" style="{{ $backgroundClass }}">
                                <span class="infoR">{{ $producto->categoria()->first()->$columna }}:</span>
                                <span class="nR">{{ $producto->$columna }}</span>
                            </div>
                        @endif
                    @endif

                @endfor
                
                    @if($producto->productCaracteristicas->isNotEmpty())
        @foreach($producto->productCaracteristicas as $caracteristica)
            <div class="d-flex">
                <span class="infoR">{{ $caracteristica->caracteristica->nombre ?? 'Nombre no disponible' }}:</span>
                <span class="nR">{{ $caracteristica->valor ?? 'Valor no disponible' }}</span>
            </div>
        @endforeach
   
    @endif
                
                
                @if ($producto->marca)
                    <div class="d-flex">
                        <span class="infoR">Marca:</span>
                        <span class="nR">{{ $producto->marca }}</span>
                    </div>
                @endif
                
                        @if ($producto->modelo)
                    <div class="d-flex">
                        <span class="infoR">Modelo:</span>
                        <span class="nR">{{ $producto->modelo }}</span>
                    </div>
                @endif
            </div>


        </div>


        <div class="col-lg-12">


            <div class="d-flex" style="width: 250px;">
                <div class="col-lg-8">
                    <span>
                        Precio Lista:
                    </span>

                </div>
                <div class="col-lg-6" style='text-align:end;'>
                    <span>
                        ${{ number_format($producto->precio(), 2, ',', '.') }}
                    </span>

                </div>

            </div>
            @if ($producto->descuento > 0)
                <div class="d-flex" style=" width: 250px;">
                    <div class="col-lg-8">
                        <span>
                            Descuento producto:
                        </span>

                    </div>
                    <div class="col-lg-6" style='text-align:end; '>
                        <span>
                            -{{ $producto->descuento }}%
                        </span>

                    </div>

                </div>


                <div class="d-flex" style=" width: 250px;">
                    <div class="col-lg-8">
                        <span>
                            Precio con descuento:
                        </span>

                    </div>
                    <div class="col-lg-6" style='text-align:end; '>
                        <span>
                            ${{ number_format($producto->precio_final(), 2, ',', '.') }}
                        </span>

                    </div>

                </div>
            @endif

            @if (Auth::guard('web')->user()->descuento > 0)
                <div class="d-flex" style=" width: 250px;">
                    <div class="col-lg-8">
                        <span>
                            Descuento cliente:
                        </span>

                    </div>
                    <div class="col-lg-6" style='text-align:end; '>
                        <span>
                            -{{ Auth::guard('web')->user()->descuento }}%
                        </span>

                    </div>

                </div>


                <div class="d-flex" style=" width: 250px;">
                    <div class="col-lg-8">
                        <span>
                            Precio con descuento:
                        </span>

                    </div>
                    <div class="col-lg-6" style='text-align:end; '>
                        <span>
                            ${{ number_format($producto->precio_unitario_descontado(), 2, ',', '.') }}
                        </span>

                    </div>

                </div>
            @endif

            <div class="d-flex" style="width: 250px;">
                <div class="col-lg-8">
                    <span>
                        Precio reventa:
                    </span>

                </div>
                <div class="col-lg-6" style='text-align:end; '>
                    <span>
                        ${{ $producto->precio_reventa() }}
                    </span>

                </div>

            </div>

            <!--<div class="d-flex" style="padding-top: 15px; width: 250px;">-->
            <!--    <div class="col-lg-8">-->
            <!--        <span>-->
            <!--            Subtotal-->



            <!--            :-->
            <!--        </span>-->

            <!--    </div>-->
            <!--    <div class="col-lg-6">-->
            <!--        <div class='fila col-1 monitor subtotal{{ $producto->id }}' style='text-align:end; width: 100%;'>-->
            <!--            <div>-->

            <!--                ${{ number_format($producto->precio_unitario_descontado(), 2, ',', '.') }}-->
            <!--            </div>-->
            <!--        </div>-->

            <!--    </div>-->

            <!--</div>-->


        </div>

        <div class="col-lg-12 d-flex justify-content-between">
            <div class='cantidad' style="width: 80px !important;">
                <div>
                    <span class="addC" onclick="event.stopPropagation(); sumar_restar('restar', '{{ $producto->id }}')">-</span>
                </div>
                <div class='cantidad-contador{{ $producto->id }}' style='width:auto;'>1</div>
                <div>
                    <span class="addC" onclick="event.stopPropagation(); sumar_restar('sumar', '{{ $producto->id }}')">+</span>

                </div>

            </div>

            <button class='carrito-btn' style="margin-right: 30px"
                onclick="event.stopPropagation(); agregar_carrito_publico('{{ $producto->id }}', {{ $producto->precio_unitario_descontado() }})">
                SUMAR AL CARRITO <svg xmlns="http://www.w3.org/2000/svg" width="15" height="17"
                    viewBox="0 0 15 17" fill="none">
                    <path
                        d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z"
                        fill="#0098DA" />
                </svg>
            </button>

        </div>



    </div>
</div>
@section('script')
    <script>
        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";


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
                        console.log(response, '?')
                        document.querySelector('.subtotal' + id).innerText = '$' + response.total
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }
    </script>
@endsection
