{{-- Aviso a BMH: un cliente se creó una cuenta (App\Mail\AvisoClienteNuevo). --}}
@php
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
    $tel = preg_replace('/(?!^\+)[^\d]/', '', (string) $cliente->celular);
    $direccion = collect([$cliente->direccion, $cliente->localidad, $cliente->provincia, $cliente->cp ? 'CP '.$cliente->cp : null])->filter()->implode(', ');
@endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" title="Cliente nuevo" eyebrow="Zona de Clientes · Registro" :preheader="$cliente->name.' se creó una cuenta en la Zona de Clientes.'">
    <h1 class="bmh-mail__h1" style="margin:0 0 6px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">{{ $cliente->name }} se creó una cuenta</h1>
    <p style="margin:0 0 24px;color:#5b6575;font-size:14px;line-height:20px;">{{ $cliente->created_at?->format('d/m/Y \a \l\a\s H:i') }} h · desde el registro de la Zona de Clientes</p>

    <x-mail.datos :rows="[
        'Nombre' => $cliente->name,
        'Usuario' => $cliente->username,
        'Email' => filter_var($cliente->email, FILTER_VALIDATE_EMAIL) ? ['text' => $cliente->email, 'href' => 'mailto:'.$cliente->email] : $cliente->email,
        'Celular' => $tel !== '' ? ['text' => $cliente->celular, 'href' => 'tel:'.$tel] : null,
        'CUIT / DNI' => $cliente->dni,
        'Dirección' => $direccion,
    ]" />

    <p style="margin:0 0 22px;padding:14px 18px;border-radius:8px;background:#fff4e0;color:#5c3a00;font-size:14px;line-height:21px;">
        Revisá sus datos y, si corresponde, <strong>habilitalo en Clientes</strong> para que vea los precios y pueda comprar.
    </p>

    <x-mail.boton :href="route('dashboard.clientes')">Ver en Clientes</x-mail.boton>
    @if (filter_var($cliente->email, FILTER_VALIDATE_EMAIL))
        <x-mail.boton :href="'mailto:'.$cliente->email" secundario>Escribirle</x-mail.boton>
    @endif

    <x-slot:footer>Aviso automático del sitio. Si respondés este mail, le escribís a {{ $cliente->name }}. Lo configurás en el panel: Contacto › Contenido › Avisos por email.</x-slot:footer>
</x-mail.layout>
