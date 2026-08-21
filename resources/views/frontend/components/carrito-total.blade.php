<style>
    .mi-pedido{
        border-radius: 18px;
        border: 1px solid #DFDFDF;
    }
    .pedido-titulo{
        border-radius: 18px 18px 0px 0px;
        background: #EFEFEF;
        height: 52px;
        padding-left:32px;
        align-content: center;
        font-size: 20px;
        font-weight: 500;
    }
    .pedido-info{
        padding:32px 32px 24px 32px;
        color: var(--tipografia, #1E1E1E);
        font-size: 15px;
        font-style: normal;
        font-weight: 300;
        line-height: 140%;
    }
</style>

<div class='mi-pedido'>
    <div class='pedido-titulo'>
        Mi pedido
    </div>

    <div class='pedido-info'>
        <div style='padding-bottom:23px;'>
            Detalle
        </div>
        @foreach(Cart::content() as $item)
        <div class='d-flex' style='padding-bottom:17px;'>
            <div style='padding-right:10px;'>x{{$item->qty}}</div>
            <div>{{$item->name}}</div>
        </div>
        @endforeach
        <div class='d-flex justify-content-between' style='padding-top:39px;padding-bottom:26px;border-bottom:1px solid #dfdfdf;'>
            <div>Subtotal</div>
            <div style='
            text-align: right;
            font-family: Poppins;
            font-size: 24px;
            font-weight: 400;'>$ {{Cart::subtotal(2, ',', '.')}}</div>
        </div>
        <div class='d-flex justify-content-between' style='padding-top:33px;'>
            <div>IVA %{{App\Models\Impuesto::find(1)->porcentaje}}</div>
            <div style='
            text-align: right;
            font-family: Poppins;
            font-size: 15px;
            font-weight: 400;
            line-height: normal;'>$ {{App\Models\Carrito::iva(0)}}</div>
        </div>
        <div class='d-flex justify-content-between' style='padding-top:33px;align-items:center;'>
            <div>Total (IVA incluido)</div>
            <div style='
            text-align: right;
            font-family: Poppins;
            font-size: 32px;
            font-weight: 400;
            line-height: normal;'>$ {{App\Models\Carrito::total_format(0)}}</div>
        </div>
    </div>
    
</div>

<div style='text-align:right;padding-top:27px;'>
    <button id='continuar' onclick="continuar({{Cart::count()}})" class='green-btn'>Continuar compra</button>
</div>

<script>
    function continuar(cantidad){
        if(cantidad > 0){
            window.location.href = "{{route('pedido')}}";
        } else {
            iziToast.warning({
                title: 'El carrito esta vacío',
                backgroundColor: '#DC1B23',
                titleColor:'#fff',
                progressBar:false,
                position:'bottomRight',
            });
        }
    }
</script>