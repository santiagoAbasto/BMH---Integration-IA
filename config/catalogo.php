<?php

/*
|--------------------------------------------------------------------------
| Buscador del catálogo
|--------------------------------------------------------------------------
*/

return [

    /*
    | Palabras que la gente usa para lo mismo que el catálogo llama de otra
    | forma. Buscar cualquiera de un grupo encuentra lo de todo el grupo
    | («bendix mercedes» trae los impulsores de Mercedes). En singular y
    | sin tildes: se comparan con LIKE, así que «impulsor» cubre «impulsores».
    */
    'sinonimos' => [
        ['impulsor', 'bendix'],
        ['solenoide', 'automatico'],
        ['escobilla', 'carbon'],
        ['rodamiento', 'ruleman'],
        ['rectificador', 'diodo'],
        ['arranque', 'burro'],
    ],

];
