@extends('layouts.plantilla-back')

{{-- Admin → Home › Trabajamos con: los logos de clientes y proveedores de la franja que está debajo del slider de la
     Home. Cada logo se agrega y se edita en un modal. Guarda HomeSeccionesController. --}}

@section('content')

@php
    $visibles = $marcas->where('visible', true);
    // Si la validación falla, el modal vuelve a abrirse con lo que se había escrito.
    $reabrir = $errors->any() ? (old('_marca') === 'nueva' ? 'nueva' : (int) old('_marca')) : null;
@endphp

<x-osole::admin-page-header.v1 eyebrow="Home" title="Trabajamos con" description="Los logos de clientes y proveedores que aparecen en la Home, en la franja de abajo del slider.">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('home') }}#trabajamos-con" target="_blank" rel="noopener">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver en la Home
        </a>
        <button type="button" class="btn btn-primary" data-marca-nueva>
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar logo
        </button>
    </x-slot:actions>
</x-osole::admin-page-header.v1>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

{{-- Cómo se ve: la misma franja que la Home, con los logos visibles y en orden. --}}
<section class="card hsec-previa" aria-labelledby="hsec-previa-titulo">
    <div class="card-header">
        <div class="adm-card-titulo">
            <h2 id="hsec-previa-titulo">Así se ve en la Home</h2>
            <p>Sólo los logos visibles, en el orden de la lista. Si no entran en la pantalla, se desplazan solos.</p>
        </div>
    </div>
    <div class="hsec-franja">
        <span class="hsec-franja__titulo">Trabajamos con</span>
        <div class="hsec-franja__logos">
            @forelse ($visibles as $marca)
                <img src="{{ $marca->logoUrl() }}" alt="{{ $marca->nombre }}" loading="lazy">
            @empty
                <p class="hsec-franja__vacia">No hay logos visibles: la franja no aparece en la Home.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="card" aria-labelledby="hsec-lista-titulo">
    <div class="card-header">
        <div class="adm-card-titulo">
            <h2 id="hsec-lista-titulo">Logos <span class="adm-chip">{{ $marcas->count() }}</span></h2>
            <p>El orden son dos letras o números (aa, ab, ac…): se muestran de menor a mayor.</p>
        </div>
    </div>

    @if ($marcas->isEmpty())
        <div class="hsec-vacio">
            <i class="fa-regular fa-handshake" aria-hidden="true"></i>
            <p><strong>Todavía no hay logos.</strong> Agregá el primero: el logo de un cliente o de un proveedor, con su nombre y, si querés, el link a su sitio.</p>
            <button type="button" class="btn btn-primary" data-marca-nueva><i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar logo</button>
        </div>
    @else
        <ul class="hsec-logos" role="list">
            @foreach ($marcas as $marca)
                <li @class(['hsec-logo', 'hsec-logo--oculto' => ! $marca->visible])>
                    <div class="hsec-logo__img">
                        <img src="{{ $marca->logoUrl() }}" alt="" loading="lazy">
                    </div>
                    <div class="hsec-logo__datos">
                        <div class="hsec-logo__fila">
                            <strong class="hsec-logo__nombre">{{ $marca->nombre }}</strong>
                            <span class="adm-chip" title="Orden">{{ $marca->orden }}</span>
                        </div>
                        @if ($marca->url)
                            <a class="hsec-logo__link" href="{{ $marca->url }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($marca->url, '/')) }}</a>
                        @else
                            <span class="hsec-logo__link hsec-logo__link--sin">Sin link</span>
                        @endif
                        <span @class(['adm-estado', 'adm-estado--ok' => $marca->visible])>{{ $marca->visible ? 'Se muestra' : 'Oculto' }}</span>
                    </div>
                    <div class="hsec-logo__acciones">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-marca-editar="{{ $marca->id }}"
                            data-nombre="{{ $marca->nombre }}" data-url="{{ $marca->url }}" data-orden="{{ $marca->orden }}" data-visible="{{ $marca->visible ? 1 : 0 }}"
                            data-logo="{{ $marca->logoUrl() }}" data-action="{{ route('dashboard.home.trabajamos-con.update', $marca) }}">
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Editar
                        </button>
                        <form method="POST" action="{{ route('dashboard.home.trabajamos-con.visible', $marca) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="visible" value="{{ $marca->visible ? 0 : 1 }}">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-regular {{ $marca->visible ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i> {{ $marca->visible ? 'Ocultar' : 'Mostrar' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.home.trabajamos-con.destroy', $marca) }}" data-confirmar="Se va a quitar «{{ $marca->nombre }}» de la Home. No se puede deshacer.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Eliminar {{ $marca->nombre }}"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

{{-- Agregar / editar un logo. --}}
<div class="modal fade" id="marca-modal" tabindex="-1" aria-labelledby="marca-modal-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="marca-form" method="POST" enctype="multipart/form-data"
            action="{{ $reabrir && $reabrir !== 'nueva' ? route('dashboard.home.trabajamos-con.update', $reabrir) : route('dashboard.home.trabajamos-con.store') }}"
            data-crear="{{ route('dashboard.home.trabajamos-con.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $reabrir && $reabrir !== 'nueva' ? 'PUT' : 'POST' }}" data-metodo>
            <input type="hidden" name="_marca" value="{{ $reabrir ?? 'nueva' }}" data-marca-id>
            <div class="modal-header">
                <h2 class="modal-title" id="marca-modal-titulo">{{ $reabrir && $reabrir !== 'nueva' ? 'Editar logo' : 'Agregar logo' }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>Revisá los datos marcados.</strong>
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif
                <div class="hsec-modal">
                    <div>
                        <input type="file" class="visually-hidden hsec-archivo" id="marca-logo" name="logo" accept="image/png,image/jpeg,image/webp" data-logo-input>
                        <label class="hsec-drop" for="marca-logo">
                            <span class="hsec-drop__mesa"><img src="" alt="" data-logo-previa hidden></span>
                            <span class="hsec-drop__vacio" data-logo-vacio>
                                <i class="fa-regular fa-image" aria-hidden="true"></i>
                                <b>Elegí el logo</b>
                                <small>PNG con fondo transparente, JPG o WebP · hasta 4 MB</small>
                            </span>
                            <span class="hsec-drop__cambiar" data-logo-cambiar hidden><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar logo</span>
                        </label>
                        <p class="form-text">Se ajusta solo: al guardar se le saca el margen y queda centrado, con el mismo aire y el mismo tamaño que los demás. Conviene PNG o WebP con fondo transparente, de al menos 400 px de ancho.</p>
                    </div>
                    <div class="hsec-campos">
                        <div>
                            <label class="form-label" for="marca-nombre">Nombre de la empresa</label>
                            <input class="form-control @error('nombre') is-invalid @enderror" id="marca-nombre" name="nombre" maxlength="120" required value="{{ old('nombre') }}" placeholder="Ej.: Prestolite">
                            <p class="form-text">No se ve en la Home: lo leen Google y los lectores de pantalla.</p>
                        </div>
                        <div>
                            <label class="form-label" for="marca-url">Link a su sitio <span class="text-muted fw-normal">(opcional)</span></label>
                            <input class="form-control @error('url') is-invalid @enderror" id="marca-url" name="url" maxlength="500" inputmode="url" value="{{ old('url') }}" placeholder="www.empresa.com">
                            <p class="form-text">Con link, el logo se puede tocar y abre ese sitio.</p>
                        </div>
                        <div class="hsec-campos__fila">
                            <div>
                                <label class="form-label" for="marca-orden">Orden</label>
                                <input class="form-control hsec-orden @error('orden') is-invalid @enderror" id="marca-orden" name="orden" maxlength="2" value="{{ old('orden', $siguienteOrden) }}" data-siguiente="{{ $siguienteOrden }}" autocomplete="off">
                            </div>
                            <div class="form-check form-switch hsec-switch">
                                <input type="hidden" name="visible" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="marca-visible" name="visible" value="1" @checked(old('visible', '1') === '1')>
                                <label class="form-check-label" for="marca-visible">Mostrar en la Home</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" data-marca-guardar>{{ $reabrir && $reabrir !== 'nueva' ? 'Guardar cambios' : 'Agregar logo' }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade adm-confirmar" id="marca-confirmar" tabindex="-1" aria-labelledby="marca-confirmar-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="adm-confirmar__icono" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
                <h2 class="adm-confirmar__titulo" id="marca-confirmar-titulo">¿Eliminar el logo?</h2>
                <p class="adm-confirmar__texto" data-confirmar-texto></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirmar-aceptar>Eliminar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        var modal = document.getElementById('marca-modal');
        var form = document.getElementById('marca-form');
        var input = form.querySelector('[data-logo-input]');
        var previa = form.querySelector('[data-logo-previa]');
        var vacio = form.querySelector('[data-logo-vacio]');
        var cambiar = form.querySelector('[data-logo-cambiar]');
        var orden = form.querySelector('#marca-orden');

        function mostrarLogo(src) {
            previa.hidden = !src;
            previa.src = src || '';
            vacio.hidden = !!src;
            cambiar.hidden = !src;
        }

        function abrir(datos) {
            var editar = !!datos;
            form.action = editar ? datos.action : form.dataset.crear;
            form.querySelector('[data-metodo]').value = editar ? 'PUT' : 'POST';
            form.querySelector('[data-marca-id]').value = editar ? datos.id : 'nueva';
            modal.querySelector('#marca-modal-titulo').textContent = editar ? 'Editar logo' : 'Agregar logo';
            form.querySelector('[data-marca-guardar]').textContent = editar ? 'Guardar cambios' : 'Agregar logo';
            form.nombre.value = editar ? datos.nombre : '';
            form.url.value = editar ? (datos.url || '') : '';
            orden.value = editar ? datos.orden : orden.dataset.siguiente;
            form.querySelector('#marca-visible').checked = editar ? datos.visible === '1' : true;
            input.value = '';
            input.required = !editar;
            mostrarLogo(editar ? datos.logo : '');
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            var aviso = form.querySelector('.alert-danger');
            if (aviso) aviso.remove();
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        document.querySelectorAll('[data-marca-nueva]').forEach(function (b) { b.addEventListener('click', function () { abrir(null); }); });
        document.querySelectorAll('[data-marca-editar]').forEach(function (b) {
            b.addEventListener('click', function () { abrir(Object.assign({ id: b.dataset.marcaEditar }, b.dataset)); });
        });
        modal.addEventListener('shown.bs.modal', function () { form.nombre.focus(); });

        input.addEventListener('change', function () {
            var archivo = input.files[0];
            if (archivo) mostrarLogo(URL.createObjectURL(archivo));
        });
        orden.addEventListener('input', function () { orden.value = orden.value.toLowerCase().replace(/[^a-z0-9]/g, ''); });

        @if ($reabrir)
            @php $marcaReabrir = $reabrir !== 'nueva' ? $marcas->firstWhere('id', $reabrir) : null; @endphp
            input.required = {{ $marcaReabrir ? 'false' : 'true' }};
            mostrarLogo(@json($marcaReabrir?->logoUrl() ?? ''));
            bootstrap.Modal.getOrCreateInstance(modal).show();
        @else
            input.required = true;
        @endif

        // Confirmación de eliminar.
        var confirmar = document.getElementById('marca-confirmar');
        var pendiente = null;
        document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                pendiente = f;
                confirmar.querySelector('[data-confirmar-texto]').textContent = f.dataset.confirmar;
                bootstrap.Modal.getOrCreateInstance(confirmar).show();
            });
        });
        confirmar.querySelector('[data-confirmar-aceptar]').addEventListener('click', function () {
            if (pendiente) { var f = pendiente; pendiente = null; f.submit(); }
        });
    })();
</script>
@endsection
