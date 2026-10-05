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
     * alcanza la fidelidad mínima; así una foto con mucho detalle sube de
     * calidad en vez de degradarse para ahorrar bytes.
     *
     * Calibrado con 80 fotos reales del catálogo (120 MB): con 82 se ahorraba
     * 97,7 % pero la textura fina del metal se suavizaba al 100 % (lo que
     * muestra la lupa); con 90 se conserva y el ahorro sigue en 96,8 %.
     */
    'calidades' => [90, 94],

    /*
     * PSNR mínimo de luminancia (dB) contra el original ya redimensionado.
     * Por encima de ~40 dB la diferencia no se ve; 42 deja margen.
     */
    'psnr_minimo' => 42.0,

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
     * Lupa al pasar el mouse por la foto de las cards y de la ficha (vista
     * normal y de clientes). Apagada por ahora: el clic abre el modal con
     * zoom. Para volver a prenderla, IMAGENES_ZOOM_HOVER=true en el .env.
     */
    'zoom_hover' => (bool) env('IMAGENES_ZOOM_HOVER', false),
];
