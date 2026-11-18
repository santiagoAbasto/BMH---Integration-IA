{{--
    Imagen de la tarjeta de una categoría (crear y editar). Dice la medida, muestra la tarjeta como en el sitio al
    lado de otra categoría (la vecina en el orden) y ofrece «Emparejar bordes» (marcado por defecto): al guardar, el
    servidor centra el producto con el mismo tamaño que en las demás (EmparejarBordes). La original queda guardada.

    $uid: único en la página. $actual: URL de la imagen guardada (null al crear). $nombre: nombre de la categoría.
    $vecina: ['nombre' => ..., 'imagen' => URL] o null. $requerida: al crear.
    $formBordes: id del formulario (fuera del de edición) para emparejar / volver a la original la foto guardada.
    $emparejada: si la foto guardada ya tiene los bordes emparejados.
    Los estilos y el script: backend.partials.imagen-categoria-recursos (una vez por página).
--}}
<div class="cimg" data-cimg>
    <div class="cimg__campo">
        <label for="portada-{{ $uid }}" class="form-label cimg__rotulo">{{ $actual ? 'Cambiar la imagen de la tarjeta' : 'Imagen de la tarjeta' }}</label>
        <p class="cimg__medida"><strong>Cuadrada, de 1080 × 1080 px</strong> (mínimo 800 px). JPG, PNG o WebP, con fondo claro y liso.</p>

        <input class="form-control" type="file" id="portada-{{ $uid }}" name="portada" accept="image/jpeg,image/png,image/webp"
            data-cimg-input aria-describedby="cimg-chequeos-{{ $uid }}" @if ($requerida ?? false) required @endif>

        <div class="form-check cimg__emparejar">
            <input type="hidden" name="emparejar_bordes" value="0">
            <input class="form-check-input" type="checkbox" name="emparejar_bordes" value="1" id="cimg-emparejar-{{ $uid }}" checked data-cimg-emparejar>
            <label class="form-check-label" for="cimg-emparejar-{{ $uid }}">
                <strong>Emparejar bordes</strong> (recomendado)
                <small>Al guardar, el producto queda centrado y del mismo tamaño que en las otras categorías, aunque la foto venga con más o menos margen. La original queda guardada: se puede volver a ella.</small>
            </label>
        </div>

        <div class="cimg__chequeos" id="cimg-chequeos-{{ $uid }}" data-cimg-chequeos aria-live="polite"></div>

        @if ($actual && ($formBordes ?? null))
            <div class="cimg__guardada">
                <p>
                    <strong>Foto guardada:</strong>
                    @if ($emparejada ?? false)
                        <span class="cimg__estado cimg__estado--ok"><i class="fa-solid fa-check" aria-hidden="true"></i> bordes emparejados</span>
                    @else
                        <span class="cimg__estado">tal como se subió</span>
                    @endif
                </p>
                <div class="cimg__guardada-botones">
                    @if ($emparejada ?? false)
                        <button type="submit" form="{{ $formBordes }}" name="accion" value="original" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Volver a la original</button>
                    @else
                        <button type="submit" form="{{ $formBordes }}" name="accion" value="emparejar" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Emparejar bordes</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="cimg__vista">
        <p class="cimg__vista-titulo" data-cimg-vista-titulo>Así se ve en el sitio</p>
        <div class="cimg__tarjetas">
            <figure class="cimg__tarjeta">
                <div class="cimg__media">
                    <img data-cimg-img @if ($actual) src="{{ $actual }}" @else hidden @endif alt="">
                    <span class="cimg__vacia" data-cimg-vacia @if ($actual) hidden @endif>Elegí una imagen</span>
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
    </div>
</div>
