@extends('layouts.plantilla-back')

@section('styles')
    <style>
        /* Ficha técnica: las columnas de la familia en una grilla que se adapta al ancho
           (campos-producto.js agrega campos con .col-3: acá ocupan una celda). */
        #dynamic-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 13rem), 1fr));
            gap: 1rem 1.25rem;
            margin: 0;
        }

        #dynamic-inputs > .row {
            display: contents;
        }

        #dynamic-inputs > *,
        #dynamic-inputs > .row > * {
            width: auto;
            max-width: none;
            margin: 0 !important;
            padding: 0;
        }

        #dynamic-inputs:empty::before {
            content: "Elegí la familia para ver sus campos.";
            color: var(--adm-muted);
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
        title="Crear producto"
        description="Cargá los datos, elegí la familia para completar la ficha técnica y guardá."
        :back-href="route('dashboard.productos')"
        back-label="Volver a productos"
    >
        <x-slot:actions>
            <button data-form-id="actualizar" type="submit" class="btn btn-primary submit">Guardar producto</button>
        </x-slot:actions>
    </x-osole::admin-page-header.v1>

  @if(session('success'))
      <div class="alert alert-success" role="status">
          {{ session('success') }}
      </div>
  @endif
  @if(session('warning'))
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

    <form class="adm-pila" id='actualizar' action="{{route('producto.store')}}" method="POST" enctype="multipart/form-data">
        @csrf

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
                        <input type="text" class="form-control" id="orden" name='orden' value='aa' required>
                    </div>
                    <div class="col-8 col-md-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name='codigo' required>
                    </div>
                    <div class="col-md-7">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name='nombre' required>
                    </div>
                    <div class='col-md-6'>
                        <label class="form-label" for="select-categoria">Familia</label>
                        <select class='form-select' name="categoria" id='select-categoria'>
                            @foreach ($categorias as $categoria)
                                <option value={{$categoria->id}}>{{ucfirst($categoria->nombre)}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" name='marca'>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name='modelo'>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="precio" class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">$</span>
                            <input type="text" class="form-control" id="precio" name='precio' inputmode="decimal">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="descuento" class="form-label">Descuento</label>
                        <div class="input-group">
                            <input type="number" min="0" class="form-control" id="descuento" name='descuento'>
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
                                    <input type="checkbox" class="form-check-input m-0" name="nuevo" id="nuevo">
                                    Nuevo
                                </label>
                                <label class="producto-estado" for="reconstruido">
                                    <input type="checkbox" class="form-check-input m-0" name="reconstruido" id="reconstruido">
                                    Reconstruido
                                </label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <input type="hidden" name="oferta" value="0">
                        <label class="adm-switch adm-switch--oferta" for="oferta">
                            <input type="checkbox" role="switch" id="oferta" name="oferta" value="1" @checked(old('oferta')) aria-describedby="oferta-ayuda">
                            En oferta
                        </label>
                        <p class="producto-oferta-ayuda" id="oferta-ayuda">Se muestra en Ofertas de la web. No cambia el descuento.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card" aria-labelledby="producto-ficha">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-ficha">Ficha técnica</h2>
                    <p>Los campos dependen de la familia elegida.</p>
                </div>
            </div>
            <div class="card-body">
                <div id="dynamic-inputs" class="row"
                     data-url-categoria="{{ route('categoria.show', ':id') }}" data-cargar-inicial="true"></div>
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
                <textarea class='summernote-text' id="summernote1" name="descripcion"></textarea>
            </div>
        </section>

        @include('backend.partials.partes-relacionadas', [
            'productoActualId' => null,
            'partesRelacionadas' => $partesRelacionadas,
        ])

        @include('backend.partials.equivalencias', [
            'equivalencias' => collect(),
        ])

        @include('backend.partials.aplicaciones', [
            'aplicaciones' => collect(),
        ])

        <section class="card" aria-labelledby="producto-archivos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2 id="producto-archivos">Imágenes</h2>
                    <p>La primera que subas queda como imagen principal; después podés cambiarla.</p>
                </div>
            </div>
            <div class="card-body">
                <label for="imagenes" class="form-label">Añadir imágenes @include('backend.partials.foto-sugerida')</label>
                <input class="form-control limit" type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" data-achicar>
            </div>
        </section>
    </form>

    <div class="adm-barra-guardar">
        <p>Al guardar, el producto queda publicado en el catálogo.</p>
        <a href="{{ route('dashboard.productos') }}" class="btn btn-secondary">Cancelar</a>
        <button data-form-id="actualizar" type="submit" class="btn btn-primary submit">Guardar producto</button>
    </div>
@endsection

@section('script')
    @include('backend.partials.achicar-imagenes')
    <script src="{{ asset('js/campos-producto.js') }}"></script>
<script>
    $(document).ready(function() {
        // Sólo «Nuevo» y «Reconstruido» se excluyen entre sí (no la oferta ni otras casillas).
        const checkboxes = document.querySelectorAll('#nuevo, #reconstruido');

checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            checkboxes.forEach((cb) => {
                if (cb !== this) cb.checked = false;
            });
        }
    });
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

</script>
@endsection
