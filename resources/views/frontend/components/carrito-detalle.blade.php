<div class='carrito-detalle' style='border-top: 1px solid var(--Gris-linea, #E5E5E5);padding-top:17px;'>
    <div class='d-flex w-100 justify-content-between'>
        <div class='d-flex' style='align-items:flex-start;'>
            <div>Productos</div>
        </div>
        <div>$ {{App\Models\Carrito::subtotal_format(0)}}</div>
    </div>
    @if(Auth::guard('web')->check() && Auth::guard('web')->user()->descuento != 0)
    <div class='d-flex w-100 justify-content-between'>
        <div class='d-flex' style='align-items:flex-start;'>
            <div>Descuento cliente</div>
        </div>
        <div id='descuento-cliente'>-{{Auth::guard('web')->user()->descuento}}%</div>
    </div>
    @endif
    <div class='d-flex w-100 justify-content-between border-bottom border-1'>
        <div class='d-flex' style='align-items:flex-start;'>
            <div>Descuento método de pago</div>
        </div>
        <div id='descuento-detalle'>-{{$descuento}}%</div>
    </div>
    <div class='d-flex w-100 justify-content-between'>
        <div class='d-flex' style='align-items:flex-start;'>
            <div>Subtotal</div>
        </div>
        <div id='subtotal-detalle'>$ {{App\Models\Carrito::subtotal_format($descuento)}}</div>
    </div>
    <div class='d-flex w-100 justify-content-between'>
        <div class='d-flex' style='align-items:flex-start;'>
            <div>IVA</div>
        </div>
        <div>+{{App\Models\Impuesto::find(1)->porcentaje}}%</div>
    </div>
</div>
<div class='d-flex justify-content-between' style='padding-top:33px;align-items:center;border-top:1px solid #dfdfdf;margin-top:13px;'>
    <div>Total</div>
    <div style='
    text-align: right;
    font-family: Poppins;
    font-size: 32px;
    font-weight: 400;
    line-height: normal;'>$ {{App\Models\Carrito::total_format($descuento)}}</div>
</div>