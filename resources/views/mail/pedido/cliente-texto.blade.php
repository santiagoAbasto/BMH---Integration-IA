¡Gracias por tu pedido, {{ $pedido->nombre }}!
Pedido #{{ $pedido->id }}. Ya lo recibimos y lo estamos preparando.

@foreach ($pedido->lineas() as $linea)
- {{ $linea->producto->codigo ?? '—' }} · {{ $linea->producto->nombre ?? '—' }} · {{ $linea->cantidad }} × $ {{ number_format((float) $linea->precio_neto, 2, ',', '.') }} = $ {{ number_format((float) $linea->subtotal, 2, ',', '.') }}
@endforeach

Subtotal: $ {{ number_format($pedido->subtotal(), 2, ',', '.') }}
IVA ({{ $pedido->porcentajeIva() + 0 }}%): $ {{ number_format($pedido->iva(), 2, ',', '.') }}
Total: $ {{ $pedido->total_pedido }}

@if (filled($mensajeEnvio))
Entrega: {{ trim(html_entity_decode(strip_tags((string) $mensajeEnvio))) }}

@endif
Ver mis pedidos: {{ route('cliente.historial') }}

---
{{ \App\Support\MailMarca::pie() }}
