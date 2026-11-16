@extends('layouts.plantilla-back')

{{-- Admin → Home › Sección Nosotros: el bloque de la Home que presenta a BMH (imagen, título, descripción y botón),
     con una vista previa que se actualiza mientras se escribe. Guarda HomeSeccionesController. --}}

@section('content')

@php
    $botonTexto = old('boton_texto', $seccion->boton_texto);
    $botonUrl = old('boton_url', $seccion->boton_url);
    $paginas = [
        '/nosotros' => 'Nosotros',
        '/categorias' => 'Productos',
        '/novedades' => 'Novedades',
        '/contacto' => 'Contacto',
    ];
@endphp

<x-osole::admin-page-header.v1 eyebrow="Home" title="Sección Nosotros" description="El bloque de la Home que presenta a BMH: está después de las líneas de productos, con fondo oscuro.">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('home') }}#seccion-nosotros" target="_blank" rel="noopener">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver en la Home
        </a>
    </x-slot:actions>
</x-osole::admin-page-header.v1>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Revisá los datos marcados.</strong>
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form id="seccion-nosotros" method="POST" action="{{ route('dashboard.home.seccion-nosotros.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Vista previa: la misma composición que la Home (imagen a la izquierda, texto y botón a la derecha). --}}
    <section class="card hsec-previa" aria-labelledby="hsn-previa-titulo">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2 id="hsn-previa-titulo">Vista previa</h2>
                <p>Se actualiza mientras editás. Los números marcan qué campo cambia cada parte.</p>
            </div>
        </div>
        <div class="hsn-previa" aria-hidden="true">
            <div class="hsn-previa__img">
                <img src="{{ $seccion->imagenUrl() }}" alt="" data-previa-imagen>
                <span class="hsn-marca">1</span>
            </div>
            <div class="hsn-previa__texto">
                <p class="hsn-previa__titulo"><span data-previa-titulo>{{ old('titulo', $seccion->titulo) }}</span><span class="hsn-marca">2</span></p>
                <div class="hsn-previa__desc"><div data-previa-descripcion>{!! $seccion->descripcionHtml() !!}</div><span class="hsn-marca">3</span></div>
                <p class="hsn-previa__boton-fila">
                    <span class="hsn-previa__boton" data-previa-boton @if (blank($botonTexto) || blank($botonUrl)) hidden @endif>{{ $botonTexto }}</span>
                    <span class="hsn-previa__sin-boton" data-previa-sin-boton @if (filled($botonTexto) && filled($botonUrl)) hidden @endif>Sin botón</span>
                    <span class="hsn-marca">4</span>
                </p>
            </div>
        </div>
    </section>

    <div class="hsn-grilla">
        <section class="card" aria-labelledby="hsn-imagen-titulo">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="hsn-imagen-titulo"><span class="hsn-marca">1</span> Imagen</h2>
                    <p>Horizontal, de unos 1120 × 960 px. Se recorta para llenar el recuadro.</p>
                </div>
            </div>
            <div class="card-body">
                <input type="file" class="visually-hidden hsec-archivo" id="hsn-imagen" name="imagen" accept="image/png,image/jpeg,image/webp" data-imagen-input>
                <label class="hsec-drop hsec-drop--foto" for="hsn-imagen">
                    <span class="hsec-drop__mesa"><img src="{{ $seccion->imagenUrl() }}" alt="Imagen actual de la sección" data-imagen-actual></span>
                    <span class="hsec-drop__cambiar"><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar imagen</span>
                </label>
                <p class="form-text" data-imagen-nombre>JPG, PNG o WebP, hasta 4 MB. Se guarda al tocar «Guardar cambios».</p>
                @if ($seccion->imagen)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="hsn-quitar" name="quitar_imagen" value="1" data-imagen-quitar>
                        <label class="form-check-label" for="hsn-quitar">Volver a la foto de la planta que trae el sitio</label>
                    </div>
                @endif
            </div>
        </section>

        <section class="card" aria-labelledby="hsn-texto-titulo">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="hsn-texto-titulo">Texto</h2>
                    <p>Lo primero que se lee de BMH en la Home: corto y concreto.</p>
                </div>
            </div>
            <div class="card-body hsec-campos">
                <div>
                    <label class="form-label" for="hsn-titulo"><span class="hsn-marca">2</span> Título</label>
                    <input class="form-control @error('titulo') is-invalid @enderror" id="hsn-titulo" name="titulo" maxlength="160" required value="{{ old('titulo', $seccion->titulo) }}" data-campo-titulo>
                    <p class="form-text"><span data-contador-titulo>{{ mb_strlen((string) old('titulo', $seccion->titulo)) }}</span>/160 · se ve bien hasta unos 60 caracteres (dos líneas).</p>
                </div>
                <div>
                    <label class="form-label" for="hsn-descripcion"><span class="hsn-marca">3</span> Descripción</label>
                    <textarea id="hsn-descripcion" name="descripcion">{{ old('descripcion', $seccion->descripcion) }}</textarea>
                    <p class="form-text">Negrita, listas y links. El formato pegado de otros programas (colores, tamaños) se quita al mostrarlo.</p>
                </div>
            </div>
        </section>

        <section class="card hsn-grilla__ancho" aria-labelledby="hsn-boton-titulo">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="hsn-boton-titulo"><span class="hsn-marca">4</span> Botón</h2>
                    <p>Para seguir leyendo. Si dejás los dos campos vacíos, la sección se muestra sin botón.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="hsn-boton-texto">Texto del botón</label>
                        <input class="form-control @error('boton_texto') is-invalid @enderror" id="hsn-boton-texto" name="boton_texto" maxlength="40" value="{{ $botonTexto }}" placeholder="Ej.: Conocé más" data-campo-boton>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="hsn-boton-url">A dónde lleva</label>
                        <input class="form-control @error('boton_url') is-invalid @enderror" id="hsn-boton-url" name="boton_url" maxlength="500" value="{{ $botonUrl }}" placeholder="/nosotros o https://…" list="hsn-paginas" data-campo-url>
                        <datalist id="hsn-paginas">
                            @foreach ($paginas as $ruta => $nombre)<option value="{{ $ruta }}">{{ $nombre }}</option>@endforeach
                        </datalist>
                        <div class="hsn-atajos" role="group" aria-label="Páginas del sitio">
                            <span>Páginas del sitio:</span>
                            @foreach ($paginas as $ruta => $nombre)
                                <button type="button" class="adm-chip hsn-atajo" data-ruta="{{ $ruta }}">{{ $nombre }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="adm-barra-guardar">
        <p>Los cambios se ven en la Home apenas guardás.</p>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
</form>
@endsection

@section('script')
<script>
    (function () {
        var $desc = $('#hsn-descripcion');
        var previaDesc = document.querySelector('[data-previa-descripcion]');
        $desc.summernote({
            height: 180,
            placeholder: 'B.M.H Bobinajes San Francisco S.R.L. se dedica a…',
            toolbar: [['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol']], ['insert', ['link']], ['view', ['codeview']]],
            callbacks: { onChange: function (html) { previaDesc.innerHTML = html; } }
        });

        var titulo = document.querySelector('[data-campo-titulo]');
        titulo.addEventListener('input', function () {
            document.querySelector('[data-previa-titulo]').textContent = titulo.value;
            document.querySelector('[data-contador-titulo]').textContent = titulo.value.length;
        });

        var botonTexto = document.querySelector('[data-campo-boton]');
        var botonUrl = document.querySelector('[data-campo-url]');
        function boton() {
            var hay = botonTexto.value.trim() !== '' && botonUrl.value.trim() !== '';
            var b = document.querySelector('[data-previa-boton]');
            b.textContent = botonTexto.value;
            b.hidden = !hay;
            document.querySelector('[data-previa-sin-boton]').hidden = hay;
        }
        botonTexto.addEventListener('input', boton);
        botonUrl.addEventListener('input', boton);
        document.querySelectorAll('[data-ruta]').forEach(function (a) {
            a.addEventListener('click', function () { botonUrl.value = a.dataset.ruta; boton(); });
        });

        var input = document.querySelector('[data-imagen-input]');
        var actual = document.querySelector('[data-imagen-actual]');
        var previaImg = document.querySelector('[data-previa-imagen]');
        var original = actual.src;
        var fabrica = @json(asset(\App\Models\HomeNosotros::IMAGEN_DE_FABRICA));
        var quitar = document.querySelector('[data-imagen-quitar]');
        input.addEventListener('change', function () {
            var archivo = input.files[0];
            if (!archivo) return;
            var url = URL.createObjectURL(archivo);
            actual.src = previaImg.src = url;
            document.querySelector('[data-imagen-nombre]').textContent = 'Imagen nueva: ' + archivo.name + '. Se guarda al tocar «Guardar cambios».';
            if (quitar) quitar.checked = false;
        });
        if (quitar) {
            quitar.addEventListener('change', function () {
                actual.src = previaImg.src = quitar.checked ? fabrica : original;
                if (quitar.checked) input.value = '';
            });
        }
    })();
</script>
@endsection
