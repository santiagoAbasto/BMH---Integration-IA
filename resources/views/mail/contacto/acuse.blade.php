{{-- Acuse de recibo para quien escribió desde /contacto (App\Mail\ContactoAcuse). $message: el mensaje de Laravel (logo embebido). --}}
@php
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
    $primerNombre = \Illuminate\Support\Str::before(trim($mensaje->nombre), ' ') ?: $mensaje->nombre;
    $wa = preg_replace('/[^0-9]/', '', (string) $contacto?->whatsapp);
    $tel = $contacto?->telHref();
@endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" title="Recibimos tu consulta" eyebrow="Recibimos tu consulta" preheader="Gracias por escribirnos: te respondemos a la brevedad.">
    <h1 class="bmh-mail__h1" style="margin:0 0 14px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">¡Gracias, {{ $primerNombre }}!</h1>
    <p style="margin:0 0 14px;color:#1c2430;font-size:15px;line-height:24px;">Recibimos tu consulta y ya está en manos de nuestro equipo. Te respondemos a la brevedad, de lunes a viernes de 8 a 17 hs.</p>
    <p style="margin:0 0 24px;color:#1c2430;font-size:15px;line-height:24px;">Si querés sumar algo, respondé este mail y nos llega directo.</p>

    <div style="margin:0 0 26px;padding:16px 18px;border-left:3px solid #0098da;border-radius:0 8px 8px 0;background:#f3f5f8;">
        <p style="margin:0 0 6px;color:#0a0e14;font-family:{{ $titulos }};font-size:13px;line-height:18px;font-weight:700;">Tu consulta{{ $mensaje->motivo ? ' · '.$mensaje->motivo : '' }}</p>
        <p style="margin:0;color:#5b6575;font-size:14px;line-height:22px;white-space:pre-line;">{{ filled($mensaje->mensaje) ? \Illuminate\Support\Str::limit($mensaje->mensaje, 800) : 'Nos dejaste tus datos para que te contactemos.' }}</p>
    </div>

    @if ($wa !== '' || $tel)
        <p style="margin:0 0 12px;color:#1c2430;font-size:15px;line-height:24px;">¿Es urgente? Escribinos o llamanos:</p>
        @if ($wa !== '')
            <x-mail.boton :href="'https://wa.me/'.$wa">WhatsApp {{ $contacto->whatsapp }}</x-mail.boton>
        @endif
        @if ($tel)
            <x-mail.boton :href="$tel" secundario>Llamar al {{ $contacto->tel }}</x-mail.boton>
        @endif
    @else
        <x-mail.boton :href="route('productos')">Ver productos</x-mail.boton>
    @endif

    <x-slot:footer>{{ \App\Support\MailMarca::pie() }}<br>Te llega este mail porque escribiste desde el formulario de Contacto de nuestro sitio.</x-slot:footer>
</x-mail.layout>
