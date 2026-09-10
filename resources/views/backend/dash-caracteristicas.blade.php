@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Caracteristicas</h1>

  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif
  @if(session('warning'))
      <div class="alert alert-danger">
          {{ session('warning') }}
      </div>
  @endif
  @if($errors->any())
      <div class="alert alert-danger">
        <ul class='mb-0'>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
  @endif

  <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>Caracteristicas</span>
      @php
        $criteriosDeOrden = [
          \App\Models\Caracteristica::ORDEN_CAMPO => 'Por campo orden',
          \App\Models\Caracteristica::ORDEN_AZ => 'Nombre A → Z',
          \App\Models\Caracteristica::ORDEN_ZA => 'Nombre Z → A',
        ];
      @endphp
      <form method="GET" action="{{ route('dashboard.caracteristicas') }}" class="d-flex gap-2 flex-wrap">
        <label for="ordenar" class="visually-hidden">Ordenar</label>
        <select name="ordenar" id="ordenar" class="form-select form-select-sm w-auto">
          @foreach ($criteriosDeOrden as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected($ordenar === $valor)>{{ $etiqueta }}</option>
          @endforeach
        </select>
        <label for="buscar" class="visually-hidden">Buscar</label>
        <input type="text" name="buscar" id="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre..." class="form-control form-control-sm">
        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fa fa-search"></i></button>
        @if($buscar !== '')
          <a href="{{ route('dashboard.caracteristicas', ['ordenar' => $ordenar]) }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
        @endif
      </form>
    </div>

    <div class="card-body">
      <div class='d-flex justify-content-between align-items-center mt-2 mb-2'>
        <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success"><i
          class="fa-solid fa-plus"></i> CREAR</button>
        <small class='text-muted'>{{ $caracteristicas->total() }} característica(s)</small>
      </div>

      <table class="table table-striped" style='border: 1px solid #dddddd;'>
        <thead>
          <tr>
            <th>Orden</th>
            <th>Nombre</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

          @forelse ($caracteristicas as $dato)
            <tr data-caracteristica-id="{{ $dato->id }}">
              <td>{{ $dato->orden }}</td>
              <td>{{ $dato->nombre }}</td>
              <td>
                <div class='d-flex'>
                  {{-- Un único modal de edición compartido: se rellena con estos
                       data-* al abrirlo. Antes había un modal por fila, y con
                       659 filas la página pesaba ~1,7 MB. --}}
                  <button type="button" class="btn btn-primary btn-sm me-1 js-editar"
                          data-id="{{ $dato->id }}"
                          data-nombre="{{ $dato->nombre }}"
                          data-orden="{{ $dato->orden }}">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <form action="{{ route('caracteristicas.delete', ['id' => $dato->id]) }}" method="POST" class='js-borrar'
                        data-nombre="{{ $dato->nombre }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class='text-center text-muted py-4'>
                @if($buscar !== '')
                  No hay características que coincidan con "{{ $buscar }}".
                @else
                  Todavía no hay características cargadas.
                @endif
              </td>
            </tr>
          @endforelse

        </tbody>
      </table>
    </div>

    <div class='card-footer'>
      {{ $caracteristicas->links() }}
    </div>
  </div>

  {{-- MODAL CREAR --}}
  <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="crearLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class='modal-title' id='crearLabel'>Crear caracteristica</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form class='pb-3' id='formulario-crear' action="{{ route('caracteristicas.store') }}" method="POST">
            @csrf
            <div class='row'>
              <div class="col-10 mb-3">
                <label for="crear-nombre" class="form-label">Nombre</label>
                <input type="text" id='crear-nombre' class="form-control" name='nombre' maxlength="255" required>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button data-form-id="formulario-crear" type="submit" class="btn btn-primary submit">Agregar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL EDITAR (uno solo para todas las filas) --}}
  <div class="modal fade" id="editar" tabindex="-1" aria-labelledby="editarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class='modal-title' id='editarLabel'>Editar caracteristica</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id='formulario-editar' action="{{ route('caracteristicas.update') }}" method="post">
            @csrf
            @method('put')
            <input type="hidden" name="id" id="editar-id" value="">

            <div class="mb-3">
              <label for="editar-orden" class="form-label">Orden</label>
              <input type="text" id='editar-orden' class="form-control" name='orden' maxlength="55">
            </div>

            <div class="mb-3">
              <label for="editar-nombre" class="form-label">Nombre</label>
              <input type="text" id='editar-nombre' class="form-control" name='nombre' maxlength="255" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button data-form-id="formulario-editar" type="submit" class="btn btn-primary submit">{{ __('Actualizar') }}</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('script')
<script src="https://cdn.tailwindcss.com"></script>
<script>
  (function () {
    var modalEditar = document.getElementById('editar');

    // Cambiar el orden aplica al instante; el form no manda `page`, así que
    // vuelve a la primera página conservando la búsqueda.
    var selectOrdenar = document.getElementById('ordenar');
    if (selectOrdenar) {
      selectOrdenar.addEventListener('change', function () {
        selectOrdenar.form.submit();
      });
    }

    // Rellena el modal compartido con los datos de la fila que se clickeó.
    document.querySelectorAll('.js-editar').forEach(function (boton) {
      boton.addEventListener('click', function () {
        document.getElementById('editar-id').value = boton.dataset.id;
        document.getElementById('editar-nombre').value = boton.dataset.nombre || '';
        document.getElementById('editar-orden').value = boton.dataset.orden || '';
        bootstrap.Modal.getOrCreateInstance(modalEditar).show();
      });
    });

    // Confirmación y bloqueo del botón: el doble submit sobre una fila ya
    // borrada era lo que devolvía el error del servidor.
    document.querySelectorAll('form.js-borrar').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var boton = form.querySelector('button[type="submit"]');

        if (form.dataset.enviando === '1') {
          e.preventDefault();
          return;
        }

        if (!confirm('¿Borrar la característica "' + (form.dataset.nombre || '') + '"?')) {
          e.preventDefault();
          return;
        }

        form.dataset.enviando = '1';
        boton.disabled = true;
      });
    });
  })();
</script>
@endsection
