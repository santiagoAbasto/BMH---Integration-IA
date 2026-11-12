@extends('layouts.plantilla-front')

{{-- Navegación con Turbo: el JS de esta página ya es compatible (ver layouts/plantilla-front). --}}
@section('turbo', true)

@section('content')
    @php
        use App\Support\IconoSitio;

        $cliente = Auth::guard('web')->user();
        $datos = [];
        if (filled($contacto?->direccion)) {
            $datos[] = ['icono' => 'ubicacion', 'etiqueta' => 'Dirección', 'texto' => $contacto->direccion, 'href' => 'https://maps.app.goo.gl/bvt6zSHzT8txo6uV7'];
        }
        if ($contacto && ($tel = $contacto->telHref())) {
            $datos[] = ['icono' => 'telefono-footer', 'etiqueta' => 'Teléfono', 'texto' => $contacto->tel, 'href' => $tel];
        }
        if ($wa = preg_replace('/[^0-9]/', '', (string) $contacto?->whatsapp)) {
            $datos[] = ['icono' => 'whatsapp', 'etiqueta' => 'WhatsApp', 'texto' => $contacto->whatsapp, 'href' => 'https://wa.me/'.$wa];
        }
        if (filled($contacto?->mail)) {
            $datos[] = ['icono' => 'mail-footer', 'etiqueta' => 'Email', 'texto' => $contacto->mail, 'href' => 'mailto:'.$contacto->mail];
        }
        $datos[] = ['icono' => 'reloj', 'etiqueta' => 'Horario de atención', 'texto' => 'Lunes a viernes de 8 a 17 hs', 'href' => null];

        $motivos = ['Consulta comercial', 'Consulta técnica', 'Precios y disponibilidad', 'Quiero ser cliente', 'Otro'];
    @endphp

    @include('layouts.partials.sitio-migas', ['items' => [['label' => 'Contacto']]])

    <section class="bmh-contacto" aria-labelledby="bmh-contacto-titulo">
        <x-osole::container.v1 class="bmh-contacto__columnas">
            <h1 class="visually-hidden">Contacto</h1>

            <ul class="bmh-contacto__datos" role="list" aria-label="Datos de contacto">
                @foreach ($datos as $dato)
                    <li class="bmh-contacto__dato">
                        <span class="bmh-contacto__icono" aria-hidden="true">{{ IconoSitio::svg($dato['icono']) }}</span>
                        <span class="bmh-contacto__texto">
                            <span class="bmh-contacto__etiqueta">{{ $dato['etiqueta'] }}</span>
                            @if ($dato['href'])
                                <a class="bmh-contacto__valor" href="{{ $dato['href'] }}" @if (str_starts_with($dato['href'], 'http')) target="_blank" rel="noopener" @endif>{{ $dato['texto'] }}</a>
                            @else
                                <span class="bmh-contacto__valor">{{ $dato['texto'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

            {{-- Envío con reCAPTCHA v3 (ContactoController@mailContacto), como antes. --}}
            <form id="formulario" class="bmh-contacto__form" action="{{ route('enviar.mail') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                <div class="bmh-contacto__encabezado">
                    <h2 id="bmh-contacto-titulo" class="bmh-contacto__titulo">Envianos tu consulta</h2>
                    <p class="bmh-contacto__bajada">Completá el formulario y te respondemos a la brevedad</p>
                </div>

                <div class="bmh-form__fila">
                    <div class="bmh-form__campo">
                        <label class="bmh-form__label" for="contacto-nombre">Nombre y apellido*</label>
                        <input class="bmh-form__control" id="contacto-nombre" type="text" name="nombre" placeholder="Ej: Juan Pérez" autocomplete="name" required value="{{ old('nombre', $cliente?->name) }}">
                    </div>
                    <div class="bmh-form__campo">
                        <label class="bmh-form__label" for="contacto-mail">Email*</label>
                        <input class="bmh-form__control" id="contacto-mail" type="email" name="mail" placeholder="tu@email.com" autocomplete="email" required value="{{ old('mail', $cliente?->email) }}">
                    </div>
                </div>

                <div class="bmh-form__fila">
                    <div class="bmh-form__campo">
                        <label class="bmh-form__label" for="contacto-celular">Celular*</label>
                        <input class="bmh-form__control" id="contacto-celular" type="tel" name="telefono" placeholder="Ej: 11 1234-5678" autocomplete="tel" required value="{{ old('telefono', $cliente?->celular) }}">
                    </div>
                    <div class="bmh-form__campo">
                        <label class="bmh-form__label" for="contacto-empresa">Empresa</label>
                        <input class="bmh-form__control" id="contacto-empresa" type="text" name="empresa" placeholder="Nombre de tu empresa o taller" autocomplete="organization" value="{{ old('empresa') }}">
                    </div>
                </div>

                <div class="bmh-form__campo">
                    <label class="bmh-form__label" for="contacto-motivo">Motivo de la consulta</label>
                    <div class="bmh-form__select">
                        <select class="bmh-form__control" id="contacto-motivo" name="motivo">
                            <option value="">Seleccioná una opción</option>
                            @foreach ($motivos as $motivo)
                                <option value="{{ $motivo }}" @selected(old('motivo') === $motivo)>{{ $motivo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bmh-form__campo">
                    <label class="bmh-form__label" for="contacto-mensaje">Mensaje</label>
                    <textarea class="bmh-form__control bmh-form__control--texto" id="contacto-mensaje" name="mensaje" rows="5" placeholder="Escribinos tu consulta">{{ old('mensaje', $producto ? '¡Hola! Quisiera consultar sobre el producto '.$producto.'.' : '') }}</textarea>
                </div>

                <div class="bmh-form__pie">
                    <p class="bmh-form__nota">* Campos obligatorios</p>
                    <button type="submit" class="bmh-btn bmh-btn--primario bmh-contacto__enviar" data-enviar>Enviar consulta</button>
                </div>
                <p class="bmh-form__error" data-error role="alert" hidden>Completá los campos obligatorios.</p>
            </form>
        </x-osole::container.v1>
    </section>

    {{-- Mapa como el de Conelec: Leaflet en gris con el pin azul (components/contact-map). Sólo se acepta
         un iframe de Google Maps u OpenStreetMap (App\Support\MapaEmbed). --}}
    @php $mapa = $contacto?->iframe ? \App\Support\MapaEmbed::url($contacto->iframe) : null; @endphp
    @if ($mapa)
        <section class="bmh-contacto-mapa" aria-label="Cómo llegar">
            <x-osole::container.v1>
                <div class="bmh-contacto-mapa__marco">
                    <x-contact-map :src="$mapa" :address="$contacto->direccion" title="Mapa: cómo llegar a BMH" />
                </div>
            </x-osole::container.v1>
        </section>
    @endif
@endsection

@section('script')
<script>
    (function () {
        var form = document.getElementById('formulario');
        var boton = form.querySelector('[data-enviar]');
        var error = form.querySelector('[data-error]');

        @if (session('success'))
            iziToast.success({ title: '¡Gracias por contactarnos!', message: 'Te respondemos a la brevedad.', position: 'bottomRight', timeout: 7000 });
        @elseif (session('warning'))
            iziToast.error({ title: 'No se pudo enviar la consulta', message: 'Probá de nuevo o escribinos por WhatsApp.', position: 'bottomRight', timeout: 7000 });
        @endif

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                error.hidden = false;
                var primero = form.querySelector(':invalid');
                if (primero) primero.focus();
                return;
            }
            error.hidden = true;
            boton.disabled = true;
            boton.textContent = 'Enviando…';
            grecaptcha.ready(function () {
                grecaptcha.execute('6LfjNywqAAAAAKlfuav604AgIuv_qsZLXYtLqlPw', { action: 'submit' }).then(function (token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    form.submit();
                });
            });
        });
    })();
</script>
@endsection
