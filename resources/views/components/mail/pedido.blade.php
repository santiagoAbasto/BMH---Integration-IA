@props(['pedido'])
{{-- Detalle de un pedido en los emails (Pedido::lineas: precios del momento de la compra). Tablas e inline, como el
     resto de los mails de BMH. --}}
@php
    $pesos = fn ($n) => '$ '.number_format((float) $n, 2, ',', '.');
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 6px;border-collapse:collapse;">
    <tr>
        <td style="padding:0 0 8px;border-bottom:2px solid #0a0e14;color:#5b6575;font-size:12px;line-height:16px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Producto</td>
        <td align="center" width="56" style="padding:0 0 8px;border-bottom:2px solid #0a0e14;color:#5b6575;font-size:12px;line-height:16px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Cant.</td>
        <td align="right" width="110" style="padding:0 0 8px;border-bottom:2px solid #0a0e14;color:#5b6575;font-size:12px;line-height:16px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Subtotal</td>
    </tr>
    @foreach ($pedido->lineas() as $linea)
        <tr>
            <td style="padding:12px 10px 12px 0;border-bottom:1px solid #e2e7ee;vertical-align:top;">
                <span style="display:inline-block;margin:0 0 3px;padding:1px 7px;border-radius:5px;background:#e5f5fc;color:#0076a8;font-family:{{ $titulos }};font-size:12px;font-weight:700;">{{ $linea->producto->codigo ?? '—' }}</span>
                <div style="color:#0a0e14;font-size:14px;line-height:20px;font-weight:600;">{{ $linea->producto->nombre ?? '—' }}</div>
                <div style="color:#5b6575;font-size:12px;line-height:18px;">{{ $pesos($linea->precio_neto) }} c/u @if ((float) $linea->descuento_cliente > 0) · {{ $linea->descuento_cliente + 0 }}% de descuento @endif</div>
            </td>
            <td align="center" style="padding:12px 0;border-bottom:1px solid #e2e7ee;color:#0a0e14;font-size:14px;line-height:20px;vertical-align:top;">{{ $linea->cantidad }}</td>
            <td align="right" style="padding:12px 0;border-bottom:1px solid #e2e7ee;color:#0a0e14;font-size:14px;line-height:20px;font-weight:600;vertical-align:top;white-space:nowrap;">{{ $pesos($linea->subtotal) }}</td>
        </tr>
    @endforeach
</table>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td align="right" style="padding:6px 16px 6px 0;color:#5b6575;font-size:14px;line-height:20px;">Subtotal</td>
        <td align="right" width="130" style="padding:6px 0;color:#0a0e14;font-size:14px;line-height:20px;white-space:nowrap;">{{ $pesos($pedido->subtotal()) }}</td>
    </tr>
    <tr>
        <td align="right" style="padding:6px 16px 6px 0;color:#5b6575;font-size:14px;line-height:20px;">IVA ({{ $pedido->porcentajeIva() + 0 }}%)</td>
        <td align="right" style="padding:6px 0;color:#0a0e14;font-size:14px;line-height:20px;white-space:nowrap;">{{ $pesos($pedido->iva()) }}</td>
    </tr>
    <tr>
        <td align="right" style="padding:10px 16px 0 0;color:#0a0e14;font-family:{{ $titulos }};font-size:16px;line-height:22px;font-weight:800;">Total</td>
        <td align="right" style="padding:10px 0 0;color:#0076a8;font-family:{{ $titulos }};font-size:20px;line-height:24px;font-weight:800;white-space:nowrap;">$ {{ $pedido->total_pedido }}</td>
    </tr>
</table>
