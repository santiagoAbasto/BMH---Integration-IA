{{--
    Biblioteca de imágenes › Emparejar bordes de todas las fotos de productos (o volver a las originales). Se hace de a
    tandas (BordesFotosController@lote) con una barra de avance; se puede detener y seguir después: las que ya están
    hechas no se repiten. Las fotos que no se pueden emparejar (fondo de escena, el producto ya llena la foto) se listan.

    $bordes: ['total' => int, 'emparejadas' => int]
--}}
<section class="bi-card bfo" aria-labelledby="bfo-titulo" data-bfo
    data-url="{{ route('dashboard.imagenes.bordes') }}" data-csrf="{{ csrf_token() }}"
    data-total="{{ $bordes['total'] }}" data-emparejadas="{{ $bordes['emparejadas'] }}">
    <div class="bi-card-cuerpo bfo__cuerpo">
        <div class="bfo__texto">
            <h2 class="bi-card-titulo" id="bfo-titulo">Emparejar bordes de las fotos</h2>
            <p class="bi-card-sub">El producto queda centrado y del mismo tamaño en todas las fotos, aunque cada una tenga distinto margen. Las originales quedan guardadas: se puede volver a ellas cuando quieras.</p>
            <p class="bfo__estado" data-bfo-estado><strong>{{ number_format($bordes['emparejadas'], 0, ',', '.') }}</strong> de {{ number_format($bordes['total'], 0, ',', '.') }} fotos con los bordes emparejados.</p>
        </div>
        <div class="bfo__acciones">
            <button type="button" class="bi-btn bi-btn-primario" data-bfo-accion="emparejar" @disabled($bordes['emparejadas'] >= $bordes['total'])>
                <i class="fa-solid fa-border-all" aria-hidden="true"></i> Emparejar todas
            </button>
            <button type="button" class="bi-btn" data-bfo-accion="original" @disabled($bordes['emparejadas'] === 0)>
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
</section>

<style>
    .bfo__cuerpo { display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap; }
    .bfo__texto { flex: 1 1 28rem; }
    .bfo__texto .bi-card-sub { max-width: 70ch; }
    .bfo__estado { margin: .5rem 0 0; font-size: .875rem; color: var(--bi-texto-2, #5F6B75); }
    .bfo__acciones { display: flex; gap: .5rem; flex-wrap: wrap; }
    .bfo__acciones .bi-btn[disabled] { opacity: .5; cursor: not-allowed; }
    .bfo__avance { padding: 0 1.5rem 1.25rem; }
    .bfo__avance[hidden] { display: none; }
    .bfo__avance-fila { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: .625rem; }
    .bfo__avance-texto { margin: 0; font-size: .875rem; }
    .bfo__salteadas { margin-top: .75rem; font-size: .82rem; color: var(--bi-texto-2, #5F6B75); }
    .bfo__salteadas summary { cursor: pointer; font-weight: 600; color: #B54708; }
    .bfo__salteadas ul { margin: .5rem 0 0; padding-left: 1.1rem; max-height: 14rem; overflow: auto; }
</style>

<script>
    (() => {
        const caja = document.querySelector('[data-bfo]:not([data-bfo-listo])');
        if (!caja) return;
        caja.dataset.bfoListo = '1';
        const url = caja.dataset.url, csrf = caja.dataset.csrf;
        let total = Number(caja.dataset.total), emparejadas = Number(caja.dataset.emparejadas);
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
            estado.innerHTML = `<strong>${numero(emparejadas)}</strong> de ${numero(total)} fotos con los bordes emparejados.`;
            botones[0].disabled = corriendo || emparejadas >= total;
            botones[1].disabled = corriendo || emparejadas === 0;
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

        const correr = async (accion) => {
            corriendo = true; parar = false;
            botones.forEach((b) => { b.disabled = true; });
            avance.hidden = false;
            detener.hidden = false;
            salteadas.hidden = true;
            salteadas.querySelector('ul').replaceChildren();
            const volver = accion === 'original';
            const aProcesar = volver ? emparejadas : total - emparejadas;
            let hechas = 0, vistas = 0, despuesDe = null;
            window.addEventListener('beforeunload', avisarAlSalir);
            try {
                while (!parar) {
                    texto.textContent = volver ? `Volviendo a las originales… ${numero(vistas)} de ${numero(aProcesar)}` : `Emparejando… ${numero(vistas)} de ${numero(aProcesar)} (se puede seguir usando el panel en otra pestaña)`;
                    const respuesta = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ accion, despues_de: despuesDe }),
                    });
                    if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
                    const r = await respuesta.json();
                    hechas += r.hechas;
                    vistas += r.hechas + r.salteadas.length;
                    emparejadas += volver ? -r.hechas : r.hechas;
                    anotarSalteadas(r.salteadas);
                    barra.style.width = Math.min(100, aProcesar ? vistas / aProcesar * 100 : 100) + '%';
                    pintarEstado();
                    despuesDe = r.despues_de;
                    if (r.terminado) break;
                }
                texto.textContent = parar
                    ? `Detenido: ${numero(hechas)} ${volver ? 'volvieron a la original' : 'emparejadas'}. Podés seguir cuando quieras: las hechas no se repiten.`
                    : (volver ? `Listo: ${numero(hechas)} fotos volvieron a la original.` : `Listo: ${numero(hechas)} fotos con los bordes emparejados.`);
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
    })();
</script>
