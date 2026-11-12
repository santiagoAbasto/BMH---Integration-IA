@extends('layouts.plantilla-back')

{{-- Admin → Contacto › Mensajes (como en Conelec): App\Http\Controllers\ContactoMensajeController, con
     osole::admin-inbox.v1. Lista a la izquierda y la consulta abierta (?mensaje={id}) a la derecha. --}}

@php
    $query = array_filter(['filtro' => $filtros['filtro'], 'q' => $filtros['q'] ?: null]);
    $fecha = fn ($momento) => $momento->isToday() ? $momento->format('H:i') : ($momento->isCurrentYear() ? $momento->locale('es')->translatedFormat('j M') : $momento->format('d/m/Y'));
@endphp

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Contacto" title="Mensajes" :description="'Las consultas del formulario de Contacto del sitio, de la más nueva a la más vieja. '.($sinLeer ? ($sinLeer === 1 ? 'Tenés 1 sin leer.' : 'Tenés '.$sinLeer.' sin leer.') : 'Están todas leídas.')">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.contacto') }}#avisos">
            <i class="fa-regular fa-envelope" aria-hidden="true"></i> Email de avisos
        </a>
    </x-slot:actions>
</x-osole::admin-page-header.v1>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<x-osole::admin-inbox.v1 class="contacto-bandeja" :reading="(bool) $abierto" reader-id="lector" reader-label="Consulta abierta">
    <x-slot:toolbar>
        <x-osole::admin-inbox.v1.toolbar
            tabs-label="Filtrar consultas"
            :tabs="[
                ['label' => 'Todas', 'href' => route('dashboard.contacto.mensajes', array_filter(['q' => $filtros['q'] ?: null])), 'count' => $total, 'current' => $filtros['filtro'] !== 'no-leidos'],
                ['label' => 'No leídas', 'href' => route('dashboard.contacto.mensajes', array_filter(['filtro' => 'no-leidos', 'q' => $filtros['q'] ?: null])), 'count' => $sinLeer, 'current' => $filtros['filtro'] === 'no-leidos', 'highlight' => true],
            ]"
            :search-action="route('dashboard.contacto.mensajes')"
            :search="$filtros['q']"
            search-label="Buscar consultas"
            placeholder="Buscar por nombre, email, empresa o texto"
            :hidden="array_filter(['filtro' => $filtros['filtro']])"
            :clear-href="$filtros['q'] !== '' ? route('dashboard.contacto.mensajes', array_filter(['filtro' => $filtros['filtro']])) : null"
        />
    </x-slot:toolbar>

    <x-slot:list>
        <x-osole::admin-inbox.v1.list label="Consultas" :bulk-action="route('contacto.mensajes.destroy-many', $query)" noun="consulta" plural="consultas" select-all-label="Seleccionar todas">
            @foreach ($mensajes as $mensaje)
                <x-osole::admin-inbox.v1.row
                    :href="route('dashboard.contacto.mensajes', [...$query, 'mensaje' => $mensaje->id]).'#lector'"
                    :name="$mensaje->nombre"
                    :time="$fecha($mensaje->created_at)"
                    :datetime="$mensaje->created_at->toIso8601String()"
                    :time-title="$mensaje->created_at->format('d/m/Y H:i')"
                    :excerpt="\Illuminate\Support\Str::limit((string) $mensaje->mensaje, 140)"
                    :tags="array_values(array_filter([$mensaje->motivo, $mensaje->empresa]))"
                    :unread="! $mensaje->leido()"
                    :active="$abierto?->is($mensaje) ?? false"
                    :value="$mensaje->id"
                />
            @endforeach
            <x-slot:empty>
                @if ($filtros['q'] !== '' || $filtros['filtro'])
                    <x-osole::admin-inbox.v1.placeholder :heading-level="3" title="No hay consultas con ese filtro." text="Probá con otra búsqueda o mirá todas las consultas." />
                @else
                    <x-osole::admin-inbox.v1.placeholder :heading-level="3" title="Todavía no llegaron consultas." text="Cuando alguien complete el formulario de Contacto del sitio, la consulta aparece acá y te llega por email." />
                @endif
            </x-slot:empty>
            @if ($mensajes->hasPages())
                <x-slot:pagination>{{ $mensajes->onEachSide(1)->links('pagination::bootstrap-5') }}</x-slot:pagination>
            @endif
        </x-osole::admin-inbox.v1.list>
    </x-slot:list>

    @if ($abierto)
        @php
            $tel = preg_replace('/(?!^\+)[^\d]/', '', (string) $abierto->telefono);
            $link = fn (string $href, string $texto) => new \Illuminate\Support\HtmlString('<a href="'.e($href).'">'.e($texto).'</a>');
        @endphp
        <x-osole::admin-inbox.v1.reader
            :title="$abierto->nombre"
            :subtitle="$abierto->created_at->locale('es')->translatedFormat('j \d\e F \d\e Y, H:i').' h'"
            :back-href="route('dashboard.contacto.mensajes', $query)"
            :data="[
                'Email' => $link('mailto:'.$abierto->email, $abierto->email),
                'Celular' => $tel !== '' ? $link('tel:'.$tel, $abierto->telefono) : null,
                'Empresa' => $abierto->empresa,
                'Motivo' => $abierto->motivo ?: 'Sin motivo',
            ]"
        >
            <x-slot:actions>
                <a class="btn btn-primary btn-sm" href="{{ $abierto->mailtoRespuesta() }}">
                    <i class="fa-solid fa-reply" aria-hidden="true"></i> Responder
                </a>
            </x-slot:actions>

            @if (filled($abierto->mensaje))
                <div class="contacto-bandeja__texto">{{ $abierto->mensaje }}</div>
            @else
                <p class="contacto-bandeja__texto contacto-bandeja__texto--vacio">No dejó un mensaje: escribile o llamalo para ver qué necesita.</p>
            @endif

            <p class="contacto-bandeja__nota">
                @if ($abierto->aviso_enviado_at)
                    <span class="adm-chip adm-chip--ok">Aviso enviado</span> Llegó por email el {{ $abierto->aviso_enviado_at->format('d/m/Y \a \l\a\s H:i') }}.
                @else
                    <span class="adm-chip adm-chip--alerta">Sin aviso por email</span> El mail no salió (revisá el email de avisos); la consulta quedó guardada igual.
                @endif
            </p>

            <x-slot:footer>
                <form method="POST" action="{{ route('contacto.mensajes.leido', [$abierto, ...$query]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="leido" value="0">
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Marcar como no leída</button>
                </form>
                <form method="POST" action="{{ route('contacto.mensajes.destroy', [$abierto, ...$query]) }}" data-confirmar="Se va a eliminar la consulta de «{{ $abierto->nombre }}». No se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Eliminar</button>
                </form>
            </x-slot:footer>
        </x-osole::admin-inbox.v1.reader>
    @else
        <x-osole::admin-inbox.v1.placeholder title="Elegí una consulta para leerla." text="Al abrirla se marca como leída. Con «Responder» le escribís desde tu correo, con el asunto ya puesto." />
    @endif
</x-osole::admin-inbox.v1>

{{-- Confirmación de eliminar (una consulta o las elegidas en la lista). --}}
<div class="modal fade adm-confirmar" id="contacto-confirmar" tabindex="-1" aria-labelledby="contacto-confirmar-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="adm-confirmar__icono" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
                <h2 class="adm-confirmar__titulo" id="contacto-confirmar-titulo">¿Eliminar la consulta?</h2>
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
        var modal = document.getElementById('contacto-confirmar');
        var pendiente = null;

        function confirmar(form, titulo, texto) {
            pendiente = form;
            modal.querySelector('#contacto-confirmar-titulo').textContent = titulo;
            modal.querySelector('[data-confirmar-texto]').textContent = texto;
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        modal.querySelector('[data-confirmar-aceptar]').addEventListener('click', function () {
            if (pendiente) {
                var form = pendiente;
                pendiente = null;
                form.submit();
            }
        });

        // Eliminar una consulta (lector).
        document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                confirmar(form, '¿Eliminar la consulta?', form.dataset.confirmar);
            });
        });

        // Eliminar las elegidas (lista de admin-inbox.v1): se suman el CSRF y el DELETE antes de enviar.
        document.querySelectorAll('[data-osole-inbox-bulk]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var cantidad = form.querySelectorAll('[data-osole-inbox-select]:checked').length;
                if (!cantidad) return;
                if (!form.querySelector('input[name="_method"]')) {
                    form.insertAdjacentHTML('beforeend', '<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">');
                }
                var nombre = cantidad === 1 ? '1 ' + form.dataset.noun : cantidad + ' ' + form.dataset.plural;
                confirmar(form, '¿Eliminar ' + nombre + '?', 'Se van a eliminar ' + nombre + '. No se puede deshacer.');
            });
        });
    })();
</script>
@endsection
