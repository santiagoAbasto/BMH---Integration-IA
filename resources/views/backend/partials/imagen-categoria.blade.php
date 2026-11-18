{{--
    Imagen de la tarjeta de una categoría (crear y editar). Dice la medida, muestra la tarjeta como en el sitio al
    lado de otra categoría (la vecina en el orden) y, si el producto ocupa demasiado del ancho, deja achicarlo dentro
    de la imagen sin editar la foto: se agrega fondo alrededor en el navegador antes de subirla.

    $uid: único en la página. $actual: URL de la imagen guardada (null al crear). $nombre: nombre de la categoría.
    $vecina: ['nombre' => ..., 'imagen' => URL] o null. $requerida: al crear.
    Los estilos y el script: backend.partials.imagen-categoria-recursos (una vez por página).
--}}
<div class="cimg" data-cimg>
    <div class="cimg__campo">
        <label for="portada-{{ $uid }}" class="form-label cimg__rotulo">Imagen de la tarjeta</label>
        <p class="cimg__medida"><strong>Cuadrada, de 1080 × 1080 px</strong> (mínimo 800 px). JPG, PNG o WebP.</p>
        <ul class="cimg__consejos">
            <li>El producto centrado, ocupando <strong>unos dos tercios del ancho</strong>, con aire a los costados. Así queda del mismo tamaño que en las demás categorías.</li>
            <li>La tarjeta recorta un poco arriba y abajo: que no quede nada importante en esos bordes.</li>
            <li>Fondo claro y liso, como el de las otras fotos.</li>
        </ul>

        <input class="form-control" type="file" id="portada-{{ $uid }}" name="portada" accept="image/jpeg,image/png,image/webp"
            data-cimg-input aria-describedby="cimg-chequeos-{{ $uid }}" @if ($requerida ?? false) required @endif>

        <div class="cimg__chequeos" id="cimg-chequeos-{{ $uid }}" data-cimg-chequeos aria-live="polite"></div>

        <div class="cimg__ajuste" data-cimg-ajuste hidden>
            <div class="cimg__ajuste-fila">
                <label for="cimg-escala-{{ $uid }}">Tamaño del producto</label>
                <output for="cimg-escala-{{ $uid }}" data-cimg-escala-valor>100%</output>
            </div>
            <input type="range" class="form-range" id="cimg-escala-{{ $uid }}" min="50" max="100" step="1" value="100" data-cimg-escala>
            <div class="cimg__ajuste-fila">
                <small>Agrega fondo alrededor de la foto para que el producto se vea más chico.</small>
                <button type="button" class="btn btn-sm btn-outline-primary" data-cimg-auto>Igualar a las demás</button>
            </div>
        </div>
    </div>

    <div class="cimg__vista">
        <p class="cimg__vista-titulo">Así se ve en el sitio</p>
        <div class="cimg__tarjetas">
            <figure class="cimg__tarjeta">
                <div class="cimg__media">
                    <img data-cimg-img @if ($actual) src="{{ $actual }}" @else hidden @endif alt="">
                    <span class="cimg__vacia" data-cimg-vacia @if ($actual) hidden @endif>Elegí una imagen</span>
                    <span class="cimg__guia" data-cimg-guia aria-hidden="true"></span>
                </div>
                <figcaption><span data-cimg-nombre>{{ $nombre ?: 'Nueva categoría' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></figcaption>
            </figure>
            @if ($vecina)
                <figure class="cimg__tarjeta cimg__tarjeta--vecina">
                    <div class="cimg__media"><img src="{{ $vecina['imagen'] }}" alt="" loading="lazy"></div>
                    <figcaption><span>{{ $vecina['nombre'] }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></figcaption>
                </figure>
            @endif
        </div>
        <label class="cimg__mostrar-guia">
            <input type="checkbox" class="form-check-input" data-cimg-mostrar-guia>
            Mostrar dónde conviene que quede el producto
        </label>
    </div>
</div>
