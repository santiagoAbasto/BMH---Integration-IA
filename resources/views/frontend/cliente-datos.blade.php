@extends('layouts.plantilla-front')

@section('styles')
<style>
    :root{
        --bmh-blue:#0098DA;
        --bmh-blue-d:#0079b0;
        --bmh-ink:#1f2933;
        --bmh-muted:#6b7785;
        --bmh-line:#e7ebef;
    }

    .bmh-page-head{ margin-bottom:28px; }
    .bmh-page-head h1{ font-family:'Montserrat'; font-weight:700; color:var(--bmh-ink); font-size:28px; margin:0 0 4px; }
    .bmh-page-head p{ color:var(--bmh-muted); font-family:'Montserrat'; font-size:14px; margin:0; }

    .bmh-card{
        background:#fff; border:1px solid var(--bmh-line); border-radius:16px;
        padding:26px 28px; margin-bottom:24px; box-shadow:0 1px 3px rgba(16,24,40,.04);
    }
    .bmh-card__head{
        display:flex; align-items:center; gap:14px; padding-bottom:18px;
        margin-bottom:20px; border-bottom:1px solid var(--bmh-line);
    }
    .bmh-card__icon{
        width:44px; height:44px; border-radius:12px; flex-shrink:0;
        background:rgba(0,152,218,.10); color:var(--bmh-blue);
        display:flex; align-items:center; justify-content:center;
    }
    .bmh-card__head h2{ font-family:'Montserrat'; font-weight:600; font-size:18px; color:var(--bmh-ink); margin:0; }
    .bmh-card__head p{ font-family:'Montserrat'; font-size:13px; color:var(--bmh-muted); margin:2px 0 0; }

    .bmh-grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px 18px; }
    @media(min-width:900px){ .bmh-grid{ grid-template-columns:repeat(3,1fr); } }
    .bmh-field{ display:flex; flex-direction:column; gap:6px; }
    .bmh-field label{
        font-family:'Montserrat'; font-size:12px; font-weight:600; color:var(--bmh-muted);
        text-transform:uppercase; letter-spacing:.03em;
    }
    .bmh-field input{
        height:44px; border:1px solid var(--bmh-line); border-radius:8px; padding:0 12px;
        font-family:'Montserrat'; font-size:14px; color:var(--bmh-ink); background:#fff;
        transition:border-color .15s, box-shadow .15s;
    }
    .bmh-field input:focus{ outline:none; border-color:var(--bmh-blue); box-shadow:0 0 0 3px rgba(0,152,218,.12); }

    .bmh-actions{ display:flex; justify-content:flex-end; margin-top:22px; }
    .btn-guardar{
        display:flex; width:392px; max-width:100%; height:39px; padding:10px 32px;
        justify-content:center; align-items:center; gap:10px; flex-shrink:0;
        border-radius:10px; background:#0098DA; color:#FFF; font-family:Montserrat;
        font-size:16px; border:none; cursor:pointer; transition:background .15s, color .15s;
    }
    .btn-guardar:hover{ background:#fff; color:#0098DA; border:1px solid #0098DA; }

    /* ---- Tarjeta de márgenes (separada) ---- */
    .bmh-card--margin{ border-color:#cfeaf7; }
    .bmh-card--margin .bmh-card__head{ border-bottom-color:#e3f2fa; }
    .bmh-card__head--accent .bmh-card__icon{
        background:linear-gradient(135deg,#0098DA,#00b4d8); color:#fff;
    }

    .bmh-subsection{ margin-bottom:24px; }
    .bmh-subsection:last-of-type{ margin-bottom:0; }
    .bmh-subsection__bar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .bmh-bar-actions{ display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
    .bmh-subtitle{
        display:flex; align-items:center; gap:9px; font-family:'Montserrat';
        font-weight:600; font-size:15px; color:var(--bmh-ink); margin:0;
    }
    .bmh-dot{ width:9px; height:9px; border-radius:50%; background:var(--bmh-blue); box-shadow:0 0 0 4px rgba(0,152,218,.15); }
    .bmh-link{
        border:none; background:none; color:var(--bmh-blue); font-family:'Montserrat';
        font-size:12.5px; font-weight:600; cursor:pointer; padding:4px 8px; border-radius:6px;
        transition:background .15s;
    }
    .bmh-link:hover{ background:rgba(0,152,218,.08); }
    .bmh-hint{ font-family:'Montserrat'; font-size:12px; color:#9aa6b2; margin:10px 0 0; }

    .bmh-general{ display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap; }
    .bmh-general .bmh-input-suffix{ width:200px; }

    .bmh-input-suffix{ position:relative; display:flex; align-items:center; }
    .bmh-input-suffix input{
        width:100%; height:44px; border:1px solid var(--bmh-line); border-radius:8px;
        padding:0 38px 0 12px; font-family:'Montserrat'; font-size:14px; color:var(--bmh-ink);
        background:#fff; transition:border-color .15s, box-shadow .15s;
    }
    .bmh-input-suffix input:focus{ outline:none; border-color:var(--bmh-blue); box-shadow:0 0 0 3px rgba(0,152,218,.12); }
    .bmh-input-suffix--sm input{ height:36px; font-size:13px; padding-right:28px; }
    .bmh-suffix{
        position:absolute; right:14px; color:var(--bmh-muted); font-family:'Montserrat';
        font-weight:600; font-size:14px; pointer-events:none;
    }
    .bmh-input-suffix--sm .bmh-suffix{ right:10px; font-size:13px; }

    /* Lista de categorías: scroll si hay muchas */
    .bmh-cats{
        display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px 14px;
        max-height:430px; overflow-y:auto; padding:4px 6px 4px 2px;
    }
    @media(min-width:1100px){ .bmh-cats{ grid-template-columns:repeat(3,1fr); } }
    .bmh-cats::-webkit-scrollbar{ width:8px; }
    .bmh-cats::-webkit-scrollbar-thumb{ background:#cdd7df; border-radius:8px; }
    .bmh-cats::-webkit-scrollbar-thumb:hover{ background:#b6c2cc; }

    .bmh-cat{
        display:flex; align-items:center; justify-content:space-between; gap:8px;
        min-width:0; padding:6px 10px; border:1px solid var(--bmh-line); border-radius:10px;
        background:#fcfdfe; transition:border-color .15s, background .15s, transform .12s;
    }
    .bmh-cat:hover{ border-color:#bfe0f1; background:#f4fafe; transform:translateY(-1px); }
    .bmh-cat__name{
        font-family:'Montserrat'; font-size:12.5px; font-weight:500; color:var(--bmh-ink);
        min-width:0; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .bmh-cat .bmh-input-suffix{ width:88px; flex-shrink:0; }
    .bmh-cat.is-specific{ border-color:#bfe0f1; background:#eef8fe; }

    .alert-exito{
        background:#e7f7ec; border:1px solid #b7e4c7; color:#1d7a3a;
        font-family:'Montserrat'; font-size:13.5px; border-radius:10px;
        padding:12px 16px; margin-bottom:22px;
    }
</style>
@endsection


@section('content')
<section style="padding-top: 50px; padding-bottom: 120px">
    <div class="container">

        <div class="bmh-page-head">
            <h1>Mis datos</h1>
            <p>Gestioná tu información y el margen de reventa de tus productos.</p>
        </div>

        @if (session('success'))
            <div class="alert-exito">{{ session('success') }}</div>
        @endif

        {{-- ===================== DATOS PERSONALES ===================== --}}
        <div class="bmh-card">
            <div class="bmh-card__head">
                <span class="bmh-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <div>
                    <h2>Datos personales</h2>
                    <p>Tu información de contacto y entrega.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('clienteD.update') }}">
                @csrf
                <input type="text" name="cliente_id" value="{{ $usuario->id }}" style="display: none">

                <div class="bmh-grid">
                    <div class="bmh-field">
                        <label for="">Dirección</label>
                        <input type="text" name="direccionEntrega" placeholder="Dirección de entrega" value="{{ $usuario->direccion }}">
                    </div>
                    <div class="bmh-field">
                        <label for="">Localidad</label>
                        <input type="text" name="localidadEntregar" placeholder="Localidad de entrega" value="{{ $usuario->localidad }}">
                    </div>
                    <div class="bmh-field">
                        <label for="">Celular</label>
                        <input type="text" name="telefono" placeholder="Teléfono" value="{{ $usuario->celular }}">
                    </div>
                    <div class="bmh-field">
                        <label for="">Email</label>
                        <input type="text" name="mail" placeholder="Mail" value="{{ $usuario->email }}">
                    </div>
                    <div class="bmh-field">
                        <label for="">Transporte</label>
                        <input type="text" name="transporte" placeholder="Transporte" value="{{ $usuario->transporte }}">
                    </div>
                </div>

                <div class="bmh-actions">
                    <button type="submit" class="btn-guardar">Guardar datos</button>
                </div>
            </form>
        </div>

        {{-- ===================== MARGEN DE REVENTA (separado) ===================== --}}
        <div class="bmh-card bmh-card--margin">
            <div class="bmh-card__head bmh-card__head--accent">
                <span class="bmh-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
                <div>
                    <h2>Margen de reventa</h2>
                    <p>El margen por categoría tiene prioridad sobre el general.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('reventa.margenes') }}">
                @csrf

                {{-- Margen general --}}
                <div class="bmh-subsection">
                    <h3 class="bmh-subtitle"><span class="bmh-dot"></span>Margen general</h3>
                    <div class="bmh-general">
                        <div class="bmh-input-suffix">
                            <input type="number" step="0.01" min="0" name="incrementoReventa" id="margenGeneral" value="{{ $usuario->reventa }}" placeholder="0">
                            <span class="bmh-suffix">%</span>
                        </div>
                        <p class="bmh-hint">Se aplica a todas las categorías, salvo que definas uno específico debajo.</p>
                    </div>
                </div>

                {{-- Margen por categoría --}}
                <div class="bmh-subsection">
                    <div class="bmh-subsection__bar">
                        <h3 class="bmh-subtitle"><span class="bmh-dot"></span>Margen por categorías</h3>
                        <div class="bmh-bar-actions">
                            <button type="button" class="bmh-link" id="copiarGeneralVacias">Aplicar general a las vacías</button>
                            <button type="button" class="bmh-link" id="copiarGeneral">Aplicar el general a todas</button>
                        </div>
                    </div>

                    <div class="bmh-cats" id="catsWrap">
                        @foreach ($categorias as $categoria)
                            @php($m = $margenes->get($categoria->id))
                            <label class="bmh-cat @if($m) is-specific @endif">
                                <span class="bmh-cat__name" title="{{ $categoria->nombre }}">{{ $categoria->nombre }}</span>
                                <span class="bmh-input-suffix bmh-input-suffix--sm">
                                    <input type="number" step="0.01" min="0" name="margen_categoria[{{ $categoria->id }}]" value="{{ $m ? $m->porcentaje : '' }}" placeholder="General" class="cat-input">
                                    <span class="bmh-suffix">%</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="bmh-hint">Dejá el campo vacío para usar el margen general. Hay {{ $categorias->count() }} categorías.</p>
                </div>

                <div class="bmh-actions">
                    <button type="submit" class="btn-guardar">Guardar márgenes</button>
                </div>
            </form>
        </div>

    </div>
</section>
@endsection

@section('script')
<script>
    (function () {
        var general = document.getElementById('margenGeneral');
        var inputs = Array.prototype.slice.call(document.querySelectorAll('.cat-input'));

        // Marca visualmente las categorías con margen propio.
        function marcarEspecificos() {
            inputs.forEach(function (inp) {
                var label = inp.closest('.bmh-cat');
                if (!label) return;
                if (inp.value.trim() !== '') label.classList.add('is-specific');
                else label.classList.remove('is-specific');
            });
        }

        // Placeholder dinámico: "General: X%".
        function actualizarPlaceholders() {
            var g = general && general.value.trim() !== '' ? general.value.trim() : '0';
            inputs.forEach(function (inp) { inp.placeholder = 'General: ' + g + '%'; });
        }

        marcarEspecificos();
        actualizarPlaceholders();

        inputs.forEach(function (inp) {
            inp.addEventListener('input', marcarEspecificos);
        });
        if (general) {
            general.addEventListener('input', actualizarPlaceholders);
        }

        var btnCopiar = document.getElementById('copiarGeneral');
        if (btnCopiar && general) {
            btnCopiar.addEventListener('click', function () {
                var g = general.value.trim();
                inputs.forEach(function (inp) { inp.value = g; });
                marcarEspecificos();
            });
        }

        var btnVacias = document.getElementById('copiarGeneralVacias');
        if (btnVacias && general) {
            btnVacias.addEventListener('click', function () {
                var g = general.value.trim();
                inputs.forEach(function (inp) {
                    if (inp.value.trim() === '') inp.value = g;
                });
                marcarEspecificos();
            });
        }

        // Provincias / localidades (si el formulario las incluye).
        var selProv = document.getElementById('select-provincia');
        if (selProv) {
            selProv.addEventListener('change', function () {
                var provinciaId = this.value;
                var url = 'https://apis.datos.gob.ar/georef/api/localidades?provincia=' + provinciaId + '&orden=nombre&max=1000';
                $.ajax({
                    url: url, type: 'GET',
                    success: function (data) {
                        var select2 = $('#localidades');
                        select2.empty();
                        $.each(data.localidades, function (index, localidad) {
                            select2.append($('<option>', { value: localidad.nombre, text: localidad.nombre }));
                        });
                    },
                    error: function (xhr, status, error) { console.error(error); }
                });
            });
        }

        var cambio = $('.cambio-contr');
        if (cambio.length) {
            cambio.on('click', function () {
                var id = $(this).data('id');
                if ($(this).is(':checked')) {
                    document.querySelectorAll('.contr' + id).forEach(function (element) { element.disabled = false; });
                } else {
                    document.querySelectorAll('.contr' + id).forEach(function (element) { element.disabled = true; });
                }
            });
        }
    })();
</script>
@endsection
