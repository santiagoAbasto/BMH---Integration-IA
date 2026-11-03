<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Productos\ListaPreciosCompleta;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Zona de Clientes → Lista de precios → «Lista completa»: todos los productos
 * en un único Excel de una sola hoja (ver ListaPreciosCompleta).
 */
class ListaPreciosCompletaController extends Controller
{
    public function __invoke(Request $request, ListaPreciosCompleta $lista): BinaryFileResponse
    {
        $temporal = tempnam(sys_get_temp_dir(), 'lista');
        (new Xlsx($lista->generar($request->user('web'))))->save($temporal);

        return response()
            ->download($temporal, 'Lista de precios completa BMH '.now()->format('Y-m-d').'.xlsx')
            ->deleteFileAfterSend(true);
    }
}
