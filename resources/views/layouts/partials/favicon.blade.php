{{-- Favicon: el que se sube en Extras › Favicon o, si no hay, el de BMH (las tres barras del logo): SVG para los
     navegadores actuales, .ico de 16/32/48 de respaldo y el ícono para la pantalla de inicio del celular. --}}
@php($logosSitio = app(\App\Services\LogosSitio::class))
@if ($logosSitio->tienePropio(\App\Services\LogosSitio::FAVICON))
    <link rel="icon" href="{{ $logosSitio->url(\App\Services\LogosSitio::FAVICON) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<meta name="theme-color" content="#0a0e14">
