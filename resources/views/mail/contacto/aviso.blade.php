{{-- Aviso a BMH de una consulta nueva desde /contacto (App\Mail\ContactoAviso). $message: el mensaje de Laravel (logo embebido). --}}
@php
    $tel = preg_replace('/(?!^\+)[^\d]/', '', (string) $mensaje->telefono);
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
@endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" title="Nueva consulta" eyebrow="Contacto · Nueva consulta" :preheader="$mensaje->nombre.' te escribió desde la web'.($mensaje->motivo ? ': '.$mensaje->motivo : '.')">
    <h1 class="bmh-mail__h1" style="margin:0 0 6px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">{{ $mensaje->nombre }} te escribió desde la web</h1>
    <p style="margin:0 0 24px;color:#5b6575;font-size:14px;line-height:20px;">{{ $mensaje->created_at?->format('d/m/Y \a \l\a\s H:i') }} h</p>

    <x-mail.datos :rows="[
        'Nombre' => $mensaje->nombre,
        'Email' => ['text' => $mensaje->email, 'href' => 'mailto:'.$mensaje->email],
        'Celular' => $tel !== '' ? ['text' => $mensaje->telefono, 'href' => 'tel:'.$tel] : null,
        'Empresa' => $mensaje->empresa,
        'Motivo' => $mensaje->motivo,
    ]" />

    @if (filled($mensaje->mensaje))
        <p style="margin:0 0 8px;color:#5b6575;font-size:13px;line-height:18px;font-weight:600;">Mensaje</p>
        <div style="margin:0 0 26px;padding:16px 18px;border-left:3px solid #0098da;border-radius:0 8px 8px 0;background:#f3f5f8;color:#1c2430;font-size:15px;line-height:24px;white-space:pre-line;">{{ $mensaje->mensaje }}</div>
    @else
        <p style="margin:0 0 26px;color:#5b6575;font-size:14px;line-height:20px;">No dejó un mensaje: escribile o llamalo para ver qué necesita.</p>
    @endif

    <x-mail.boton :href="$mensaje->mailtoRespuesta()">Responder a {{ \Illuminate\Support\Str::before($mensaje->nombre, ' ') ?: $mensaje->nombre }}</x-mail.boton>
    @if ($mensaje->exists)
        <x-mail.boton :href="route('dashboard.contacto.mensajes', ['mensaje' => $mensaje->id])" secundario>Ver en el panel</x-mail.boton>
    @endif

    <x-slot:footer>Llegó desde el formulario de Contacto del sitio. Si respondés este mail, le escribís directo a {{ $mensaje->nombre }}. También queda guardado en el panel, en Contacto › Mensajes.</x-slot:footer>
</x-mail.layout>
