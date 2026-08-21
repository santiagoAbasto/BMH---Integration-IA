<style>
    .hotspot{
        opacity: 0.8;
        transition: opacity ease 0.3s;
    }
    .hotspot:hover{
        opacity: 1;
    }
    .hotspot {
        cursor: pointer;
        animation: titilar 2s ease-in-out infinite; /* Animación de titilación */
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5); /* Sombra blanca */
    }
    .hotspot:hover {
        animation: none; /* Pausa la animación al hacer hover */
    }
    @keyframes titilar {
        0% {
            background-color: transparent; /* Color inicial (negro) */
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5); /* Sombra blanca */
        }
        50% {
            background-color: #fff; /* Color intermedio (blanco) */
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.7); /* Sombra blanca más intensa */
        }
        100% {
            background-color: transparent; /* Color final (negro) */
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5); /* Sombra blanca */
        }
    }

</style>
<?php
$producto = App\Models\Producto::find(strval($imagen->baner_texto));
?>
<a href="{{$producto != null ? route('producto', ['id' => $producto->id]) : ''}}">
    <div id='hotspot{{$imagen->id}}' data-id='{{$imagen->id}}' class='hotspot' style='
    background-image:url("{{asset('imagenes/iconos/hotspot.png')}}");background-position:center;background-size:contain;z-index:100;
    position:absolute;border-radius:50%;height:50px;width:50px;top:{{$imagen->top()}}%;left:{{$imagen->left()}}%' 
    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{$producto != null ? $producto->nombre : ''}}" data-bs-placement="bottom"></div>
</a>
