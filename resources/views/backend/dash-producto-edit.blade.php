@extends('layouts.plantilla-back')

@section('styles')
    <style>
        .seleccionada {
            border: 4px solid #22BE4A;

        }

        .imagen-producto {
            position: relative;
        }

        .middle {
            transition: .2s ease;
            opacity: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            text-align: center;
        }

        .imagen-producto:hover .image {
            opacity: 0.3;
        }

        .imagen-producto:hover .middle {
            opacity: 1;
        }

        .text {
            /* background-color: #04AA6D; */
            color: white;
            font-size: 16px;
            padding: 16px 32px;
        }

        th {
            background-color: #254F70 !important;
            color: #fff;
            font-weight: 300;
        }

        .acciones {
            max-width: 50px;
        }
    </style>
@endsection

@section('content')
    <h1 class='mb-4'>Editar producto</h1>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-danger">
            {{ session('warning') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">


            <form class='pb-3' id='actualizar' action="{{ route('producto.update', ['id' => $producto->id]) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('put')

                <div class='row'>
                    <div class="col-1 mb-3">
                        <label for="orden" class="form-label">Orden</label>
                        <input type="text" class="form-control" name='orden' value='{{ $producto->orden }}' required>
                    </div>
                    <div class="col-lg-2 mb-2">
                        <label for="orden" class="form-label">Codigo</label>
                        <input type="text" class="form-control" name='codigo' value="{{ $producto->codigo }}" required>

                    </div>
                    <div class="col-5 mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name='nombre' value='{{ $producto->nombre }}' required>
                    </div>
                    <div class='col-4'>
                        <label class="form-label" for="categoria">Familia</label>
                        <select class='form-select' name="categoria" id='select-categoria'>
                            @foreach ($categorias as $categoria)
                                <option {{ $categoria->id == $producto->categoria_id ? 'selected' : '' }}
                                    value={{ $categoria->id }}>{{ ucfirst($categoria->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- <div class="col-2 mb-3">
            <label for="precio" class="form-label">Precio</label>
            <input type="text" class="form-control" name='precio' value='{{$producto->precio}}' required>
        </div> --}}
                    {{-- <div class="col-2 mb-3">
          <label for="iva" class="form-label">Iva incluído</label>
          <select class='form-select' name="iva">
            <option {{$producto->iva == true ? 'selected' : ''}} value='1'>Sí</option>
            <option {{$producto->iva == false ? 'selected' : ''}} value='0'>No</option>
          </select>
        </div> --}}
                </div>



                <div class="row">
                    {{-- <div class="col-3 mb-3">
     
          <label for="precio" class="form-label">Precio neto</label>
          <input type="text" class="form-control" value="{{$producto->precioN}}" name='precioN' >
      </div> --}}
                    <div class="col-lg-6 mb-3">
                        <label for="precio" class="form-label">Precio lista</label>
                        <input type="text" class="form-control" value="{{ $producto->precio }}" name='precio'>
                    </div>
                    {{-- <div class="col-lg-4 mb-3">
            <label for="precio" class="form-label">Precio venta</label>
            <input type="text" class="form-control" value="{{$producto->precioV}}" name='precioV' >
        </div> --}}
                    <div class="col-lg-6 mb-3">
                        <label for="orden" class="form-label">Descuento %</label>
                        <input type="number" min="0" class="form-control" name='descuento'
                            value="{{ $producto->descuento }}">
                    </div>

                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="precio" class="form-label">Marca</label>
                        <input type="text" class="form-control" value="{{ $producto->marca }}" name='marca'>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="precio" class="form-label">Modelo</label>
                        <input type="text" class="form-control" value="{{ $producto->modelo }}" name='modelo'>
                    </div>
                </div>




                <!-- Campos dinámicos para las 38 columnas -->
                <div id="dynamic-inputs">

                    <div class='row'>
                        @for ($i = 1; $i <= 78; $i++)
                            @php
                                $columna = 'columna_' . $i; // nombre de la columna, como 'columna_1', 'columna_2', etc.
                                
                                $nombreCategoria = $producto->categoria()->first()->$columna; // obtener el nombre de la columna desde la tabla categorías
                                $valorProducto = $producto->$columna; // obtener el valor de la columna desde la tabla productos
                            @endphp

                            @if(!empty($nombreCategoria))
                                <div class="col-3 mb-3">
                                    <label for="{{ $columna }}" class="form-label">{{ $nombreCategoria }}</label>
                                    <input type="text" class="form-control" name="{{ $columna }}"
                                        value="{{ $valorProducto }}">
                                </div>
                                @endif

                        @endfor
                    </div>
                    @foreach ($categoriaSelected['caracteristicas'] as $caracteristica)
                    @php
                        // Cambiar estas líneas:
                        // $caractEnProducto = $producto->caracteristicas->firstWhere('id', $caracteristica['id']);
                        // $valor = old("caracteristicas.{$caracteristica['id']}", $caractEnProducto?->pivot->valor);
                        
                        // Por estas:
                        $caractEnProducto = $caracteristicas->firstWhere('id', $caracteristica['id']);
                        $valor = old("caracteristicas.{$caracteristica['id']}", $caractEnProducto?->valor ?? '');
                    @endphp
                
                    <div class="col-3 mb-3">
                        <label for="caracteristica_{{ $caracteristica['id'] }}" class="form-label">
                            {{ $caracteristica['nombre'] }}
                        </label>
                        <input type="text" class="form-control"
                               name="caracteristicas[{{ $caracteristica['id'] }}]"
                               id="caracteristica_{{ $caracteristica['id'] }}"
                               value="{{ $valor }}">
                    </div>
                @endforeach
                
                    
 
                
                
                
                

                </div>


                <div class="row">
                    <label for="precio" class="form-label">Estado:</label>

                    <div class="col-lg-6">
                        <div class="row">
                            <div class="d-flex col-lg-4">
                                <input type="checkbox" style="height: 20px; width: 20px" name="nuevo" id="nuevo"
                                    @checked($producto->estado == 1)>
                                <p style="margin-left: 10px">Nuevo</p>

                            </div>

                            <div class="d-flex col-lg-4">
                                <input type="checkbox" style="height: 20px; width: 20px" name="reconstruido"
                                    id="reconstruido" @checked($producto->estado == 2)>
                                <p style="margin-left: 10px">Reconstruido</p>

                            </div>
                        </div>
                    </div>


                </div>



                <label class='mt-3' for="descripcion">Descripción</label><br>
                <textarea class='summernote-text' id="summernote1" name="descripcion">{!! $producto->descripcion !!}</textarea>

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

                <label class='mt-2 form-label' for=""
                    style='font-size:20px;font-weight:500;'>Imágenes</label><br>
                <div class="row">

                    <div class="col-6 my-3">
                        <label for="imagenes" class="form-label">Añadir imágenes <span class='recomendada'>(recomendada
                                500x500 px)</span></label>
                        <input class="form-control limit" type="file" id="imagenes" name="imagenes[]" multiple
                            accept="image/*">
                    </div>

                    <div class="col-6 my-3">
                        <label for="imagenes" class="form-label">Añadir ficha tecnica </label>
                        <input class="form-control limit" type="file" id="imagenes" name="ficha" accept="file/*">
                    </div>

                </div>


            </form>


            <div class="row">

                @foreach ($imagenes as $imagen)
                    <div class='col-3 imagen-producto {{ $imagen->tipo == 'portada' ? 'seleccionada' : '' }}'
                        style='padding:10px;overflow:hidden;'>
                        <div class='d-flex justify-content-center'>
                            <img src="{{ asset('imagenes/' . $imagen->path) }}" class="img-fluid"
                                style="max-height: 180px;">

                        </div>
                        <div class='overlay'></div>
                        <div class="middle">
                            <div class="text d-flex">
                                @if ($imagen->tipo != 'portada')
                                    <form
                                        action="{{ route('producto.portada', ['id_imagen' => $imagen->id, 'id_producto' => $producto->id]) }}"
                                        method="POST">
                                        @csrf
                                        @method('put')
                                        <button type="submit" class="btn btn-success btn-sm me-2">Portada</button>
                                    </form>
                                @endif
                                <div data-path='{{ $imagen->path }}' class="ver-imagen btn btn-primary btn-sm me-2"
                                    data-bs-toggle="modal" data-bs-target="{{ '#editar' . $imagen->id }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </div>
                                @if (count($imagenes) > 1)
                                    <form action="{{ route('imagen.delete', ['id' => $imagen->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- MODAL --}}
                    <div class="modal fade" id="{{ 'editar' . $imagen->id }}" tabindex="-1"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id='{{ 'form' . $imagen->id }}'
                                        action="{{ route('producto.imagen.update', ['id' => $imagen->id]) }}"
                                        method="post" enctype="multipart/form-data">
                                        @csrf
                                        @method('put')
                                        <div class='row'>
                                            <div class="col-9 mb-3">
                                                <label for="imagen" class="form-label">Imagen <span
                                                        class='recomendada'>(recomendada 500x500 px)</span></label>
                                                <input class="form-control preview"
                                                    data-form-id="{{ 'imagen' . $imagen->id }}" type="file"
                                                    id="imagen" name='imagen' accept="image/*">
                                            </div>
                                            <div class="mb-3 col-3">
                                                <label for="orden" class="form-label">Orden</label>
                                                <input type="text" class="form-control" name='orden'
                                                    value='{{ $imagen->orden }}' required>
                                            </div>
                                        </div>

                                        <div class='d-flex justify-content-center' style='max-height:50vh;'>
                                            <img id="{{ 'imagen' . $imagen->id }}"
                                                src="{{ asset('imagenes/' . $imagen->path) }}"
                                                alt="Vista previa de la imagen"
                                                style="max-width: 100%; object-fit: contain;">

                                        </div>

                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button data-form-id="{{ 'form' . $imagen->id }}" type="button"
                                        class="btn btn-success submit">Guardar</button>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
            <button data-form-id="actualizar" type="submit" class="btn btn-success submit mt-3"
                style='float:right;'>Guardar</button>
        </div>
    </div>
@endsection

@section('script')
<script>
  let idProducto = "{{ $producto->id }}"; 
</script>

    <script>
        $(document).ready(function() {

            $('#select-categoria').change(function() {
    var categoriaId = $(this).val();
    var idProducto = "{{ $producto->id }}"; // Asegúrate de que el id del producto esté disponible en tu Blade

    // Si la categoría seleccionada es la misma que la del producto
    if (categoriaId == "{{ $producto->categoria_id }}") {
        // Utiliza la función route para obtener la URL de la ruta en el servidor
        var url = "{{ route('categoria.show', ':id') }}".replace(':id', categoriaId);

        $.ajax({
            url: url, // URL para obtener datos de la categoría
            type: 'GET',
            success: function(categoriaData) {
                // Limpia los inputs previos
                $('#dynamic-inputs').empty();

                // Itera sobre las propiedades del objeto categoriaData
                for (const [key, value] of Object.entries(categoriaData)) {
                    // Verifica que la columna tiene un dato
                    if (value && key.startsWith('columna_')) {
                        const columnaId = key.split('_')[1]; // Obtiene el número de columna (1, 2, 3, etc.)

                        // Solicitud para obtener el valor de la columna del producto
                        $.ajax({
                            url: `/getProductoValor/${columnaId}/${idProducto}/${categoriaId}`, // Usar el idProducto y categoriaId
                            type: 'GET',
                            success: function(productColumnValue) {
                                // Crea el input dinámico con el valor obtenido
                                const inputGroup = `
                                    <div class="mb-3">
                                        <label for="${key}" class="form-label">${value}</label>
                                        <input type="text" class="form-control" name="${key}" id="${key}" value="${productColumnValue || ''}">
                                    </div>
                                `;
                                $('#dynamic-inputs').append(inputGroup);
                            },
                            error: function(xhr, status, error) {
                                console.error(error); // Manejo de errores
                            }
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error(error); // Manejo de errores
            }
        });
    } else {
        // Si la categoría no coincide con la categoría del producto, solo se cargan los inputs vacíos
        $('#dynamic-inputs').empty();
        // Solo carga los inputs vacíos sin los valores
        var url = "{{ route('categoria.show', ':id') }}".replace(':id', categoriaId);

        $.ajax({
            url: url, // URL para obtener datos de la categoría
            type: 'GET',
            success: function(categoriaData) {
                // Itera sobre las propiedades del objeto categoriaData
                for (const [key, value] of Object.entries(categoriaData)) {
                    // Verifica que la columna tiene un dato
                    if (value && key.startsWith('columna_')) {
                        const inputGroup = `
                            <div class="mb-3">
                                <label for="${key}" class="form-label">${value}</label>
                                <input type="text" class="form-control" name="${key}" id="${key}" value="">
                            </div>
                        `;
                        $('#dynamic-inputs').append(inputGroup);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error(error); // Manejo de errores
            }
        });
    }
});





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
