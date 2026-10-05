<dialog id="product-gallery" class="foto-galeria" aria-labelledby="foto-galeria-titulo" aria-describedby="foto-galeria-ayuda">
    <div class="foto-galeria__layout">
        <header class="foto-galeria__cabecera">
            <div class="foto-galeria__identidad">
                <span class="foto-galeria__codigo" data-gallery-code-label></span>
                <h2 id="foto-galeria-titulo" class="foto-galeria__titulo"></h2>
            </div>
            <button type="button" class="foto-galeria__cerrar" data-gallery-close aria-label="Cerrar galería" autofocus>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </header>

        <div class="foto-galeria__escena" data-gallery-stage>
            <img class="foto-galeria__imagen" data-gallery-image alt="" draggable="false">
            <div class="foto-galeria__cargando" aria-hidden="true"><span></span></div>
            <p class="foto-galeria__error" role="status" hidden>No pudimos cargar esta foto. Probá con otra imagen.</p>
            <button type="button" class="foto-galeria__flecha foto-galeria__flecha--anterior" data-gallery-prev aria-label="Foto anterior">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 5l-7 7 7 7"/></svg>
            </button>
            <button type="button" class="foto-galeria__flecha foto-galeria__flecha--siguiente" data-gallery-next aria-label="Foto siguiente">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 5l7 7-7 7"/></svg>
            </button>
        </div>

        <footer class="foto-galeria__pie">
            <div class="foto-galeria__herramientas">
                <span class="foto-galeria__contador" data-gallery-counter role="status" aria-live="polite" aria-atomic="true"></span>
                <div class="foto-galeria__zoom" role="group" aria-label="Zoom de la foto">
                    <button type="button" data-gallery-zoom-out aria-label="Reducir zoom" title="Reducir zoom (−)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>
                    </button>
                    <span class="foto-galeria__porcentaje" data-gallery-percent>100%</span>
                    <button type="button" data-gallery-zoom-in aria-label="Ampliar foto" title="Ampliar foto (+)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5v14"/></svg>
                    </button>
                    <span class="foto-galeria__separador" aria-hidden="true"></span>
                    <button type="button" class="foto-galeria__ajustar" data-gallery-reset title="Ver foto completa (0)">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
                        Ajustar
                    </button>
                </div>
            </div>
            <div class="foto-galeria__miniaturas" data-gallery-thumbs role="group" aria-label="Fotos del producto"></div>
            <p id="foto-galeria-ayuda" class="foto-galeria__ayuda">
                <span class="foto-galeria__ayuda-desktop">Click o rueda para ampliar · Arrastrá para explorar · ← → para cambiar de foto</span>
                <span class="foto-galeria__ayuda-touch">Pellizcá o tocá dos veces para ampliar · Deslizá para cambiar de foto</span>
            </p>
        </footer>
    </div>
</dialog>
