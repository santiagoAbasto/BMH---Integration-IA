<style>
    .cuadro-info-pedido {
        padding: 0px 0px 0px 0px;
        border-radius: 10px;
        border: 1px solid #C4C4C4;
   

    }

 

    .precioP {
        text-align: right;
      
        padding-right: 24px;

        color: #000;
font-family: 'Montserrat';
font-size: 17px;
font-style: normal;
font-weight: 400;
line-height: normal;
    }
    .precioPD{
        padding-right: 24px;
        color: #00AB07;
text-align: right;
font-family: "Roboto Condensed";
font-size: 18px;
font-style: normal;
font-weight: 400;
line-height: normal;
    }

    .precioT {
        color: #000;
font-family: 'Montserrat';
font-size: 17px;
font-style: normal;
font-weight: 400;
line-height: normal;
padding-left: 24px;

    }

    .totalT{
        color: #000;
font-family: 'Montserrat';
font-size: 24px;
font-style: normal;
font-weight: 400;
line-height: normal;
padding-left: 24px
    }

    .totalP{
        color: #000;
font-family: 'Montserrat';
font-size: 24px;
font-style: normal;
font-weight: 600;
line-height: normal;
padding-right: 24px;

    }

    .ivaIncluido{
        color: #979797;
font-family: 'Montserrat';
font-size: 17px;
font-style: normal;
font-weight: 400;
line-height: normal;
    }

    .cuadro-titulo {
        color: #131313;
font-family: "Montserrat";
font-size: 20px;
font-style: normal;
font-weight: 500;
line-height: normal;
        padding: 0px !important; 
        height: 56px;
        border-radius: 8px 8px 0px 0px;
        background: #F5F5F5;
        width: 100%;
        margin-bottom: 21px;
        z-index: -10;
    position: relative;
    }

    .cuadro-titulo p{
        padding-left: 24px;
        padding-top: 15px
    }

    .envioT {
        color: var(--Negro, #1D1C1B);
        font-family: "Roboto Condensed";
        font-size: 18px;
        font-style: normal;
        font-weight: 400;
        line-height: normal;
        padding-left: 11px;
    }
</style>
{{-- <div class='cuadro-info-pedido' data-cantidad={{ Cart::count() }} style="height: 206px;">
    <div class='cuadro-titulo'><p>Entrega</p></div>
    <div style='padding-bottom:15px; padding-left:24px'>
        <div class='d-flex w-100 justify-content-between detalle carrito-total-opcion' style='padding-bottom:19px;'>
            <div class='d-flex' style='align-items:flex-start;'>
                <input class="form-check-input envio-opcion" type="radio" data-tipo='Retiro cliente' name="envio">
                <div class='envioT'>Retiro cliente</div>
            </div>
        </div>
        <div class='d-flex w-100 justify-content-between detalle carrito-total-opcion' style='padding-bottom:19px;'>
            <div class='d-flex' style='align-items:flex-start;'>
                <input class="form-check-input envio-opcion" type="radio" data-tipo='Reparto Iptsa' data-descuento='0'
                    name="envio">
                <div class='envioT'>Reparto Iptsa</div>
            </div>
        </div>

        <div class='d-flex w-100 justify-content-between detalle carrito-total-opcion'>
            <div class='d-flex' style='align-items:flex-start;'>
                <input class="form-check-input envio-opcion" type="radio" data-tipo='A Convenir' data-descuento='0'
                    name="envio">
                <div class='envioT'>A Convenir</div>
            </div>
        </div>


    </div>

</div> --}}

<div class='cuadro-info-pedido' data-cantidad={{ Cart::count() }} style="height: 211px;">
    <div class='cuadro-titulo'><p>TU PEDIDO</p></div>
    <div class='d-flex justify-content-between detalle' style='padding-bottom:5px;align-items:center;'>
        <div class="precioT">Subtotal</div>
        <div class="precioP">$ {{ App\Models\Carrito::subtotal_format() }}</div>
    </div>
    {{-- <div class='d-flex justify-content-between detalle' style='align-items:center;padding-bottom:15px;'>
        <div class="precioT">Bonificación ({{ App\Models\Carrito::bonificacion() }}%)</div>
        <div class="precioPD">-${{ App\Models\Carrito::total_bonificacion() }}</div>
    </div> --}}
    <!--@if(Auth::guard('web')->user()->descuento > 0)-->
    <!--<div class='d-flex justify-content-between detalle' style='padding-bottom:5px;align-items:center;'>-->
    <!--    <div class="precioT" style="color: #00AB07">Descuento cliente ({{ Auth::guard('web')->user()->descuento }}%)</div>-->
    <!--    <div class="precioP" style="color: #00AB07">-$ {{ App\Models\Carrito::descuentoCliente() }}</div>-->
    <!--</div>-->
    <!--@endif-->
    <div class='d-flex justify-content-between detalle'>
        <div class="precioT">IVA {{ App\Models\Impuesto::find(1)->porcentaje }}%</div>
        <div class="precioP">+$ {{ App\Models\Carrito::iva() }}</div>
    </div>

    <div class='d-flex justify-content-between detalle'
        style='padding-top:20px;align-items:center;padding-bottom: 0px;'>
        <div class="totalT">Total <span class="ivaIncluido">(IVA INCLUIDO)</span></div>
        <div class="totalP">$ {{ App\Models\Carrito::total_format() }}</div>
    </div>
    <input hidden type="text" name='total_pedido' value="{{ App\Models\Carrito::total_format() }}">
</div>

<script>
    if (document.querySelector('.cuadro-info-pedido').getAttribute('data-cantidad') == 0) {
        document.querySelector('.enviar-pedido').disabled = true
    }
    document.querySelectorAll('.envio-opcion').forEach(input => {
        if (input.getAttribute('data-tipo') == '{{ session('tipo_envio') }}') {
            input.click()
        }
    })
    // PAGO INFO
    $('.envio-opcion').on('click', function() {
        document.querySelectorAll('.pago-info').forEach(element => {
            if ($(element).is(':visible')) {
                $(element).slideUp();
            }
        });
        var pagoInfo = $(this).closest('.carrito-total-opcion').next('.pago-info')
        pagoInfo.slideDown()

        var tipo = this.getAttribute('data-tipo')
        document.getElementById('envio-input').value = tipo
        // document.getElementById('descuento-input').value = this.getAttribute('data-descuento')

        // actualizarCosto(this.getAttribute('data-descuento'))

        $.ajax({
            url: "{{ route('tipo.envio') }}",
            type: 'GET',
            data: {
                tipo: tipo
            },
            success: function(response) {
                console.log(response)
                actualizar_pedido('private')
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    })
</script>
