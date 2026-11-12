@extends('layouts.plantilla-back')

@section('content')
    <x-osole::admin-page-header.v1 eyebrow="Catálogo" title="Categorías" description="Las familias del catálogo: su imagen, si se destacan y los campos de su ficha técnica.">
        <x-slot:actions>
            <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Crear categoría
            </button>
        </x-slot:actions>
    </x-osole::admin-page-header.v1>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-danger" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    <section class="card mb-4" aria-labelledby="ajuste-precios">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2 id="ajuste-precios">Ajustar precios por porcentaje</h2>
                <p>Sube o baja los precios de una categoría completa o de un solo producto.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <form action="{{ route('categoria.actualizarPorcentaje') }}" method="POST" class="adm-ajuste">
                        @csrf
                        <h3 class="adm-subtitulo">Por categoría</h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="categoria_select" class="form-label">Categoría</label>
                                <select name="categoria_id" id="categoria_select" class="form-select" required>
                                    <option value="todos">Todas</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label for="tipo" class="form-label">Ajuste</label>
                                <select name="tipo" id="tipo" class="form-select" required>
                                    <option value="aumentar">Aumentar</option>
                                    <option value="descontar">Descontar</option>
                                    <option value="limpiar">Limpiar</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="0">General</option>
                                    <option value="1">Nuevo</option>
                                    <option value="2">Reconstruido</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label for="porcentaje" class="form-label">Porcentaje</label>
                                <div class="input-group">
                                    <input type="number" name="porcentaje" id="porcentaje" class="form-control" required>
                                    <span class="input-group-text" aria-hidden="true">%</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Aplicar a la categoría</button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('producto.actualizarPorcentaje') }}" method="POST" class="adm-ajuste">
                        @csrf
                        <h3 class="adm-subtitulo">Por producto</h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="producto_select" class="form-label">Producto</label>
                                <select name="producto_id" id="producto_select" class="form-select" required>
                                    <option value="">Escribí el código del producto…</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->codigo }} - {{ $producto->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="tipo-producto" class="form-label">Ajuste</label>
                                <select name="tipo" id="tipo-producto" class="form-select" required>
                                    <option value="aumentar">Aumentar</option>
                                    <option value="descontar">Descontar</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="porcentaje-producto" class="form-label">Porcentaje</label>
                                <div class="input-group">
                                    <input type="number" name="porcentaje" id="porcentaje-producto" class="form-control" required>
                                    <span class="input-group-text" aria-hidden="true">%</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Aplicar al producto</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="card" aria-labelledby="categorias-listado">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2 id="categorias-listado">Todas las categorías</h2>
                <p>{{ count($categorias) }} {{ count($categorias) === 1 ? 'categoría' : 'categorías' }}. Las destacadas se muestran primero en el sitio.</p>
            </div>
            {{-- <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success"><i
                    class="fa-solid fa-plus"></i> CREAR</button> --}}

            {{-- MODAL CREAR --}}
            <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="crear-titulo" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="crear-titulo">Crear categoría</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form class='pb-3' id='formulario-crear' action="{{ route('categoria.store') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class='row'>
                                    <div class="col-2 mb-3">
                                        <label for="orden" class="form-label">Orden</label>
                                        <input type="text" class="form-control" name='orden' value='aa'
                                            required>
                                    </div>
                                    <div class="col-10 mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" name='nombre' required>
                                    </div>

                                    <h3 class="adm-subtitulo mt-2">Atributos de la ficha técnica</h3>
                                    <div class="checkbox-group">
<div class="row">
                                                    @foreach ($caracteristicas as $caracteristica)
                                                        <div class="col-md-4">
                                                            <label>
                                                                <input type="checkbox" name="caracteristicas[]" value="{{ $caracteristica->id }}">
                                                                {{ $caracteristica->nombre }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                    </div>



                                    <div class="mb-3">
                                        <label for="portada" class="form-label">Imagen <span
                                                class='recomendada'>(recomendada 260x260 px)</span></label>
                                        <input class="form-control preview" type="file" id="imagen" name='portada'
                                            accept="image/*" required>
                                    </div>

                                </div>

                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button data-form-id="formulario-crear" type="submit"
                                class="btn btn-primary submit">Agregar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        {{-- <th>Descuento</th> --}}
                        <th>Destacada</th>
                        <th><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($categorias as $categoria)
                        <tr>
                            <td>{{ $categoria->orden }}</td>
                            <td><img src="{{ asset('imagenes/' . $categoria->portada) }}" class="adm-miniatura adm-miniatura--contener" alt="" loading="lazy"></td>
                            <td class="fw-semibold">{{ ucfirst($categoria->nombre) }}</td>
                            {{-- <td>{{$categoria->descuento.'%'}}</td> --}}
                            <td><label class="adm-switch" for="categoria{{ $categoria->id }}"><input class='categoriaCheckbox'
                                    type="checkbox" role="switch" data-id="{{ $categoria->id }}" id="categoria{{ $categoria->id }}"
                                    name="destacada" value="1" {{ $categoria->destacada ? 'checked' : '' }}><span class="visually-hidden">Destacar {{ $categoria->nombre }}</span></label></td>
                            <td>
                                <div class='adm-acciones'>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="{{ '#editar' . $categoria->id }}"><i
                                            class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#{{ 'eliminar' . $categoria->id }}"><i
                                            class="fa-solid fa-trash-can"></i></button>
                                </div>

                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

        </div>
    </section>

    @foreach ($categorias as $categoria)
        {{-- MODAL EDITAR --}}
        <div class="modal fade" id="editar{{ $categoria->id }}" tabindex="-1" aria-labelledby="editar{{ $categoria->id }}-titulo"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="editar{{ $categoria->id }}-titulo">Editar categoría</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id='agregar{{ $categoria->id }}' class='mb-4 loading'
                            action="{{ route('categoria.update', ['id' => $categoria->id]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('put')

                            <div class="row g-3 align-items-center">
                                <div class="col-2 ">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="text" class="form-control" name='orden'
                                        value='{{ $categoria->orden }}' required>
                                </div>
                                <div class="col-10">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name='nombre'
                                        value='{{ $categoria->nombre }}' required>
                                </div>
                                {{-- <div class="col-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" class="form-control" name='descuento' value={{$categoria->descuento}} required>
              </div> --}}

                                <div class="col-lg-12">

                                    <div class="checkbox-group">
<div class="row">
                                                @foreach($caracteristicas as $caracteristica)
                                                    <div class="col-md-4">
                                                        <label>
                                                            <input
                                                                type="checkbox"
                                                                name="caracteristicas[]"
                                                                value="{{ $caracteristica->id }}"
                                                                @if($categoria->caracteristicas->contains($caracteristica->id)) checked @endif
                                                            >
                                                            {{ $caracteristica->nombre }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            

                                            


                                    </div>
                                </div>
                                <div class="my-3">
                                    <label for="portada" class="form-label">Imagen <span
                                            class='recomendada'>(recomendada 260x260 px)</span></label>
                                    <input class="form-control preview" data-form-id="{{ 'imagen' . $categoria->id }}"
                                        type="file" id="imagen" name='portada' accept="image/*">
                                </div>
                                <div class='d-flex justify-content-center' style='max-height:50vh;'>
                                    <img id="{{ 'imagen' . $categoria->id }}"
                                        src="{{ asset('imagenes/' . $categoria->portada) }}"
                                        alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
                                </div>


                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button data-form-id="agregar{{ $categoria->id }}" type="submit"
                            class="btn btn-primary submit">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
       

        {{-- MODAL ELIMINAR --}}
        <div class="modal fade" id="{{ 'eliminar' . $categoria->id }}" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body adm-confirmar-cuerpo"><span class="adm-confirmar__icono" aria-hidden="true"><i class="fa-solid fa-trash-can"></i></span>
                        ¿Eliminar la categoría {{ ucfirst($categoria->nombre) }}?<br>
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('categoria.delete', ['id' => $categoria->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#{{ 'eliminar' . $categoria->id }}">Eliminar</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('script')
    <script>
        // CARACTERÍSTICAS
        $(document).ready(function() {
            $('.categoriaCheckbox').click(function() {

                var productoID = $(this).data('id');
                var estadoDestacada = $(this).is(':checked');
                $.ajax({
                    url: "{{ route('categoria.destacada') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        producto_id: productoID,
                        destacada: estadoDestacada
                    },
                    success: function(response) {
                        // Manejo de respuesta exitosa
                        console.log(response.mensaje);
                    },
                    error: function(xhr) {
                        // Manejo de error
                        console.error(xhr.responseText);
                    }
                });
            });

        });
    </script>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#producto_select').select2({
                placeholder: "Escribe el código del producto...",
                allowClear: true
            });
        });
    </script>
@endpush
