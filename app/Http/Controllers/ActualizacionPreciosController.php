<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Productos\ActualizacionPrecios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin → Productos → «Actualizar desde Excel: Precios». Aplica el Excel y
 * vuelve al listado con el reporte (ver dash-productos): cuántos se
 * actualizaron y cuáles no, con un Excel descargable de los que no.
 *
 * El reporte se guarda unos días con un token para poder bajar ese Excel.
 */
class ActualizacionPreciosController extends Controller
{
    private const DIAS_GUARDADO = 30;

    public function __construct(private readonly ActualizacionPrecios $precios)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['lista' => 'required|file|mimes:xlsx,xls']);

        $archivo = $request->file('lista');
        $reporte = $this->precios->aplicar($archivo->getRealPath(), $archivo->getClientOriginalName());

        $this->limpiarViejos();
        $token = (string) Str::uuid();
        File::ensureDirectoryExists($this->carpeta());
        File::put($this->ruta($token), json_encode($reporte, JSON_UNESCAPED_UNICODE));

        return redirect()->back()->with('precios_reporte', $token);
    }

    public function noActualizados(string $token): BinaryFileResponse
    {
        $reporte = $this->reporte($token);
        abort_if($reporte === null, 404, 'El reporte ya no está disponible. Volvé a subir el Excel.');

        $temporal = tempnam(sys_get_temp_dir(), 'precios');
        (new Xlsx($this->precios->excelNoActualizados($reporte)))->save($temporal);

        $base = pathinfo($reporte['archivo'], PATHINFO_FILENAME);

        return response()
            ->download($temporal, 'No actualizados - '.(Str::slug($base) ?: 'precios').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    /** El reporte guardado, o null si no existe (token inválido o vencido). */
    public function reporte(?string $token): ?array
    {
        if (! is_string($token) || ! Str::isUuid($token) || ! is_file($this->ruta($token))) {
            return null;
        }

        return json_decode((string) File::get($this->ruta($token)), true) ?: null;
    }

    private function carpeta(): string
    {
        return storage_path('app/actualizacion-precios');
    }

    private function ruta(string $token): string
    {
        return $this->carpeta().'/'.$token.'.json';
    }

    private function limpiarViejos(): void
    {
        $limite = now()->subDays(self::DIAS_GUARDADO)->getTimestamp();
        foreach (File::glob($this->carpeta().'/*.json') ?: [] as $viejo) {
            if (File::lastModified($viejo) < $limite) {
                File::delete($viejo);
            }
        }
    }
}
