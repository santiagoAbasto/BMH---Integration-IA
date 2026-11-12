{{--
    Migas de pan del rediseño (Figma: «Breadcrumb», 60px de alto): osole::breadcrumb.v1 dentro
    del contenedor. $items: [['label' => 'Productos', 'href' => ...], …]; el inicio se agrega solo.
--}}
<div class="bmh-migas">
    <x-osole::container.v1>
        <x-osole::breadcrumb.v1 :items="array_merge([['label' => 'Inicio', 'href' => route('home')]], $items)" />
    </x-osole::container.v1>
</div>
