@extends('layouts.plantilla-front')

{{-- @section('metadatos')
<meta name='keyword' content='{{$metadatos[0]->keyword}}'>
<meta name='descripcion' content='{{$metadatos[0]->descripcion}}'>
@endsection --}}

@section('styles')
<link rel="stylesheet" href="css/productos.css">
<style>

    
    .envio-info{
        display: none;
    }
    .mostrar-info{
        display: block;
    }
    .modal-dialog{
        max-width: 80vw;
    }
    .modal-content{
        padding: 67px 81px 60px 81px;
        border-radius: 25px;
        background: #FFF;
        box-shadow: 0px 0px 6.3px 0px rgba(0, 0, 0, 0.25);
    }
    .inicio{
        width: 284px;
    }
</style>
@endsection

@section('content')

    @include('frontend.components.miga-baner', ['titulo' => 'Carrito'])

    <section style='padding-top:58px;padding-bottom:100px;'>
        <div class='container'>
            <div class='row'>
                <div class='col-lg-8'>
                    <div id='carrito-desplegado' style='margin-bottom:29px;'>
                        @include('frontend.components.carrito-desplegado')
                    </div>
                    <a href='{{$_SERVER['HTTP_REFERER']}}'>
                        < Seguir comprando
                    </a>
                </div>
                <div id='carrito-total' class='col-lg-4'>
                    @include('frontend.components.carrito-total')
                </div>
            </div>
            
        </div>
    </section>

    @if($aviso)
    {{-- MODAL --}}
    <div class="modal fade" id="aviso" aria-hidden="true" aria-labelledby="exampleModalToggleLabel2" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg" style='width:828px;'>
        <div class="modal-content">
          <div class="modal-body" style='overflow:hidden;text-align:center;'>
              <div style='
              font-size: 32px;
              font-style: normal;
              font-weight: 500;
              line-height: normal;'>{{$informacion->pedido_titulo}}</div>
              <div style='
              font-size: 24px;
              font-style: normal;
              font-weight: 300;
              line-height: normal;
              margin-top:23px;margin-bottom:37px;'>{!!$informacion->pedido!!}</div>
              <a href="{{route('home')}}"><button class='green-btn inicio'>Ir al inicio</button></a>
          </div>
        </div>
      </div>
    </div>
    @endif

@endsection

@section('script')
<script>

    function enviar_pedido(e){
        e.target.innerHTML = '<div class="loading-spinner"></div>  Procensando pedido';
        document.getElementById('pedido').submit()
    }

    $(document).ready(function() {
        $('#aviso').modal('show');
    })
    
    function notificar(texto){
        iziToast.warning({
            title: texto,
            backgroundColor: '#254F70',
            titleColor:'#fff',
            progressBar:false,
            position:'bottomRight',
        });
    }

</script>
@endsection

