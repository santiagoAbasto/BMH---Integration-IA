{{--
    Grilla de categorías (Figma: «Productos – Categorías»): un filtro por nombre arriba y las
    tarjetas (osole::category-card.v1), 5 por fila. La usan /categorias y la Zona de Clientes.
    $categorias: colección de Categoria.
--}}
<div class="bmh-categorias" data-filtro-categorias>
    <div class="bmh-filtro">
        <label for="bmh-filtro-categorias" class="visually-hidden">Filtrar categorías</label>
        <span class="bmh-filtro__lupa" aria-hidden="true">{{ \App\Support\IconoSitio::svg('lupa-chica') }}</span>
        <input id="bmh-filtro-categorias" class="bmh-filtro__input" type="search" placeholder="Filtrar categorías…" autocomplete="off" data-filtro-input>
    </div>

    <ul class="bmh-grilla-rubros categorias-grilla" role="list">
        @foreach ($categorias as $categoria)
            <li data-nombre="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($categoria->nombre)) }}">
                <x-osole::category-card.v1
                    :name="$categoria->nombre"
                    :href="route('productos', ['categoria' => $categoria->id])"
                    :image="$categoria->portada ? asset('imagenes/'.$categoria->portada) : null"
                    :heading-level="2"
                />
            </li>
        @endforeach
    </ul>

    <p class="bmh-filtro__vacio" data-filtro-vacio role="status" hidden>Ninguna categoría coincide con la búsqueda.</p>
</div>

<script>
    (function () {
        var raiz = document.querySelector('[data-filtro-categorias]');
        if (!raiz) return;
        var input = raiz.querySelector('[data-filtro-input]');
        var items = Array.prototype.slice.call(raiz.querySelectorAll('li[data-nombre]'));
        var vacio = raiz.querySelector('[data-filtro-vacio]');
        // Sin tildes ni mayúsculas, como el data-nombre.
        var normalizar = function (texto) { return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); };
        input.addEventListener('input', function () {
            var texto = normalizar(input.value);
            var visibles = 0;
            items.forEach(function (item) {
                var ve = texto === '' || item.dataset.nombre.indexOf(texto) !== -1;
                item.hidden = !ve;
                if (ve) visibles++;
            });
            vacio.hidden = visibles > 0;
        });
    })();
</script>
