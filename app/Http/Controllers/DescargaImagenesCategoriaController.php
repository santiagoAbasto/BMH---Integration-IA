<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Services\Imagenes\BibliotecaImagenes;
use App\Services\Imagenes\ImagenesDeCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

/**
 * Descarga en un ZIP todas las imágenes de los productos de una categoría
 * (admin → Productos, filtrado por categoría).
 *
 * El ZIP se arma mientras se envía: no se escribe ningún archivo temporal en
 * el servidor, que tiene el espacio justo. Las imágenes van sin recomprimir
 * (ya están comprimidas; hacerlo sólo gastaría CPU).
 */
class DescargaImagenesCategoriaController extends Controller
{
    public function __invoke(Request $request, ImagenesDeCategoria $imagenes): StreamedResponse
    {
        // Sin categoría elegida («todos»): las imágenes de todo el catálogo.
        $todas = in_array($request->query('categoria_id'), [null, '', 'todos'], true);
        $categoria = $todas ? null : Categoria::query()->findOrFail((int) $request->query('categoria_id'));
        // Las fotos cargadas desde la ficha de cada producto también salen con su código.
        if (Schema::hasTable('biblioteca_imagenes')) {
            app(BibliotecaImagenes::class)->sincronizar();
        }
        ['archivos' => $archivos, 'sinImagenes' => $sinImagenes] = $imagenes->armar($categoria?->id);

        abort_if($archivos === [], 404, $todas ? 'No hay imágenes para descargar.' : 'La categoría no tiene imágenes para descargar.');

        $nombreZip = 'imagenes-'.($todas ? 'todas' : (Str::slug($categoria->nombre) ?: 'categoria-'.$categoria->id)).'-'.now()->format('Y-m-d').'.zip';
        $titulo = $todas ? 'Productos sin imágenes cargadas' : "Productos de «{$categoria->nombre}» sin imágenes cargadas";

        return response()->streamDownload(function () use ($archivos, $sinImagenes, $titulo): void {
            @set_time_limit(0);

            $zip = new ZipStream(
                sendHttpHeaders: false,
                defaultCompressionMethod: CompressionMethod::STORE,
                defaultEnableZeroHeader: true,
                enableZip64: true,
                flushOutput: true,
            );

            foreach ($archivos as [$nombre, $ruta]) {
                $zip->addFileFromPath(fileName: $nombre, path: $ruta);
            }

            if ($sinImagenes !== []) {
                $zip->addFile(
                    fileName: 'sin-imagenes.txt',
                    data: "{$titulo}:\r\n\r\n".implode("\r\n", $sinImagenes)."\r\n",
                    compressionMethod: CompressionMethod::DEFLATE,
                );
            }

            $zip->finish();
        }, $nombreZip, ['Content-Type' => 'application/zip']);
    }
}
