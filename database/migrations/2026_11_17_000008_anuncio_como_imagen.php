<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El anuncio que ven los clientes al ingresar pasa de texto enriquecido a una imagen.
 *
 * El contenido era siempre una sola imagen pegada en el editor como base64 (2,6 MB dentro del HTML de cada página
 * en la que aparecía). Esta migración la saca a un archivo en public/imagenes y la deja como la imagen del anuncio.
 * La columna `contenido` queda como estaba: no se borra nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('contenido');
            $table->unsignedSmallInteger('ancho')->nullable()->after('imagen');
            $table->unsignedSmallInteger('alto')->nullable()->after('ancho');
            $table->string('texto_alternativo', 200)->nullable()->after('alto');
        });

        $anuncio = DB::table('anuncios')->where('id', 1)->first();

        if ($anuncio === null || blank($anuncio->contenido)) {
            return;
        }

        $datos = $this->imagenDelContenido((string) $anuncio->contenido);

        if ($datos === null) {
            return;
        }

        [$archivo, $ancho, $alto] = $datos;

        DB::table('anuncios')->where('id', 1)->update([
            'imagen' => $archivo,
            'ancho' => $ancho,
            'alto' => $alto,
            'texto_alternativo' => $this->textoAlternativo((string) $anuncio->contenido),
        ]);
    }

    public function down(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'ancho', 'alto', 'texto_alternativo']);
        });
    }

    /** @return array{0: string, 1: int|null, 2: int|null}|null  El archivo (relativo a public/imagenes) y su tamaño. */
    private function imagenDelContenido(string $html): ?array
    {
        // 1. Pegada en el editor: data:image/...;base64,...
        if (preg_match('~src="data:image/(png|jpe?g|webp|gif);base64,([A-Za-z0-9+/=\s]++)"~i', $html, $m)) {
            $binario = base64_decode(preg_replace('/\s+/', '', $m[2]), true);

            if ($binario === false || @getimagesizefromstring($binario) === false) {
                return null;
            }

            $extension = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            $archivo = 'anuncio-'.substr(sha1($binario), 0, 12).'.'.$extension;
            $destino = public_path('imagenes/'.$archivo);

            if (! is_dir(dirname($destino))) {
                mkdir(dirname($destino), 0755, true);
            }
            if (! is_file($destino)) {
                file_put_contents($destino, $binario);
            }

            $medidas = getimagesizefromstring($binario);

            return [$archivo, $medidas[0] ?? null, $medidas[1] ?? null];
        }

        // 2. Subida al servidor: src="…/imagenes/archivo.ext"
        if (preg_match('~src="[^"]*?/?imagenes/([^"/?#]+\.(?:png|jpe?g|webp|gif))"~i', $html, $m) && is_file(public_path('imagenes/'.$m[1]))) {
            $medidas = @getimagesize(public_path('imagenes/'.$m[1]));

            return [$m[1], $medidas[0] ?? null, $medidas[1] ?? null];
        }

        return null;
    }

    private function textoAlternativo(string $html): ?string
    {
        if (preg_match('~data-filename="([^"]+)"~', $html, $m)) {
            $nombre = trim(preg_replace('/[_-]+/', ' ', pathinfo(html_entity_decode($m[1]), PATHINFO_FILENAME)));

            return $nombre !== '' ? mb_substr(mb_strtoupper(mb_substr($nombre, 0, 1)).mb_substr($nombre, 1), 0, 200) : null;
        }

        return null;
    }
};
