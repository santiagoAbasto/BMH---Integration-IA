{{-- Aviso a BMH de un pedido nuevo (App\Mail\AvisoPedidoNuevo). --}}
@php
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
    $tel = preg_replace('/(?!^\+)[^\d]/', '', (string) $pedido->celular);
    $entrega = collect([$pedido->direccion, $pedido->localidad, $pedido->provincia, $pedido->cp ? 'CP '.$pedido->cp : null])->filter()->implode(', ');
@endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" title="Pedido nuevo" eyebrow="Pedido #{{ $pedido->id }}" :preheader="$pedido->nombre.' hizo un pedido por $ '.$pedido->total_pedido.'.'">
    <h1 class="bmh-mail__h1" style="margin:0 0 6px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">Pedido nuevo de {{ $pedido->nombre }}</h1>
    <p style="margin:0 0 24px;color:#5b6575;font-size:14px;line-height:20px;">{{ $pedido->created_at?->format('d/m/Y \a \l\a\s H:i') }} h · Total <strong style="color:#0a0e14;">$ {{ $pedido->total_pedido }}</strong></p>

    <x-mail.datos :rows="[
        'Cliente' => $pedido->nombre,
        'Email' => filter_var($pedido->mail, FILTER_VALIDATE_EMAIL) ? ['text' => $pedido->mail, 'href' => 'mailto:'.$pedido->mail] : $pedido->mail,
        'Celular' => $tel !== '' ? ['text' => $pedido->celular, 'href' => 'tel:'.$tel] : null,
        'CUIT / DNI' => $pedido->dni,
        'Envío' => $pedido->tipo_envio,
        'Pago' => $pedido->tipo_pago,
        'Entrega' => $entrega,
    ]" />

    @if (filled($pedido->notas))
        <p style="margin:0 0 8px;color:#5b6575;font-size:13px;line-height:18px;font-weight:600;">Nota del cliente</p>
        <div style="margin:0 0 24px;padding:14px 18px;border-left:3px solid #0098da;border-radius:0 8px 8px 0;background:#f3f5f8;color:#1c2430;font-size:15px;line-height:23px;white-space:pre-line;">{{ $pedido->notas }}</div>
    @endif

    <x-mail.pedido :pedido="$pedido" />

    @if ($archivo)
        <p style="margin:0 0 22px;color:#5b6575;font-size:13px;line-height:19px;">📎 Adjuntó un archivo: <strong style="color:#0a0e14;">{{ $archivo->getClientOriginalName() }}</strong> (va en este mail).</p>
    @endif

    <x-mail.boton :href="route('pedido.datos', ['id' => $pedido->id])">Ver el pedido en el panel</x-mail.boton>
    @if (filter_var($pedido->mail, FILTER_VALIDATE_EMAIL))
        <x-mail.boton :href="'mailto:'.$pedido->mail.'?subject='.rawurlencode('Tu pedido #'.$pedido->id.' en BMH')" secundario>Escribirle</x-mail.boton>
    @endif

    <x-slot:footer>Aviso automático del sitio. Si respondés este mail, le escribís a {{ $pedido->nombre }}. Lo configurás en el panel: Contacto › Contenido › Avisos por email.</x-slot:footer>
</x-mail.layout>
