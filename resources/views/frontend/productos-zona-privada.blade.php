@extends('layouts.plantilla-front')
{{-- 
@section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection --}}

@section('styles')
    <style>

        
        .checkbox-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        input[type="checkbox"] {
            display: none;
            /* Oculta el checkbox real */
        }

        .custom-checkbox {
  cursor: pointer;
  width: 200px;
  height: 40px;
  padding-right: 32px;
  padding-left: 32px;
  align-items: center;
  gap: 32px;
  flex-shrink: 0;
  border-radius: 10px;
border: 1px solid #4caf50;
color: #4caf50;
font-family: 'Montserrat';
font-size: 16px;
font-style: normal;
font-weight: 600;
line-height: normal;

background: linear-gradient(to right, #4caf50 0%, #4caf50 100%);
background-size: 0 100%;
background-repeat: no-repeat;
transition: background-size 0.5s ease, border-color 0.5s ease;

text-align: center;
padding-top: 10px;

  }

  .custom-checkbox:hover{

  
border-color: #4caf50;
background-color: #4caf50;
color: #fff !important;
background-size: 100% 100%;

}

input[type="checkbox"]:checked + .custom-checkbox {
    background-color: #4caf50; /* Color cuando está seleccionado */
    color: #fff; /* Color del texto cuando está seleccionado */
    font-weight: bold;
}

.custom-checkbox-2 {
  padding-top: 10px;

  cursor: pointer;
  text-align: center;
  width: 200px;
  height: 40px;
  padding-right: 32px;
  padding-left: 32px;
  align-items: center;
  gap: 32px;
  flex-shrink: 0;
  border-radius: 10px;
border: 1px solid #0098DA;
color: #0098DA;
font-family: 'Montserrat';
font-size: 16px;
font-style: normal;
font-weight: 600;
line-height: normal;

background: linear-gradient(to right, #0098DA 0%, #0098DA 100%);
background-size: 0 100%;
background-repeat: no-repeat;
transition: background-size 0.5s ease, border-color 0.5s ease;

  
  }

  .custom-checkbox-2:hover{

  
border-color: #0098DA;
background-color: #0098DA;
color: #fff !important;
background-size: 100% 100%;

}

        input[type="checkbox"]:checked+.custom-checkbox-2 {
            background-color: #0098DA;
            /* Color cuando está seleccionado */
            color: #fff;
            /* Color del texto cuando está seleccionado */
            font-weight: bold;
        }

        .modal-dialog {
            max-width: 500px !important;
            width: 500px !important;
        }


        .reventa {
            width: 54px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 10px;
            border: 1px solid #EBEBEB;
            background: #FFF;
            margin-left: 46px;
            color: #000;
            font-family: Montserrat;
            font-size: 16px;
            font-style: normal;
            font-weight: 400;
            line-height: normal;
            text-align: center
        }

        .tabla .fila {
            border: 1px #D4D4D4 !important;
            align-content: center;
            padding-top: 12.5px;
            padding-bottom: 12.5px;
            font-size: 15px;
        }

        .cabecera {
            background-color: #EFEFEF;
            height: 52px;
            font-weight: 300;
        }

        .producto-img {
            width: 35px !important;
            height: 38px !important;
            flex-shrink: 0;

        }

        .modal-dialog {
            max-width: 80vw;
        }

        .modal-content {
            padding: 29px 40px 29px 40px;
        }

        .modal-nombre {
            font-size: 24px;
            font-style: normal;
            font-weight: 400;
            line-height: 140%;
            /* 33.6px */
            width: 261px;
        }

        .tabla-medidas {
            margin-top: 29px;
            margin-bottom: 27px;
            overflow-y: scroll;
        }

        .cabecera-medidas {
            font-size: 12px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
        }

        .cabecera-medidas div {
            width: 11.1%;
            height: 56px;
            background-color: #EFEFEF;
            text-align: center;
            align-content: center;
            padding: 10px;
        }

        .cabecera-medidas.under div {
            height: 27px;
            background-color: #FAFAFA;
            text-align: center;
            align-content: center;
        }

        .dimension .fila {
            width: 11.1%;
            height: 72px;
            text-align: center;
            align-content: center;
            border: 1px #D4D4D4;


            font-size: 15px;
            font-style: normal;
            font-weight: 300;
            line-height: 140%;
            /* 21px */
        }

        .continuar {
            color: #fff;
            background-color: var(--azuloscuro);
            border-color: var(--azuloscuro);
        }

        .dropdown-menu {
            border-radius: 0px 0px 15px 15px;
            background: #FFF;
            box-shadow: 0px 1px 3px 0px rgba(0, 0, 0, 0.35);
            padding: 0;
        }

        .dropdown-menu li {
            width: 245px;
            border-bottom: 1px solid #d4d4d4;
            padding: 0;
        }

        .dropdown-item:hover {
            background-color: rgba(78, 153, 212, 0.22);
        }

        .cantidad {
            display: flex;
            justify-content: space-between;
            width: 80px !important;
            height: 42px;
            padding: 0px 16px 0px 16px;
            border-radius: 10px;
            border-radius: 10px;
            border: 1px solid #EBEBEB;
            background: #FFF;
            align-items: center;
        }

        .cantidad div {
            height: auto !important;
        }

        .cantidad svg {
            cursor: pointer;
        }

        .descuentos {
            margin-top: 7px;
            color: #329943;
            font-size: 13px;
            font-style: normal;
            font-weight: 300;
            line-height: normal;
        }

        .buscador-cont {
            width: 50%;
        }

        .seguir-comprando {
            margin-right: 23px;
        }

        .porcentajes {
            color: #329943;
            text-align: right;
            font-size: 12px;
            font-style: normal;
            font-weight: 400;
            line-height: normal;
        }

        #inputPrivada {
            height: 50px;
            flex-shrink: 0;
            border-radius: 26px;
            border: 1px solid var(--Gris-linea, #E5E5E5);
            background: transparent;
            width: 100%;
            padding-left: 20px;
            color: #fff;
            color: #FFF;
            font-family: Roboto;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 50px;
            /* 357.143% */
        }

        input:focus {
            outline: none;
            border: #ccc;
            /* O cualquier otro color para mantener el borde */
        }

        select:focus {
            outline: none;
            border: #ccc;

        }

        #inputCodigo {
            height: 50px;
            flex-shrink: 0;
            border-radius: 26px;
            border: 1px solid var(--Gris-linea, #E5E5E5);
            background: transparent;
            width: 100%;
            padding-left: 20px;
            color: #fff;
            color: #FFF;
            font-family: Roboto;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 50px;
            /* 357.143% */
        }


        .btnPrivada:hover {
            color: var(--Verde, #ffff) !important;
            background: #236644;
            border: 1px solid var(--Verde, #ffff);

        }

        .descuentoTexto {
            color: #ABD430 !important;
            font-family: 'Montserrat' !important;
            font-size: 16px !important;
            font-style: normal;
            font-weight: 400;
            line-height: 140%;
            /* 22.4px */
        }

        .btnPrivada {
            height: 50px;
            padding: 11px 32px;
            width: 100%;
            gap: 10px;
            flex-shrink: 0;
            border-radius: 20px;
            border: 1px solid var(--Verde, #236644);
            background: #FFF;
            color: var(--Verde, #236644) !important;
            font-family: Roboto;
            font-size: 18px;
            font-style: normal;
            font-weight: 400;
            line-height: normal;
        }

        #inputPrivada::placeholder {
            padding-left: 0px;
            color: #ffff;
        }

        #inputCodigo::placeholder {
            padding-left: 0px;
            color: #ffff;
        }

        #selectPrivada option {
            color: #ffff !important;
            padding-left: 20px !important;
        }


        #selectPrivada {
            padding-right: 40px;
            padding-left: 20px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMyIgaGVpZ2h0PSI3IiB2aWV3Qm94PSIwIDAgMTMgNyIgZmlsbD0ibm9uZSI+PHBhdGggZD0iTTEgMS4wMDAwN0w2LjUgNi4yMTc3N0wxMiAxLjAwMDA3IiBzdHJva2U9IndoaXRlIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=');
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 12px;
            color: white;
            height: 50px;
            flex-shrink: 0;
            border-radius: 26px;
            border: 1px solid var(--Gris-linea, #E5E5E5);
            background-color: #236644 !important;
            width: 100%;
            color: #FFF;
            font-family: Roboto;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 50px;
            /* 357.143% */

        }

        select::-ms-expand {
            display: none;
        }

        #inputCodigo::-webkit-outer-spin-button,
        #inputCodigo::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Para Firefox */
        #inputCodigo[type=number] {
            -moz-appearance: textfield;
        }

        @media (max-width: 990px) {
            .buscador-cont {
                width: 100%;
            }

            .modal-dialog {
                max-width: none;
            }

            .cabecera-medidas div,
            .dimension .fila {
                width: 20%;
            }

            .modal-content {
                padding: 20px;
            }

            .mobile .cabecera-medidas div,
            .mobile .dimension .fila {
                width: 25%;
            }

            .seguir-comprando {
                margin-right: 0;
            }
        }
    </style>
@endsection

@section('content')
    {{-- <section>
    <div class="container-fluid" style="background: var(--Verde, #236644);">
        <div class="container">
            <div class="buscadorc" >
    
                <div style="padding-top: 24px; color: #FFF; font-family: Roboto; font-size: 16px; font-style: normal; font-weight: 400; line-height: normal;">
                    <a class="text-white" href="">Inicio  <span style="padding-right: 14px; padding-left:14px">/</span> <a class="text-white" href="">Productos</a></a>
    
                </div>

                <form action="{{route('productos.clientes')}}">
                <div class="row d-flex" style="margin-top: 30px">
                    <div class="col-lg-3">
                        <select name="categoria" id="selectPrivada">
                            <option selected value="" >Seleccionar categoría</option>
                            <option value="">Todas</option>

                            @foreach ($categorias as $categoria)
                            <option value="{{$categoria->id}}">{{$categoria->nombre}}</option>
                                
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-5">
                        <input type="text" placeholder="Nombre" name="producto" id="inputPrivada">

                    </div>

                    <div class="col-lg-2">
                        <input type="number" min="0" placeholder="Código" name="codigo" id="inputCodigo">

                    </div>
                    <div class="col-lg-2">
                        <button class="btn btnPrivada">Buscar</button>

                    </div>

                
                </div>
            </form>
    
            </div>

        </div>

    </div>
    <div class='container'>
        <div class='row' style='padding-bottom:42px;align-items:center;'>
            <div class='col-6'>
                <div class='buscador-cont'>
                    @include('components/buscador')
                </div>
            </div>  
            <div class='col-6 d-flex justify-content-end'>
                <div class='d-flex'>
                    <span style='margin-right:12px;'>Filtrar por categoría:</span>
                    <div type="button" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        @if ($categoria == 0)
                        <b>{{ucfirst($categorias[0]->nombre)}}</b>
                        @else
                        <b>{{ucfirst(App\Models\Categoria::find($categoria)->nombre)}}</b>
                        @endif
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @for ($i = 0; $i < count($categorias); $i++)
                            @if (($categoria == 0 && $i != 0) || ($categoria != 0 && $categorias[$i]->id != $categoria))
                            <li><a href="{{route('productos.clientes', ['categoria_id' => $categorias[$i]->id])}}" class="dropdown-item" type="button">{{ucfirst($categorias[$i]->nombre)}}</a></li>
                            @endif
                        @endfor
                    </ul>
                </div>
            </div>
            
            
        </div>

     
        
      
        
    </div>
    
    
</section> --}}

    {{-- 
<section class="sectionBo" >
    <div class="container" style="padding-top: 44px">

        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="bonificacionw">
                
                <div class="row d-flex justify-content-between align-items-end" style="width: 100%;">
                    <div class="col-lg-6" style="padding-left: 0px">
                        <h4>BONIFICACIONES</h4>                            

                     
                    </div>
                 
                    
                </div>

                @foreach ($bonificaciones as $bonificacion)
                    
                <div class="row d-flex justify-content-between align-items-end" style="width: 100%; border-bottom: 1px solid #E5E5E5; ">
                    <div class="col-lg-6" style="padding-left: 0px">
                        @if ($bonificacion->orden == 'gg')
                        <span class="bonificacion">Más de ${{ number_format($bonificacion->desde, 0, '.', '.') }} </span>                            

                        @else
                        <span class="bonificacion">Desde ${{ number_format($bonificacion->desde, 0, '.', '.') }} hasta ${{ number_format($bonificacion->hasta, 0, '.', '.') }}      </span>                            
                        @endif
                    </div>
                    <div class="col-lg-6 d-flex justify-content-end" style="padding-right: 0px">
                    <span class="bonificacion"> {{ number_format($bonificacion->porcentaje, 2, '.', '.') }}%</span>
    
                    </div>
                    
                </div>
    
         
                @endforeach
            </div>

        </div>

    </div>


</section> --}}



  




    {{-- El filtro que había acá lo reemplazan el buscador del header y el
         sidebar de filtros del catálogo (CatalogoController). --}}



{{-- 
    <section>
        <div class="container" style="padding-top: 18px; padding-bottom:300px">
            <div id='productos-contenedor'>
                @include('frontend/components/productos-clientes-listado')
            </div>

        </div>


    </section> --}}

    <section style="padding-top: 14px">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 d-flex justify-content-start align-items-center">
                    <div class="">
                        <span>Incremento reventa</span>
                        <form action="{{ route('reventa.user') }}" method="POST" style="display: inline;">
                            @csrf 
                            <input type="number" name="reventa" class="reventa" value="{{ Auth::guard('web')->user()->reventa }}">
                            <span style="padding-left:10px">%</span>
                            <button type="submit" class="boton-filtro" style="margin-left: 10px;">Actualizar</button>
                        </form>
                    </div>
                    


                </div>
             
            </div>

        </div>

    </section> 

    <section style='padding-top:78px;padding-bottom:82px;'>
        <div class='container'>            
            {{-- Misma grilla que /categorias y la Home: 6 por fila. --}}
            <div class='row categorias-grilla'>
                @foreach($categorias as $categoria)
                    <div class='col-6 col-md-4 col-lg-3 col-xl-2' style='margin-bottom:24px;' data-aos="fade-up">
                        @include('components/categoria')
                    </div>
                @endforeach
            </div>
        </div>
    </section>



@endsection

@section('script')
    <script>
        var carritoQuitarUrl = "{{ route('carrito.quitar') }}";
        var carritoSumarUrl = "{{ route('carrito.sumar') }}";
        var carritoAddUrl = "{{ route('carrito.agregar') }}";
        var carritoRemoverUrl = "{{ route('carrito.remover') }}";
        var carritoActualizarUrl = "{{ route('carrito.actualizar') }}";
    </script>
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
                        document.querySelector('.subtotal' + id).innerText = '$' + response
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        $(document).ready(function() {


            $('#searchInput').on('input', function(e) {
                var valor = $('#searchInput').val()
                $.ajax({
                    url: "{{ route('productos.clientes.buscar') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        valor: valor,
                    },
                    success: function(response) {
                        // console.log(response);
                        $('#productos-contenedor').html(response)
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            })











        });
    </script>
@endsection
