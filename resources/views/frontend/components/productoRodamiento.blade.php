<div class='productoRodamiento'>
    <a href="{{ route('producto', ['id' => $producto->id]) }}" >
        @if($producto->estado == 0)
        <div class="d-flex justify-content-start p-2">
         
         
        

   

        </div>
     
        @else
        <div class="d-flex justify-content-between p-2">
            <div class="col-lg-6">
                @if($producto->estado == 1)
                <span class="textoNuevo" style="color: #ABD430">Nuevo</span>
                @elseif ($producto->estado == 2)
                <span class="textoNuevo">Reconstruido</span>
                @endif
            </div>
     
   

        </div>
        @endif
        <div class="p-4 hoverGradient">
            
            @if ($producto->portada() && $producto->portada()->path)
            <div class='producto-portada' style='position: relative; background-image: url("{{ asset('imagenes/'.$producto->portada()->path) }}"); background-size: contain; background-position: center; background-repeat: no-repeat;'>
                <div class="overlayThree">
                    <svg xmlns="http://www.w3.org/2000/svg" width="39" height="37" viewBox="0 0 39 37" fill="none">
                        <rect width="39" height="37" fill="#FCFCFC" fill-opacity="0.8"/>
                        <path d="M29.6781 26.8921L24.9175 22.1315C26.0637 20.6057 26.6824 18.7485 26.6803 16.8402C26.6803 11.9657 22.7146 8 17.8402 8C12.9657 8 9 11.9657 9 16.8402C9 21.7146 12.9657 25.6803 17.8402 25.6803C19.7485 25.6824 21.6057 25.0637 23.1315 23.9175L27.8921 28.6781C28.1331 28.8936 28.4474 29.0085 28.7705 28.9995C29.0936 28.9905 29.401 28.8581 29.6295 28.6295C29.8581 28.401 29.9905 28.0936 29.9995 27.7705C30.0085 27.4474 29.8936 27.1331 29.6781 26.8921ZM11.5258 16.8402C11.5258 15.5913 11.8961 14.3705 12.5899 13.3321C13.2838 12.2937 14.2699 11.4843 15.4237 11.0064C16.5775 10.5285 17.8472 10.4034 19.072 10.6471C20.2969 10.8907 21.422 11.4921 22.3051 12.3752C23.1882 13.2583 23.7896 14.3834 24.0332 15.6083C24.2769 16.8332 24.1518 18.1028 23.6739 19.2566C23.196 20.4104 22.3867 21.3966 21.3483 22.0904C20.3099 22.7842 19.089 23.1546 17.8402 23.1546C16.1661 23.1526 14.5612 22.4866 13.3774 21.3029C12.1937 20.1192 11.5278 18.5142 11.5258 16.8402Z" fill="#6B6B6B"/>
                    </svg>
                </div>
            </div>
            @else
            <div class='producto-portada' style='position: relative; background-image: url("{{ asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}"); background-size: contain; background-position: center; background-repeat: no-repeat;'>
                <div class="overlayThree">
                    <svg xmlns="http://www.w3.org/2000/svg" width="39" height="37" viewBox="0 0 39 37" fill="none">
                        <rect width="39" height="37" fill="#FCFCFC" fill-opacity="0.8"/>
                        <path d="M29.6781 26.8921L24.9175 22.1315C26.0637 20.6057 26.6824 18.7485 26.6803 16.8402C26.6803 11.9657 22.7146 8 17.8402 8C12.9657 8 9 11.9657 9 16.8402C9 21.7146 12.9657 25.6803 17.8402 25.6803C19.7485 25.6824 21.6057 25.0637 23.1315 23.9175L27.8921 28.6781C28.1331 28.8936 28.4474 29.0085 28.7705 28.9995C29.0936 28.9905 29.401 28.8581 29.6295 28.6295C29.8581 28.401 29.9905 28.0936 29.9995 27.7705C30.0085 27.4474 29.8936 27.1331 29.6781 26.8921ZM11.5258 16.8402C11.5258 15.5913 11.8961 14.3705 12.5899 13.3321C13.2838 12.2937 14.2699 11.4843 15.4237 11.0064C16.5775 10.5285 17.8472 10.4034 19.072 10.6471C20.2969 10.8907 21.422 11.4921 22.3051 12.3752C23.1882 13.2583 23.7896 14.3834 24.0332 15.6083C24.2769 16.8332 24.1518 18.1028 23.6739 19.2566C23.196 20.4104 22.3867 21.3966 21.3483 22.0904C20.3099 22.7842 19.089 23.1546 17.8402 23.1546C16.1661 23.1526 14.5612 22.4866 13.3774 21.3029C12.1937 20.1192 11.5278 18.5142 11.5258 16.8402Z" fill="#6B6B6B"/>
                    </svg>
                </div>
            </div>
            @endif
        </div>
        <div class='producto-texto-rodamiento d-flex flex-column justify-content-start'>
            <div class="d-flex flex-column">
                            <span class="textoCodigo">Codigo: {{ $producto->codigo }}</span>
                <span class="textoTitulo">{{ $producto->nombre }}</span>
            </div>

            <div class="row" style='margin-top: 20px; overflow-y: auto; max-height: 300px;'>
                <div class="col-lg-12">
                    @for ($i = 1; $i <= 78; $i++)
                    @php
                        $columna = "columna_$i";
                    @endphp
                
                    @if ($producto->$columna)


                    @if($producto->categoria()->first()->$columna)
                    <div class="d-flex">
                        <span class="infoR">{{ $producto->categoria()->first()->$columna }}:</span>
                        <span class="nR">{{ $producto->$columna }}</span>
                    </div>
                    @endif
                    @endif
  
                @endfor
                
       
               {{-- Características dinámicas --}}
@foreach ($producto->productCaracteristicas as $pc)
    @continue(blank($pc->valor)) {{-- salta vacíos --}}
    <div class="d-flex">
        <span class="infoR">{{ $pc->caracteristica->nombre }}:</span>
        <span class="nR">{{ $pc->valor }}</span>
    </div>
@endforeach

    

                
                
            @if ($producto->marca)
            <div class="d-flex">
                <span class="infoR">Marca:</span>
                <span class="nR">{{ $producto->marca }}</span>
            </div>
            @endif

            @if (Auth::guard('web')->check())
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
    
        
    
    
            </div>

            @endif
                </div>


            </div>

           
        </div>
    </a>
</div>
