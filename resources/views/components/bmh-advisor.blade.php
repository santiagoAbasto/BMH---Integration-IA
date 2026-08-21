{{--
    Asesor Técnico BMH — punto de montaje.

    Se incluye una sola vez en el layout de la Zona de Clientes. El widget se
    monta en un shadow root, así que no hereda ni contamina los estilos de
    Bootstrap del sitio.

    No agrega un link ni una URL: el asesor vive en la misma pantalla que el
    catálogo. Para abrirlo desde el header alcanza con poner `data-bmh-advisor`
    en cualquier elemento, sin tocar el bundle:

        <a href="#" data-bmh-advisor>Asesor IA</a>
--}}
@php($bmhAdvisor = app(\App\Services\Ai\AdvisorBootstrap::class))

@if ($bmhAdvisor->shouldRender() && ($bmhAdvisorPayload = $bmhAdvisor->payload()) !== null)
    <div id="bmh-advisor" data-advisor="{{ json_encode($bmhAdvisorPayload, JSON_UNESCAPED_UNICODE) }}"></div>

    @vite('resources/js/advisor.tsx')
@endif
