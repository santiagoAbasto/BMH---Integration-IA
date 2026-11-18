@extends('layouts.plantilla-back')

@section('styles')
    <style>
        /* Imágenes cargadas: grilla de tarjetas con las acciones siempre a la vista. */
        .producto-imagenes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 12.5rem), 1fr));
            gap: 1rem;
        }

        .imagen-producto {
            display: flex;
            flex-direction: column;
            gap: .625rem;
            padding: .75rem;
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            background: var(--adm-surface);
        }

        .imagen-producto.portada-producto {
            border-color: var(--adm-brand);
            box-shadow: 0 0 0 1px var(--adm-brand), 0 8px 24px rgb(0 118 168 / .12);
            background: var(--adm-brand-softer);
        }

        .imagen-producto__marco {
            position: relative;
            display: grid;
            aspect-ratio: 1;
            place-items: center;
            overflow: hidden;
            border-radius: var(--adm-radius-sm);
            background: #fff;
        }

        /* «Emparejada»: la foto tiene los bordes emparejados (se puede volver a la original). */
        .imagen-producto__bordes {
            position: absolute;
            inset-block-end: .375rem;
            inset-inline-start: .375rem;
            padding: .125rem .5rem;
            border-radius: 999px;
            background: rgb(18 128 92 / .1);
            color: #12805C;
            font-size: .72rem;
            font-weight: 600;
        }

        .producto-bordes {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .imagen-producto__marco img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .imagen-producto__acciones {
            display: flex;
            flex-wrap: wrap;
            gap: .375rem;
        }

        .imagen-producto__acciones form {
            display: inline-flex;
            margin: 0;
        }

        .portada-nota {
            margin: 0;
            padding-top: .625rem;
            border-top: 1px solid #b9e1f2;
            color: var(--adm-brand-stronger);
            font-size: .8125rem;
        }

        .producto-estados {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem 1.5rem;
        }

        .producto-estado {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            min-height: 2.75rem;
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .producto-oferta-ayuda {
            margin: .125rem 0 0;
            color: var(--adm-muted);
            font-size: .8125rem;
        }
    </style>
@endsection

@section('content')
    <x-osole::admin-page-header.v1
        eyebrow="Catálogo · Productos"
        title="Editar producto"
        :description="trim($producto->codigo.' · '.$producto->nombre, ' ·')"
        :back-href="route('dashboard.productos')"
        back-label="Volver a productos"
    >
        <x-slot:actions>
            <a href="{{ route('producto', ['id' => $producto->id]) }}" class="btn btn-secondary" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver en la web<span class="visually-hidden"> (se abre en otra pestaña)</span>
            </a>
            <button data-form-id="actualizar" type="submit" class="btn btn-primary submit">Guardar cambios</button>
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
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form class="adm-pila" id='actualizar' action="{{ route('producto.update', ['id' => $producto->id]) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('put')

        <section class="card" aria-labelledby="producto-datos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-datos">Datos del producto</h2>
                    <p>Lo que identifica al producto en el catálogo y en la Zona de Clientes.</p>
                </div>
            </div>
            <div class="card-body">
                <div class='row g-3'>
                    <div class="col-4 col-md-2">
                        <label for="orden" class="form-label">Orden</label>
                        <input type="text" class="form-control" id="orden" name='orden' value='{{ $producto->orden }}' required>
                    </div>
                    <div class="col-8 col-md-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name='codigo' value="{{ $producto->codigo }}" required>
                    </div>
                    <div class="col-md-7">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name='nombre' value='{{ $producto->nombre }}' required>
                    </div>
                    <div class='col-md-6'>
                        <label class="form-label" for="select-categoria">Familia</label>
                        <select class='form-select' name="categoria" id='select-categoria'>
                            @foreach ($categorias as $categoria)
                                <option {{ $categoria->id == $producto->categoria_id ? 'selected' : '' }}
                                    value={{ $categoria->id }}>{{ ucfirst($categoria->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" value="{{ $producto->marca }}" name='marca'>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" value="{{ $producto->modelo }}" name='modelo'>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="precio" class="form-label">Precio lista</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">$</span>
                            <input type="text" class="form-control" id="precio" value="{{ $producto->precio }}" name='precio' inputmode="decimal">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="descuento" class="form-label">Descuento</label>
                        <div class="input-group">
                            <input type="number" min="0" class="form-control" id="descuento" name='descuento' value="{{ $producto->descuento }}">
                            <span class="input-group-text" aria-hidden="true">%</span>
                        </div>
                    </div>
                </div>

                <h3 class="adm-subtitulo mt-4">Estado y publicación</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <fieldset>
                            <legend class="form-label">Estado</legend>
                            <div class="producto-estados">
                                <label class="producto-estado" for="nuevo">
                                    <input type="checkbox" class="form-check-input m-0" name="nuevo" id="nuevo" @checked($producto->estado == 1)>
                                    Nuevo
                                </label>
                                <label class="producto-estado" for="reconstruido">
                                    <input type="checkbox" class="form-check-input m-0" name="reconstruido" id="reconstruido" @checked($producto->estado == 2)>
                                    Reconstruido
                                </label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        @include('backend.partials.oferta-producto', ['actual' => $producto->oferta?->value ?? '', 'tipo' => $producto->oferta_tipo, 'valor' => $producto->oferta_valor])
                    </div>
                </div>
            </div>
        </section>

        <section class="card" aria-labelledby="producto-ficha">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-ficha">Características</h2>
                    <p>Los datos técnicos que se muestran en la ficha del producto. Las que dejes vacías no se guardan.</p>
                </div>
            </div>
            <div class="card-body">
                @include('backend.partials.caracteristicas.producto', ['producto' => $producto, 'cargadas' => $caracteristicas])
            </div>
        </section>

        <section class="card" aria-labelledby="producto-descripcion">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-descripcion">Descripción</h2>
                    <p>Texto libre que se muestra en la ficha del producto.</p>
                </div>
            </div>
            <div class="card-body">
                <textarea class='summernote-text' id="summernote1" name="descripcion">{!! $producto->descripcion !!}</textarea>
            </div>
        </section>

        @include('backend.partials.partes-relacionadas', [
            'productoActualId' => $producto->id,
            'partesRelacionadas' => $partesRelacionadas,
        ])

        @include('backend.partials.equivalencias', [
            'equivalencias' => $equivalencias,
        ])

        @include('backend.partials.aplicaciones', [
            'aplicaciones' => $aplicaciones,
        ])

        <section class="card" aria-labelledby="producto-archivos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-archivos">Subir imágenes y ficha</h2>
                    <p>Las imágenes nuevas se suman a las que ya tiene el producto.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="imagenes" class="form-label">Añadir imágenes @include('backend.partials.foto-sugerida')</label>
                        <input class="form-control limit" type="file" id="imagenes" name="imagenes[]" multiple data-achicar
                            accept="image/*">
                        @include('backend.partials.emparejar-bordes-casilla', ['id' => 'emparejar-editar'])
                    </div>

                    <div class="col-md-6">
                        <label for="ficha" class="form-label">Añadir ficha técnica</label>
                        <input class="form-control limit" type="file" id="ficha" name="ficha" accept="file/*">
                    </div>
                </div>
            </div>
        </section>
    </form>

    <section class="card mt-4" aria-labelledby="producto-imagenes">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2 id="producto-imagenes">Imágenes cargadas</h2>
                <p>La imagen principal es la que se ve en la card del producto.</p>
            </div>
            <div class="producto-bordes">
                @php($emparejadas = collect($imagenes)->filter(fn ($i) => \App\Services\Imagenes\EmparejarBordes::esPareja((string) $i->path))->count())
                @if (count($imagenes) > 0)
                    {{-- Emparejar bordes de todas las fotos del producto (o volver a las originales). --}}
                    <form method="POST" action="{{ route('producto.bordes', $producto) }}" class="d-flex gap-2 flex-wrap">
                        @csrf
                        @if ($emparejadas < count($imagenes))
                            <button type="submit" name="accion" value="emparejar" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Emparejar bordes</button>
                        @endif
                        @if ($emparejadas > 0)
                            <button type="submit" name="accion" value="original" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Volver a las originales</button>
                        @endif
                    </form>
                @endif
                <span class="adm-chip">{{ count($imagenes) }} {{ count($imagenes) === 1 ? 'imagen' : 'imágenes' }}</span>
            </div>
        </div>
        <div class="card-body">
            @if (count($imagenes) === 0)
                <div class="adm-vacio">
                    <i class="fa-regular fa-image" aria-hidden="true"></i>
                    <strong>Este producto no tiene imágenes</strong>
                    <p>Subilas arriba, en «Subir imágenes y ficha», y guardá los cambios.</p>
                </div>
            @endif
            <div class="producto-imagenes">

                @foreach ($imagenes as $imagen)
                    <div class='imagen-producto {{ $imagen->tipo == 'portada' ? 'seleccionada portada-producto' : '' }}'>
                        @if ($imagen->tipo == 'portada')
                            <span class="adm-chip adm-chip--marca align-self-start"><i class="fa-solid fa-star" aria-hidden="true"></i> Imagen principal</span>
                        @endif
                        <div class='imagen-producto__marco'>
                            <img src="{{ asset('imagenes/' . $imagen->path) }}" alt="Imagen {{ $loop->iteration }} de {{ $producto->codigo }}" loading="lazy">
                            @if (\App\Services\Imagenes\EmparejarBordes::esPareja((string) $imagen->path))
                                <span class="imagen-producto__bordes" title="Bordes emparejados"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Emparejada</span>
                            @endif
                        </div>
                        <div class="imagen-producto__acciones">
                            @if ($imagen->tipo != 'portada')
                                <form
                                    action="{{ route('producto.portada', ['id_imagen' => $imagen->id, 'id_producto' => $producto->id]) }}"
                                    method="POST">
                                    @csrf
                                    @method('put')
                                    <button type="submit" class="btn btn-secondary btn-sm">Hacer principal</button>
                                </form>
                            @endif
                            {{-- Emparejar los bordes de esta foto, o volver a la original. --}}
                            <form action="{{ route('producto.bordes', $producto) }}" method="POST">
                                @csrf
                                <input type="hidden" name="imagen" value="{{ $imagen->id }}">
                                @if (\App\Services\Imagenes\EmparejarBordes::esPareja((string) $imagen->path))
                                    <button type="submit" name="accion" value="original" class="btn btn-outline-secondary btn-sm" title="Volver a la original" aria-label="Volver a la foto original {{ $loop->iteration }}"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
                                @else
                                    <button type="submit" name="accion" value="emparejar" class="btn btn-outline-primary btn-sm" title="Emparejar bordes" aria-label="Emparejar bordes de la imagen {{ $loop->iteration }}"><i class="fa-solid fa-border-all" aria-hidden="true"></i></button>
                                @endif
                            </form>
                            <button type="button" data-path='{{ $imagen->path }}' class="ver-imagen btn btn-outline-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="{{ '#editar' . $imagen->id }}" aria-label="Editar imagen {{ $loop->iteration }}">
                                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                            </button>
                            @if ($imagen->tipo != 'portada' && count($imagenes) > 1)
                                <form action="{{ route('imagen.delete', ['id' => $imagen->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Eliminar imagen {{ $loop->iteration }}">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if ($imagen->tipo == 'portada')
                            <div class="portada-nota">
                                <p class="mb-2">Si la eliminás, la card del producto mostrará la imagen predeterminada de BMH.</p>
                                <form action="{{ route('producto.portada.delete', ['id_imagen' => $imagen->id, 'id_producto' => $producto->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar la imagen principal? La card mostrará la imagen predeterminada de BMH.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar imagen principal</button>
                                </form>
                            </div>
                        @endif

                    </div>

                    {{-- MODAL --}}
                    <div class="modal fade" id="{{ 'editar' . $imagen->id }}" tabindex="-1"
                        aria-labelledby="{{ 'editar' . $imagen->id }}-titulo" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2 class="modal-title" id="{{ 'editar' . $imagen->id }}-titulo">Editar imagen</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <form id='{{ 'form' . $imagen->id }}'
                                        action="{{ route('producto.imagen.update', ['id' => $imagen->id]) }}"
                                        method="post" enctype="multipart/form-data">
                                        @csrf
                                        @method('put')
                                        <div class='row g-3'>
                                            <div class="col-sm-9">
                                                <label for="{{ 'archivo' . $imagen->id }}" class="form-label">Reemplazar imagen @include('backend.partials.foto-sugerida')</label>
                                                <input class="form-control preview"
                                                    data-form-id="{{ 'imagen' . $imagen->id }}" type="file"
                                                    id="{{ 'archivo' . $imagen->id }}" name='imagen' accept="image/*" data-achicar>
                                                @include('backend.partials.emparejar-bordes-casilla', ['id' => 'emparejar-imagen-'.$imagen->id])
                                            </div>
                                            <div class="col-sm-3">
                                                <label for="{{ 'orden' . $imagen->id }}" class="form-label">Orden</label>
                                                <input type="text" class="form-control" id="{{ 'orden' . $imagen->id }}" name='orden'
                                                    value='{{ $imagen->orden }}' required>
                                            </div>
                                        </div>

                                        <div class='d-flex justify-content-center mt-3' style='max-height:50vh;'>
                                            <img id="{{ 'imagen' . $imagen->id }}"
                                                src="{{ asset('imagenes/' . $imagen->path) }}"
                                                alt="Vista previa de la imagen"
                                                class="adm-preview mt-0" style="max-height: 48vh;">

                                        </div>

                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button data-form-id="{{ 'form' . $imagen->id }}" type="button"
                                        class="btn btn-primary submit">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    <div class="adm-barra-guardar">
        <p>Revisá los datos y guardá: las imágenes nuevas se suben al guardar.</p>
        <a href="{{ route('dashboard.productos') }}" class="btn btn-secondary">Cancelar</a>
        <button data-form-id="actualizar" type="submit" class="btn btn-primary submit">Guardar cambios</button>
    </div>
@endsection

@section('script')
    @include('backend.partials.achicar-imagenes')
<script>
  var idProducto = "{{ $producto->id }}"; // var: con Turbo el script se vuelve a correr en cada visita
</script>

    <script>
        $(document).ready(function() {

            document.getElementById('nuevo').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('reconstruido').checked = false;
                }
            });

            document.getElementById('reconstruido').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('nuevo').checked = false;
                }
            });

            $('.summernote-text').each(function() {

                // Inicializar Summernote para este editor
                $(this).summernote({
                    placeholder: '',
                    tabsize: 2,
                    height: 120,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        // ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        // ['table', ['table']],
                        // ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });
            });
        });


        // Preview de imagenes
        function mostrarImagenSeleccionada(input) {
            var imagenId = $(input).data('form-id');
            if (input.files && input.files[0]) {
                var lector = new FileReader();
                lector.onload = function(e) {
                    // Muestra la imagen previa
                    document.getElementById(imagenId).src = e.target.result;
                    document.getElementById(imagenId).style.display = 'block';
                };
                // Lee el archivo de imagen como una URL de datos
                lector.readAsDataURL(input.files[0]);
            }
        }

        $(document).ready(function() {
            $('.preview').change(function() {
                mostrarImagenSeleccionada(this);
            });
        });


        // Ajax imagenes producto
        $(document).ready(function() {
            $('.eliminar').click(function() {
                var imagenId = $(this).data('id');
                $.ajax({
                    url: "{{ route('imagen.delete') }}",
                    type: 'delete',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: imagenId,
                        tipo: 'producto'
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
