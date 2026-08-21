@extends('layouts.plantilla-front')

@section('metadatos')
    <meta name='keyword' content='{{ App\Models\Metadatos::all()[0]->keyword }}'>
    <meta name='descripcion' content='{{ App\Models\Metadatos::all()[0]->descripcion }}'>
@endsection

@section('styles')
    <style>
    
        .carrito-btn {
            display: flex;
            width: auto;
            height: 39px;
            padding: 8px 20px;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            border-radius: 10px;
            border: 1px solid #0098DA;
            background: #FFF;
            color: #0098DA;

        }

        .carrito-btn:hover {
            background: var(--Verde, #0098DA) !important;
            border: 1px solid #0098DA !important;
            color: #FFF;


        }

        .carrito-btn:hover svg path {
            fill: #FFF;
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
            margin-bottom: 15px
        }

        .cantidad div {
            height: auto !important;
        }

        .cantidad svg {
            cursor: pointer;
        }


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

        .custom-checkbox:hover {


            border-color: #4caf50;
            background-color: #4caf50;
            color: #fff !important;
            background-size: 100% 100%;

        }

        input[type="checkbox"]:checked+.custom-checkbox {
            background-color: #4caf50;
            /* Color cuando está seleccionado */
            color: #fff;
            /* Color del texto cuando está seleccionado */
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

        .custom-checkbox-2:hover {


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


        .accordion-button {
            padding-left: 0;
            padding-right: 0;
            background-color: #fff !important;
        }

        .accordion-button:focus {
            border-color: none;
            box-shadow: none;
        }

        .filtro {
            cursor: pointer;
        }

        .filtrando {

            font-weight: 600
        }

        #consultar {
            border-radius: 10px;
            background: #0098DA;
            border: none;
            color: white;
            width: 100%;
            height: 39px;
            color: #FFF;
            font-family: 'Montserrat';
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: normal;

        }

        #consultar:hover {
            border-radius: 10px;
            background: white;
            border: 1px solid #0098DA;
            color: #0098DA !important;
            height: 39px;

        }

        .fotorama__stage {
            border: 1px solid #D8D8D8 !important;
            border-radius: 4px;

        }

        .fotorama__stage__frame img {
            padding: 0px !important;
            width: 100% !important;
            left: 0px !important;
            height: 274px;
        }


        .fotorama__thumb-border {
            margin-top: 16px !important;
            border-radius: 4px;
            border: 2px solid #0098DA !important;
        }

        .fotorama__nav__frame {
            padding-top: 16px !important;
            /* padding-right:16px !important; */
        }

        .fotorama__nav.fotorama__nav--thumbs {
            display: flex;
            justify-content: start;
        }

        .fotorama__thumb {
            background-color: transparent;
        }

        .fotorama__thumb {
            border-radius: 8px !important;
            border: 1px solid #DFDFDF;
        }

        .caracteristicas {
            column-count: 2;
            column-gap: 20px;
            /* Espacio entre columnas */
        }

        .caracteristicas ul {
            /* padding: 0;  */
            padding-left: 1.5rem !important;
        }

        .caracteristicas li {
            /* margin-bottom: 10px; */
        }
      
    </style>
@endsection

@section('content')

  



    <section style="padding-top: 50px">
        <div class='container miga'>
            <div>
                <a href="{{ route('home') }}">
                    @if (!Auth::guard('web')->check())
                        Inicio
                    @else
                        Carrito
                    @endif
                </a>
                <span style="padding-left:8px; padding-right:8px">/ </span>
                <a href="{{ route('categorias') }}">Productos</a>
                <span style="padding-left:8px; padding-right:8px">/ </span>
                @if ($producto->categoria()->first() != null)
                    <a
                        href="{{ route('productos', ['categoria' => $producto->categoria()->first()->id]) }}">{{ ucfirst($producto->categoria()->first()->nombre) }}</a>
                    <span style="padding-left:8px; padding-right:8px">/ </span>
                @endif

                <span style='font-weight:600;'>{{ ucfirst($producto->nombre) }} {{ ucfirst($producto->codigo) }}</span>

            </div>
        </div>
    </section>
    
    
    @if(Auth::guard('web')->check())
<section class="filtro2">

  <div class="container">
    <div class="filtroBuscadores">

      <form method="GET" action="{{route('filtroRodamientos')}}">
        @csrf

        <div class="row">
          <div class="col-lg-5 position-relative">
              <input type="text" name="buscadorPrincipal" class="inputFiltroTexto" placeholder="Marca / Modelo / Equivalencias / Atributo">
              <svg xmlns="http://www.w3.org/2000/svg" class="input-icon" width="21" height="21" viewBox="0 0 21 21" fill="none">
                <path d="M20.6781 18.8921L15.9175 14.1315C17.0637 12.6057 17.6824 10.7485 17.6803 8.84016C17.6803 3.96573 13.7146 0 8.84016 0C3.96573 0 0 3.96573 0 8.84016C0 13.7146 3.96573 17.6803 8.84016 17.6803C10.7485 17.6824 12.6057 17.0637 14.1315 15.9175L18.8921 20.6781C19.1331 20.8936 19.4474 21.0085 19.7705 20.9995C20.0936 20.9905 20.401 20.8581 20.6295 20.6295C20.8581 20.401 20.9905 20.0936 20.9995 19.7705C21.0085 19.4474 20.8936 19.1331 20.6781 18.8921ZM2.52576 8.84016C2.52576 7.59129 2.89609 6.37047 3.58993 5.33207C4.28376 4.29367 5.26994 3.48434 6.42374 3.00642C7.57755 2.52849 8.84716 2.40345 10.072 2.64709C11.2969 2.89073 12.422 3.49212 13.3051 4.3752C14.1882 5.25829 14.7896 6.38341 15.0332 7.60828C15.2769 8.83315 15.1518 10.1028 14.6739 11.2566C14.196 12.4104 13.3867 13.3966 12.3483 14.0904C11.3099 14.7842 10.089 15.1546 8.84016 15.1546C7.16609 15.1526 5.56117 14.4866 4.37742 13.3029C3.19368 12.1192 2.52777 10.5142 2.52576 8.84016Z" fill="#0098DA"/>
              </svg>
            </div>
            <div class="col-lg-2 d-flex justify-content-start align-items-center">
              <div class="checkbox-container">
                <input type="checkbox" name="nuevo" class="checkFiltro" id="checkFiltro">
                <label for="checkFiltro" class="custom-checkbox">Nuevo</label>
            </div>
                
            </div>
  
            <div class="col-lg-2 d-flex justify-content-start align-items-center recons">
              <div class="checkbox-container">
                <input type="checkbox" name="reconstruido" class="checkFiltro2" id="checkFiltro2">
                <label for="checkFiltro2" class="custom-checkbox-2">Reconstruido</label>
            </div>
                
            </div>
          <div class="col-lg-3">
            <div class="d-flex justify-content-end gap-2">
              <button type="button" id="limpiarFiltros" class="btn boton-filtro-producto">Limpiar</button>
                <button type="submit" class="btn boton-filtro-producto">Buscar</button>

              </div>

          </div>

        </div>

        <div class="row">
          <div class="col-lg-3" style="padding-top: 31px">
            <div class="d-flex">
              <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 60 39" fill="none">
                <path d="M7.99135 19.6078V37.3813C7.99135 38.627 7.6082 38.9849 6.31232 38.9837H1.50842C0.463283 38.9837 0.0086702 38.5308 0.00749851 37.4832C0.00124954 34.4073 -0.00109354 31.3318 0.000468703 28.2567C0.000468703 19.4829 0.000468703 10.7098 0.000468703 1.93749C0.000468703 0.619258 0.380098 0.246669 1.72519 0.247801C3.30696 0.247801 4.88795 0.247801 6.46816 0.247801C7.57657 0.247801 7.99135 0.6555 7.99135 1.78007C7.99135 7.72191 7.99135 13.6645 7.99135 19.6078Z" fill="#0098DA"/>
                <path d="M59.9918 19.5988C59.9918 25.5036 59.9813 31.4096 60 37.3144C60 38.3495 59.6157 39.0585 58.2425 38.9962C56.6267 38.9237 55.0039 38.9826 53.3835 38.9815C52.5188 38.9815 52.0466 38.5409 52.0091 37.7108C52.0033 37.5602 52.0033 37.4084 52.0033 37.2578C52.0033 25.4663 52.008 13.6739 52.0173 1.88086C52.0173 1.47543 52.1146 0.998646 52.3513 0.684944C52.5387 0.435795 53.0062 0.27385 53.3554 0.265922C55.0907 0.222887 56.8283 0.239872 58.5659 0.245535C59.4962 0.245535 59.9883 0.733643 59.9906 1.65663C59.9965 4.10849 59.9906 6.56148 59.9906 9.01786V19.6022L59.9918 19.5988Z" fill="#0098DA"/>
                <path d="M26.9726 14.37C26.9726 10.2387 26.9832 6.10281 26.9656 1.97599C26.9656 0.784603 27.4343 0.163996 28.7231 0.236476C29.6604 0.293101 30.5978 0.243272 31.5351 0.247802C32.4526 0.247802 32.8966 0.630586 33.0079 1.50034C33.0185 1.63183 33.0208 1.76382 33.015 1.89558C33.015 10.2345 33.015 18.5731 33.015 26.9113C33.015 27.4277 32.9927 27.96 32.5053 28.276C32.2302 28.4449 31.9135 28.5399 31.5879 28.5512C30.5732 28.5851 29.5585 28.5648 28.5415 28.5648C27.4682 28.5648 26.9808 28.1038 26.975 27.0472C26.9656 25.2556 26.975 23.4629 26.975 21.6713L26.9726 14.37Z" fill="#0098DA"/>
                <path d="M11.0108 14.387C11.0108 10.1975 11.0108 6.0073 11.0108 1.81631C11.0108 0.609067 11.4021 0.238741 12.6593 0.238741C13.6541 0.238741 14.6512 0.233078 15.646 0.238741C16.4743 0.245536 16.9875 0.739305 16.9887 1.54791C16.9887 10.096 16.9887 18.6441 16.9887 27.1922C16.9887 28.0755 16.4872 28.5512 15.5733 28.5512C14.5383 28.5557 13.5037 28.5557 12.4695 28.5512C11.5123 28.5512 11.0178 28.0835 11.0178 27.1729C11.0178 22.9079 11.0178 18.6422 11.0178 14.3757L11.0108 14.387Z" fill="#0098DA"/>
                <path d="M48.978 14.3904C48.978 18.6547 48.978 22.9185 48.978 27.182C48.978 28.088 48.4824 28.5557 47.5251 28.5591C46.5678 28.5625 45.5742 28.5591 44.5959 28.5591C43.4242 28.5534 43.0024 28.1446 43.0024 27.0291C43.0024 20.3678 43.0024 13.7068 43.0024 7.04618C43.0024 5.27269 43.0024 3.49845 43.0024 1.72344C43.0024 0.68268 43.4617 0.239874 44.5256 0.241006C45.5403 0.241006 46.5561 0.241006 47.572 0.241006C48.5093 0.241006 48.978 0.694005 48.978 1.6C48.978 5.86348 48.978 10.1292 48.978 14.3972V14.3904Z" fill="#0098DA"/>
                <path d="M24.9866 14.3972C24.9866 17.2247 24.9866 20.0529 24.9866 22.8819C24.9866 24.3145 24.9866 25.7471 24.9737 27.1797C24.9667 28.1197 24.505 28.5591 23.5361 28.5614C22.5214 28.5614 21.5067 28.5614 20.4897 28.5614C19.5066 28.5614 18.9864 28.0846 18.9852 27.1537C18.9806 18.6479 18.9806 10.1409 18.9852 1.63285C18.9852 0.726848 19.509 0.248935 20.4358 0.24667C21.4704 0.24667 22.505 0.238743 23.5384 0.24667C24.4968 0.252333 24.9761 0.687211 24.9866 1.61586C25.003 3.04847 24.9866 4.48221 24.9866 5.91935C24.9866 8.74757 24.9866 11.5758 24.9866 14.404V14.3972Z" fill="#0098DA"/>
                <path d="M35.0127 14.4233C35.0127 11.5958 35.0127 8.76796 35.0127 5.93973C35.0127 4.50599 34.9916 3.07338 35.0057 1.63624C35.015 0.669089 35.4849 0.242138 36.4843 0.23874C37.5185 0.23874 38.5527 0.23874 39.5869 0.23874C40.4388 0.23874 40.9192 0.666826 41.007 1.49015C41.0223 1.63851 41.007 1.7914 41.007 1.94315C41.007 10.2421 41.007 18.541 41.007 26.84C41.007 28.1491 40.5899 28.5557 39.2331 28.5591C38.277 28.5591 37.3209 28.5591 36.3648 28.5591C35.5446 28.5534 35.0256 28.0914 35.0232 27.3202C35.0104 24.1344 35.0139 20.9476 35.0115 17.7619C35.0123 16.649 35.0127 15.5361 35.0127 14.4233Z" fill="#0098DA"/>
              </svg>
              <p class="textCodigo">
                Por cód. BMH
              </p>
            </div>
            <div>
              <input type="text" name="codigoBMH" class="inputFiltroCodigo" placeholder="Ingresar código">
            </div>

          </div>

          <div class="col-lg-3" style="padding-top: 31px">
            <div class="d-flex">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 39 39" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.79426 7.55805C5.7939 7.68735 5.75733 7.81396 5.68869 7.92354C5.62006 8.03313 5.52212 8.12131 5.40595 8.17809C5.28978 8.23488 5.16003 8.258 5.03139 8.24484C4.90275 8.23168 4.78037 8.18276 4.67811 8.10363L0.266108 4.67226C0.183223 4.60751 0.116182 4.52472 0.0700727 4.43019C0.0239639 4.33565 0 4.23186 0 4.12668C0 4.0215 0.0239639 3.9177 0.0700727 3.82317C0.116182 3.72864 0.183223 3.64585 0.266108 3.58109L4.67811 0.146958C4.78074 0.0675355 4.90364 0.0185554 5.03277 0.00560422C5.1619 -0.00734694 5.29207 0.0162522 5.40844 0.0737099C5.5248 0.131168 5.62268 0.220171 5.6909 0.330568C5.75912 0.440964 5.79493 0.568311 5.79426 0.698081V2.74748H22.412V0.692542C22.4119 0.562761 22.4484 0.435583 22.5172 0.325517C22.5859 0.215451 22.6842 0.126927 22.8009 0.0700453C22.9176 0.0131632 23.0479 -0.00978725 23.1769 0.00381198C23.306 0.0174112 23.4287 0.0670126 23.5309 0.146958L27.9401 3.57832C28.023 3.64308 28.0901 3.72587 28.1362 3.8204C28.1823 3.91493 28.2062 4.01873 28.2062 4.12391C28.2062 4.22909 28.1823 4.33288 28.1362 4.42742C28.0901 4.52195 28.023 4.60474 27.9401 4.66949L23.5309 8.10363C23.4285 8.18372 23.3056 8.23336 23.1762 8.24685C23.0469 8.26035 22.9164 8.23716 22.7996 8.17994C22.6829 8.12272 22.5846 8.03378 22.516 7.92331C22.4475 7.81284 22.4114 7.68529 22.412 7.55528V5.51141H5.79426V7.55805ZM23.7968 13.8198H4.40946C4.04218 13.8198 3.68995 13.9657 3.43025 14.2254C3.17055 14.4851 3.02465 14.8373 3.02465 15.2045V34.5908C3.02465 34.958 3.17055 35.3103 3.43025 35.5699C3.68995 35.8296 4.04218 35.9755 4.40946 35.9755H23.7968C24.1641 35.9755 24.5163 35.8296 24.776 35.5699C25.0357 35.3103 25.1816 34.958 25.1816 34.5908V15.2045C25.1816 14.8373 25.0357 14.4851 24.776 14.2254C24.5163 13.9657 24.1641 13.8198 23.7968 13.8198ZM4.40946 11.0503C3.30763 11.0503 2.25094 11.488 1.47183 12.2671C0.692727 13.0461 0.25503 14.1028 0.25503 15.2045V34.5908C0.25503 35.6926 0.692727 36.7492 1.47183 37.5282C2.25094 38.3073 3.30763 38.745 4.40946 38.745H23.7968C24.8986 38.745 25.9553 38.3073 26.7344 37.5282C27.5135 36.7492 27.9512 35.6926 27.9512 34.5908V15.2045C27.9512 14.1028 27.5135 13.0461 26.7344 12.2671C25.9553 11.488 24.8986 11.0503 23.7968 11.0503H4.40946ZM38.3068 16.5893H36.2601V33.2061H38.3068C38.4361 33.2064 38.5627 33.243 38.6723 33.3116C38.7819 33.3802 38.8701 33.4782 38.9269 33.5944C38.9837 33.7105 39.0068 33.8403 38.9936 33.9689C38.9805 34.0975 38.9316 34.2199 38.8524 34.3222L35.4209 38.7339C35.3561 38.8168 35.2733 38.8838 35.1788 38.9299C35.0842 38.976 34.9804 39 34.8752 39C34.7701 39 34.6663 38.976 34.5717 38.9299C34.4772 38.8838 34.3944 38.8168 34.3296 38.7339L30.8953 34.3222C30.816 34.2197 30.7671 34.0971 30.754 33.9682C30.741 33.8393 30.7643 33.7094 30.8215 33.5931C30.8786 33.4768 30.9672 33.3789 31.0772 33.3105C31.1872 33.2421 31.3141 33.2059 31.4437 33.2061H33.4904V16.5893H31.4437C31.3144 16.5889 31.1878 16.5523 31.0782 16.4837C30.9686 16.4151 30.8804 16.3171 30.8236 16.201C30.7668 16.0848 30.7437 15.9551 30.7569 15.8264C30.77 15.6978 30.8189 15.5754 30.8981 15.4732L34.3296 11.0614C34.3944 10.9785 34.4772 10.9115 34.5717 10.8654C34.6663 10.8193 34.7701 10.7953 34.8752 10.7953C34.9804 10.7953 35.0842 10.8193 35.1788 10.8654C35.2733 10.9115 35.3561 10.9785 35.4209 11.0614L38.8552 15.4732C38.9345 15.5756 38.9834 15.6982 38.9965 15.8271C39.0095 15.956 38.9862 16.086 38.929 16.2022C38.8719 16.3185 38.7833 16.4164 38.6733 16.4848C38.5633 16.5532 38.4363 16.5894 38.3068 16.5893Z" fill="#0098DA"/>
              </svg>
              <p class="textCodigo">
                Por dimensiones                </p>
            </div>
            <div>
              <select name="categoriaFiltro" id="selectCategoria" class="selectFiltroProducto">

                <option  disabled selected value="">Seleccionar categoria</option>
                                     <option value="" class="fw-bold" >TODAS LAS CATEGORIAS</option>

                @foreach($categoriasAll as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
            @endforeach
              </select>
            </div>
            
            <div id="atributosCategoria">
            </div>

            <div class="row" style="padding-top: 12px">
              <div class="col-lg-4">
                <input type="text" name="alto" class="inputFiltroMedidas" placeholder="Alto" style="padding-left: 22px">

              </div>
              <div class="col-lg-4">
                <input type="text" name="ancho" class="inputFiltroMedidas" placeholder="Ancho" style="padding-left: 14px">

              </div>

              <div class="col-lg-4">
                <input type="text" name="largo" class="inputFiltroMedidas" placeholder="Largo" style="padding-left: 18px">

              </div>

            </div>

          </div>

          <div class="col-lg-3" style="padding-top: 31px">
            <div class="d-flex">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 48 31" fill="none">
                <path d="M10.6321 27.6898C10.6321 28.4447 10.6321 29.1241 10.6321 29.811C10.6321 30.5886 10.4191 30.8641 9.62307 30.9019C8.27398 30.9623 6.91741 30.9736 5.56831 30.9811C4.37617 30.9811 3.18777 30.9811 1.99563 30.9509C1.21457 30.9509 0.874496 30.5735 0.874496 29.8186C0.874496 28.7126 0.874496 27.6105 0.874496 26.5083C0.866555 26.2655 0.799826 26.0284 0.680159 25.8176C0.234935 25.0838 -0.000479916 24.2399 7.34542e-07 23.3793C0.0560575 20.7749 0.138278 18.178 0.190597 15.5736C0.163114 14.6709 0.395671 13.7795 0.860035 13.0076C1.3244 12.2357 2.0006 11.6166 2.80658 11.2254C3.96135 10.6101 5.07875 9.9345 6.20736 9.27396C6.43509 9.12441 6.65236 8.9592 6.85761 8.7795C6.80899 8.63375 6.71233 8.50927 6.58378 8.42683C6.45523 8.3444 6.30258 8.309 6.1513 8.32656C5.07875 8.29636 4.00246 8.2322 2.93738 8.10764C2.78284 8.0908 2.63469 8.03619 2.50574 7.94852C2.3768 7.86086 2.27094 7.74277 2.19734 7.6045C2.12373 7.46622 2.0846 7.31192 2.08331 7.15493C2.08202 6.99795 2.11863 6.84301 2.18996 6.70352C2.28338 6.45818 2.42166 6.23171 2.51882 5.98636C2.59742 5.76659 2.74269 5.57749 2.93394 5.44601C3.12518 5.31452 3.35265 5.24737 3.58391 5.25411C4.21921 5.25411 4.85452 5.25411 5.48983 5.29563C5.67078 5.3054 5.84416 5.37217 5.98559 5.48659C6.12703 5.601 6.22941 5.7573 6.27836 5.93352C6.37608 6.16062 6.44029 6.40098 6.46895 6.6469C6.51753 7.31499 6.95851 7.64337 7.52282 7.98685C7.66856 7.68866 7.80684 7.42068 7.9339 7.14514C8.56921 5.73725 9.19331 4.32558 9.83235 2.92147C10.6882 1.03422 12.1344 0.0717165 14.1936 0.0717165C16.9142 0.0717165 19.6348 0.101913 22.3592 0.0717165C25.6516 0.0717165 28.944 0.00754901 32.2364 0C33.0508 0.0105956 33.8642 0.0647758 34.673 0.162307C35.3039 0.208361 35.9131 0.414454 36.4442 0.761501C36.9753 1.10855 37.4111 1.58533 37.7112 2.14769C38.2681 3.17058 38.7614 4.22744 39.2547 5.2843C39.6545 6.14112 40.0021 7.02058 40.402 7.92646C40.6642 7.81027 40.894 7.63072 41.0713 7.40363C41.2486 7.17653 41.3678 6.90887 41.4185 6.62425C41.6427 5.39377 41.8408 5.26544 43.089 5.26544C43.4963 5.26544 43.9036 5.26544 44.311 5.26544C44.5251 5.25735 44.7364 5.31604 44.9163 5.43355C45.0961 5.55107 45.2358 5.72168 45.3163 5.9222C45.4359 6.19019 45.5853 6.45063 45.69 6.72617C45.7542 6.86453 45.7852 7.01625 45.7803 7.16898C45.7755 7.32171 45.735 7.47112 45.6621 7.60505C45.5893 7.73897 45.4861 7.85362 45.3611 7.93965C45.236 8.02567 45.0926 8.08064 44.9426 8.10009C43.8775 8.22087 42.8012 8.24729 41.7324 8.3341C41.5023 8.37359 41.2769 8.43683 41.0597 8.52283V8.71533C41.467 9.00597 41.8594 9.32303 42.2892 9.57592C43.2609 10.1459 44.2362 10.7083 45.2341 11.2291C46.9457 12.1199 47.7417 13.558 47.7977 15.4566C47.8725 18.1252 47.9622 20.79 47.9995 23.4585C48.0084 24.0544 47.8939 24.6455 47.6635 25.194C47.4332 25.7426 47.0919 26.2365 46.6616 26.6442C46.5036 26.8199 46.4099 27.0451 46.3963 27.2821C46.3664 28.1389 46.3963 28.9957 46.3963 29.8526C46.3963 30.6075 46.1982 30.8528 45.4172 30.9094C44.4904 30.9774 43.5486 30.9962 42.633 31C41.0522 31 39.4677 30.9698 37.8869 30.9585C36.9377 30.9585 36.6462 30.6527 36.6462 29.6714C36.6462 29.1278 36.6462 28.5843 36.6462 28.0408C36.6462 27.7124 36.5677 27.5728 36.2127 27.5765C32.5578 27.5954 28.9066 27.5992 25.2554 27.6105L15.9126 27.6407L11.0208 27.6671C10.8489 27.6596 10.7704 27.6747 10.6321 27.6898ZM39.1127 8.43979C38.3652 6.99416 37.7598 5.72593 37.1021 4.48034C36.6985 3.72544 36.25 2.97054 35.7941 2.21564C35.6592 1.98855 35.4662 1.80243 35.2353 1.67688C35.0045 1.55133 34.7443 1.491 34.4824 1.50226C28.4507 1.50226 22.4152 1.45318 16.3835 1.44564C15.3241 1.45452 14.2661 1.52383 13.2144 1.65323C13.0418 1.6742 12.8752 1.73055 12.7248 1.81881C12.5744 1.90708 12.4435 2.02541 12.34 2.16657C11.2188 4.05382 10.1687 5.99014 9.07746 7.90004C8.87566 8.25484 8.97283 8.43224 9.31664 8.50018C9.94792 8.65873 10.59 8.76976 11.2375 8.83234C14.5112 8.99842 17.7849 9.18715 21.0587 9.25131C26.2582 9.40491 31.4622 9.26378 36.6462 8.82857C37.4235 8.7795 38.1933 8.61342 39.1052 8.45112L39.1127 8.43979ZM24.0185 23.2925C21.0699 23.2925 18.1213 23.2925 15.1727 23.2925C14.6905 23.3265 14.2172 23.44 13.7713 23.6284C13.7059 23.6445 13.6456 23.677 13.596 23.7229C13.5464 23.7688 13.5091 23.8267 13.4875 23.891C13.4658 23.9554 13.4607 24.0242 13.4724 24.0911C13.4841 24.158 13.5124 24.2209 13.5545 24.2738C14.3505 25.2288 15.0942 26.18 16.5741 26.1309C19.0967 26.0516 21.6267 26.1309 24.1493 26.1309C26.5111 26.1309 28.873 26.1309 31.2498 26.1309C32.4008 26.1309 33.3986 25.8101 34.0676 24.7796C34.1477 24.6753 34.2351 24.5769 34.3292 24.4852C34.6618 24.1078 34.6468 23.7756 34.1909 23.5831C33.7585 23.3994 33.2958 23.2995 32.8268 23.2887C29.8745 23.2887 26.9446 23.3038 24.011 23.3038L24.0185 23.2925ZM6.73429 18.6876C8.12076 18.5743 9.07746 18.5139 10.0342 18.4196C10.8302 18.3403 11.032 17.9213 10.6097 17.2457C10.2914 16.675 9.82904 16.1998 9.26974 15.8683C8.71044 15.5367 8.07415 15.3608 7.42566 15.3585C7.1811 15.3671 6.93831 15.3136 6.71957 15.2028C6.50083 15.092 6.31314 14.9275 6.17372 14.7243C5.87679 14.32 5.49713 13.985 5.06056 13.742C4.62399 13.4991 4.14075 13.3539 3.64369 13.3165C2.71315 13.1957 2.17127 13.5429 2.05169 14.4752C1.97869 15.258 1.95621 16.0448 1.98441 16.8305C1.98256 17.0804 2.05542 17.325 2.19344 17.5325C2.33145 17.7399 2.52821 17.9004 2.75799 17.9931C3.04221 18.1536 3.35007 18.267 3.66985 18.329C4.81715 18.5064 5.98312 18.6159 6.72681 18.6989L6.73429 18.6876ZM39.8825 18.6461V18.6083C40.4879 18.6083 41.0896 18.631 41.6913 18.6083C42.1929 18.5826 42.6923 18.5234 43.1861 18.4309C44.752 18.1516 45.1182 17.7288 45.2191 16.1473C45.2284 16.0646 45.2284 15.981 45.2191 15.8982C45.1799 15.2962 45.1025 14.6973 44.9874 14.1053C44.9317 13.8565 44.7857 13.6378 44.5781 13.4924C44.3705 13.347 44.1165 13.2856 43.8663 13.3202C42.7788 13.3504 41.7922 13.6977 41.112 14.5734C40.9254 14.8281 40.6828 15.0357 40.4033 15.1798C40.1238 15.3239 39.8151 15.4005 39.5013 15.4038C38.8616 15.4244 38.2399 15.6223 37.704 15.9757C37.1682 16.3292 36.7389 16.8247 36.463 17.408C36.1641 18.0195 36.3584 18.3516 37.0274 18.4158C37.9915 18.5177 38.9333 18.5781 39.875 18.6574L39.8825 18.6461ZM10.2322 26.731L10.3182 26.5499C9.89963 25.5949 9.49228 24.6286 9.03636 23.6888C8.97656 23.5567 8.66264 23.4812 8.49073 23.4812C6.53622 23.4812 4.57798 23.4812 2.62346 23.4812C2.00684 23.4812 1.87603 23.7416 2.17126 24.2852C2.47007 24.8772 2.9094 25.3852 3.45 25.7639C3.99061 26.1427 4.61565 26.3802 5.26934 26.4555C6.91367 26.6216 8.57668 26.6404 10.2322 26.731ZM37.4459 26.5499C39.1052 26.5499 40.6897 26.6518 42.2556 26.5234C43.7504 26.3951 44.8716 25.5156 45.619 24.1946C45.8731 23.7492 45.7311 23.4812 45.2453 23.4774C43.2235 23.4774 41.2017 23.4774 39.1799 23.4774C39.0879 23.4794 38.9973 23.5018 38.9147 23.5428C38.8321 23.5839 38.7593 23.6427 38.7016 23.7152C38.2868 24.6437 37.8682 25.5987 37.4459 26.5461V26.5499ZM13.42 17.4495C13.5695 17.4495 13.7302 17.4722 13.8871 17.4722H33.877C33.9895 17.4913 34.1045 17.4913 34.217 17.4722C34.2621 17.4512 34.3019 17.4203 34.3336 17.3818C34.3653 17.3432 34.388 17.298 34.4002 17.2495C34.3951 17.1985 34.3782 17.1494 34.351 17.1061C34.3237 17.0628 34.2868 17.0266 34.2432 17.0004C34.1213 16.9686 33.9948 16.9584 33.8695 16.9702C29.0636 16.9437 24.2576 16.906 19.4517 16.8909C17.5831 16.8909 15.7146 16.9324 13.846 16.9551C13.5097 16.9438 13.2481 16.9853 13.42 17.4458V17.4495ZM34.0601 18.3063H13.704C13.7675 18.7064 13.932 18.8461 14.2795 18.8461C20.68 18.8461 27.0841 18.8461 33.492 18.8461C33.8284 18.8423 34.0078 18.7178 34.0601 18.3026V18.3063ZM14.3692 19.635C14.3991 20.1558 14.6906 20.1898 15.0494 20.1898C20.7074 20.1898 26.3629 20.1898 32.0159 20.1898C32.3111 20.1898 32.6026 20.1898 32.8978 20.1898C33.1931 20.1898 33.3986 20.0313 33.4061 19.6425L14.3692 19.635ZM15.1914 20.9598L15.1391 21.141C15.4923 21.2685 15.8582 21.3571 16.2303 21.4052C17.4486 21.443 18.6706 21.4279 19.8927 21.4241C23.7394 21.4241 27.5837 21.4241 31.4254 21.4241C31.6537 21.4468 31.8841 21.4179 32.1 21.3396C32.3159 21.2612 32.5118 21.1354 32.6736 20.9711L15.1914 20.9598ZM33.5817 16.053L33.6042 15.8529C33.1088 15.7764 32.6098 15.726 32.1093 15.7019C29.5307 15.7019 26.9558 15.7284 24.381 15.7359C21.2642 15.7359 18.1512 15.7359 15.0382 15.7359C14.7355 15.7604 14.4354 15.8109 14.1413 15.8869L14.1674 16.0379L33.5817 16.053Z" fill="#0098DA"/>
              </svg>
              <p class="textCodigo">
                Por marca y modelo                </p>
            </div>
            <div>
              <select id="marca" name="marca" class="selectFiltroMarca">
                <option  disabled selected value="">Seleccionar Marca</option>
                                     <option value="" class="fw-bold" >TODAS LAS MARCAS</option>

                @foreach($marcas as $marca => $modelos)
                    <option value="{{ $marca }}">{{ $marca }}</option>
                @endforeach
            </select>



            </div>


          </div>

          <div class="col-lg-3" style="padding-top: 31px">
            <div class="d-flex">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 41 30" fill="none">
                <path d="M31.3309 22.3128H9.50568L12.4944 25.8446C12.9263 26.3612 13.3755 26.8662 13.8016 27.3886C14.4667 28.2059 14.4466 28.9765 13.7584 29.5924C13.0703 30.2083 12.2554 30.1382 11.5414 29.3005C8.25318 25.4087 4.96789 21.5169 1.68548 17.6251C1.30829 17.1785 0.925339 16.7494 0.562546 16.2824C-0.183195 15.3484 -0.197592 14.8405 0.579821 13.9123C3.30173 10.6665 6.03228 7.42756 8.77146 4.19541C9.68132 3.11836 10.5912 2.03547 11.5068 0.961326C12.2324 0.10902 13.0213 -0.0310828 13.7268 0.552688C14.4322 1.13646 14.4667 2.01211 13.7268 2.90236C12.6441 4.19542 11.5471 5.47972 10.4616 6.77277C10.2313 7.04422 10.0153 7.32443 9.70436 7.71264H31.3654C30.8558 7.09092 30.4296 6.5451 29.9891 6.0343C29.0591 4.93389 28.1118 3.84223 27.1846 2.73599C26.4331 1.83698 26.4274 0.984677 27.1415 0.377555C27.8555 -0.229567 28.6531 -0.115737 29.4276 0.800784C33.1112 5.14015 36.7958 9.48925 40.4813 13.8481C41.181 14.677 41.1723 15.3075 40.4583 16.1569C36.7929 20.4943 33.1362 24.8405 29.4276 29.14C29.062 29.5661 28.345 29.9572 27.8296 29.9135C26.6462 29.8113 26.2316 28.4278 27.032 27.4441C28.2356 25.9672 29.4852 24.5252 30.7118 23.0658C30.899 22.8644 31.0717 22.6309 31.3309 22.3128ZM3.41594 15.109C4.53312 16.4371 5.60422 17.736 6.71851 19.0028C6.93896 19.1978 7.22356 19.302 7.51608 19.2947C16.131 19.3063 24.7468 19.3063 33.3636 19.2947C33.628 19.3162 33.891 19.2394 34.1036 19.0787C35.2726 17.7506 36.4071 16.3875 37.5588 15.0185C36.433 13.6846 35.382 12.4062 34.2822 11.1715C34.0358 10.9221 33.7057 10.7762 33.3579 10.7628C24.8025 10.7434 16.2481 10.7434 7.69459 10.7628C7.40266 10.7464 7.11489 10.8388 6.88551 11.0226C5.71651 12.3303 4.58782 13.6992 3.41594 15.0944V15.109Z" fill="#0098DA"/>
              </svg>
              <p class="textCodigo">
                Por equivalencia
              </p>
            </div>
            <div>
              <input type="text" name="equivalenciaFiltro" class="inputFiltroCodigo" placeholder="Ingresar código">
            </div>
            <div>
              <p class="descripcionCodigo">(GV, Dipra, PH, ZEN, ZM, NOSSO)</p>
            </div>

          </div>

        </div>

     


      </form>
    </div>
  </div>

</section>
@endif
    
    
    

    <section style="padding-top: 67px">
        <div class='container'>
            <div class='row'>
                <div class='col-lg-3 monitor'>
                    @include('components/categorias-filtro')
                </div>
                <div class='col-lg-3 mobile' style='margin-bottom:20px;'>
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item" style='border:none;'>
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed"
                                    style='color: #848484;
                font-size: 16px;
                font-style: normal;
                font-weight: 400;
                line-height: 130%; 
                letter-spacing: 0.96px;
                text-transform: uppercase;'
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                    aria-expanded="false" aria-controls="collapseTwo">
                                    Categorías
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body" style='padding:0 0 20px 0;'>
                                    @foreach ($categorias as $categoria)
                                        <div style='padding-top:16px;padding-bottom:11px;border-bottom:1px solid #E0E0E0;'>
                                            <a href='{{ route('productos', ['categoria' => $categoria->id]) }}'
                                                class='filtro {{ $categoria_id == $categoria->id ? 'filtrando' : '' }}'
                                                data-id='{{ $categoria->id }}'>{{ ucfirst($categoria->nombre) }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='col-lg-9'>
                    <div class='row'>
                        <div class='col-lg-6' style='padding-bottom:48px;'>
                            <div class="fotorama" data-thumbfit='cover' data-thumbmargin="16" data-nav="thumbs"
                                data-thumbwidth="80px" data-thumbheight="78px" data-height='431px' data-width="100%"
                                data-fit='cover' data-ratio='800/600'>
                                @if ($producto->portada() && $producto->portada()->path)
                                    <img class='producto-img'
                                        src="{{ asset('imagenes/' . $producto->portada()->path) }}">

                                    @foreach ($imagenes as $imagen)
                                        <img class='producto-img' src="{{ asset('imagenes/' . $imagen->path) }}">
                                    @endforeach
                                @else
                                    <img class='producto-img'
                                        src="{{ asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg') }}">
                                @endif
                            </div>
                        </div>
                        <div class='col-lg-6 caja-producto' style="margin-bottom: 30px">
                            <div class='d-flex flex-column justify-content-between producto-informacion'>
                                <div>

                                    <div class='d-flex flex-column'>
                                        <div class="d-flex justify-content-between">
                                            <span class="producto-titulo"
                                                style="color: #000 !important">{{ ucfirst($producto->codigo) }}</span>
                                            <span class="producto-titulo" style="color: #0098DA !important">
                                                @if ($producto->estado == 1)
                                                    <span class="producto-titulo" style="color: #ABD430">Nuevo</span>
                                                @elseif ($producto->estado == 2)
                                                    <span class="producto-titulo" style="color: #0098DA">Reconstruido</span>
                                                @endif


                                            </span>

                                        </div>
                                        <span class="producto-titulo">{{ ucfirst($producto->nombre) }}</span>



                                    </div>

                                    <div class='d-flex flex-column mt-3'>

                                        <div class="infoR" style="font-size 14px !important">{!! ucfirst($producto->descripcion) !!}
                                        </div>



                                    </div>


                                    <div class="d-flex flex-column" style="overflow-y: auto; max-height: 200px; ">

                                        @for ($i = 1; $i <= 78; $i++)
                                            @php
                                                $columna = "columna_$i";
                                            @endphp

                                            @if ($producto->$columna)
                                            @if($producto->categoria()->first()->$columna)
                                                <div class="d-flex">
                                                    <span
                                                        class="infoR">{{ $producto->categoria()->first()->$columna }}:</span>
                                                    <span class="nR">{{ $producto->$columna }}</span>
                                                </div>
                                                @endif
                                            @endif
                                        @endfor
                                        
                                        @foreach($caracteristicas as $caracteristica)
                                              <div class="d-flex">
                                                    <span
                                                        class="infoR">{{ $caracteristica->nombre }}:</span>
                                                    <span class="nR">{{ $caracteristica->valor }}</span>
                                                </div>
                                        @endforeach
                                        
                                    

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

                                        {{-- @if ($producto->equivalencias)
                                    <div class="d-flex">
                                        <span class="infoR">Equivalencias:</span>
                                        <span class="nR">{{ $producto->equivalencias }}</span>
                                    </div>
                                    @endif --}}



                                    </div>
                                </div>


                                <div class="row d-flex">
                                    @if (!Auth::guard('web')->check())
                                        <div class="col-lg-12">
                                            <a href="{{ route('contacto', ['producto' => $producto->nombre]) }}"><button
                                                    id='consultar' class='green-btn'>Consultar</button></a>
                                        </div>
                                    @else
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
                            
                                        <div class="d-flex" style="padding-top: 15px; width: 250px;">
                                            <div class="col-lg-8">
                                                <span>
                                                    Subtotal
                            
                            
                            
                                                    :
                                                </span>
                            
                                            </div>
                                            <div class="col-lg-6">
                                                <div class='fila col-1 monitor subtotal{{ $producto->id }}' style='text-align:end; width: 100%;'>
                                                    <div>
                            
                                                        ${{ number_format($producto->precio_unitario_descontado(), 2, ',', '.') }}
                                                    </div>
                                                </div>
                            
                                            </div>
                            
                                        </div>
                            
                            
                                    </div>
                                        <div class="col-lg-12 d-flex">

                                            <div class='cantidad' style="width: 80px !important;">
                                                <div>
                                                    <span class="addC"
                                                        onclick="sumar_restar('restar', '{{ $producto->id }}')">-</span>
                                                </div>
                                                <div class='cantidad-contador{{ $producto->id }}' style='width:auto;'>1
                                                </div>
                                                <div>
                                                    <span class="addC"
                                                        onclick="sumar_restar('sumar', '{{ $producto->id }}')">+</span>

                                                </div>

                                            </div>
                                            
                                            
                                            <div>
                                                
                                                 <button class='carrito-btn' style="margin-left: 50px !important"
                                                onclick="agregar_carrito_publico('{{ $producto->id }}', {{ $producto->precio_unitario_descontado() }})">
                                                SUMAR AL CARRITO <svg xmlns="http://www.w3.org/2000/svg" width="15"
                                                    height="17" viewBox="0 0 15 17" fill="none">
                                                    <path
                                                        d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z"
                                                        fill="#0098DA" />
                                                </svg>
                                            </button>
                                            </div>

                                        </div>
                                        <!--<div class="col-lg-12">-->


                                        <!--    <button class='carrito-btn'-->
                                        <!--        onclick="agregar_carrito_publico('{{ $producto->id }}', {{ $producto->precio_unitario_descontado() }})">-->
                                        <!--        SUMAR AL CARRITO <svg xmlns="http://www.w3.org/2000/svg" width="15"-->
                                        <!--            height="17" viewBox="0 0 15 17" fill="none">-->
                                        <!--            <path-->
                                        <!--                d="M4.50416 16.5C4.09128 16.5 3.73795 16.3435 3.44418 16.0304C3.15041 15.7173 3.00327 15.3405 3.00277 14.9C3.00277 14.46 3.14991 14.0835 3.44418 13.7704C3.73845 13.4573 4.09178 13.3005 4.50416 13.3C4.91704 13.3 5.27062 13.4568 5.56489 13.7704C5.85916 14.084 6.00605 14.4605 6.00555 14.9C6.00555 15.34 5.85866 15.7168 5.56489 16.0304C5.27112 16.344 4.91754 16.5005 4.50416 16.5ZM12.0111 16.5C11.5982 16.5 11.2449 16.3435 10.9511 16.0304C10.6573 15.7173 10.5102 15.3405 10.5097 14.9C10.5097 14.46 10.6568 14.0835 10.9511 13.7704C11.2454 13.4573 11.5987 13.3005 12.0111 13.3C12.424 13.3 12.7776 13.4568 13.0718 13.7704C13.3661 14.084 13.513 14.4605 13.5125 14.9C13.5125 15.34 13.3656 15.7168 13.0718 16.0304C12.7781 16.344 12.4245 16.5005 12.0111 16.5ZM3.86607 3.7L5.66774 7.7H10.9226L12.987 3.7H3.86607ZM3.15291 2.1H14.2256C14.5134 2.1 14.7324 2.2368 14.8825 2.5104C15.0326 2.784 15.0389 3.06053 14.9013 3.34L12.2363 8.46C12.0987 8.72667 11.9143 8.93333 11.683 9.08C11.4518 9.22667 11.1983 9.3 10.9226 9.3H5.32992L4.50416 10.9H13.5125V12.5H4.50416C3.94114 12.5 3.51575 12.2368 3.22798 11.7104C2.94022 11.184 2.9277 10.6605 3.19045 10.14L4.20388 8.18L1.50139 2.1H0V0.5H2.43975L3.15291 2.1Z"-->
                                        <!--                fill="#0098DA" />-->
                                        <!--        </svg>-->
                                        <!--    </button>-->


                                        <!--</div>-->
                                    @endif
                                </div>

                            </div>
                        </div>

                        @if ($productos != null)
                            <h4
                                style='padding-bottom:10px; margin-top: 101px;
            font-size: 24px;
            font-style: normal;
            font-weight: 600;
            line-height: 130%; /* 31.2px */'>
                                Productos relacionados</h4>

                            @include('frontend/productos-listado')
                        @endif


                    </div>
                </div>

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


        
    document.getElementById('limpiarFiltros').addEventListener('click', function () {
    const form = document.querySelector('.filtroBuscadores form');
    localStorage.removeItem("atributosCategoria");

    // Limpiar inputs
    form.querySelector("input[name='buscadorPrincipal']").value = '';
    form.querySelector("input[name='codigoBMH']").value = '';
    form.querySelector("input[name='equivalenciaFiltro']").value = '';

    // Limpiar checkboxes
    form.querySelector("input[name='nuevo']").checked = false;
    form.querySelector("input[name='reconstruido']").checked = false;

    // Resetear selects
    form.querySelector("select[name='categoriaFiltro']").selectedIndex = 0;
    form.querySelector("select[name='marca']").selectedIndex = 0;

    // Limpiar atributos dinámicos si tenés
    const atributosCategoria = document.getElementById('atributosCategoria');
    if (atributosCategoria) atributosCategoria.innerHTML = '';

    // Eliminar items del localStorage
    localStorage.removeItem("ultimaBusqueda");
    localStorage.removeItem("codigoBMH");
    localStorage.removeItem("categoriaFiltro");
    localStorage.removeItem("marca");
    localStorage.removeItem("equivalenciaFiltro");
    localStorage.removeItem("checkboxNuevo");
    localStorage.removeItem("checkboxReconstruido");

    // Eliminar campo de paginación si existe
    const inputPage = form.querySelector("input[name='page']");
    if (inputPage) inputPage.remove();

    // Enviar el formulario limpio
    form.submit();
});


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
                        document.querySelector('.subtotal' + id).innerText =  response.total
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }
    </script>
@endsection
