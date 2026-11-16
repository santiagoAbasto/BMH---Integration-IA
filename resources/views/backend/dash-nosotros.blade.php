@extends('layouts.plantilla-back')

{{-- Admin → Nosotros: la página Nosotros tiene dos bloques, la historia (título, texto y foto) y «Lo que nos mueve»
     (título y tres tarjetas). Cada campo está en el bloque que edita, y el mapa de la derecha marca dónde va lo que
     se está escribiendo. Guarda NosotrosController@update. --}}

@section('content')

@php
    use App\Models\Nosotros;
    use App\Support\IconoSitio;

    $contenido = $nosotros_contenido ?? new Nosotros();
    $foto = $nosotros_portada?->path ? asset('imagenes/'.$nosotros_portada->path) : asset(Nosotros::IMAGEN_DE_FABRICA);
    $ayudaTarjetas = [
        'mision' => 'Para qué existe BMH.',
        'vision' => 'Hacia dónde va la empresa.',
        'valores' => 'Qué ofrece a sus clientes. Una línea por servicio.',
    ];
@endphp

<x-osole::admin-page-header.v1 eyebrow="Contenido del sitio" title="Nosotros" description="La página Nosotros tiene dos bloques: la historia de BMH con una foto, y «Lo que nos mueve» con tres tarjetas.">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('nosotros') }}" target="_blank" rel="noopener">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver la página
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

<div class="nadm">
    <form id="nosotros-form" class="nadm__form" action="{{ route('nosotros.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- 1. Historia --}}
        <section class="card" id="bloque-historia" aria-labelledby="nadm-historia-titulo">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="nadm-historia-titulo"><span class="nadm-num">1</span> Historia</h2>
                    <p>El primer bloque de la página: título y texto a la izquierda, foto a la derecha.</p>
                </div>
            </div>
            <div class="card-body nadm-historia">
                <div class="nadm-campos">
                    <div>
                        <label class="form-label" for="historia_titulo">Título</label>
                        <input class="form-control @error('historia_titulo') is-invalid @enderror" id="historia_titulo" name="historia_titulo" maxlength="160"
                            value="{{ old('historia_titulo', $contenido->historiaTitulo()) }}" placeholder="{{ Nosotros::HISTORIA_TITULO }}" data-zona-campo="historia-titulo">
                        <p class="form-text">Es el título principal de la página (el que lee Google). Si lo dejás vacío, vuelve a «{{ Nosotros::HISTORIA_TITULO }}».</p>
                    </div>
                    <div data-zona-campo="historia-texto">
                        <label class="form-label" for="info">Texto</label>
                        <textarea id="info" name="info" class="nadm-editor" data-zona="historia-texto">{{ old('info', $contenido->info) }}</textarea>
                        <p class="form-text">Separá las ideas en párrafos: se leen mejor que un bloque largo.</p>
                    </div>
                </div>
                <div class="nadm-foto" data-zona-campo="historia-foto">
                    <span class="form-label d-block">Foto</span>
                    <input type="file" class="visually-hidden hsec-archivo" id="portada" name="portada" accept="image/png,image/jpeg,image/webp" data-foto-input>
                    <label class="hsec-drop nadm-foto__drop" for="portada">
                        <span class="hsec-drop__mesa"><img src="{{ $foto }}" alt="Foto actual de la historia" data-foto-previa></span>
                        <span class="hsec-drop__cambiar"><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar foto</span>
                    </label>
                    <p class="form-text" data-foto-nombre>Vertical, de unos 1040 × 1200 px (JPG, PNG o WebP, hasta 4 MB). Se recorta para llenar el recuadro.</p>
                </div>
            </div>
        </section>

        {{-- 2. Lo que nos mueve --}}
        <section class="card" id="bloque-valores" aria-labelledby="nadm-valores-titulo">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="nadm-valores-titulo"><span class="nadm-num">2</span> Lo que nos mueve</h2>
                    <p>El bloque gris de abajo: un título y tres tarjetas. Si dejás una tarjeta vacía, no se muestra.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="nadm-titulo-valores">
                    <label class="form-label" for="valores_titulo">Título del bloque</label>
                    <input class="form-control @error('valores_titulo') is-invalid @enderror" id="valores_titulo" name="valores_titulo" maxlength="120"
                        value="{{ old('valores_titulo', $contenido->valoresTitulo()) }}" placeholder="{{ Nosotros::VALORES_TITULO }}" data-zona-campo="valores-titulo">
                </div>
                <div class="nadm-tarjetas">
                    @foreach (Nosotros::TARJETAS as $campo => [$titulo, $icono])
                        <div class="nadm-tarjeta" data-zona-campo="{{ $campo }}">
                            <div class="nadm-tarjeta__cabeza">
                                <span class="nadm-tarjeta__icono" aria-hidden="true">{!! IconoSitio::svg($icono) !!}</span>
                                <div>
                                    <label class="nadm-tarjeta__titulo" for="{{ $campo }}">{{ $titulo }}</label>
                                    <p>{{ $ayudaTarjetas[$campo] }}</p>
                                </div>
                            </div>
                            <textarea id="{{ $campo }}" name="{{ $campo }}" class="nadm-editor nadm-editor--chico" data-zona="{{ $campo }}">{{ old($campo, $contenido->{$campo}) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="adm-barra-guardar">
            <p>Los cambios se ven en la página Nosotros apenas guardás.</p>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>

    {{-- Mapa de la página: dónde va cada campo. Se marca la parte que se está editando; tocar una parte lleva al campo. --}}
    <aside class="nadm-mapa" aria-labelledby="nadm-mapa-titulo">
        <h2 id="nadm-mapa-titulo">Mapa de la página</h2>
        <p>Marca dónde se ve lo que estás editando. Tocá una parte para ir a su campo.</p>
        <div class="nadm-mapa__pagina">
            <div class="nadm-mapa__header">Header</div>
            <div class="nadm-mapa__migas">Inicio › Nosotros</div>
            <div class="nadm-mapa__historia">
                <span class="nadm-num nadm-mapa__num">1</span>
                <div class="nadm-mapa__col">
                    <button type="button" class="nadm-zona nadm-zona--titulo" data-zona="historia-titulo" data-ir="historia_titulo">Título</button>
                    <button type="button" class="nadm-zona nadm-zona--texto" data-zona="historia-texto" data-ir="info">Texto</button>
                </div>
                <button type="button" class="nadm-zona nadm-zona--foto" data-zona="historia-foto" data-ir="portada">Foto</button>
            </div>
            <div class="nadm-mapa__valores">
                <span class="nadm-num nadm-mapa__num">2</span>
                <button type="button" class="nadm-zona nadm-zona--titulo-chico" data-zona="valores-titulo" data-ir="valores_titulo">Título del bloque</button>
                <div class="nadm-mapa__tarjetas">
                    @foreach (Nosotros::TARJETAS as $campo => [$titulo])
                        <button type="button" class="nadm-zona nadm-zona--tarjeta" data-zona="{{ $campo }}" data-ir="{{ $campo }}">{{ $titulo }}</button>
                    @endforeach
                </div>
            </div>
            <div class="nadm-mapa__footer">Footer</div>
        </div>
    </aside>
</div>
@endsection

@section('script')
<script>
    (function () {
        var zonas = document.querySelectorAll('.nadm-mapa [data-zona]');
        function marcar(nombre) {
            zonas.forEach(function (z) { z.classList.toggle('is-activa', z.dataset.zona === nombre); });
        }

        // Editores de texto: el de la historia más alto; las tarjetas, compactos.
        $('.nadm-editor').each(function () {
            var zona = this.dataset.zona;
            $(this).summernote({
                height: this.classList.contains('nadm-editor--chico') ? 150 : 260,
                toolbar: [['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol']], ['insert', ['link']], ['view', ['codeview']]],
                callbacks: { onFocus: function () { marcar(zona); } }
            });
        });

        // Al entrar en un campo se marca su parte en el mapa.
        document.querySelectorAll('[data-zona-campo]').forEach(function (campo) {
            campo.addEventListener('focusin', function (e) {
                var dueño = e.target.closest('[data-zona-campo]');
                if (dueño) marcar(dueño.dataset.zonaCampo);
            });
            campo.addEventListener('mouseenter', function () { campo.dataset.zonaCampo && marcar(campo.dataset.zonaCampo); });
        });

        // Tocar una parte del mapa lleva a su campo.
        zonas.forEach(function (z) {
            z.addEventListener('click', function () {
                var destino = document.getElementById(z.dataset.ir);
                if (!destino) return;
                var bloque = destino.closest('[data-zona-campo]') || destino;
                bloque.scrollIntoView({ behavior: 'smooth', block: 'center' });
                marcar(z.dataset.zona);
                if ($(destino).hasClass('nadm-editor')) {
                    setTimeout(function () { $(destino).summernote('focus'); }, 350);
                } else if (destino.type !== 'file') {
                    destino.focus({ preventScroll: true });
                }
            });
        });

        // Foto: vista previa de la nueva antes de guardar.
        var input = document.querySelector('[data-foto-input]');
        input.addEventListener('change', function () {
            var archivo = input.files[0];
            if (!archivo) return;
            document.querySelector('[data-foto-previa]').src = URL.createObjectURL(archivo);
            document.querySelector('[data-foto-nombre]').textContent = 'Foto nueva: ' + archivo.name + '. Se guarda al tocar «Guardar cambios».';
        });
    })();
</script>
@endsection
