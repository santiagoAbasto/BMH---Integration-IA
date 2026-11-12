@extends('layouts.plantilla-back')

@section('styles')
<style>
    .pedido-detalle {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 1.25rem;
    }

    @media (width >= 1100px) {
        .pedido-detalle { grid-template-columns: minmax(0, 2fr) minmax(16rem, 1fr); }
        .pedido-detalle__productos { grid-column: 1 / -1; }
    }

    .pedido-detalle > .card + .card { margin-top: 0; }

    .pedido-datos {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 15rem), 1fr));
        gap: 1rem 1.5rem;
        margin: 0;
    }

    .pedido-datos div { min-width: 0; }

    .pedido-datos dt {
        margin-bottom: .125rem;
        color: var(--adm-muted);
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .pedido-datos dd {
        margin: 0;
        color: var(--adm-ink);
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .pedido-estados {
        display: grid;
        gap: .875rem;
        margin: 0;
    }

    .pedido-estados div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .pedido-estados dt { color: var(--adm-muted); font-weight: 500; }
    .pedido-estados dd { margin: 0; }

    /* La tabla del pedido (backend/components/pedido-tabla, la misma del mail y el PDF):
       totales alineados a la derecha y en negrita. */
    .pedido-detalle__productos .table td:last-child,
    .pedido-detalle__productos .table th[colspan] { text-align: end; white-space: nowrap; }
    .pedido-detalle__productos .table td { font-variant-numeric: tabular-nums; }
    .pedido-detalle__productos .table tbody tr:nth-last-child(-n + 3) > * {
        background: var(--adm-surface-soft);
        --bs-table-accent-bg: transparent;
    }
    .pedido-detalle__productos .table tbody tr:last-child > * {
        color: var(--adm-ink);
        font-size: 1rem;
        font-weight: 700;
    }
</style>
@endsection

@section('content')

  <x-osole::admin-page-header.v1
      eyebrow="Ventas · Pedidos"
      :title="'Pedido #'.$pedido->id"
      :description="trim('Hecho el '.$pedido->fecha.' por '.$pedido->nombre)"
      :back-href="route('ventas')"
      back-label="Volver a pedidos"
  >
      <x-slot:actions>
          <a href="{{ route('pedido.pdf', ['id' => $pedido->id]) }}" target="_blank" rel="noopener" class="btn btn-primary">
              <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Generar PDF<span class="visually-hidden"> (se abre en otra pestaña)</span>
          </a>
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

  <div class="pedido-detalle">
    <section class="card" aria-labelledby="pedido-facturacion">
      <div class="card-header">
        <h2 id="pedido-facturacion" class="mb-0">Datos de facturación</h2>
      </div>
      <div class="card-body">
        <dl class="pedido-datos">
          <div><dt>Razón social</dt><dd>{{$pedido->nombre}}</dd></div>
          <div><dt>DNI / CUIT</dt><dd>{{$pedido->dni}}</dd></div>
          <div><dt>Email</dt><dd>@if(str_contains((string) $pedido->mail, '@'))<a href="mailto:{{ $pedido->mail }}">{{$pedido->mail}}</a>@else {{ $pedido->mail ?: '—' }} @endif</dd></div>
          <div><dt>Celular</dt><dd>{{$pedido->celular ?: '—'}}</dd></div>
          <div><dt>Dirección</dt><dd>{{$pedido->direccion ?: '—'}}</dd></div>
          <div><dt>Provincia</dt><dd>{{$provincias[array_search($pedido->provincia, array_column($provincias, 'id'))]['nombre']}}</dd></div>
          <div><dt>Localidad</dt><dd>{{$pedido->localidad ?: '—'}}</dd></div>
          <div><dt>Código postal</dt><dd>{{$pedido->cp ?: '—'}}</dd></div>
        </dl>
        {{-- <div class='col-6 mb-2'><strong>Tipo de pago: </strong>{{$pedido->tipo_pago}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Método de envío: </strong>{{$pedido->tipo_envio}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Mensaje: </strong>{{$pedido->notas}}</div> --}}
        {{-- <div class='col-6 mb-2'><strong>Archivo adjunto: </strong>{{$pedido->archivo ? 'disponible en el mail' : 'ninguno'}}</div> --}}
      </div>
    </section>

    <section class="card" aria-labelledby="pedido-estado">
      <div class="card-header">
        <h2 id="pedido-estado" class="mb-0">Estado</h2>
      </div>
      <div class="card-body">
        <dl class="pedido-estados">
          <div>
            <dt>Compra</dt>
            <dd><span class="adm-chip {{ $pedido->estado == 'Esperando pago' ? 'adm-chip--alerta' : 'adm-chip--ok' }}">{{ $pedido->estado }}</span></dd>
          </div>
          <div>
            <dt>Orden</dt>
            <dd><span class="adm-chip {{ ['Pendiente' => 'adm-chip--alerta', 'Procesado' => 'adm-chip--ok', 'Cancelado' => 'adm-chip--peligro'][$pedido->estado_orden] ?? '' }}">{{ $pedido->estado_orden }}</span></dd>
          </div>
          <div>
            <dt>Total</dt>
            <dd class="fw-bold">$ {{ $pedido->total_pedido }}</dd>
          </div>
        </dl>
        <p class="adm-ayuda-tabla mt-3 mb-0">Los estados se cambian desde el <a href="{{ route('ventas') }}">listado de pedidos</a>.</p>
      </div>
    </section>

    {{-- <div class="row">
        <label class='mb-3 pt-3 pb-2 form-label' for="" style='font-size:20px;font-weight:500;text-transform:uppercase;color:#C10B17;'>Datos de envío</label><br>
        @if($pedido->direccion2 != null)
            <div class='col-6 mb-2'><strong>Dirección: </strong>{{$pedido->direccion2}}</div>
            <div class='col-6 mb-2'><strong>Provincia: </strong>{{$provincias[array_search($pedido->provincia2, array_column($provincias, 'id'))]['nombre']}}</div>
            <div class='col-6 mb-2'><strong>Localidad: </strong>{{$pedido->localidad2}}</div>
            <div class='col-6 mb-2'><strong>Código postal: </strong>{{$pedido->cp2}}</div>
        @else
        <div>Mismos que facturación</div>
        @endif
    </div> --}}

    <section class="card pedido-detalle__productos" aria-labelledby="pedido-productos">
      <div class="card-header">
        <h2 id="pedido-productos" class="mb-0">Productos</h2>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          @include('backend/components/pedido-tabla')
        </div>
      </div>
    </section>
  </div>

@endsection
