<div class='producto'>
    <a href="{{route('producto', ['id' => $producto->id])}}" >
        <div class='producto-portada' style='position: relative;background-image: url("{{asset('imagenes/'.$producto->portada()->path)}}"); background-size: contain; background-position: center;background-repeat:no-repeat;'>
            {{-- <div class='middle'>
                <div class='d-flex'>
                
                    <a href='{{route('producto', ['id' => $producto->id])}}' class='producto-btn btn-search'>
                       
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80" fill="none">
                            <circle cx="35" cy="35" r="35" fill="#236644"/>
                            <path d="M35.5907 34.4093H42V35.6203H35.5907V42H34.3797V35.6203H28V34.4093H34.3797V28H35.5907V34.4093Z" fill="white"/>
                          </svg>
                    </a>
                </div>
            </div> --}}
            {{-- <div class='producto-img-layer' style='
            position: absolute;
            width:100%;
            height:100%;
            '>
            </div> --}}
         
        </div>
        <div class='producto-texto d-flex flex-column justify-content-between'>
            <div class="line">
                <div class="linea">

                </div>

            </div>

                <div class='producto-nombre'>
                    {{ucfirst($producto->nombre)}}
                </div>
                
             

                <div class='producto-codigo'>
                    {{ucfirst($producto->codigo)}}
                </div>
                
        </div>
    </a>
</div>
    