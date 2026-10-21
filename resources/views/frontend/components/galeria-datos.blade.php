{{-- Datos locales: también funcionan en las cards que llegan por AJAX. --}}
data-gallery-images="{{ json_encode($fotosGaleria ?? $productoGaleria->galeriaUrls(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"
data-gallery-title="{{ $productoGaleria->nombre }}"
data-gallery-code="{{ $productoGaleria->codigo }}"
