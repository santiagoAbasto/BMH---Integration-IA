{{--
    Mapa de /contacto (Figma: «Mapa»), como el de Conelec. Si la URL cargada en
    Admin > Contacto (iframe del mapa) trae coordenadas (Google "Insertar un mapa" u OpenStreetMap), se dibuja con
    Leaflet (resources/js/components/contact-map.js): teselas de OpenStreetMap en gris, como en el Figma, y el pin
    azul de BMH anclado a la dirección, que acompaña el desplazamiento y el zoom sin perder su color.
    Sin coordenadas, queda el iframe en gris con el pin fijo en el centro.

    Tarjeta de la dirección: al pasar el mouse por el mapa (o enfocar el pin, o tocarlo en el celular) muestra la
    dirección y el link para abrirla en Google Maps. Con Leaflet va en un popup anclado al pin; en el iframe, sobre el
    pin fijo, y la abre el pin (el navegador no avisa del mouse que está sobre un iframe de otro sitio).
--}}
@props(['src', 'title' => 'Mapa de ubicación', 'address' => null])
@use('App\Support\MapaEmbed')
@php
    $location = MapaEmbed::location($src);
    $lines = array_values(array_filter(array_map('trim', explode("\n", str_replace('\n', "\n", (string) $address)))));
    $mapsUrl = MapaEmbed::googleMapsUrl($address, $location);
@endphp
<div {{ $attributes->class(['contact-map', 'contact-map--static' => ! $location]) }}>
    @if ($location)
        <div class="contact-map__canvas" data-contact-map data-location="{{ json_encode($location) }}" role="region" aria-label="{{ $title }}"></div>
        <noscript><iframe class="contact-map__frame" src="{{ MapaEmbed::withoutMarker($src) }}" title="{{ $title }}" loading="lazy"></iframe></noscript>
    @else
        <iframe class="contact-map__frame" src="{{ MapaEmbed::withoutMarker($src) }}" title="{{ $title }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        {{-- El mouse sobre un iframe de otro sitio no llega a esta página: la tarjeta la abre el pin (botón). --}}
        <button class="contact-map__pin" type="button" aria-label="Ver la dirección de BMH"@unless ($mapsUrl) disabled @endunless>
            <svg viewBox="0 0 31.2542 39.0673" aria-hidden="true" focusable="false"><path d="M31.2542 15.6271C31.2542 25.3804 20.4344 35.538 16.8011 38.6751C16.4626 38.9296 16.0506 39.0673 15.6271 39.0673C15.2036 39.0673 14.7916 38.9296 14.4531 38.6751C10.8198 35.538 0 25.3804 0 15.6271C0 11.4825 1.64642 7.50772 4.57707 4.57707C7.50772 1.64642 11.4825 0 15.6271 0C19.7717 0 23.7465 1.64642 26.6771 4.57707C29.6078 7.50772 31.2542 11.4825 31.2542 15.6271Z" fill="#0098da"/><circle cx="15.6271" cy="15.6271" r="5.8602" fill="#fff"/></svg>
        </button>
    @endif

    @if ($mapsUrl)
        {{-- Con Leaflet, contact-map.js la pasa al popup del pin. --}}
        <div class="contact-map__card" data-contact-map-card>
            <p class="contact-map__card-name">BMH Distribuidora</p>
            @if ($lines !== [])
                <address class="contact-map__card-address">{!! collect($lines)->map(fn (string $line): string => e($line))->implode('<br>') !!}</address>
            @endif
            <a class="contact-map__card-link" href="{{ $mapsUrl }}" target="_blank" rel="noopener">
                Abrir en Google Maps
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                <span class="contact-map__sr">(se abre en otra pestaña)</span>
            </a>
        </div>
    @endif
</div>
