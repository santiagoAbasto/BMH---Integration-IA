<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Productos\ListaPreciosCompleta;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Zona de Clientes → Lista de precios → «Lista completa»: todos los productos
 * en un único Excel de una sola hoja (ver ListaPreciosCompleta).
 */
class ListaPreciosCompletaController extends Controller
{
    public function __invoke(ListaPreciosCompleta $lista): BinaryFileResponse
    {
        $temporal = tempnam(sys_get_temp_dir(), 'lista');
        (new Xlsx($lista->generar()))->save($temporal);

        return response()
            ->download($temporal, 'Lista de precios completa BMH '.now()->format('Y-m-d').'.xlsx')
            ->deleteFileAfterSend(true);
    }
}
