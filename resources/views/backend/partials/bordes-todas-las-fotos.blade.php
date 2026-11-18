{{--
    Biblioteca de imágenes › Emparejar bordes: el margen (productos y categorías, AjustesBordes) y emparejar todas las
    fotos de productos, volver a las originales o rehacerlas con el margen nuevo. Se hace de a tandas
    (BordesFotosController@lote) con una barra de avance; se puede detener y seguir después: las hechas no se repiten.
    Las fotos que no se pueden emparejar (fondo de escena, el producto ya llena la foto) se listan.

    $bordes: BordesFotosController::resumen()
--}}
@php
    $m = $bordes['margenes'];
    $lim = $bordes['limites'];
    $def = $bordes['defecto'];
@endphp
<section class="bi-card bfo" id="bordes" aria-labelledby="bfo-titulo" data-bfo
    data-url="{{ route('dashboard.imagenes.bordes') }}" data-csrf="{{ csrf_token() }}"
    data-total="{{ $bordes['total'] }}" data-emparejadas="{{ $bordes['emparejadas'] }}" data-otro-margen="{{ $bordes['con_otro_margen'] }}">
    <div class="bi-card-cuerpo bfo__cuerpo">
        <div class="bfo__texto">
            <h2 class="bi-card-titulo" id="bfo-titulo">Emparejar bordes de las fotos</h2>
            <p class="bi-card-sub">El producto queda centrado y del mismo tamaño en todas las fotos, aunque cada una tenga distinto margen. Las originales quedan guardadas: se puede volver a ellas cuando quieras.</p>
            <p class="bfo__estado" data-bfo-estado></p>
        </div>
        <div class="bfo__acciones">
            <button type="button" class="bi-btn bi-btn-primario" data-bfo-accion="emparejar">
                <i class="fa-solid fa-border-all" aria-hidden="true"></i> Emparejar todas
            </button>
            <button type="button" class="bi-btn" data-bfo-accion="rehacer">
                <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Rehacer con el margen nuevo
            </button>
            <button type="button" class="bi-btn" data-bfo-accion="original">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Volver todas a las originales
            </button>
        </div>
    </div>

    <div class="bfo__avance" data-bfo-avance hidden>
        <div class="bi-progreso" aria-hidden="true"><span data-bfo-barra></span></div>
        <div class="bfo__avance-fila">
            <p class="bfo__avance-texto" data-bfo-texto aria-live="polite"></p>
            <button type="button" class="bi-btn" data-bfo-detener>Detener</button>
        </div>
        <details class="bfo__salteadas" data-bfo-salteadas hidden>
            <summary></summary>
            <ul></ul>
        </details>
    </div>

    {{-- El margen: cuánto aire queda alrededor del producto. --}}
    <form method="POST" action="{{ route('dashboard.imagenes.bordes.margen') }}" class="bfo__margen" data-bfo-margen>
        @csrf
        @method('PUT')
        <h3 class="bfo__margen-titulo">Margen alrededor del producto</h3>
        <div class="bfo__margenes">
            <div class="bfo__control">
                <div class="bfo__muestra bfo__muestra--producto" aria-hidden="true"><span data-bfo-caja="producto"></span></div>
                <div>
                    <label for="bfo-producto" class="bfo__rotulo">Fotos de productos</label>
                    <div class="bfo__valor"><input type="range" id="bfo-producto" name="producto" min="{{ $lim['producto'][0] }}" max="{{ $lim['producto'][1] }}" value="{{ $m['producto'] }}" data-bfo-rango="producto"><output data-bfo-salida="producto">{{ $m['producto'] }}%</output></div>
                    <small>De fábrica: {{ $def['producto'] }}% de cada lado. Más margen, producto más chico.</small>
                </div>
            </div>
            <div class="bfo__control">
                <div class="bfo__muestra bfo__muestra--categoria" aria-hidden="true"><span data-bfo-caja="categoria"></span></div>
                <div>
                    <label for="bfo-categoria" class="bfo__rotulo">Fotos de categorías</label>
                    <div class="bfo__valor"><input type="range" id="bfo-categoria" name="categoria" min="{{ $lim['categoria'][0] }}" max="{{ $lim['categoria'][1] }}" value="{{ $m['categoria'] }}" data-bfo-rango="categoria"><output data-bfo-salida="categoria">{{ $m['categoria'] }}%</output></div>
                    <small>De fábrica: {{ $def['categoria'] }}%. Es mayor porque la tarjeta de la categoría recorta la foto arriba y abajo.</small>
                </div>
            </div>
        </div>
        <div class="bfo__margen-pie">
            <button type="button" class="bi-btn" data-bfo-defecto data-producto="{{ $def['producto'] }}" data-categoria="{{ $def['categoria'] }}">Volver a los de fábrica</button>
            <button type="submit" class="bi-btn bi-btn-primario">Guardar margen</button>
        </div>
        <p class="bfo__margen-nota">Se usa en las fotos que se emparejen desde que lo guardás. Las ya emparejadas se actualizan con «Rehacer con el margen nuevo» (las de categorías, desde Categorías).</p>
    </form>
</section>

<style>
    .bfo__cuerpo { display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap; }
    .bfo__texto { flex: 1 1 28rem; }
    .bfo__texto .bi-card-sub { max-width: 70ch; }
    .bfo__estado { margin: .5rem 0 0; font-size: .875rem; color: var(--bi-texto-2, #5F6B75); }
    .bfo__acciones { display: flex; gap: .5rem; flex-wrap: wrap; }
    .bfo__acciones .bi-btn[hidden] { display: none; }
    .bfo__acciones .bi-btn[disabled] { opacity: .5; cursor: not-allowed; }
    .bfo__avance { padding: 0 1.5rem 1.25rem; }
    .bfo__avance[hidden] { display: none; }
    .bfo__avance-fila { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: .625rem; }
    .bfo__avance-texto { margin: 0; font-size: .875rem; }
    .bfo__salteadas { margin-top: .75rem; font-size: .82rem; color: var(--bi-texto-2, #5F6B75); }
    .bfo__salteadas summary { cursor: pointer; font-weight: 600; color: #B54708; }
    .bfo__salteadas ul { margin: .5rem 0 0; padding-left: 1.1rem; max-height: 14rem; overflow: auto; }

    .bfo__margen { margin: 0; padding: 1.125rem 1.5rem 1.25rem; border-top: 1px solid var(--bi-borde-suave, #EEF1F3); }
    .bfo__margen-titulo { margin: 0 0 .875rem; font-size: .95rem; font-weight: 700; color: var(--bi-texto, #1D2430); }
    .bfo__margenes { display: grid; grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr)); gap: 1.25rem 2rem; }
    .bfo__control { display: grid; grid-template-columns: 6.5rem 1fr; gap: 1rem; align-items: center; }
    .bfo__rotulo { font-size: .875rem; font-weight: 600; color: var(--bi-texto, #1D2430); }
    .bfo__valor { display: flex; align-items: center; gap: .625rem; margin: .25rem 0; }
    .bfo__valor input { flex: 1; accent-color: var(--bi-azul, #0098DA); }
    .bfo__valor output { min-width: 2.75rem; text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--bi-azul-oscuro, #00699A); }
    .bfo__control small { display: block; font-size: .76rem; color: var(--bi-texto-3, #8A96A0); line-height: 1.4; }
    /* Muestras: la foto (blanca) y hasta dónde llega el producto (el recuadro gris). */
    .bfo__muestra { position: relative; border: 1px solid var(--bi-borde, #DCE3E8); border-radius: 6px; background: #fff; overflow: hidden; }
    .bfo__muestra--producto { aspect-ratio: 1; }
    .bfo__muestra--categoria { aspect-ratio: 5 / 4; background: #F3F5F8; }
    .bfo__muestra span { position: absolute; border-radius: 4px; background: repeating-linear-gradient(135deg, #C9D3DB 0 6px, #D9E1E7 6px 12px); outline: 1px dashed var(--bi-azul, #0098DA); transition: inset .15s; }
    .bfo__margen-pie { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; }
    .bfo__margen-nota { margin: .625rem 0 0; font-size: .78rem; color: var(--bi-texto-3, #8A96A0); text-align: right; }
</style>

<script>
    (() => {
        const caja = document.querySelector('[data-bfo]:not([data-bfo-listo])');
        if (!caja) return;
        caja.dataset.bfoListo = '1';
        const url = caja.dataset.url, csrf = caja.dataset.csrf;
        let total = Number(caja.dataset.total), emparejadas = Number(caja.dataset.emparejadas), otroMargen = Number(caja.dataset.otroMargen);
        const boton = (accion) => caja.querySelector(`[data-bfo-accion="${accion}"]`);
        const botones = caja.querySelectorAll('[data-bfo-accion]');
        const avance = caja.querySelector('[data-bfo-avance]');
        const barra = caja.querySelector('[data-bfo-barra]');
        const texto = caja.querySelector('[data-bfo-texto]');
        const detener = caja.querySelector('[data-bfo-detener]');
        const estado = caja.querySelector('[data-bfo-estado]');
        const salteadas = caja.querySelector('[data-bfo-salteadas]');
        const numero = (n) => n.toLocaleString('es-AR');
        let corriendo = false, parar = false;

        const pintarEstado = () => {
            estado.innerHTML = `<strong>${numero(emparejadas)}</strong> de ${numero(total)} fotos con los bordes emparejados`
                + (otroMargen > 0 ? `. ${numero(otroMargen)} tienen un margen anterior.` : '.');
            boton('emparejar').disabled = corriendo || emparejadas >= total;
            boton('original').disabled = corriendo || emparejadas === 0;
            boton('rehacer').disabled = corriendo;
            boton('rehacer').hidden = otroMargen === 0;
        };

        const anotarSalteadas = (lista) => {
            if (!lista.length) return;
            const ul = salteadas.querySelector('ul');
            lista.forEach((s) => {
                const li = document.createElement('li');
                li.textContent = (s.productos.length ? s.productos.join(', ') : s.foto) + ': ' + s.motivo;
                ul.append(li);
            });
            const n = ul.children.length;
            salteadas.querySelector('summary').textContent = n === 1 ? '1 foto no se pudo emparejar (quedó como estaba)' : `${numero(n)} fotos no se pudieron emparejar (quedaron como estaban)`;
            salteadas.hidden = false;
        };

        const TEXTOS = {
            emparejar: { haciendo: 'Emparejando', listo: 'fotos con los bordes emparejados', detenido: 'emparejadas' },
            original: { haciendo: 'Volviendo a las originales', listo: 'fotos volvieron a la original', detenido: 'volvieron a la original' },
            rehacer: { haciendo: 'Rehaciendo con el margen nuevo', listo: 'fotos rehechas con el margen nuevo', detenido: 'rehechas' },
        };

        const correr = async (accion) => {
            corriendo = true; parar = false;
            botones.forEach((b) => { b.disabled = true; });
            avance.hidden = false;
            detener.hidden = false;
            salteadas.hidden = true;
            salteadas.querySelector('ul').replaceChildren();
            const t = TEXTOS[accion];
            const aProcesar = accion === 'original' ? emparejadas : accion === 'rehacer' ? otroMargen : total - emparejadas;
            let hechas = 0, vistas = 0, despuesDe = null;
            window.addEventListener('beforeunload', avisarAlSalir);
            try {
                while (!parar) {
                    texto.textContent = `${t.haciendo}… ${numero(vistas)} de ${numero(aProcesar)} (se puede seguir usando el panel en otra pestaña)`;
                    const respuesta = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ accion, despues_de: despuesDe }),
                    });
                    if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
                    const r = await respuesta.json();
                    hechas += r.hechas;
                    vistas += r.hechas + r.salteadas.length;
                    if (accion === 'emparejar') emparejadas += r.hechas;
                    if (accion === 'original') { emparejadas -= r.hechas; otroMargen = Math.max(0, otroMargen - r.hechas); }
                    if (accion === 'rehacer') otroMargen = Math.max(0, otroMargen - r.hechas);
                    anotarSalteadas(r.salteadas);
                    barra.style.width = Math.min(100, aProcesar ? vistas / aProcesar * 100 : 100) + '%';
                    pintarEstado();
                    despuesDe = r.despues_de;
                    if (r.terminado) break;
                }
                texto.textContent = parar
                    ? `Detenido: ${numero(hechas)} ${t.detenido}. Podés seguir cuando quieras: las hechas no se repiten.`
                    : `Listo: ${numero(hechas)} ${t.listo}.`;
            } catch (e) {
                texto.textContent = `Se cortó (${e.message}). Lo hecho quedó guardado: tocá el botón de nuevo para seguir desde donde quedó.`;
            } finally {
                corriendo = false;
                detener.hidden = true;
                window.removeEventListener('beforeunload', avisarAlSalir);
                pintarEstado();
            }
        };

        function avisarAlSalir(ev) { if (corriendo) { ev.preventDefault(); ev.returnValue = ''; } }

        botones.forEach((b) => b.addEventListener('click', () => { if (!corriendo) correr(b.dataset.bfoAccion); }));
        detener.addEventListener('click', () => { parar = true; detener.hidden = true; texto.textContent = 'Deteniendo después de esta tanda…'; });
        pintarEstado();

        // Margen: las muestras muestran hasta dónde llega el producto con el valor elegido.
        const form = caja.querySelector('[data-bfo-margen]');
        const CATEGORIA_DEFECTO = {{ (int) $def['categoria'] }};
        const muestra = (perfil) => {
            const v = Number(form.querySelector(`[data-bfo-rango="${perfil}"]`).value);
            form.querySelector(`[data-bfo-salida="${perfil}"]`).textContent = v + '%';
            const s = form.querySelector(`[data-bfo-caja="${perfil}"]`);
            if (perfil === 'producto') {
                s.style.inset = `${v}%`;
            } else {
                // Igual que el servidor: ancho 68% y alto 54% (de la foto cuadrada) con 16% de margen, a escala.
                const f = (100 - 2 * v) / (100 - 2 * CATEGORIA_DEFECTO);
                const ancho = 68 * f, alto = 54 * f / 0.8; // la tarjeta muestra el 80% del alto de la foto
                s.style.inset = `${(100 - alto) / 2}% ${(100 - ancho) / 2}%`;
            }
        };
        ['producto', 'categoria'].forEach((perfil) => {
            form.querySelector(`[data-bfo-rango="${perfil}"]`).addEventListener('input', () => muestra(perfil));
            muestra(perfil);
        });
        form.querySelector('[data-bfo-defecto]').addEventListener('click', (ev) => {
            ['producto', 'categoria'].forEach((perfil) => { form.querySelector(`[data-bfo-rango="${perfil}"]`).value = ev.currentTarget.dataset[perfil]; muestra(perfil); });
        });
    })();
</script>
