Pedido nuevo #{{ $pedido->id }} de {{ $pedido->nombre }}
{{ $pedido->created_at?->format('d/m/Y H:i') }} h · Total $ {{ $pedido->total_pedido }}

Cliente: {{ $pedido->nombre }}
@if (filled($pedido->mail))
Email: {{ $pedido->mail }}
@endif
@if (filled($pedido->celular))
Celular: {{ $pedido->celular }}
@endif
Envío: {{ $pedido->tipo_envio }} · Pago: {{ $pedido->tipo_pago }}
Entrega: {{ collect([$pedido->direccion, $pedido->localidad, $pedido->provincia, $pedido->cp ? 'CP '.$pedido->cp : null])->filter()->implode(', ') }}
@if (filled($pedido->notas))

Nota del cliente:
{{ $pedido->notas }}
@endif

@foreach ($pedido->lineas() as $linea)
- {{ $linea->producto->codigo ?? '—' }} · {{ $linea->producto->nombre ?? '—' }} · {{ $linea->cantidad }} × $ {{ number_format((float) $linea->precio_neto, 2, ',', '.') }} = $ {{ number_format((float) $linea->subtotal, 2, ',', '.') }}
@endforeach

Subtotal: $ {{ number_format($pedido->subtotal(), 2, ',', '.') }}
IVA ({{ $pedido->porcentajeIva() + 0 }}%): $ {{ number_format($pedido->iva(), 2, ',', '.') }}
Total: $ {{ $pedido->total_pedido }}

Ver el pedido en el panel: {{ route('pedido.datos', ['id' => $pedido->id]) }}

---
Aviso automático del sitio. Lo configurás en Contacto › Contenido › Avisos por email.
{{ \App\Support\MailMarca::pie() }}
