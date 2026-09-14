{{--
    Una versión del logo por estado del header; layouts/partials/apariencia
    muestra una sola: reposo (según la página), al hacer scroll, o celular.

    Cada estado elige su logo en el admin, porque también elige su color de
    fondo: si el header de las páginas internas se pinta oscuro, ahí conviene
    el logo para fondo transparente y no el de fondo blanco.
--}}
@php
    use App\Models\Apariencia;
    use App\Services\LogosSitio;

    $urlsLogo = [
        Apariencia::LOGO_TRANSPARENTE => $logosSitio->url(LogosSitio::HEADER_TRANSPARENTE),
        Apariencia::LOGO_BLANCO => $logosSitio->url(LogosSitio::HEADER_BLANCO),
    ];

    $enHome = Route::is('home');
    $logoReposo = $urlsLogo[$apariencia->logoDe($enHome ? 'transparente_reposo' : 'blanco_reposo')];
    $logoScroll = $urlsLogo[$apariencia->logoDe($enHome ? 'transparente_scroll' : 'blanco_scroll')];
    $logoMobile = $urlsLogo[$apariencia->logoDe('celular')];
@endphp
<img id="logo1" class="logo-header logo-reposo" src="{{ $logoReposo }}" alt="BMH">
<img class="logo-header logo-scroll" src="{{ $logoScroll }}" alt="" aria-hidden="true">
<img class="logo-header logo-mobile" src="{{ $logoMobile }}" alt="" aria-hidden="true">
