{{--
    Pedido para imprimir o guardar como PDF (admin → Pedidos → «PDF» y Zona de Clientes → Historial → «Descargar»).
    Página propia, sin el layout del sitio: A4, con el logo actual de BMH (admin → Extras → Logos), los renglones
    con los precios del momento de la compra (Pedido::lineas) y los totales guardados.
--}}
@php
    use App\Services\LogosSitio;

    $logo = app(LogosSitio::class)->url(LogosSitio::HEADER_TRANSPARENTE);
    $empresa = \App\Models\Contacto::query()->find(1);

    $numero = str_pad((string) $pedido->id, 11, '0', STR_PAD_LEFT);
    try {
        $fecha = $pedido->fecha ? \Illuminate\Support\Carbon::parse(str_replace('/', '-', $pedido->fecha)) : $pedido->created_at;
    } catch (\Throwable) {
        $fecha = $pedido->created_at;
    }
    $fechaTexto = $fecha ? $fecha->format('d/m/Y') : '—';
    $horaTexto = $fecha ? $fecha->format('H:i') : null;

    $provincia = function (?string $valor) use ($provincias) {
        if ($valor === null || trim($valor) === '' || mb_strtolower(trim($valor)) === 'no tiene') {
            return null;
        }
        foreach ($provincias ?? [] as $p) {
            if ((string) $p['id'] === (string) $valor) {
                return $p['nombre'];
            }
        }

        return $valor;
    };
    $valor = fn ($v) => is_string($v) && trim($v) !== '' && mb_strtolower(trim($v)) !== 'no tiene' ? trim($v) : null;

    $cliente = array_filter([
        'Razón social' => $valor($pedido->nombre),
        'DNI / CUIT' => $valor($pedido->dni),
        'Email' => $valor($pedido->mail),
        'Celular' => $valor($pedido->celular),
    ]);
    $domicilio = array_filter([
        'Dirección' => $valor($pedido->direccion),
        'Localidad' => $valor($pedido->localidad),
        'Provincia' => $provincia($pedido->provincia),
        'Código postal' => $valor($pedido->cp),
    ]);
    $entrega = array_filter([
        'Dirección' => $valor($pedido->direccion2),
        'Localidad' => $valor($pedido->localidad2),
        'Provincia' => $provincia($pedido->provincia2),
        'Código postal' => $valor($pedido->cp2),
        'Envío' => $valor($pedido->tipo_envio),
        'Pago' => $valor($pedido->tipo_pago),
    ]);

    $lineas = $pedido->lineas();
    $unidades = $lineas->sum('cantidad');
    $moneda = fn (float $n) => '$ '.number_format($n, 2, ',', '.');
    $numero2 = fn (float $n) => number_format($n, 2, ',', '.');
    $estado = $valor($pedido->estado_orden) ?? $valor($pedido->estado);
    $descuentoCliente = $pedido->descuentoCliente();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pedido N° {{ $numero }} · BMH Distribuidora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tinta: #0a0e14;
            --tinta-950: #05070a;
            --texto: #1c2430;
            --gris: #5b6575;
            --gris-claro: #8792a2;
            --borde: #e2e7ee;
            --fondo: #f3f5f8;
            --azul: #0098da;
            --azul-fuerte: #0076a8;
            --azul-suave: #e5f5fc;
        }

        * { box-sizing: border-box; }

        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            margin: 0;
            background: var(--fondo);
            color: var(--texto);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.45;
        }

        /* --- Barra para descargar (no se imprime) --- */
        .acciones {
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            background: rgb(255 255 255 / 0.92);
            border-bottom: 1px solid var(--borde);
            backdrop-filter: blur(6px);
        }
        .acciones p { margin: 0; color: var(--gris); font-size: 13px; }
        .boton {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 0;
            border-radius: 8px;
            background: var(--azul-fuerte);
            color: #fff;
            font: 600 14px/1 Inter, Arial, sans-serif;
            cursor: pointer;
        }
        .boton:hover { background: #005f87; }
        .boton:focus-visible { outline: 3px solid var(--azul); outline-offset: 2px; }

        /* --- Hoja A4 --- */
        .hoja {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            background: #fff;
            box-shadow: 0 1px 2px rgb(10 14 20 / 0.05), 0 12px 32px rgb(10 14 20 / 0.08);
        }

        .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            padding: 26px 34px;
            background: var(--tinta-950);
            color: #fff;
            border-bottom: 4px solid var(--azul);
        }
        /* El PNG del logo trae aire transparente: se recorta como en el header del sitio. */
        .cabecera__logo { display: block; height: 58px; width: auto; margin: -6px -26px; }
        .cabecera__doc { text-align: right; }
        .cabecera__tipo {
            margin: 0;
            color: #c4ccd7;
            font: 700 10.5px/1 Montserrat, Arial, sans-serif;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .cabecera__numero {
            margin: 6px 0 0;
            font: 800 21px/1.15 Montserrat, Arial, sans-serif;
            letter-spacing: 0.01em;
        }
        .cabecera__fecha { margin: 6px 0 0; color: #c4ccd7; font-size: 12px; }

        .contenido { padding: 26px 34px 30px; }

        .resumen {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 22px;
            border: 1px solid var(--borde);
            border-radius: 10px;
            overflow: hidden;
        }
        .resumen div { padding: 11px 14px; border-left: 1px solid var(--borde); }
        .resumen div:first-child { border-left: 0; }
        .resumen dt { color: var(--gris); font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; }
        .resumen dd { margin: 3px 0 0; color: var(--tinta); font-size: 14px; font-weight: 600; }
        .estado {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: var(--azul-suave);
            color: var(--azul-fuerte);
            font-size: 12px;
        }

        .bloques { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
        .bloque { padding: 14px 16px; border: 1px solid var(--borde); border-radius: 10px; break-inside: avoid; }
        .bloque--ancho { grid-column: 1 / -1; }
        .bloque h2 {
            margin: 0 0 10px;
            color: var(--tinta);
            font: 700 11px/1 Montserrat, Arial, sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .bloque h2::before {
            content: "";
            display: inline-block;
            width: 14px;
            height: 3px;
            margin: 0 8px 3px 0;
            background: var(--azul);
            vertical-align: middle;
        }
        .datos { display: grid; grid-template-columns: max-content minmax(0, 1fr); gap: 4px 14px; margin: 0; }
        .datos dt { color: var(--gris); }
        .datos dd { margin: 0; color: var(--tinta); font-weight: 500; overflow-wrap: anywhere; }
        .notas { margin: 0; white-space: pre-line; }

        h2.titulo-tabla {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 0 0 8px;
            color: var(--tinta);
            font: 700 13px/1 Montserrat, Arial, sans-serif;
        }
        h2.titulo-tabla span { color: var(--gris); font: 500 12px/1 Inter, Arial, sans-serif; }

        table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
        thead th {
            padding: 9px 8px;
            background: var(--azul);
            color: #fff;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-align: left;
            text-transform: uppercase;
        }
        thead th:first-child { border-radius: 8px 0 0 8px; }
        thead th:last-child { border-radius: 0 8px 8px 0; }
        tbody td { padding: 8px 8px; border-bottom: 1px solid var(--borde); vertical-align: top; }
        tbody tr { break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #fafbfc; }
        .num { text-align: right; white-space: nowrap; }
        .codigo { color: var(--azul-fuerte); font-weight: 600; white-space: nowrap; }
        .producto { color: var(--tinta); }
        .desc { color: #3f8f1a; }
        .apagado { color: var(--gris-claro); }

        .cierre { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; margin-top: 18px; break-inside: avoid; }
        .aviso { max-width: 300px; margin: 0; color: var(--gris); font-size: 11px; }
        .totales { width: 280px; margin: 0; border: 1px solid var(--borde); border-radius: 10px; overflow: hidden; }
        .totales div { display: flex; justify-content: space-between; gap: 12px; padding: 8px 14px; }
        .totales dt { color: var(--gris); }
        .totales dd { margin: 0; font-weight: 600; font-variant-numeric: tabular-nums; }
        .totales .total { padding: 12px 14px; background: var(--tinta); color: #fff; }
        .totales .total dt { color: #fff; font: 700 12px/1.4 Montserrat, Arial, sans-serif; letter-spacing: 0.06em; text-transform: uppercase; }
        .totales .total dt small { display: block; color: #c4ccd7; font: 400 10px/1.3 Inter, Arial, sans-serif; letter-spacing: 0; text-transform: none; }
        .totales .total dd { font: 800 18px/1.2 Montserrat, Arial, sans-serif; }

        .pie {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 6px 18px;
            margin: 0 34px;
            padding: 14px 0 22px;
            border-top: 1px solid var(--borde);
            color: var(--gris);
            font-size: 10.5px;
        }
        .pie strong { color: var(--tinta); }

        /* La banda de la cabecera va de borde a borde en la primera hoja; las siguientes llevan margen arriba
           y todas el número de página abajo (pedidos largos). */
        @page {
            size: A4;
            margin: 12mm 0 14mm;

            @bottom-center {
                content: "Pedido N° {{ $numero }} · Página " counter(page) " de " counter(pages);
                color: #8792a2;
                font: 9px Inter, Arial, sans-serif;
            }
        }

        @page :first {
            margin-top: 0;
        }

        @media print {
            body { background: #fff; }
            .acciones { display: none !important; }
            .hoja { width: auto; min-height: 0; margin: 0; box-shadow: none; }
            thead { display: table-header-group; }
        }

        @media screen and (max-width: 820px) {
            .hoja { width: auto; min-height: 0; margin: 0; }
            .cabecera, .contenido { padding-inline: 18px; }
            .pie { margin-inline: 18px; }
            .resumen, .bloques { grid-template-columns: minmax(0, 1fr); }
            .resumen div { border-left: 0; border-top: 1px solid var(--borde); }
            .resumen div:first-child { border-top: 0; }
            .tabla-scroll { overflow-x: auto; }
            .cierre { flex-direction: column-reverse; }
            .totales { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="acciones">
        <button type="button" class="boton" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
            Descargar PDF
        </button>
        <p>En la ventana de impresión elegí «Guardar como PDF».</p>
    </div>

    <main class="hoja">
        <header class="cabecera">
            <img class="cabecera__logo" src="{{ $logo }}" alt="BMH Distribuidora">
            <div class="cabecera__doc">
                <p class="cabecera__tipo">Pedido</p>
                <p class="cabecera__numero">N° {{ $numero }}</p>
                <p class="cabecera__fecha">{{ $fechaTexto }}@if ($horaTexto) · {{ $horaTexto }} hs @endif</p>
            </div>
        </header>

        <div class="contenido">
            <dl class="resumen">
                <div><dt>Fecha</dt><dd>{{ $fechaTexto }}</dd></div>
                <div><dt>Estado</dt><dd>@if ($estado)<span class="estado">{{ $estado }}</span>@else — @endif</dd></div>
                <div><dt>Artículos</dt><dd>{{ $lineas->count() }} · {{ $unidades }} {{ $unidades === 1 ? 'unidad' : 'unidades' }}</dd></div>
                <div><dt>Total</dt><dd>$ {{ $pedido->total_pedido }}</dd></div>
            </dl>

            <div class="bloques">
                <section @class(['bloque', 'bloque--ancho' => $domicilio === [] && $entrega === []])>
                    <h2>Cliente</h2>
                    <dl class="datos">
                        @forelse ($cliente as $etiqueta => $dato)
                            <dt>{{ $etiqueta }}</dt><dd>{{ $dato }}</dd>
                        @empty
                            <dt>Datos</dt><dd class="apagado">Sin datos</dd>
                        @endforelse
                        @if ($descuentoCliente > 0)
                            <dt>Descuento</dt><dd>{{ $descuentoCliente + 0 }}%</dd>
                        @endif
                    </dl>
                </section>
                @if ($domicilio !== [])
                    <section class="bloque">
                        <h2>{{ $entrega !== [] ? 'Facturación' : 'Domicilio' }}</h2>
                        <dl class="datos">
                            @foreach ($domicilio as $etiqueta => $dato)
                                <dt>{{ $etiqueta }}</dt><dd>{{ $dato }}</dd>
                            @endforeach
                        </dl>
                    </section>
                @endif
                @if ($entrega !== [])
                    <section class="bloque">
                        <h2>Entrega</h2>
                        <dl class="datos">
                            @foreach ($entrega as $etiqueta => $dato)
                                <dt>{{ $etiqueta }}</dt><dd>{{ $dato }}</dd>
                            @endforeach
                        </dl>
                    </section>
                @endif
                @if ($valor($pedido->notas))
                    <section @class(['bloque', 'bloque--ancho' => $entrega === []])>
                        <h2>Observaciones</h2>
                        <p class="notas">{{ $valor($pedido->notas) }}</p>
                    </section>
                @endif
            </div>

            <h2 class="titulo-tabla">Productos <span>{{ $lineas->count() }} {{ $lineas->count() === 1 ? 'artículo' : 'artículos' }}</span></h2>
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Descripción</th>
                            <th scope="col" class="num">P. lista $</th>
                            <th scope="col" class="num">Desc.</th>
                            <th scope="col" class="num">P. neto $</th>
                            <th scope="col" class="num">Cant.</th>
                            <th scope="col" class="num">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lineas as $linea)
                            @php
                                // Descuento total del renglón: el del producto y el del cliente, combinados.
                                $descuento = $linea->precio_lista > 0 ? round((1 - $linea->precio_neto / $linea->precio_lista) * 100, 2) : 0;
                            @endphp
                            <tr>
                                <td class="codigo">{{ $linea->producto->codigo ?: '—' }}</td>
                                <td class="producto">{{ $linea->producto->nombre ?: '—' }}</td>
                                <td class="num">{{ $numero2($linea->precio_lista) }}</td>
                                <td @class(['num', 'desc' => $descuento > 0, 'apagado' => $descuento <= 0])>{{ $descuento > 0 ? '−'.rtrim(rtrim(number_format($descuento, 2, ',', '.'), '0'), ',').'%' : '—' }}</td>
                                <td class="num">{{ $numero2($linea->precio_neto) }}</td>
                                <td class="num">{{ $linea->cantidad }}</td>
                                <td class="num"><strong>{{ $moneda($linea->subtotal) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="cierre">
                <p class="aviso">Precios en pesos argentinos, con los descuentos vigentes al momento del pedido. Venta sujeta a disponibilidad de stock. Documento no válido como factura.</p>
                <dl class="totales">
                    <div><dt>Subtotal</dt><dd>$ {{ $pedido->subtotal_format() }}</dd></div>
                    <div><dt>IVA {{ $pedido->porcentajeIva() + 0 }}%</dt><dd>{{ $moneda($pedido->iva()) }}</dd></div>
                    <div class="total"><dt>Total<small>IVA incluido</small></dt><dd>$ {{ $pedido->total_pedido }}</dd></div>
                </dl>
            </div>
        </div>

        <footer class="pie">
            <span><strong>BMH Distribuidora</strong> · {{ $valor($empresa?->direccion) ?? 'Constantino Gaito 2874, San Justo, Buenos Aires' }}</span>
            <span>
                @if ($valor($empresa?->tel)){{ $empresa->tel }}@endif
                @if ($valor($empresa?->tel) && $valor($empresa?->mail)) · @endif
                @if ($valor($empresa?->mail)){{ $empresa->mail }}@endif
            </span>
        </footer>
    </main>

    @if (request()->boolean('imprimir'))
        <script>window.addEventListener('load', () => window.print(), { once: true });</script>
    @endif
</body>
</html>
