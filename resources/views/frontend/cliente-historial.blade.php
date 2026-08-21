@extends('layouts.plantilla-front')

@section('styles')
<style>
    .modal-body{
        padding: 30px;
    }
    table{
        border:none !important;
    }
    tr{
        border-bottom:1px solid #E1E5E9;
    }
    thead{
        background-color: #E1E5E9;
        color: var(--Gris-medio-2, #656565);
        font-family: "DM Sans";
        font-size: 14px;
        font-style: normal;
        line-height: normal;
        letter-spacing: 0.98px;
        text-transform: uppercase;
    }
    th{
        font-weight: 400 !important;
    }
    td{
        height: 60px;
        align-content: center;
        color: var(--tipografia, #1E1E1E);
        font-size: 15px;
        font-style: normal;
        font-weight: 400;
        line-height: 140%; /* 21px */
    }
    .pedir-btn, .detalle-btn{
        height: 32px;
        padding:0px 12px 0px 12px;
        color: #0C0C0C;
        font-size: 15px;
        font-style: normal;
        font-weight: 400;
        line-height: normal;
        border-radius: 40px;
        background-color: #fff;
        transition: all ease 0.3s;
    }
    .detalle-btn{
        border: 1px solid #0098DA;
    }
    .detalle-btn:hover{
        background-color: #0098DA;
        color: #fff;
    }
    .pedir-btn{
        border: 1px solid #0098DA;
        margin-right: 20px;
    }
    .pedir-btn:hover{
        border-color: #0098DA;
        background-color: #0098DA;
        color: white;
    }
</style>
@endsection


@section('content')


    <section style='padding-top:69px;padding-bottom:125px' data-aos='fade-up'>

        <div class='container' style='overflow-x:scroll;'>
            @if(count($historial) > 0)
            
                <table class="table" style='border: 1px solid #dddddd;'>
                    <thead>
                      <tr>
                        <th>Fecha</th>
                        <th>Pedido</th>
                        <th>Productos</th>
                        <th style='text-align:right;'>Subtotal</th>
                        @if(Auth::guard('web')->user()->descuento > 0)
                        <th style='text-align:center;'>desc</th>
                        @endif
                        <th style='text-align:right;'>Total</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id='productos-contenedor'>
                        @foreach ($historial as $pedido)
                        <tr>
                            <td>{{$pedido->fecha}}</td>
                            <td>{{$pedido->id}}</td>
                            <td>
                                @foreach($pedido->productos()->get() as $producto)
                                {{$producto->id.'; '}}
                                @endforeach
                            </td>
                            <td style='text-align:right;'>${{$pedido->subtotal_format()}}</td>
                            @if(Auth::guard('web')->user()->descuento > 0)
                            <td style='text-align:center;color:#28811A;'>{{'(-'.$pedido->descuento_cliente.'%)' }}</td>
                            @endif

                            <td style='text-align:right;'>${{$pedido->total_pedido}}</td>
                            <td class='d-flex' style='justify-content:center;align-items:center;'>
                                <button class='pedir-btn' onclick="repetir_pedido(event, {{$pedido->id}})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="20" viewBox="0 0 19 20" fill="none">
                                    <path d="M14.1788 4.45832H4.67878L5.70795 3.43707C5.85702 3.28799 5.94077 3.08581 5.94077 2.87498C5.94077 2.66416 5.85702 2.46197 5.70795 2.3129C5.55888 2.16383 5.35669 2.08008 5.14587 2.08008C4.93504 2.08008 4.73286 2.16383 4.58378 2.3129L2.20878 4.6879C2.13458 4.7615 2.07569 4.84906 2.03549 4.94553C1.9953 5.042 1.97461 5.14547 1.97461 5.24998C1.97461 5.35449 1.9953 5.45797 2.03549 5.55444C2.07569 5.65091 2.13458 5.73847 2.20878 5.81207L4.58378 8.18707C4.65738 8.26127 4.74494 8.32016 4.84141 8.36036C4.93788 8.40055 5.04136 8.42124 5.14587 8.42124C5.25038 8.42124 5.35385 8.40055 5.45032 8.36036C5.54679 8.32016 5.63435 8.26127 5.70795 8.18707C5.78215 8.11347 5.84105 8.02591 5.88124 7.92944C5.92143 7.83297 5.94212 7.72949 5.94212 7.62498C5.94212 7.52047 5.92143 7.417 5.88124 7.32053C5.84105 7.22406 5.78215 7.1365 5.70795 7.0629L4.67878 6.04165H14.1788C14.341 6.0385 14.5022 6.06734 14.6532 6.12651C14.8043 6.18568 14.9422 6.27403 15.0591 6.38651C15.176 6.49899 15.2696 6.6334 15.3345 6.78206C15.3994 6.93072 15.4344 7.09071 15.4375 7.2529V9.20832C15.4375 9.41828 15.5209 9.61964 15.6694 9.76811C15.8179 9.91658 16.0192 9.99998 16.2292 9.99998C16.4392 9.99998 16.6405 9.91658 16.789 9.76811C16.9375 9.61964 17.0209 9.41828 17.0209 9.20832V7.2529C17.0178 6.88279 16.9418 6.51691 16.7973 6.17616C16.6528 5.83542 16.4425 5.52647 16.1786 5.26696C15.9147 5.00746 15.6023 4.80248 15.2591 4.66373C14.916 4.52499 14.5489 4.45519 14.1788 4.45832ZM14.4163 11.8129C14.2672 11.6638 14.065 11.5801 13.8542 11.5801C13.6434 11.5801 13.4412 11.6638 13.2921 11.8129C13.143 11.962 13.0593 12.1642 13.0593 12.375C13.0593 12.5858 13.143 12.788 13.2921 12.9371L14.3213 13.9583H4.82128C4.65909 13.9615 4.49787 13.9326 4.34683 13.8735C4.19578 13.8143 4.05788 13.7259 3.94098 13.6135C3.82409 13.501 3.73051 13.3666 3.66557 13.2179C3.60063 13.0693 3.56562 12.9093 3.56253 12.7471V10.7917C3.56253 10.5817 3.47913 10.3803 3.33066 10.2319C3.18219 10.0834 2.98083 9.99998 2.77087 9.99998C2.5609 9.99998 2.35954 10.0834 2.21107 10.2319C2.06261 10.3803 1.9792 10.5817 1.9792 10.7917V12.7471C1.98231 13.1172 2.05828 13.4831 2.20279 13.8238C2.34731 14.1646 2.55752 14.4735 2.82144 14.733C3.08535 14.9925 3.3978 15.1975 3.74093 15.3362C4.08407 15.475 4.45117 15.5448 4.82128 15.5417H14.3213L13.2921 16.5629C13.2179 16.6365 13.159 16.7241 13.1188 16.8205C13.0786 16.917 13.0579 17.0205 13.0579 17.125C13.0579 17.2295 13.0786 17.333 13.1188 17.4294C13.159 17.5259 13.2179 17.6135 13.2921 17.6871C13.3657 17.7613 13.4533 17.8202 13.5497 17.8604C13.6462 17.9005 13.7497 17.9212 13.8542 17.9212C13.9587 17.9212 14.0622 17.9005 14.1587 17.8604C14.2551 17.8202 14.3427 17.7613 14.4163 17.6871L16.7913 15.3121C16.8655 15.2385 16.9244 15.1509 16.9646 15.0544C17.0048 14.958 17.0255 14.8545 17.0255 14.75C17.0255 14.6455 17.0048 14.542 16.9646 14.4455C16.9244 14.3491 16.8655 14.2615 16.7913 14.1879L14.4163 11.8129Z" fill="black"/>
                                </svg>
                                Volver a pedir</button>
                                <a href="{{route('cliente.pedido', ['id' => $pedido->id])}}"><button class='detalle-btn'>Ver detalle</button></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            @else

            <div style='text-align:center;'>No has realizado ninguna compra aún.</div>
            
            @endif
        </div>
    </section>
    
@endsection

@section('script')
<script>
    function repetir_pedido(event, id){
        var btn = event.target.closest('.pedir-btn');
        btn.style.backgroundColor = "#E1E5E9"
        $.ajax({
            url: "{{ route('repetir.pedido') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
            },
            success: function(response) {
                // console.log(response);
                iziToast.success({
                    title: 'Productos agregados al carrito',
                    backgroundColor: '#DAF6D3',
                    titleColor:'#479831',
                    iconColor:'#479831',
                    progressBar:false,
                    icon:'fa-solid fa-square-check',
                    position:'bottomRight',
                });
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                toastr.info('Ha ocurrido un error')
            }
        });
    }
</script>
@endsection
