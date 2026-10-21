<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ImagenBiblioteca;
use App\Services\Productos\ExcelProductos;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exportar e importar el Excel de productos (precio e imágenes).
 *
 * Importar va en tres pasos: subir → vista previa (no cambia nada) →
 * confirmar. El archivo se guarda con un token mientras tanto; al confirmar
 * se vuelve a analizar, por si algo cambió en la web entre medio.
 */
class ExcelProductosController extends Controller
{
    public function __construct(private readonly ExcelProductos $excel)
    {
    }

    public function exportar(Request $request): BinaryFileResponse
    {
        $categoria = $request->input('categoria_id');
        $categoriaId = is_numeric($categoria) ? (int) $categoria : null;
        // El texto del buscador del listado: el Excel trae lo mismo que se ve filtrado.
        $busqueda = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $temporal = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($this->excel->exportar($categoriaId, $busqueda)))->save($temporal);

        $nombre = 'productos'
            .($categoriaId !== null ? '_categoria_'.$categoriaId : '_todos')
            .($busqueda !== '' ? '_busqueda_'.Str::slug($busqueda) : '')
            .'_'.now()->format('Y-m-d').'.xlsx';

        return response()->download($temporal, $nombre)->deleteFileAfterSend(true);
    }

    public function importar(): View
    {
        return view('backend.excel-productos.importar', [
            'categorias' => Categoria::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function previsualizar(Request $request): RedirectResponse
    {
        $request->validate(
            ['archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480']],
            [
                'archivo.required' => 'Elegí el archivo Excel.',
                'archivo.mimes' => 'Tiene que ser un Excel (.xlsx o .xls).',
                'archivo.max' => 'El archivo pesa más de 20 MB.',
            ]
        );

        $this->limpiarViejos();
        $token = (string) Str::uuid();
        $carpeta = $this->carpeta($token);
        File::ensureDirectoryExists($carpeta);

        $archivo = $request->file('archivo');
        $archivo->move($carpeta, 'archivo.'.strtolower($archivo->getClientOriginalExtension() ?: 'xlsx'));
        File::put($carpeta.'/nombre.txt', $archivo->getClientOriginalName());

        return redirect()->route('dashboard.productos.excel.preview', $token);
    }

    public function preview(string $token): View|RedirectResponse
    {
        if (! is_dir($this->carpeta($token))) {
            return redirect()->route('dashboard.productos.excel.importar')
                ->with('warning', 'Esa importación ya no está disponible. Subí el Excel de nuevo.');
        }
        if (is_file($this->carpeta($token).'/resultado.json')) {
            $reporte = json_decode((string) File::get($this->carpeta($token).'/reporte.json'), true);

            return view('backend.excel-productos.reporte', [
                'token' => $token,
                'reporte' => $reporte,
                'resultado' => json_decode((string) File::get($this->carpeta($token).'/resultado.json'), true),
                'urls' => $this->urlsDeImagenes($reporte),
            ]);
        }

        $reporte = $this->analizar($token);
        File::put($this->carpeta($token).'/reporte.json', json_encode($reporte, JSON_UNESCAPED_UNICODE));

        return view('backend.excel-productos.reporte', ['token' => $token, 'reporte' => $reporte, 'resultado' => null, 'urls' => $this->urlsDeImagenes($reporte)]);
    }

    /**
     * URL de cada código (o nombre de archivo) que aparece en el reporte, para las miniaturas.
     *
     * @return array<string, string>
     */
    private function urlsDeImagenes(array $reporte): array
    {
        $codigos = [];
        foreach ($reporte['cambios'] ?? [] as $cambio) {
            foreach (array_merge($cambio['imagenes']['antes'] ?? [], $cambio['imagenes']['despues'] ?? []) as $codigo) {
                $codigos[$codigo] = true;
            }
        }
        if ($codigos === []) {
            return [];
        }

        $urls = [];
        foreach (array_chunk(array_keys($codigos), 1000) as $tanda) {
            foreach (ImagenBiblioteca::query()->whereIn('codigo', $tanda)->get(['codigo', 'path']) as $imagen) {
                $urls[$imagen->codigo] = $imagen->url;
            }
        }
        // Lo que no está en la biblioteca (un archivo que falta) se muestra por su nombre.
        foreach (array_keys($codigos) as $codigo) {
            $urls[$codigo] ??= preg_match('/\.(png|jpe?g|webp|gif)$/i', $codigo) ? asset('imagenes/'.$codigo) : '';
        }

        return $urls;
    }

    public function confirmar(string $token): RedirectResponse
    {
        if (! is_dir($this->carpeta($token))) {
            return redirect()->route('dashboard.productos.excel.importar')
                ->with('warning', 'Esa importación ya no está disponible. Subí el Excel de nuevo.');
        }

        // Un doble clic no aplica dos veces.
        $candado = Cache::lock('excel-productos-'.$token, 120);
        if (! $candado->get()) {
            return redirect()->route('dashboard.productos.excel.preview', $token);
        }

        try {
            if (! is_file($this->carpeta($token).'/resultado.json')) {
                $reporte = $this->analizar($token);
                if ($reporte['fatal'] === null) {
                    $hechos = $this->excel->aplicar($reporte);
                    File::put($this->carpeta($token).'/reporte.json', json_encode($reporte, JSON_UNESCAPED_UNICODE));
                    File::put($this->carpeta($token).'/resultado.json', json_encode($hechos + ['fecha' => now()->format('d/m/Y H:i')]));
                }
            }
        } finally {
            $candado->release();
        }

        return redirect()->route('dashboard.productos.excel.preview', $token);
    }

    /** @return array<string, mixed> */
    private function analizar(string $token): array
    {
        $carpeta = $this->carpeta($token);
        $archivo = collect(File::files($carpeta))->first(fn ($f) => str_starts_with($f->getFilename(), 'archivo.'));
        $nombre = is_file($carpeta.'/nombre.txt') ? trim((string) File::get($carpeta.'/nombre.txt')) : 'Excel';

        return $this->excel->analizar($archivo->getPathname(), $nombre);
    }

    private function carpeta(string $token): string
    {
        abort_unless(Str::isUuid($token), 404);

        return storage_path('app/excel-productos/'.$token);
    }

    /** Las importaciones de hace más de 2 días ya no se van a mirar. */
    private function limpiarViejos(): void
    {
        File::ensureDirectoryExists(storage_path('app/excel-productos'));
        foreach (File::directories(storage_path('app/excel-productos')) as $carpeta) {
            if (filemtime($carpeta) < now()->subDays(2)->getTimestamp()) {
                File::deleteDirectory($carpeta);
            }
        }
    }
}
