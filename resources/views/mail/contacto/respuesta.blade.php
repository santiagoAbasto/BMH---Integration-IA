{{-- Respuesta de BMH a una consulta de /contacto (App\Mail\RespuestaContacto): el texto escrito en el panel y, abajo,
     la consulta original citada. --}}
@php $titulos = \App\Support\MailMarca::FUENTE_TITULOS; @endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" :title="$respuesta->asunto" eyebrow="Respuesta a tu consulta" :preheader="\Illuminate\Support\Str::limit($respuesta->cuerpo, 110)">
    <p style="margin:0 0 18px;color:#0a0e14;font-family:{{ $titulos }};font-size:18px;line-height:26px;font-weight:700;">Hola {{ $mensaje->nombreDePila() }},</p>
    <div style="margin:0 0 26px;color:#1c2430;font-size:15px;line-height:25px;white-space:pre-line;">{{ $respuesta->cuerpo }}</div>

    <p style="margin:0 0 4px;color:#0a0e14;font-size:15px;line-height:22px;font-weight:600;">{{ filled($respuesta->autor) ? $respuesta->autor : 'Equipo de BMH' }}</p>
    <p style="margin:0 0 28px;color:#5b6575;font-size:14px;line-height:20px;">{{ \App\Support\MailMarca::NOMBRE }}</p>

    <p style="margin:0 0 8px;color:#5b6575;font-size:12px;line-height:16px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Tu consulta del {{ $mensaje->created_at?->format('d/m/Y') }}</p>
    <div style="margin:0 0 6px;padding:14px 18px;border-left:3px solid #c4ccd7;border-radius:0 8px 8px 0;background:#f8fafc;color:#5b6575;font-size:14px;line-height:22px;white-space:pre-line;">@if ($mensaje->motivo)<strong style="color:#1c2430;">{{ $mensaje->motivo }}</strong>
@endif{{ filled($mensaje->mensaje) ? $mensaje->mensaje : 'Sin mensaje.' }}</div>

    <x-slot:footer>Para seguir la conversación, respondé este mail. {{ \App\Support\MailMarca::pie() }}</x-slot:footer>
</x-mail.layout>
