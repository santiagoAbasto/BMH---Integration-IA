{{-- Contenido de la campana del panel (también lo devuelve panel.notificaciones para actualizarla sin recargar). --}}
@php
    $iconos = ['pedido' => 'fa-solid fa-bag-shopping', 'consulta' => 'fa-regular fa-envelope', 'cliente' => 'fa-regular fa-user'];
@endphp
<div class="adm-campana__cabeza">
    <strong id="adm-campana-titulo">Notificaciones</strong>
    @if ($sinVer > 0)<span>{{ $sinVer === 1 ? '1 sin ver' : $sinVer.' sin ver' }}</span>@endif
</div>
@forelse ($avisos as $aviso)
    <a class="adm-campana__item adm-campana__item--{{ $aviso['tipo'] }}" href="{{ $aviso['url'] }}" data-campana-item="{{ $aviso['key'] }}">
        <span class="adm-campana__icono" aria-hidden="true"><i class="{{ $iconos[$aviso['tipo']] ?? 'fa-regular fa-bell' }}"></i></span>
        <span class="adm-campana__texto">
            <span class="adm-campana__etiqueta">{{ $aviso['etiqueta'] }}</span>
            <strong>{{ $aviso['nombre'] }}</strong>
            <small>{{ $aviso['detalle'] }}</small>
        </span>
        <time datetime="{{ $aviso['fecha']->toIso8601String() }}">{{ $aviso['fecha']->locale('es')->diffForHumans(short: true) }}</time>
    </a>
@empty
    <div class="adm-campana__vacio">
        <i class="fa-regular fa-circle-check" aria-hidden="true"></i>
        <p><strong>Estás al día.</strong> No hay pedidos, consultas ni clientes nuevos sin ver.</p>
    </div>
@endforelse
<div class="adm-campana__links">
    <a href="{{ route('ventas') }}">Pedidos</a>
    <a href="{{ route('dashboard.contacto.mensajes') }}">Consultas</a>
    <a href="{{ route('dashboard.clientes') }}">Clientes</a>
</div>
