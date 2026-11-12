¡Gracias, {{ \Illuminate\Support\Str::before(trim($mensaje->nombre), ' ') ?: $mensaje->nombre }}!

Recibimos tu consulta y ya está en manos de nuestro equipo. Te respondemos a la brevedad, de lunes a viernes de 8 a 17 hs.
Si querés sumar algo, respondé este mail y nos llega directo.

Tu consulta{{ $mensaje->motivo ? ' · '.$mensaje->motivo : '' }}:
{{ filled($mensaje->mensaje) ? \Illuminate\Support\Str::limit($mensaje->mensaje, 800) : 'Nos dejaste tus datos para que te contactemos.' }}

@if (filled($contacto?->whatsapp))
WhatsApp: {{ $contacto->whatsapp }}
@endif
@if (filled($contacto?->tel))
Teléfono: {{ $contacto->tel }}
@endif

---
{{ \App\Support\MailMarca::pie() }}
Te llega este mail porque escribiste desde el formulario de Contacto de nuestro sitio.
