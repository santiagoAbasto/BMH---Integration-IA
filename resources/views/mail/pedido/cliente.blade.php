{{-- Confirmación al cliente de su pedido (App\Mail\PedidoClienteMail). --}}
@php $titulos = \App\Support\MailMarca::FUENTE_TITULOS; @endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" title="Recibimos tu pedido" eyebrow="Pedido #{{ $pedido->id }}" :preheader="'Recibimos tu pedido #'.$pedido->id.' por $ '.$pedido->total_pedido.'. Te avisamos cuando esté listo.'">
    <h1 class="bmh-mail__h1" style="margin:0 0 10px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">¡Gracias por tu pedido, {{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::before(trim((string) $pedido->nombre), ' ') ?: (string) $pedido->nombre) }}!</h1>
    <p style="margin:0 0 24px;color:#1c2430;font-size:15px;line-height:24px;">Ya lo recibimos y lo estamos preparando. Si necesitamos confirmar algo, te escribimos o te llamamos.</p>

    <x-mail.pedido :pedido="$pedido" />

    @if (filled($mensajeEnvio))
        <p style="margin:0 0 8px;color:#5b6575;font-size:13px;line-height:18px;font-weight:600;">Entrega</p>
        <div style="margin:0 0 24px;padding:14px 18px;border-left:3px solid #0098da;border-radius:0 8px 8px 0;background:#f3f5f8;color:#1c2430;font-size:14px;line-height:22px;">{!! str_replace('<p>', '<p style="margin:0 0 6px;">', strip_tags((string) $mensajeEnvio, '<p><br><strong><b><em><ul><ol><li><a>')) !!}</div>
    @endif

    <x-mail.datos :rows="[
        'Envío' => $pedido->tipo_envio,
        'Pago' => $pedido->tipo_pago,
        'Dirección' => collect([$pedido->direccion, $pedido->localidad, $pedido->provincia])->filter()->implode(', '),
    ]" />

    <x-mail.boton :href="route('cliente.historial')">Ver mis pedidos</x-mail.boton>

    <x-slot:footer>¿Dudas con tu pedido? Respondé este mail o escribinos desde la web. {{ \App\Support\MailMarca::pie() }}</x-slot:footer>
</x-mail.layout>
