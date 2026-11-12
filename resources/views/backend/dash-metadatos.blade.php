@extends('layouts.plantilla-back')

{{-- Admin → Metadatos SEO (como en Conelec): App\Http\Controllers\MetadatosController. --}}

@section('styles')
<style>
    .seo-paginas { display: flex; flex-direction: column; }
    .seo-pagina { border-block-start: 1px solid var(--adm-border); }
    .seo-pagina:first-child { border-block-start: 0; }
    .seo-pagina > summary {
        display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 0.25rem 1rem; align-items: center;
        padding: 1rem 0; cursor: pointer; list-style: none;
    }
    .seo-pagina > summary::-webkit-details-marker { display: none; }
    .seo-pagina > summary:focus-visible { outline: 2px solid var(--adm-brand); outline-offset: 2px; border-radius: var(--adm-radius-sm); }
    .seo-pagina__nombre { display: flex; align-items: center; gap: 0.625rem; color: var(--adm-ink); font-family: var(--adm-font-heading); font-size: 0.9375rem; font-weight: 700; }
    .seo-pagina__flecha { transition: transform 150ms ease; color: var(--adm-muted); }
    .seo-pagina[open] .seo-pagina__flecha { transform: rotate(90deg); }
    .seo-pagina__resumen { grid-column: 1 / -1; color: var(--adm-muted); font-size: 0.8125rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .seo-pagina[open] .seo-pagina__resumen { display: none; }
    .seo-pagina__cuerpo { display: grid; gap: 1.25rem; padding-block-end: 1.25rem; }
    @media (width >= 1100px) { .seo-pagina__cuerpo { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); align-items: start; } }

    .seo-campo { display: flex; flex-direction: column; gap: 0.375rem; }
    .seo-campo + .seo-campo { margin-block-start: 1rem; }
    .seo-campo__cabeza { display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; }
    .seo-contador { color: var(--adm-muted); font-size: 0.75rem; font-variant-numeric: tabular-nums; }
    .seo-contador.is-largo { color: var(--adm-warning); font-weight: 600; }
    .seo-sugerido { color: var(--adm-muted); font-size: 0.75rem; line-height: 1.4; }
    .seo-acciones { display: flex; flex-wrap: wrap; gap: 0.625rem; margin-block-start: 1.25rem; }

    /* Vista previa de un resultado de Google. */
    .seo-google { padding: 1rem 1.125rem; border: 1px solid var(--adm-border); border-radius: var(--adm-radius); background: var(--adm-surface); font-family: Arial, sans-serif; }
    .seo-google__etiqueta { margin: 0 0 0.75rem; color: var(--adm-muted); font-family: var(--adm-font-body); font-size: 0.75rem; font-weight: 600; }
    .seo-google__sitio { display: flex; align-items: center; gap: 0.625rem; margin-block-end: 0.25rem; }
    .seo-google__favicon { display: grid; place-items: center; inline-size: 1.75rem; block-size: 1.75rem; border-radius: 50%; background: #f1f3f4; color: #0a0e14; font-size: 0.625rem; font-weight: 700; }
    .seo-google__nombre { color: #202124; font-size: 0.875rem; line-height: 1.2; }
    .seo-google__url { color: #4d5156; font-size: 0.75rem; line-height: 1.3; word-break: break-all; }
    .seo-google .seo-google__titulo { margin: 0.25rem 0; color: #1a0dab; font-family: Arial, sans-serif; font-size: 1.25rem; font-weight: 400; letter-spacing: normal; line-height: 1.3; text-transform: none; }
    .seo-google__desc { margin: 0; color: #4d5156; font-size: 0.875rem; line-height: 1.58; }

    .seo-ejemplos { display: grid; gap: 1rem; }
    @media (width >= 1100px) { .seo-ejemplos { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .seo-ejemplo__datos { margin: 0.625rem 0 0; color: var(--adm-muted); font-size: 0.8125rem; }

    /* Imagen para compartir: cómo se ve un link compartido. */
    .seo-imagen { display: grid; gap: 1.5rem; }
    @media (width >= 992px) { .seo-imagen { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); align-items: start; } }
    .seo-tarjeta { overflow: hidden; border: 1px solid var(--adm-border); border-radius: var(--adm-radius); background: var(--adm-surface-soft); }
    .seo-tarjeta img { display: block; inline-size: 100%; block-size: auto; aspect-ratio: 1200 / 630; object-fit: cover; background: var(--adm-bg); }
    .seo-tarjeta__texto { padding: 0.75rem 1rem; }
    .seo-tarjeta__dominio { color: var(--adm-muted); font-size: 0.75rem; text-transform: uppercase; }
    .seo-tarjeta__titulo { margin: 0.125rem 0 0; color: var(--adm-ink); font-size: 0.9375rem; font-weight: 600; }
    .seo-lista { margin: 0.5rem 0 0; padding-inline-start: 1.125rem; color: var(--adm-muted); font-size: 0.8125rem; }
    .seo-lista li + li { margin-block-start: 0.25rem; }
</style>
@endsection

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Contenido del sitio" title="Metadatos SEO" description="Cómo aparece cada página en Google y cuando se comparte un link en WhatsApp, Facebook o LinkedIn." />

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="adm-pila">

    {{-- Páginas generales --}}
    <div class="card">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2>Páginas</h2>
                <p>Cada página ya tiene un título y una descripción sugeridos. Cambiá sólo lo que quieras; si dejás un campo vacío, vuelve al sugerido.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="seo-paginas">
                @foreach ($paginas as $clave => $pagina)
                    @php
                        $propio = $pagina['custom'];
                        $abierta = $errors->any() && old('_pagina') === $clave;
                    @endphp
                    <details class="seo-pagina" id="pagina-{{ $clave }}" @if ($abierta) open @endif>
                        <summary>
                            <span class="seo-pagina__nombre">
                                <svg class="seo-pagina__flecha" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                {{ $pagina['label'] }}
                            </span>
                            <span @class(['adm-chip', 'adm-chip--marca' => $propio])>{{ $propio ? 'Personalizado' : 'Sugerido' }}</span>
                            <span class="seo-pagina__resumen">{{ $pagina['title'] }}</span>
                        </summary>

                        <div class="seo-pagina__cuerpo" data-seo-pagina>
                            <form method="POST" action="{{ route('metadatos.pagina', $clave) }}">
                                @csrf
                                <input type="hidden" name="_pagina" value="{{ $clave }}">

                                <div class="seo-campo">
                                    <div class="seo-campo__cabeza">
                                        <label class="form-label mb-0" for="titulo-{{ $clave }}">Título</label>
                                        <span class="seo-contador" data-seo-contador="60" aria-live="polite"></span>
                                    </div>
                                    <input class="form-control" id="titulo-{{ $clave }}" name="titulo" maxlength="70" data-seo-titulo
                                        value="{{ $abierta ? old('titulo') : $pagina['title'] }}" placeholder="{{ $pagina['defaults']['title'] }}">
                                    <p class="seo-sugerido mb-0">Google muestra unos 60 caracteres. Sugerido: {{ $pagina['defaults']['title'] }}</p>
                                </div>

                                <div class="seo-campo">
                                    <div class="seo-campo__cabeza">
                                        <label class="form-label mb-0" for="descripcion-{{ $clave }}">Descripción</label>
                                        <span class="seo-contador" data-seo-contador="160" aria-live="polite"></span>
                                    </div>
                                    <textarea class="form-control" id="descripcion-{{ $clave }}" name="descripcion" rows="3" maxlength="200" data-seo-descripcion
                                        placeholder="{{ $pagina['defaults']['description'] }}">{{ $abierta ? old('descripcion') : $pagina['description'] }}</textarea>
                                    <p class="seo-sugerido mb-0">Google muestra unos 160 caracteres.</p>
                                </div>

                                <div class="seo-campo">
                                    <label class="form-label mb-0" for="palabras-{{ $clave }}">Palabras clave</label>
                                    <textarea class="form-control" id="palabras-{{ $clave }}" name="palabras_clave" rows="2" maxlength="255"
                                        placeholder="{{ implode(', ', $pagina['defaults']['keywords']) }}">{{ $abierta ? old('palabras_clave') : implode(', ', $pagina['keywords']) }}</textarea>
                                    <p class="seo-sugerido mb-0">Separadas por comas. Ayudan a Bing; Google no las usa para posicionar.</p>
                                </div>

                                <div class="seo-acciones">
                                    <button type="submit" class="btn btn-primary">Guardar {{ mb_strtolower($pagina['label']) }}</button>
                                    @if ($propio)
                                        <button type="submit" class="btn btn-outline-secondary" data-seo-sugerido>Volver a lo sugerido</button>
                                    @endif
                                    <a class="btn btn-link" href="{{ route($pagina['route']) }}" target="_blank" rel="noopener">Ver la página</a>
                                </div>
                            </form>

                            <div class="seo-google" aria-label="Vista previa en Google">
                                <p class="seo-google__etiqueta">Así se vería en Google</p>
                                <div class="seo-google__sitio">
                                    <span class="seo-google__favicon" aria-hidden="true">BMH</span>
                                    <span>
                                        <span class="seo-google__nombre d-block">BMH Distribuidora</span>
                                        <span class="seo-google__url d-block">{{ route($pagina['route']) }}</span>
                                    </span>
                                </div>
                                <h3 class="seo-google__titulo" data-seo-ver-titulo>{{ $pagina['title'] }}</h3>
                                <p class="seo-google__desc" data-seo-ver-descripcion>{{ $pagina['description'] }}</p>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Fichas automáticas --}}
    @if ($ejemplos !== [])
        <div class="card">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2>Productos, categorías y novedades</h2>
                    <p>Se arman solos con los datos de cada uno y se actualizan cuando los editás. No hace falta cargarlos.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="seo-ejemplos">
                    @foreach ($ejemplos as $tipo => $ejemplo)
                        <div>
                            <div class="seo-google">
                                <p class="seo-google__etiqueta">{{ $tipo }} (ejemplo)</p>
                                <div class="seo-google__sitio">
                                    <span class="seo-google__favicon" aria-hidden="true">BMH</span>
                                    <span>
                                        <span class="seo-google__nombre d-block">BMH Distribuidora</span>
                                        <span class="seo-google__url d-block">{{ $ejemplo['meta']->canonical }}</span>
                                    </span>
                                </div>
                                <h3 class="seo-google__titulo">{{ $ejemplo['meta']->title }}</h3>
                                @if ($ejemplo['meta']->description)<p class="seo-google__desc">{{ $ejemplo['meta']->description }}</p>@endif
                            </div>
                            <p class="seo-ejemplo__datos">Usa: {{ $ejemplo['datos'] }}.</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Imagen para compartir --}}
    <div class="card" id="imagen">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2>Imagen para compartir</h2>
                <p>Aparece cuando alguien comparte un link del sitio en WhatsApp, Facebook, LinkedIn o X. Los productos y las novedades con foto usan la suya.</p>
            </div>
            <span @class(['adm-chip', 'adm-chip--marca' => (bool) $ajustes->imagen_path])>{{ $ajustes->imagen_path ? 'Imagen propia' : 'Imagen del sitio' }}</span>
        </div>
        <div class="card-body">
            <div class="seo-imagen">
                <div class="seo-tarjeta">
                    <img src="{{ $ajustes->imageUrl() }}" alt="Imagen para compartir actual" width="1200" height="630" data-seo-imagen-vista>
                    <div class="seo-tarjeta__texto">
                        <span class="seo-tarjeta__dominio">{{ parse_url(url('/'), PHP_URL_HOST) }}</span>
                        <p class="seo-tarjeta__titulo">{{ $paginas['home']['title'] }}</p>
                    </div>
                </div>

                <div>
                    <form method="POST" action="{{ route('metadatos.imagen') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label" for="imagen-compartir">Subir otra imagen</label>
                        <input class="form-control" type="file" id="imagen-compartir" name="imagen" accept="image/jpeg,image/png,image/webp" required data-seo-imagen>
                        <ul class="seo-lista">
                            <li>Ideal 1200 × 630 px (horizontal). Si tiene otra proporción, se recorta al centro.</li>
                            <li>JPG, PNG o WebP de hasta 8 MB.</li>
                            <li>Dejá el texto importante lejos de los bordes: algunas redes recortan la imagen.</li>
                        </ul>
                        <div class="seo-acciones">
                            <button type="submit" class="btn btn-primary">Guardar imagen</button>
                        </div>
                    </form>

                    @if ($ajustes->imagen_path)
                        <form method="POST" action="{{ route('metadatos.imagen.quitar') }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary">Volver a la imagen del sitio</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Verificación de buscadores --}}
    <div class="card" id="verificacion">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2>Verificación de buscadores</h2>
                <p>Para ver las búsquedas, los clics y los errores del sitio en Google Search Console y Bing Webmaster Tools.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('metadatos.verificacion') }}">
                @csrf
                <div class="adm-campos">
                    <div>
                        <label class="form-label" for="google-verificacion">Google Search Console</label>
                        <input class="form-control" id="google-verificacion" name="google_verificacion" value="{{ old('google_verificacion', $ajustes->google_verificacion) }}" placeholder="Código o etiqueta &lt;meta&gt; completa" autocomplete="off" spellcheck="false">
                        <p class="seo-sugerido mt-1 mb-0">En Search Console: Agregar propiedad → Prefijo de URL → Etiqueta HTML.</p>
                    </div>
                    <div>
                        <label class="form-label" for="bing-verificacion">Bing Webmaster Tools</label>
                        <input class="form-control" id="bing-verificacion" name="bing_verificacion" value="{{ old('bing_verificacion', $ajustes->bing_verificacion) }}" placeholder="Código o etiqueta &lt;meta&gt; completa" autocomplete="off" spellcheck="false">
                        <p class="seo-sugerido mt-1 mb-0">En Bing: Agregar sitio → Etiqueta HTML (también se puede importar desde Search Console).</p>
                    </div>
                </div>
                <p class="seo-sugerido mt-3 mb-0">Después de verificar, enviá el mapa del sitio: <a href="{{ route('sitemap') }}" target="_blank" rel="noopener">{{ route('sitemap') }}</a></p>
                <div class="seo-acciones">
                    <button type="submit" class="btn btn-primary">Guardar verificación</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
(() => {
    // Contadores y vista previa de Google en vivo; «Volver a lo sugerido» vacía los campos antes de enviar.
    document.querySelectorAll('[data-seo-pagina]').forEach((bloque) => {
        const titulo = bloque.querySelector('[data-seo-titulo]');
        const descripcion = bloque.querySelector('[data-seo-descripcion]');
        const verTitulo = bloque.querySelector('[data-seo-ver-titulo]');
        const verDescripcion = bloque.querySelector('[data-seo-ver-descripcion]');

        const contar = (campo) => {
            const contador = campo.closest('.seo-campo').querySelector('[data-seo-contador]');
            const limite = Number(contador.dataset.seoContador);
            const largo = campo.value.trim().length;
            contador.textContent = largo + ' / ' + limite;
            contador.classList.toggle('is-largo', largo > limite);
        };
        const actualizar = () => {
            contar(titulo);
            contar(descripcion);
            verTitulo.textContent = titulo.value.trim() || titulo.placeholder;
            verDescripcion.textContent = descripcion.value.trim() || descripcion.placeholder;
        };
        [titulo, descripcion].forEach((campo) => campo.addEventListener('input', actualizar));
        actualizar();

        bloque.querySelector('[data-seo-sugerido]')?.addEventListener('click', () => {
            bloque.querySelectorAll('input[name], textarea[name]').forEach((campo) => {
                if (campo.type !== 'hidden') campo.value = '';
            });
        });
    });

    // Vista previa de la imagen elegida antes de subirla.
    const archivo = document.querySelector('[data-seo-imagen]');
    const vista = document.querySelector('[data-seo-imagen-vista]');
    archivo?.addEventListener('change', () => {
        const elegido = archivo.files?.[0];
        if (elegido && elegido.type.startsWith('image/')) vista.src = URL.createObjectURL(elegido);
    });
})();
</script>
@endsection
