@extends('layouts.plantilla-back')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/biblioteca-imagenes.css') }}?v=1">
@endsection

@section('content')
@php
    $ladoSugerido = (int) config('imagenes.tamano_sugerido.lado', 1200);
@endphp
<div class="bi" id="biblioteca"
     data-store-url="{{ route('dashboard.imagenes.store') }}"
     data-edit-url="{{ route('producto.edit', ['id' => '__ID__']) }}"
     data-csrf="{{ csrf_token() }}">

    <header class="bi-header">
        <div>
            <p class="bi-eyebrow">Productos</p>
            <h1 class="bi-title">Biblioteca de imágenes</h1>
            <p class="bi-subtitle">
                Acá están todas las fotos de los productos. Cada una tiene un <strong>código</strong>: escribiéndolo en la
                columna <strong>Imágenes</strong> del Excel de productos, la foto queda asignada a ese producto.
            </p>
        </div>
        <div class="bi-actions">
            <a class="bi-btn" href="{{ route('dashboard.productos.exportar', ['categoria_id' => 'todos']) }}">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Exportar Excel
            </a>
            <a class="bi-btn bi-btn-primario" href="{{ route('dashboard.productos.excel.importar') }}">
                <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> Importar Excel
            </a>
        </div>
    </header>

    <ol class="bi-pasos" aria-label="Cómo cargar imágenes masivamente">
        <li class="bi-paso is-actual"><div><strong>Subí las imágenes</strong><span>Arrastrá todas las fotos juntas a la zona de abajo. Cada una recibe un código.</span></div></li>
        <li class="bi-paso"><div><strong>Revisá los códigos</strong><span>Se ponen solos según el nombre del archivo. Si querés, cambialos con el lápiz.</span></div></li>
        <li class="bi-paso"><div><strong>Asignalas con el Excel</strong><span>En la columna Imágenes del Excel escribí los códigos y subilo en «Importar Excel».</span></div></li>
    </ol>

    {{-- Subida --}}
    <section class="bi-card" aria-labelledby="titulo-subir">
        <div class="bi-card-cuerpo">
            <div class="bi-subida">
                <label class="bi-zona" data-zona>
                    <input type="file" id="input-imagenes" accept="image/png,image/jpeg,image/webp" multiple aria-describedby="ayuda-subida">
                    <span class="bi-zona-icono" aria-hidden="true"><i class="fa-solid fa-images"></i></span>
                    <span class="bi-zona-titulo" id="titulo-subir">Arrastrá las imágenes acá o hacé clic para elegirlas</span>
                    <span class="bi-zona-ayuda" id="ayuda-subida">
                        Podés elegir muchas a la vez · JPG, PNG o WEBP · hasta 15 MB cada una<br>
                        Ideal: cuadradas de {{ $ladoSugerido }} × {{ $ladoSugerido }} px. Se optimizan solas.
                    </span>
                </label>
                <div>
                    <h2 class="bi-card-titulo">¿Qué código recibe cada imagen?</h2>
                    <ul class="bi-reglas">
                        <li><i class="fa-solid fa-link" aria-hidden="true"></i><span>Si el archivo se llama como un producto, toma <strong>su código</strong>: <span class="bi-mono">NFSO42.jpg</span> → <span class="bi-codigo"><span>NFSO42</span></span></span></li>
                        <li><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Si ese código ya lo tiene otra foto, se numera: <span class="bi-codigo"><span>NFSO42-2</span></span>, <span class="bi-codigo"><span>NFSO42-3</span></span>…</span></li>
                        <li><i class="fa-solid fa-hashtag" aria-hidden="true"></i><span>Si el nombre no coincide con ningún producto, recibe uno <strong>genérico</strong>: <span class="bi-codigo bi-codigo-mudo"><span>IMG-0001</span></span></span></li>
                        <li><i class="fa-solid fa-pen" aria-hidden="true"></i><span>Podés <strong>cambiar el código</strong> de cualquier imagen con el lápiz.</span></li>
                    </ul>
                </div>
            </div>

            <div class="bi-subidas" id="panel-subidas" hidden>
                <div class="bi-subidas-cabecera">
                    <div>
                        <h2 class="bi-card-titulo">Subidas de ahora</h2>
                        <p class="bi-card-sub" id="resumen-subidas" aria-live="polite"></p>
                    </div>
                    <button type="button" class="bi-btn bi-btn-chico" id="copiar-codigos" hidden>
                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Copiar todos los códigos
                    </button>
                </div>
                <div class="bi-progreso" aria-hidden="true"><span id="barra-subidas"></span></div>
                <div class="bi-subidas-lista" id="lista-subidas"></div>
            </div>
        </div>
    </section>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('dashboard.imagenes') }}" class="bi-barra" role="search">
        <div class="bi-buscar">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $busqueda }}" placeholder="Buscar por código de imagen, código o nombre de producto…" aria-label="Buscar imágenes">
        </div>
        <input type="hidden" name="filtro" value="{{ $filtro }}">
        <nav class="bi-segmentado" aria-label="Filtrar imágenes">
            @foreach (['todas' => 'Todas', 'en-uso' => 'En uso', 'sin-asignar' => 'Sin asignar'] as $valor => $etiqueta)
                <a href="{{ request()->fullUrlWithQuery(['filtro' => $valor, 'page' => null]) }}"
                   class="{{ $filtro === $valor ? 'is-activo' : '' }}" @if ($filtro === $valor) aria-current="true" @endif>
                    {{ $etiqueta }} <span class="bi-cuenta">{{ number_format($totales[$valor], 0, ',', '.') }}</span>
                </a>
            @endforeach
        </nav>
        <select name="orden" class="bi-select" aria-label="Ordenar" onchange="this.form.submit()">
            <option value="recientes" @selected($orden === 'recientes')>Más recientes primero</option>
            <option value="codigo" @selected($orden === 'codigo')>Por código (A-Z)</option>
        </select>
    </form>

    {{-- Grilla --}}
    <div class="bi-grilla" id="grilla">
        @forelse ($imagenes as $imagen)
            @php
                $usosImagen = $usos[mb_strtolower($imagen->path)] ?? [];
                $principal = array_values(array_filter($usosImagen, fn ($u) => $u['rol'] === 'principal'));
                $galeria = array_values(array_filter($usosImagen, fn ($u) => $u['rol'] === 'galeria'));
            @endphp
            <article class="bi-tile" data-update-url="{{ route('dashboard.imagenes.update', $imagen) }}"
                     data-destroy-url="{{ route('dashboard.imagenes.destroy', $imagen) }}" data-en-uso="{{ $usosImagen ? '1' : '0' }}">
                <div class="bi-tile-foto">
                    <img src="{{ $imagen->url }}" alt="Imagen {{ $imagen->codigo }}" loading="lazy">
                    <span class="bi-tile-estado">
                        @if ($usosImagen)
                            <span class="bi-etiqueta bi-etiqueta-uso"><i class="fa-solid fa-check" aria-hidden="true"></i> En uso</span>
                        @else
                            <span class="bi-etiqueta bi-etiqueta-libre">Sin asignar</span>
                        @endif
                    </span>
                    <a class="bi-tile-abrir" href="{{ $imagen->url }}" target="_blank" rel="noopener" title="Ver en tamaño real" aria-label="Ver {{ $imagen->codigo }} en tamaño real">
                        <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="bi-tile-cuerpo">
                    <div class="bi-fila" data-rol="vista">
                        <span class="bi-codigo" title="{{ $imagen->codigo }}">
                            <span data-rol="codigo">{{ $imagen->codigo }}</span>
                            <button type="button" class="bi-copiar" data-copiar="{{ $imagen->codigo }}" title="Copiar código" aria-label="Copiar el código {{ $imagen->codigo }}"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                        </span>
                        <button type="button" class="bi-btn bi-btn-fantasma bi-btn-icono" data-accion="editar" title="Cambiar el código" aria-label="Cambiar el código {{ $imagen->codigo }}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                    </div>
                    <form class="bi-editar" data-rol="edicion" hidden>
                        <input type="text" maxlength="60" value="{{ $imagen->codigo }}" aria-label="Nuevo código" autocomplete="off" spellcheck="false">
                        <button type="submit" class="bi-btn bi-btn-primario bi-btn-icono" title="Guardar" aria-label="Guardar el código"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                        <button type="button" class="bi-btn bi-btn-icono" data-accion="cancelar" title="Cancelar" aria-label="Cancelar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </form>
                    <div class="bi-error-campo" data-rol="error" role="alert" hidden></div>
                    <div class="bi-usos">
                        @if (! $usosImagen)
                            No está asignada a ningún producto.
                        @endif
                        @foreach (['Principal de' => $principal, 'También en' => $galeria] as $texto => $lista)
                            @if ($lista)
                                <div>{{ $texto }}
                                    @foreach (array_slice($lista, 0, 3) as $uso)
                                        <a href="{{ route('producto.edit', ['id' => $uso['id']]) }}" title="{{ $uso['nombre'] }}">{{ $uso['codigo'] }}</a>@if (! $loop->last), @endif
                                    @endforeach
                                    @if (count($lista) > 3) y {{ count($lista) - 3 }} más @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="bi-fila">
                        <span class="bi-tile-nombre" title="Archivo: {{ $imagen->nombre_original }}">{{ $imagen->ancho && $imagen->alto ? $imagen->ancho.' × '.$imagen->alto.' px' : $imagen->nombre_original }}</span>
                        @if ($usosImagen)
                            <span class="bi-btn bi-btn-fantasma bi-btn-icono is-deshabilitado" title="No se puede eliminar porque está en uso" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
                        @else
                            <button type="button" class="bi-btn bi-btn-fantasma bi-btn-icono" data-accion="eliminar" title="Eliminar imagen" aria-label="Eliminar {{ $imagen->codigo }}"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                        @endif
                    </div>
                    <div class="bi-confirmar" data-rol="confirmar" hidden>
                        ¿Eliminar la imagen? No se puede deshacer.
                        <button type="button" class="bi-btn bi-btn-peligro bi-btn-chico" data-accion="confirmar-eliminar">Sí, eliminar</button>
                        <button type="button" class="bi-btn bi-btn-chico" data-accion="no-eliminar">No</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="bi-card" style="grid-column: 1 / -1" id="grilla-vacia">
                <div class="bi-vacio">
                    <i class="fa-regular fa-images" aria-hidden="true"></i>
                    @if ($busqueda !== '' || $filtro !== 'todas')
                        <h3>No hay imágenes con estos filtros</h3>
                        <p>Probá con otra búsqueda o <a href="{{ route('dashboard.imagenes') }}">mirá todas las imágenes</a>.</p>
                    @else
                        <h3>Todavía no hay imágenes</h3>
                        <p>Subí las fotos de los productos arrastrándolas a la zona de arriba.</p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($imagenes->hasPages())
        <div class="bi-paginacion">
            <span>Mostrando {{ $imagenes->firstItem() }}–{{ $imagenes->lastItem() }} de {{ number_format($imagenes->total(), 0, ',', '.') }}</span>
            {{ $imagenes->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- Una imagen recién subida (la completa biblioteca-imagenes.js) --}}
<template id="plantilla-tile">
    <article class="bi-tile is-nueva" data-en-uso="0">
        <div class="bi-tile-foto">
            <img alt="" loading="lazy">
            <span class="bi-tile-estado"><span class="bi-etiqueta bi-etiqueta-nueva"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva</span></span>
            <a class="bi-tile-abrir" target="_blank" rel="noopener" title="Ver en tamaño real"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i></a>
        </div>
        <div class="bi-tile-cuerpo">
            <div class="bi-fila" data-rol="vista">
                <span class="bi-codigo"><span data-rol="codigo"></span><button type="button" class="bi-copiar" title="Copiar código" aria-label="Copiar código"><i class="fa-regular fa-copy" aria-hidden="true"></i></button></span>
                <button type="button" class="bi-btn bi-btn-fantasma bi-btn-icono" data-accion="editar" title="Cambiar el código" aria-label="Cambiar el código"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            </div>
            <form class="bi-editar" data-rol="edicion" hidden>
                <input type="text" maxlength="60" aria-label="Nuevo código" autocomplete="off" spellcheck="false">
                <button type="submit" class="bi-btn bi-btn-primario bi-btn-icono" title="Guardar" aria-label="Guardar el código"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                <button type="button" class="bi-btn bi-btn-icono" data-accion="cancelar" title="Cancelar" aria-label="Cancelar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </form>
            <div class="bi-error-campo" data-rol="error" role="alert" hidden></div>
            <div class="bi-usos" data-rol="usos">Recién subida: todavía no está asignada a ningún producto.</div>
            <div class="bi-fila">
                <span class="bi-tile-nombre"></span>
                <button type="button" class="bi-btn bi-btn-fantasma bi-btn-icono" data-accion="eliminar" title="Eliminar imagen" aria-label="Eliminar imagen"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
            </div>
            <div class="bi-confirmar" data-rol="confirmar" hidden>
                ¿Eliminar la imagen? No se puede deshacer.
                <button type="button" class="bi-btn bi-btn-peligro bi-btn-chico" data-accion="confirmar-eliminar">Sí, eliminar</button>
                <button type="button" class="bi-btn bi-btn-chico" data-accion="no-eliminar">No</button>
            </div>
        </div>
    </article>
</template>
@endsection

@section('script')
    {{-- Achica las fotos en el navegador antes de subirlas (más rápido). --}}
    <script src="{{ asset('js/achicar-imagenes.js') }}?v=3"
            data-lado-maximo="{{ (int) config('imagenes.lado_maximo', 2000) }}"
            data-lado-sugerido="{{ $ladoSugerido }}"
            data-lado-minimo="{{ (int) config('imagenes.tamano_sugerido.minimo', 800) }}"></script>
    <script src="{{ asset('js/biblioteca-imagenes.js') }}?v=1"></script>
@endsection
