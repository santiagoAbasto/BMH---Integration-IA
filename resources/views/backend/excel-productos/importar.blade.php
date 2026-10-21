@extends('layouts.plantilla-back')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/biblioteca-imagenes.css') }}?v=1">
@endsection

@section('content')
<div class="bi">
    <a class="bi-volver" href="{{ route('dashboard.productos') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver a Productos</a>

    <header class="bi-header">
        <div>
            <p class="bi-eyebrow">Productos</p>
            <h1 class="bi-title">Importar Excel de productos</h1>
            <p class="bi-subtitle">
                Actualizá el <strong>precio</strong> y las <strong>imágenes</strong> de muchos productos a la vez.
                Antes de cambiar nada vas a ver una vista previa con lo que va a cambiar y los avisos de cada fila.
            </p>
        </div>
        <div class="bi-actions">
            <a class="bi-btn" href="{{ route('dashboard.imagenes') }}"><i class="fa-solid fa-images" aria-hidden="true"></i> Biblioteca de imágenes</a>
        </div>
    </header>

    @if (session('warning'))
        <div class="bi-cartel bi-cartel-advertencia" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div>{{ session('warning') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="bi-cartel bi-cartel-error" role="alert"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i><div><strong>No se pudo subir el archivo</strong>{{ $errors->first() }}</div></div>
    @endif

    <ol class="bi-pasos" aria-label="Pasos para importar">
        <li class="bi-paso"><div><strong>Descargá el Excel</strong><span>Trae todos los productos con su precio y los códigos de sus imágenes.</span></div></li>
        <li class="bi-paso"><div><strong>Completá Precio e Imágenes</strong><span>En Imágenes, escribí los códigos de la biblioteca separados por coma.</span></div></li>
        <li class="bi-paso is-actual"><div><strong>Subilo y revisá</strong><span>Ves qué cambia y los avisos por fila. Recién al confirmar se aplica.</span></div></li>
    </ol>

    {{-- 1. Descargar --}}
    <section class="bi-card" aria-labelledby="titulo-descargar">
        <div class="bi-card-cuerpo">
            <h2 class="bi-card-titulo" id="titulo-descargar">1. Descargá el Excel con los datos actuales</h2>
            <p class="bi-card-sub">Usalo siempre como base: tiene las columnas en el orden correcto y los códigos de imagen de hoy. Si cambiaste códigos en la biblioteca, descargalo de nuevo.</p>
            <form method="GET" action="{{ route('dashboard.productos.exportar') }}" class="bi-barra" style="margin: 14px 0 0">
                <select name="categoria_id" class="bi-select" aria-label="Qué productos descargar">
                    <option value="todos">Todos los productos</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}">Sólo {{ $categoria->nombre }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bi-btn"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Descargar Excel</button>
            </form>
        </div>
    </section>

    {{-- 2. Reglas --}}
    <section class="bi-card" aria-labelledby="titulo-reglas">
        <div class="bi-card-cuerpo">
            <h2 class="bi-card-titulo" id="titulo-reglas">2. Qué pasa con cada columna al importar</h2>
            <p class="bi-card-sub" style="margin-bottom: 12px">Leelo antes de subir: así sabés exactamente qué se cambia y qué no.</p>
            <table class="bi-reglas-tabla">
                <tr>
                    <th scope="row"><span class="bi-chip-columna">Código</span></th>
                    <td><ul>
                        <li>Con el código se encuentra cada producto. <strong>No lo cambies.</strong></li>
                        <li>Si no hay ningún producto con ese código, la fila se ignora y se avisa. <strong>No se crean productos nuevos</strong> desde el Excel.</li>
                    </ul></td>
                </tr>
                <tr>
                    <th scope="row"><span class="bi-chip-columna">Nombre</span> <span class="bi-chip-columna">Categoría</span></th>
                    <td><ul><li>Son de referencia, para que sepas qué producto es cada fila. <strong>No se modifican</strong> desde el Excel.</li></ul></td>
                </tr>
                <tr>
                    <th scope="row"><span class="bi-chip-columna">Precio</span></th>
                    <td><ul>
                        <li><strong>Celda vacía:</strong> el precio no se cambia.</li>
                        <li><strong>Un número</strong> (2591 o 2591,50): reemplaza el precio actual.</li>
                        <li>Si no es un número, no se cambia y se avisa.</li>
                    </ul></td>
                </tr>
                <tr>
                    <th scope="row"><span class="bi-chip-columna is-imagenes">Imágenes</span></th>
                    <td><ul>
                        <li>Escribí los <strong>códigos de la biblioteca</strong> separados por coma: <span class="bi-codigo"><span>NFSO42, NFSO42-2</span></span></li>
                        <li><strong>Reemplazan todas las fotos</strong> que tenía el producto. La <strong>primera es la principal</strong> y las demás se muestran en ese orden.</li>
                        <li><strong>Celda vacía:</strong> las fotos del producto <strong>no se tocan</strong>.</li>
                        <li><strong>Un guion (-):</strong> se le <strong>sacan todas las fotos</strong> al producto.</li>
                        <li>Si un código <strong>no existe</strong> en la biblioteca, las fotos de ese producto <strong>no se cambian</strong> y se avisa en la vista previa.</li>
                        <li>Los archivos de imagen <strong>nunca se borran</strong>: sólo cambia qué fotos muestra cada producto. Una foto que dejó de usarse sigue en la biblioteca.</li>
                    </ul></td>
                </tr>
            </table>
        </div>
    </section>

    {{-- 3. Subir --}}
    <section class="bi-card" aria-labelledby="titulo-subir-excel">
        <div class="bi-card-cuerpo">
            <h2 class="bi-card-titulo" id="titulo-subir-excel">3. Subí el Excel para revisarlo</h2>
            <p class="bi-card-sub" style="margin-bottom: 14px"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Todavía no se cambia nada: primero vas a ver qué cambia en cada producto y los avisos.</p>
            <form method="POST" action="{{ route('dashboard.productos.excel.previsualizar') }}" enctype="multipart/form-data" class="bi-archivo" id="form-excel">
                @csrf
                <label class="bi-zona" data-zona-excel>
                    <input type="file" name="archivo" accept=".xlsx,.xls" required aria-describedby="ayuda-excel">
                    <span class="bi-zona-icono" aria-hidden="true"><i class="fa-solid fa-file-excel"></i></span>
                    <span class="bi-zona-titulo" data-nombre-archivo>Arrastrá el Excel acá o hacé clic para elegirlo</span>
                    <span class="bi-zona-ayuda" id="ayuda-excel">Archivo .xlsx o .xls · hasta 20 MB</span>
                </label>
                <button type="submit" class="bi-btn bi-btn-primario" id="boton-revisar" disabled>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Revisar el Excel
                </button>
            </form>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
    (function () {
        var form = document.getElementById('form-excel');
        var campo = form.querySelector('input[type="file"]');
        var zona = form.querySelector('[data-zona-excel]');
        var nombre = form.querySelector('[data-nombre-archivo]');
        var boton = document.getElementById('boton-revisar');
        campo.addEventListener('change', function () {
            var archivo = campo.files[0];
            nombre.textContent = archivo ? archivo.name : 'Arrastrá el Excel acá o hacé clic para elegirlo';
            boton.disabled = !archivo;
        });
        ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.add('is-encima'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.remove('is-encima'); }); });
        form.addEventListener('submit', function () {
            boton.disabled = true;
            boton.innerHTML = '<span class="bi-girando" aria-hidden="true"></span> Revisando el Excel…';
        });
    })();
</script>
@endsection
