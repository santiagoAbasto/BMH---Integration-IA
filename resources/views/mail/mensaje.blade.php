{{-- Mail al cliente con un texto cargado en el panel (App\Mail\CompraMail): bienvenida y cuenta habilitada. --}}
@php
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
    $html = \App\Support\TextoAdmin::html($contenido);
@endphp
<x-mail.layout :logo="\App\Support\MailMarca::logo($message ?? null)" :title="$titulo" :preheader="\Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $contenido))), 110)">
    <h1 class="bmh-mail__h1" style="margin:0 0 16px;color:#0a0e14;font-family:{{ $titulos }};font-size:24px;line-height:30px;font-weight:800;">{{ $titulo }}</h1>
    <div style="margin:0 0 24px;color:#1c2430;font-size:15px;line-height:24px;">{!! $html ? str_replace('<p>', '<p style="margin:0 0 14px;">', $html->toHtml()) : '' !!}</div>
    <x-mail.boton :href="route('home')">Ir a BMH Distribuidora</x-mail.boton>
</x-mail.layout>
