{{ $mensaje->nombre }} te escribió desde la web
{{ $mensaje->created_at?->format('d/m/Y H:i') }} h

Nombre: {{ $mensaje->nombre }}
Email: {{ $mensaje->email }}
@if (filled($mensaje->telefono))
Celular: {{ $mensaje->telefono }}
@endif
@if (filled($mensaje->empresa))
Empresa: {{ $mensaje->empresa }}
@endif
@if (filled($mensaje->motivo))
Motivo: {{ $mensaje->motivo }}
@endif

@if (filled($mensaje->mensaje))
Mensaje:
{{ $mensaje->mensaje }}
@else
No dejó un mensaje: escribile o llamalo para ver qué necesita.
@endif

@if ($mensaje->exists)
Ver en el panel: {{ route('dashboard.contacto.mensajes', ['mensaje' => $mensaje->id]) }}
@endif

---
Llegó desde el formulario de Contacto del sitio. Si respondés este mail, le escribís directo a {{ $mensaje->nombre }}.
{{ \App\Support\MailMarca::pie() }}
