{{--
    Una versión del logo por estado del header; layouts/partials/apariencia
    muestra una sola: reposo (según la página), al hacer scroll, o mobile.
--}}
@php
    $urlsLogo = [
        \App\Models\Apariencia::LOGO_TRANSPARENTE => $logosSitio->url(\App\Services\LogosSitio::HEADER_TRANSPARENTE),
        \App\Models\Apariencia::LOGO_BLANCO => $logosSitio->url(\App\Services\LogosSitio::HEADER_BLANCO),
    ];
    $logoReposo = $urlsLogo[Route::is('home') ? \App\Models\Apariencia::LOGO_TRANSPARENTE : \App\Models\Apariencia::LOGO_BLANCO];
    $logoScroll = $urlsLogo[$apariencia->header_scroll_logo] ?? $logoReposo;
    $logoMobile = $urlsLogo[$apariencia->header_mobile_logo] ?? $logoReposo;
@endphp
<img id="logo1" class="logo-header logo-reposo" src="{{ $logoReposo }}" alt="BMH">
<img class="logo-header logo-scroll" src="{{ $logoScroll }}" alt="" aria-hidden="true">
<img class="logo-header logo-mobile" src="{{ $logoMobile }}" alt="" aria-hidden="true">
