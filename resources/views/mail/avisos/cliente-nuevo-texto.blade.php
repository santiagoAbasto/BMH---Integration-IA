{{ $cliente->name }} se creó una cuenta en la Zona de Clientes
{{ $cliente->created_at?->format('d/m/Y H:i') }} h

Nombre: {{ $cliente->name }}
Usuario: {{ $cliente->username }}
@if (filled($cliente->email))
Email: {{ $cliente->email }}
@endif
@if (filled($cliente->celular))
Celular: {{ $cliente->celular }}
@endif
@if (filled($cliente->dni))
CUIT / DNI: {{ $cliente->dni }}
@endif
Dirección: {{ collect([$cliente->direccion, $cliente->localidad, $cliente->provincia, $cliente->cp ? 'CP '.$cliente->cp : null])->filter()->implode(', ') }}

Revisá sus datos y, si corresponde, habilitalo en Clientes: {{ route('dashboard.clientes') }}

---
Aviso automático del sitio. Lo configurás en Contacto › Contenido › Avisos por email.
{{ \App\Support\MailMarca::pie() }}
