@extends('layouts.plantilla-front')

{{-- Compatible con Turbo: el JS de la página (copiar el enlace) es una delegación en document registrada una sola vez. --}}

{{--
    Detalle de una novedad (rediseño). No hay frame de detalle en el Figma: sigue el sistema de las páginas internas
    (migas, Montserrat + Inter, tinta y azul de marca) con una lectura cómoda: cabecera con etiqueta, fecha y
    tiempo de lectura; la foto grande; el texto a ~68 caracteres por línea; al costado, compartir y la consulta; al
    pie, más novedades. Estilos: resources/css/sitio/novedad.css.
--}}
@section('content')
    @php
        use App\Support\IconoSitio;
        use App\Support\TextoAdmin;

        $texto = TextoAdmin::html($novedad->texto);
        $palabras = str_word_count(strip_tags((string) $novedad->texto));
        $minutos = max(1, (int) round($palabras / 200));
        $fecha = $novedad->created_at?->locale('es');
        $url = route('novedad', ['id' => $novedad->id]);
        $titulo = (string) $novedad->titulo;
        $tituloLegible = mb_convert_case($titulo, MB_CASE_TITLE, 'UTF-8');
        $compartir = [
            ['nombre' => 'WhatsApp', 'href' => 'https://wa.me/?text='.rawurlencode($tituloLegible.' · BMH Distribuidora '.$url), 'icono' => IconoSitio::svg('whatsapp')],
            ['nombre' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url), 'icono' => IconoSitio::svg('facebook')],
            ['nombre' => 'LinkedIn', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url), 'icono' => new \Illuminate\Support\HtmlString('<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9.75h4v11H3v-11Zm6.5 0h3.83v1.5h.06c.53-1 1.84-2.06 3.79-2.06 4.05 0 4.8 2.67 4.8 6.13v5.43h-4v-4.82c0-1.15-.02-2.63-1.6-2.63-1.6 0-1.85 1.25-1.85 2.55v4.9h-4v-11Z"/></svg>')],
        ];
    @endphp

    @include('layouts.partials.sitio-migas', ['items' => [
        ['label' => 'Novedades', 'href' => route('novedades')],
        ['label' => \Illuminate\Support\Str::limit($tituloLegible, 48)],
    ]])

    <article class="bmh-novedad" aria-labelledby="bmh-novedad-titulo">
        <x-osole::container.v1>
            <header class="bmh-novedad__cabecera">
                <p class="bmh-novedad__meta">
                    @if (filled($novedad->etiqueta))
                        <span class="bmh-novedad__etiqueta">{{ $novedad->etiqueta }}</span>
                    @endif
                    @if ($fecha)
                        <time datetime="{{ $fecha->toDateString() }}">{{ $fecha->isoFormat('D [de] MMMM [de] YYYY') }}</time>
                    @endif
                    <span class="bmh-novedad__lectura">{{ IconoSitio::svg('reloj') }} {{ $minutos }} min de lectura</span>
                </p>
                <h1 id="bmh-novedad-titulo" class="bmh-novedad__titulo">{{ $titulo }}</h1>
                @if (filled($novedad->epigrafe))
                    <p class="bmh-novedad__bajada">{{ $novedad->epigrafe }}</p>
                @endif
            </header>

            @if ($novedad->portada)
                <figure class="bmh-novedad__portada">
                    <img src="{{ asset('imagenes/'.$novedad->portada) }}" alt="{{ $tituloLegible }}" fetchpriority="high" decoding="async">
                </figure>
            @endif

            <div class="bmh-novedad__grilla">
                <div class="bmh-novedad__cuerpo">
                    @if ($texto)
                        <div class="bmh-novedad__texto">{{ $texto }}</div>
                    @endif
                    <a class="bmh-novedad__volver" href="{{ route('novedades') }}">
                        <span aria-hidden="true">{{ IconoSitio::svg('flecha') }}</span> Volver a novedades
                    </a>
                </div>

                <aside class="bmh-novedad__lado" aria-label="Compartir y consultar">
                    <div class="bmh-novedad__compartir">
                        <p class="bmh-novedad__lado-titulo">Compartir</p>
                        <ul role="list">
                            @foreach ($compartir as $red)
                                <li>
                                    <a href="{{ $red['href'] }}" target="_blank" rel="noopener" aria-label="Compartir en {{ $red['nombre'] }} (se abre en otra pestaña)">{{ $red['icono'] }}</a>
                                </li>
                            @endforeach
                            <li>
                                <button type="button" data-copiar-enlace="{{ $url }}" aria-label="Copiar el enlace">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1"/><path d="M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1-1"/></svg>
                                </button>
                            </li>
                        </ul>
                        <p class="bmh-novedad__copiado" data-copiado role="status" aria-live="polite"></p>
                    </div>

                    <div class="bmh-novedad__consulta">
                        <p class="bmh-novedad__consulta-titulo">¿Te interesa esta línea?</p>
                        <p>Consultanos por precios, stock y envíos a todo el país.</p>
                        <a class="bmh-novedad__boton" href="{{ route('contacto', ['producto' => $tituloLegible]) }}">Consultar</a>
                        <a class="bmh-novedad__boton bmh-novedad__boton--fantasma" href="{{ route('categorias') }}">Ver productos</a>
                    </div>
                </aside>
            </div>
        </x-osole::container.v1>
    </article>

    @if ($otras->isNotEmpty())
        <section class="bmh-novedad-mas" aria-labelledby="bmh-novedad-mas-titulo">
            <x-osole::container.v1>
                <div class="bmh-novedad-mas__cabecera">
                    <h2 id="bmh-novedad-mas-titulo" class="bmh-seccion-titulo">Más novedades</h2>
                    <a class="bmh-novedad-mas__todas" href="{{ route('novedades') }}">Ver todas <span aria-hidden="true">{{ IconoSitio::svg('flecha') }}</span></a>
                </div>
                <ul class="bmh-grilla-novedades" role="list">
                    @foreach ($otras as $otra)
                        <li>
                            <x-osole::post-card.v1
                                :title="$otra->titulo"
                                :href="route('novedad', ['id' => $otra->id])"
                                :image="$otra->portada ? asset('imagenes/'.$otra->portada) : null"
                                :category="$otra->etiqueta"
                                :description="$otra->epigrafe"
                                :heading-level="3"
                            />
                        </li>
                    @endforeach
                </ul>
            </x-osole::container.v1>
        </section>
    @endif

    {{-- Copiar el enlace: delegación en el documento, registrada una sola vez (con Turbo este script corre en cada
         visita a una novedad; sin DOMContentLoaded). --}}
    <script>
        if (!window.bmhCopiarEnlace) {
            window.bmhCopiarEnlace = true;
            document.addEventListener('click', function (evento) {
                var boton = evento.target.closest && evento.target.closest('[data-copiar-enlace]');
                if (!boton) return;
                var aviso = document.querySelector('[data-copiado]');
                var avisar = function (texto) {
                    if (!aviso) return;
                    aviso.textContent = texto;
                    clearTimeout(window.bmhCopiadoEspera);
                    window.bmhCopiadoEspera = setTimeout(function () { aviso.textContent = ''; }, 2500);
                };
                var enlace = boton.getAttribute('data-copiar-enlace');
                // Primero la copia sincrónica (anda dentro del clic aunque el navegador no dé permiso al portapapeles);
                // si no, la API moderna; si tampoco, se muestra el enlace para copiarlo a mano.
                var campo = document.createElement('textarea');
                campo.value = enlace;
                campo.setAttribute('readonly', '');
                campo.style.cssText = 'position:fixed;inset-inline-start:-9999px;opacity:0';
                document.body.appendChild(campo);
                campo.select();
                var copiado = false;
                try { copiado = document.execCommand('copy'); } catch (e) { copiado = false; }
                campo.remove();
                if (copiado) {
                    avisar('Enlace copiado.');
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(enlace).then(function () { avisar('Enlace copiado.'); }, function () { avisar(enlace); });
                } else {
                    avisar(enlace);
                }
            });
        }
    </script>
@endsection
