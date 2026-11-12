{{--
    Footer del sitio (rediseño 2026): osole::footer.v1 con los datos de Contacto (la misma fila
    que la barra superior y la página Contacto) y el logo de footer elegido en el admin.
--}}
@php
    use App\Models\Contacto;
    use App\Services\LogosSitio;
    use App\Support\IconoSitio;

    $contacto ??= Contacto::actual();
    $conAsesor = app(\App\Services\Ai\AdvisorBootstrap::class)->shouldRender();

    $secciones = [
        ['label' => 'Nosotros', 'href' => route('nosotros')],
        ['label' => 'Productos', 'href' => route('categorias')],
        ['label' => 'Ofertas', 'href' => route('ofertas')],
        ['label' => 'Novedades', 'href' => route('novedades')],
    ];
    if ($conAsesor) {
        $secciones[] = ['label' => 'Asistente virtual', 'href' => route('assistant.page')];
    }
    $secciones[] = ['label' => 'Contacto', 'href' => route('contacto')];

    $datos = [];
    if (filled($contacto->direccion)) {
        $datos[] = ['text' => $contacto->direccion, 'href' => 'https://maps.app.goo.gl/bvt6zSHzT8txo6uV7', 'icon' => IconoSitio::svg('ubicacion')];
    }
    if ($telHref = $contacto->telHref()) {
        $datos[] = ['text' => $contacto->tel, 'href' => $telHref, 'icon' => IconoSitio::svg('telefono-footer')];
    }
    if ($whatsapp = preg_replace('/[^0-9]/', '', (string) $contacto->whatsapp)) {
        $datos[] = ['text' => $contacto->whatsapp, 'href' => 'https://wa.me/'.$whatsapp, 'icon' => IconoSitio::svg('whatsapp')];
    }
    $datos[] = ['text' => 'Lun a Vie de 8 a 17 hs', 'icon' => IconoSitio::svg('reloj')];
    if (filled($contacto->mail)) {
        $datos[] = ['text' => $contacto->mail, 'href' => 'mailto:'.$contacto->mail, 'icon' => IconoSitio::svg('mail-footer')];
    }

    $redes = [];
    foreach ($contacto->redesBarra() as $red => $url) {
        $redes[] = ['label' => Contacto::REDES_BARRA[$red].' de BMH', 'href' => $url, 'icon' => IconoSitio::svg($red)];
    }
@endphp

<x-osole::footer.v1
    id="site-footer"
    class="bmh-footer"
    :home-href="route('home')"
    home-label="BMH Distribuidora, ir al inicio"
    :sections="[['title' => 'Secciones', 'links' => $secciones, 'columns' => 2]]"
    :social-links="$redes"
    social-placement="brand"
    contact-title="Contacto"
    :contact-items="$datos"
    :copyright="'© Copyright '.app(\App\Services\Anio::class)->actual().' BMH Distribuidora. Todos los derechos reservados.'"
    credit-prefix="By"
>
    <x-slot:logo>
        <img src="{{ $logosSitio->url(LogosSitio::FOOTER) }}" alt="" width="268" height="99">
    </x-slot:logo>
</x-osole::footer.v1>
