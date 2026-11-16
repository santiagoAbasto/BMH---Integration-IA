@extends('layouts.plantilla-back')

{{--
    Admin → Características (CaracteristicaController). Un solo modal para crear y editar (con 600+ filas, uno por fila
    hacía la página enorme), confirmación de borrado con el impacto y fusión de duplicadas. Lo interactivo está en
    resources/js/admin/caracteristicas.js ([data-car-pantalla]).
--}}

@section('content')
@php
    use App\Http\Controllers\CaracteristicaController;
    use App\Models\Caracteristica;

    $criteriosDeOrden = [
        Caracteristica::ORDEN_CAMPO => 'Por orden',
        Caracteristica::ORDEN_AZ => 'Nombre A → Z',
        Caracteristica::ORDEN_ZA => 'Nombre Z → A',
    ];
    $base = array_filter(['buscar' => $buscar ?: null, 'ordenar' => $ordenar !== Caracteristica::ORDEN_CAMPO ? $ordenar : null]);

    // Si el servidor rechazó el formulario, el modal se vuelve a abrir con lo que se escribió.
    $reabrir = $errors->any() && old('nombre') !== null && old('id')
        ? [
            'id' => (int) old('id'), 'nombre' => old('nombre'), 'orden' => old('orden'),
            'numerica' => old('tipo') === 'numero', 'mayusculas' => (bool) old('mayusculas'), 'unidad' => old('unidad'),
            'uso' => '', 'productos' => 0, 'urlVistaFormato' => route('caracteristicas.vista-formato', (int) old('id')),
        ]
        : null;
@endphp

<div class="adm-pila" data-car-pantalla data-url-buscar="{{ route('caracteristicas.buscar') }}">

    <x-osole::admin-page-header.v1 eyebrow="Catálogo" title="Características"
        description="Los datos técnicos de los productos (voltaje, diámetro, giro…). Cada categoría elige cuáles usa y cada producto carga sus valores.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-car-nueva>
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva característica
            </button>
        </x-slot:actions>
    </x-osole::admin-page-header.v1>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-danger" role="alert">{{ session('warning') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="adm-card-titulo">
                <h2>{{ $soloSinUso ? 'Sin usar' : 'Todas' }}</h2>
                <p>{{ number_format($caracteristicas->total(), 0, ',', '.') }} {{ $caracteristicas->total() === 1 ? 'característica' : 'características' }}{{ $buscar !== '' ? ' con «'.$buscar.'»' : '' }}.</p>
            </div>
            <nav class="car-vistas" aria-label="Qué mostrar">
                <a href="{{ route('dashboard.caracteristicas', $base) }}" @if (! $soloSinUso) aria-current="page" @endif>
                    Todas <span class="adm-chip">{{ number_format($totales['todas'], 0, ',', '.') }}</span>
                </a>
                <a href="{{ route('dashboard.caracteristicas', $base + ['mostrar' => 'sin-uso']) }}" @if ($soloSinUso) aria-current="page" @endif>
                    Sin usar <span @class(['adm-chip', 'adm-chip--alerta' => $totales['sin_uso'] > 0])>{{ number_format($totales['sin_uso'], 0, ',', '.') }}</span>
                </a>
            </nav>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.caracteristicas') }}" class="car-filtros mb-3" role="search">
                @if ($soloSinUso)
                    <input type="hidden" name="mostrar" value="sin-uso">
                @endif
                <div class="car-filtros__campo car-filtros__buscar">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" name="buscar" id="buscar" value="{{ $buscar }}" placeholder="Nombre de la característica…" class="form-control">
                </div>
                <div class="car-filtros__campo">
                    <label for="ordenar" class="form-label">Ordenar</label>
                    <select name="ordenar" id="ordenar" class="form-select" data-car-ordenar>
                        @foreach ($criteriosDeOrden as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($ordenar === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-secondary">Buscar</button>
                @if ($buscar !== '')
                    <a href="{{ route('dashboard.caracteristicas', array_filter(['ordenar' => $base['ordenar'] ?? null, 'mostrar' => $soloSinUso ? 'sin-uso' : null])) }}" class="btn btn-link">Limpiar</a>
                @endif
            </form>

            @if ($caracteristicas->isEmpty())
                <div class="adm-vacio">
                    @if ($buscar !== '')
                        <p>No hay características con «{{ $buscar }}».</p>
                        <button type="button" class="btn btn-primary btn-sm" data-car-nueva>Crear una nueva</button>
                    @elseif ($soloSinUso)
                        <p>Todas las características están en uso: no hay ninguna suelta.</p>
                    @else
                        <p>Todavía no hay características. Creá la primera con «Nueva característica».</p>
                    @endif
                </div>
            @else
                <table class="table car-tabla">
                    <thead>
                        <tr>
                            <th scope="col">Orden</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Formato</th>
                            <th scope="col">La usan</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($caracteristicas as $c)
                            @php
                                $uso = CaracteristicaController::impacto((int) $c->categorias_count, (int) $c->productos_count);
                                $datos = $c->paraFormulario() + [
                                    'orden' => $c->orden,
                                    'uso' => $uso,
                                    'productos' => (int) $c->productos_count,
                                    'urlVistaFormato' => route('caracteristicas.vista-formato', $c->id),
                                    'urlVistaFusion' => route('caracteristicas.vista-fusion', $c->id),
                                    'urlFusionar' => route('caracteristicas.fusionar', $c->id),
                                ];
                            @endphp
                            <tr data-caracteristica-id="{{ $c->id }}">
                                <td class="car-tabla__orden" data-celda="orden">{{ $c->orden ?: '—' }}</td>
                                <td class="car-tabla__nombre" data-celda="nombre">{{ $c->nombre }}</td>
                                <td class="car-tabla__formato" data-celda="formato" data-etiqueta="Formato">{{ ucfirst($c->descripcionFormato()) }}</td>
                                <td data-celda="uso" data-etiqueta="La usan">
                                    @if ($uso !== '')
                                        <span class="car-uso">
                                            @if ($c->categorias_count)<span>{{ $c->categorias_count }} {{ $c->categorias_count == 1 ? 'categoría' : 'categorías' }}</span>@endif
                                            @if ($c->productos_count)<span>{{ number_format($c->productos_count, 0, ',', '.') }} {{ $c->productos_count == 1 ? 'producto' : 'productos' }}</span>@endif
                                        </span>
                                    @else
                                        <span class="adm-chip adm-chip--alerta">Sin usar</span>
                                    @endif
                                </td>
                                <td data-celda="acciones">
                                    <div class="adm-acciones">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-car-editar="{{ json_encode($datos) }}" aria-label="Editar {{ $c->nombre }}" title="Editar">
                                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-car-fusionar="{{ json_encode(['id' => $c->id, 'nombre' => $c->nombre, 'urlFusionar' => $datos['urlFusionar'], 'urlVistaFusion' => $datos['urlVistaFusion']]) }}" aria-label="Fusionar {{ $c->nombre }} con otra" title="Fusionar con otra (si están repetidas)">
                                            <i class="fa-solid fa-code-merge" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-car-borrar="{{ json_encode(['id' => $c->id, 'nombre' => $c->nombre, 'uso' => $uso]) }}" aria-label="Eliminar {{ $c->nombre }}" title="Eliminar">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($caracteristicas->hasPages())
            <div class="card-footer">
                {{ $caracteristicas->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- Crear / editar (uno solo, lo llena el JS) --}}
<div class="modal fade" id="car-modal" tabindex="-1" aria-labelledby="car-modal-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="car-form" method="POST" class="car-form" novalidate
                action="{{ route('caracteristicas.store') }}"
                data-url-crear="{{ route('caracteristicas.store') }}"
                data-url-editar="{{ route('caracteristicas.update') }}"
                data-siguiente-orden="{{ $siguienteOrden }}"
                @if ($reabrir) data-reabrir="{{ json_encode($reabrir) }}" @endif>
                @csrf
                <input type="hidden" name="_method" value="PUT" disabled>
                <input type="hidden" name="id" value="">
                <input type="hidden" name="formato_presente" value="1">

                <div class="modal-header">
                    <h2 class="modal-title" id="car-modal-titulo">Nueva característica</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body car-form">
                    <p class="car-ayuda mt-0" id="car-uso" hidden></p>

                    <div class="car-form__fila">
                        <div>
                            <label for="car-orden" class="form-label">Orden</label>
                            <input type="text" id="car-orden" name="orden" class="form-control" maxlength="2" pattern="[A-Za-z0-9]{2}" autocomplete="off" aria-describedby="car-orden-ayuda">
                            <p class="car-ayuda" id="car-orden-ayuda">aa, ab… el lugar en la ficha.</p>
                        </div>
                        <div>
                            <label for="car-nombre" class="form-label">Nombre</label>
                            <input type="text" id="car-nombre" name="nombre" class="form-control" maxlength="255" required aria-describedby="car-nombre-ayuda">
                            <p class="car-ayuda" id="car-nombre-ayuda">Así se ve en la ficha del producto. Se guarda en mayúsculas.</p>
                        </div>
                    </div>

                    <fieldset class="car-formato">
                        <legend>Cómo se cargan los valores</legend>
                        <div class="car-opciones">
                            <label class="car-opcion">
                                <input type="radio" name="tipo" value="texto" class="form-check-input" checked>
                                <span><strong>Texto</strong> Cualquier valor: RUEDA LIBRE, C3/2A, IZQUIERDO…</span>
                            </label>
                            <label class="car-opcion">
                                <input type="radio" name="tipo" value="numero" class="form-check-input">
                                <span><strong>Número</strong> Sólo números (o dos con «/», como 12/24) con su unidad.</span>
                            </label>
                        </div>

                        <div class="car-formato__extra">
                            <div id="car-mayusculas-campo" class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" role="switch" id="car-mayusculas" name="mayusculas" value="1" checked>
                                <label class="form-check-label" for="car-mayusculas">Pasar el texto a mayúsculas al guardar</label>
                            </div>
                            <div id="car-unidad-campo" class="car-unidad" hidden>
                                <label for="car-unidad" class="form-label">Unidad (opcional)</label>
                                <input type="text" id="car-unidad" name="unidad" class="form-control" maxlength="10" placeholder="V, MM, A, KG…" autocomplete="off" aria-describedby="car-unidad-ayuda">
                                <p class="car-ayuda" id="car-unidad-ayuda">Se agrega sola: si escriben «12» se guarda «12V».</p>
                            </div>
                        </div>

                        <div class="car-ejemplo">
                            <label for="car-ejemplo" class="form-label">Probá cómo queda</label>
                            <input type="text" id="car-ejemplo" class="form-control" autocomplete="off" aria-describedby="car-ejemplo-salida">
                            <p class="car-ejemplo__resultado" id="car-ejemplo-salida" aria-live="polite"></p>
                        </div>
                    </fieldset>

                    <div class="car-impacto" id="car-impacto" role="status" hidden></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-car-guardar>Crear característica</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Borrar: dice qué se pierde antes de confirmar --}}
<div class="modal fade" id="car-borrar" tabindex="-1" aria-labelledby="car-borrar-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('caracteristicas.delete') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="id" value="">
                <div class="modal-header">
                    <h2 class="modal-title" id="car-borrar-titulo">¿Eliminar «<span data-car-borrar-nombre></span>»?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="car-confirmar__impacto mb-0" data-car-borrar-impacto></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Fusionar dos repetidas: muestra qué pasa con cada valor antes de confirmar --}}
<div class="modal fade" id="car-fusion" tabindex="-1" aria-labelledby="car-fusion-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                @csrf
                <input type="hidden" name="con" value="">
                <div class="modal-header">
                    <h2 class="modal-title" id="car-fusion-titulo">Fusionar «<span data-car-fusion-origen></span>» con otra</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="car-ayuda mt-0 mb-3">Para características repetidas con distinto nombre: queda una sola, con los valores y las categorías de las dos.</p>
                    @include('backend.partials.caracteristicas.buscador', [
                        'id' => 'car-fusion-buscar',
                        'etiqueta' => '¿Con cuál se fusiona? (la que queda)',
                        'placeholder' => 'Buscá la característica que queda…',
                    ])
                    <div data-car-fusion-detalle></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" disabled>Fusionar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
