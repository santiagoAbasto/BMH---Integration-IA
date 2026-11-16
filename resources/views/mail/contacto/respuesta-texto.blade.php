Hola {{ $mensaje->nombreDePila() }},

{{ $respuesta->cuerpo }}

{{ filled($respuesta->autor) ? $respuesta->autor : 'Equipo de BMH' }}
{{ \App\Support\MailMarca::NOMBRE }}
{{ route('home') }}

—
Tu consulta del {{ $mensaje->created_at?->format('d/m/Y') }}:
@if ($mensaje->motivo)
{{ $mensaje->motivo }}
@endif
{{ filled($mensaje->mensaje) ? $mensaje->mensaje : 'Sin mensaje.' }}

---
Para seguir la conversación, respondé este mail.
{{ \App\Support\MailMarca::pie() }}
