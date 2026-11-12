<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Textos enriquecidos que se cargan en el admin (editor WYSIWYG): traen estilos en línea y
 * marcas de lo que se pegó desde otras herramientas (Figma) que pisan la tipografía del
 * rediseño, y párrafos armados a veces con <div>, a veces con <p> y a veces con saltos de línea.
 * Se sacan esos atributos y se rearman los párrafos; se conservan negritas, links y listas.
 */
final class TextoAdmin
{
    public static function html(?string $html): ?HtmlString
    {
        $texto = preg_replace(
            ['/\s(?:style|class|data-[a-z-]+|face|color|size)="[^"]*"/i', '#</?span>#i', '/&nbsp;/i'],
            ['', '', ' '],
            (string) $html,
        );

        // Las listas quedan como están; el resto se parte en párrafos.
        $partes = preg_split('#(<(?:ul|ol)\b.*?</(?:ul|ol)>)#is', (string) $texto, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $salida = '';

        foreach ($partes as $parte) {
            if (preg_match('#^<(ul|ol)\b#i', $parte)) {
                $salida .= trim($parte);

                continue;
            }

            // <div>, <p> y los saltos de línea separan párrafos; un <br> suelto es un salto dentro del párrafo.
            $parte = preg_replace('#</?(?:div|p)\b[^>]*>#i', "\n", $parte);
            foreach (preg_split('/\R+/', (string) $parte) ?: [] as $linea) {
                $linea = trim(preg_replace('#^(?:<br\s*/?>\s*)+|(?:\s*<br\s*/?>)+$#i', '', trim($linea)) ?? '');
                if (trim(strip_tags($linea)) !== '') {
                    $salida .= '<p>'.$linea.'</p>';
                }
            }
        }

        return $salida === '' ? null : new HtmlString($salida);
    }
}
