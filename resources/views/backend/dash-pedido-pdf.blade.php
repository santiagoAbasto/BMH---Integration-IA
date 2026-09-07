<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pedido #{{ $pedido->id }} - BMH</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', Arial, Helvetica, sans-serif;
            color: #212529;
            margin: 0;
            padding: 24px;
            font-size: 13px;
        }
        .no-print { text-align: right; margin: 0 0 18px; }
        .btn-print {
            background: #C10B17; color: #fff; border: none; padding: 10px 18px;
            border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600;
        }
        .btn-print:hover { background: #a30d15; }

        .doc-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #C10B17; padding-bottom: 12px; margin-bottom: 18px;
        }
        .doc-header img { height: 56px; width: auto; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 20px; color: #C10B17; }
        .doc-title .fecha { font-size: 13px; color: #555; margin-top: 6px; }

        .section-title {
            font-size: 15px; font-weight: 600; text-transform: uppercase; color: #C10B17;
            margin: 20px 0 8px; border-bottom: 1px solid #eee; padding-bottom: 4px;
        }

        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 24px; }
        .datos div { padding: 3px 0; }
        .datos strong { color: #444; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; vertical-align: top; }
        thead th { background: #C10B17; color: #fff; }
        tbody tr:nth-child(even) { background: #f7f7f7; }
        tfoot td, tfoot th { font-weight: 700; }

        .doc-footer {
            margin-top: 26px; padding-top: 10px; border-top: 1px solid #eee;
            font-size: 11px; color: #999; text-align: center;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { margin: 16mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    @php
        $fechaPedido = $pedido->fecha ?? ($pedido->created_at ?? null);
        $fechaPedidoStr = $fechaPedido ? \Carbon\Carbon::parse($fechaPedido)->format('d/m/Y H:i') : '—';

        $provNombre = '';
        if (!empty($provincias) && $pedido->provincia) {
            $idx = array_search($pedido->provincia, array_column($provincias, 'id'));
            if ($idx !== false) $provNombre = $provincias[$idx]['nombre'];
        }
    @endphp

    <div class="doc-header">
        <img src="{{ asset('imagenes/logobmh.png') }}" alt="BMH">
        <div class="doc-title">
            <h1>Pedido (orden #{{ $pedido->id }})</h1>
            <div class="fecha">Fecha del pedido: {{ $fechaPedidoStr }}</div>
        </div>
    </div>

    <div class="section-title">Datos de facturación</div>
    <div class="datos">
        <div><strong>Razón social: </strong>{{ $pedido->nombre }}</div>
        <div><strong>DNI / CUIT: </strong>{{ $pedido->dni }}</div>
        <div><strong>Email: </strong>{{ $pedido->mail }}</div>
        <div><strong>Celular: </strong>{{ $pedido->celular }}</div>
        <div><strong>Dirección: </strong>{{ $pedido->direccion }}</div>
        <div><strong>Provincia: </strong>{{ $provNombre }}</div>
        <div><strong>Localidad: </strong>{{ $pedido->localidad }}</div>
        <div><strong>Código postal: </strong>{{ $pedido->cp }}</div>
    </div>

    <div class="section-title">Productos</div>
    @include('backend/components/pedido-tabla')

    <div class="doc-footer">
        Documento generado automáticamente desde BMH &middot; {{ $fechaPedidoStr }}
    </div>
</body>
</html>
