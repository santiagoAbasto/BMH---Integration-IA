{{--
    Casilla «Emparejar bordes» para las fotos de productos que se suben (ficha del producto, crear producto, biblioteca).
    Va como emparejar_bordes (1 / 0). Recuerda la última elección en este navegador. Ver App\Services\Imagenes\EmparejarBordes.

    $id: id único de la casilla.
--}}
<div class="form-check ebc">
    <input type="hidden" name="emparejar_bordes" value="0">
    <input class="form-check-input" type="checkbox" name="emparejar_bordes" value="1" id="{{ $id }}" checked data-emparejar-bordes>
    <label class="form-check-label" for="{{ $id }}">
        <strong>Emparejar bordes</strong> (recomendado)
        <small>El producto queda centrado y del mismo tamaño en todas las fotos, aunque vengan con más o menos margen. La original queda guardada: se puede volver a ella.</small>
    </label>
</div>
@once
    <style>
        .ebc { margin: .625rem 0 0; padding: .625rem .75rem .625rem 2.25rem; border: 1px solid #E3E8EE; border-radius: 8px; background: #fff; }
        .ebc .form-check-input { margin-top: .2rem; }
        .ebc label { font-size: .875rem; line-height: 1.4; }
        .ebc small { display: block; margin-top: .125rem; color: #667085; font-size: .78rem; }
    </style>
    <script>
        (() => {
            const clave = 'bmh.emparejarBordes';
            let guardado = null;
            try { guardado = localStorage.getItem(clave); } catch {}
            document.querySelectorAll('[data-emparejar-bordes]').forEach((casilla) => {
                if (guardado !== null) casilla.checked = guardado === '1';
                casilla.addEventListener('change', () => { try { localStorage.setItem(clave, casilla.checked ? '1' : '0'); } catch {} });
            });
        })();
    </script>
@endonce
