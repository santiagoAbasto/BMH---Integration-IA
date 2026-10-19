<?php

/*
 * Imágenes subidas desde el admin (public/imagenes, en producción un enlace a
 * public_html/imagenes) y su optimización a WebP.
 */
return [

    'directorio' => env('IMAGENES_DIRECTORIO', public_path('imagenes')),

    /*
     * Lado mayor máximo. La lupa de la Zona de Clientes amplía hasta 4,2 veces
     * una caja de 420 px (≈1.760 px): con 2.000 el zoom conserva el detalle.
     * Nunca se agranda una imagen.
     */
    'lado_maximo' => 2000,

    /*
     * Calidades WebP que se prueban en orden. Se queda con la primera que
     * pasa los controles de fidelidad; así una foto con mucho detalle sube de
     * calidad en vez de degradarse para ahorrar bytes. Si ninguna los pasa,
     * WebP sin pérdida o el original tal cual.
     */
    'calidades' => [90, 92, 94, 96, 98],

    /*
     * Fidelidad mínima contra el original ya redimensionado, en PSNR (dB):
     *  - luma: luminancia de toda la imagen.
     *  - color: Cb y Cr (el WebP con pérdida submuestrea el color).
     *  - zona: la peor parte de la imagen (percentil 1 de zonas chicas),
     *    para que un borde o una etiqueta no se dañen aunque el promedio dé bien.
     *
     * Calibrado con 60 fotos originales del catálogo. El criterio anterior
     * (sólo luminancia ≥ 42 dB, calidades 90 y 94) dejaba pasar fotos con el
     * color en 32 dB y zonas en 32 dB, que se notan. Con estos valores lo peor
     * queda en 47 / 44 / 38 dB, sin diferencia visible, y ahorra 77 % (antes
     * 81 %). Más exigente (48 / 46 / 40) ahorraba 73 % y dejaba 1 de cada 9
     * fotos sin convertir.
     */
    'fidelidad' => ['luma' => 47.0, 'color' => 44.0, 'zona' => 38.0],

    /*
     * Ahorro mínimo (%) para reemplazar una imagen existente. Si el WebP no
     * ahorra al menos esto, queda el original: no vale la pena cambiar el
     * nombre del archivo por poco.
     */
    'ahorro_minimo' => 10,

    /* Archivos con nombre fijo en el código: no se renombran nunca. */
    'excluidas' => [
        'logobmh.png', 'wp-logo.png', 'video-placeholder.png', 'exc.png', 'doc.png',
        'capa1.png', 'capa2.png', 'capa3.png', 'ISO-1.png',
        'WhatsApp-Image-2020-11-11-at-15.25.09.jpeg',
    ],

    /*
     * Logos y favicon (LogosSitio): quedan en su formato. Son pocos y livianos,
     * y un favicon WebP no lo muestran todos los navegadores.
     */
    'sectores_excluidos' => ['logo', 'logo2', 'logo-header-blanco', 'favicon'],

    /*
     * Foto de producto que se le sugiere al admin al cargarla. Las cards la
     * muestran en una caja cuadrada (--pbmh-foto en productoBmh-recursos):
     * cuadrada se ve sin márgenes. 1200 px alcanza para el zoom del modal;
     * debajo del mínimo se avisa que puede verse borrosa.
     */
    'tamano_sugerido' => ['lado' => 1200, 'minimo' => 800],

    /*
     * Lupa al pasar el mouse por la foto de las cards y de la ficha (vista
     * normal y de clientes). Apagada por ahora: el clic abre el modal con
     * zoom. Para volver a prenderla, IMAGENES_ZOOM_HOVER=true en el .env.
     */
    'zoom_hover' => (bool) env('IMAGENES_ZOOM_HOVER', false),
];
