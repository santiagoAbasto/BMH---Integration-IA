<?php

declare(strict_types=1);

namespace App\Services\Productos;

use App\Models\Producto;
use App\Services\Imagenes\BibliotecaImagenes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Throwable;

/**
 * El Excel de productos: Código | Nombre / Descripción | Categoría | Precio | Imágenes.
 *
 * Exportar arma el Excel con los datos actuales (en IMÁGENES, los códigos de
 * la biblioteca). Importar lo lee en dos pasos: analizar (no cambia nada y
 * arma el reporte con lo que va a cambiar y los avisos por fila) y aplicar.
 *
 * Reglas de la importación (también se le explican al usuario en pantalla):
 *  - CÓDIGO identifica al producto. No se crean productos nuevos.
 *  - Celda vacía = ese dato no se toca.
 *  - IMÁGENES: códigos de la biblioteca separados por coma; reemplazan las
 *    fotos del producto y la primera es la principal. Un guion (-) le saca
 *    todas. Si algún código no existe, las fotos de ese producto no cambian.
 *    Los archivos nunca se borran.
 *  - PRECIO: se actualiza si es un número válido.
 *  - NOMBRE y CATEGORÍA son de referencia: no se modifican desde acá.
 */
final class ExcelProductos
{
    public const ERROR = 'error';

    public const ADVERTENCIA = 'advertencia';

    public const INFO = 'info';

    public const ENCABEZADOS = ['Código', 'Nombre / Descripción', 'Categoría', 'Precio', 'Imágenes'];

    public function __construct(private readonly BibliotecaImagenes $biblioteca)
    {
    }

    // ------------------------------------------------------------ exportar

    /**
     * Los productos como están filtrados en el listado del admin: la categoría
     * elegida y el texto del buscador (con el mismo orden: los códigos que
     * coinciden primero).
     */
    public function exportar(?int $categoriaId, string $busqueda = ''): Spreadsheet
    {
        $this->biblioteca->sincronizar();
        $celdas = $this->biblioteca->celdasPorProducto();

        $productos = Producto::with('categoria')
            ->busquedaAdmin($busqueda)
            ->ordenado()
            ->when($categoriaId !== null, fn ($q) => $q->where('categoria_id', $categoriaId))
            ->get(['id', 'codigo', 'nombre', 'categoria_id', 'precio']);

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Productos');
        $hoja->fromArray(self::ENCABEZADOS, null, 'A1');

        $fila = 2;
        foreach ($productos as $producto) {
            // Texto explícito: si no, Excel le saca los ceros a «0094».
            $hoja->setCellValueExplicit("A{$fila}", (string) $producto->codigo, DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", $producto->nombre);
            $hoja->setCellValue("C{$fila}", optional($producto->categoria)->nombre ?? '');
            // El precio base, no uno calculado: al importar es el que se guarda.
            $hoja->setCellValue("D{$fila}", $producto->getRawOriginal('precio'));
            $hoja->setCellValueExplicit("E{$fila}", $celdas[$producto->id] ?? '', DataType::TYPE_STRING);
            $fila++;
        }

        $hoja->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B3A5B');
        $hoja->getStyle('E1')->getFill()->getStartColor()->setRGB('0098DA');
        $hoja->freezePane('A2');
        $hoja->setAutoFilter('A1:E'.max(1, $fila - 1));
        foreach (['A' => 16, 'B' => 60, 'C' => 30, 'D' => 12, 'E' => 40] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }
        $hoja->getComment('E1')->getText()->createText(
            "Códigos de la biblioteca de imágenes separados por coma. La primera es la principal.\n"
            ."Vacío = no se cambian. Un guion (-) le saca todas las fotos."
        );

        $this->hojaDeInstrucciones($libro);
        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    private function hojaDeInstrucciones(Spreadsheet $libro): void
    {
        $hoja = $libro->createSheet();
        $hoja->setTitle('Cómo usar');
        $lineas = [
            ['Cómo actualizar productos con este Excel'],
            [''],
            ['1. Cambiá sólo las columnas Precio e Imágenes. Nombre y Categoría son de referencia: no se modifican desde el Excel.'],
            ['2. No cambies los títulos de la primera fila ni la columna Código: con el código se encuentra cada producto.'],
            ['3. Subilo en Productos → Importar Excel. Antes de aplicar vas a ver qué cambia y los avisos de cada fila.'],
            [''],
            ['Columna Imágenes'],
            ['• Escribí los códigos de la biblioteca de imágenes (Productos → Imágenes), separados por coma: NFSO42, NFSO42-2'],
            ['• Reemplazan las fotos que tenía el producto. La primera es la principal; las demás van en ese orden.'],
            ['• Celda vacía: las fotos no se cambian.'],
            ['• Un guion (-): se le sacan todas las fotos al producto.'],
            ['• Si un código no existe en la biblioteca, las fotos de ese producto no se cambian y se avisa.'],
            ['• Los archivos de imagen nunca se borran: sólo cambia qué foto muestra cada producto.'],
            [''],
            ['Columna Precio'],
            ['• Celda vacía: el precio no se cambia. Usá números: 2591 o 2591,50.'],
        ];
        $hoja->fromArray($lineas, null, 'A1');
        $hoja->getColumnDimension('A')->setWidth(120);
        foreach (['A1', 'A7', 'A15'] as $titulo) {
            $hoja->getStyle($titulo)->getFont()->setBold(true)->setSize($titulo === 'A1' ? 14 : 12);
        }
    }

    // ----------------------------------------------------------- analizar

    /**
     * Lee el Excel y arma el reporte sin cambiar nada.
     *
     * @return array<string, mixed> ver la estructura en reporteVacio()
     */
    public function analizar(string $ruta, string $nombreArchivo): array
    {
        $reporte = $this->reporteVacio($nombreArchivo);

        try {
            $lector = IOFactory::createReaderForFile($ruta);
            $lector->setReadDataOnly(true);
            $filas = $lector->load($ruta)->getSheet(0)->toArray(null, true, false, false);
        } catch (Throwable $e) {
            $reporte['fatal'] = 'No se pudo leer el archivo. Tiene que ser un Excel (.xlsx o .xls). Si lo guardaste en otro formato, abrilo en Excel y guardalo como «Libro de Excel (.xlsx)».';

            return $reporte;
        }

        [$filaTitulos, $columnas] = $this->columnas($filas);
        if ($columnas === null || ! isset($columnas['codigo'])) {
            $reporte['fatal'] = 'No encontré la columna «Código». La primera fila del Excel tiene que tener los títulos (Código, Nombre / Descripción, Categoría, Precio, Imágenes). Lo más fácil es descargar el Excel con «Exportar Excel» y usarlo como base.';

            return $reporte;
        }
        if (! isset($columnas['precio']) && ! isset($columnas['imagenes'])) {
            $reporte['fatal'] = 'El Excel no tiene columna «Precio» ni «Imágenes»: no hay nada para actualizar.';

            return $reporte;
        }
        $reporte['columnas'] = array_map(fn ($i) => self::letra($i), $columnas);

        $this->biblioteca->sincronizar();
        $indice = $this->biblioteca->indice();
        $productos = $this->productosPorCodigo();
        $imagenesActuales = $this->imagenesActuales();
        $codigoPorPath = array_change_key_case(array_column(array_map(fn ($d) => $d, $indice['porCodigo']), 'codigo', 'path'), CASE_LOWER);
        $vistos = [];

        foreach ($filas as $i => $valores) {
            if ($i <= $filaTitulos) {
                continue;
            }
            $numeroFila = $i + 1;
            $codigoEscrito = self::texto($valores[$columnas['codigo']] ?? null);
            $precioEscrito = isset($columnas['precio']) ? $valores[$columnas['precio']] ?? null : null;
            $imagenesEscritas = isset($columnas['imagenes']) ? self::texto($valores[$columnas['imagenes']] ?? null) : '';

            if ($codigoEscrito === '' && self::texto($precioEscrito) === '' && $imagenesEscritas === '') {
                continue; // fila vacía
            }
            $reporte['filas_leidas']++;

            if ($codigoEscrito === '') {
                $this->aviso($reporte, self::ERROR, $numeroFila, '', 'Código', 'Esta fila no tiene código: se ignora.');
                continue;
            }

            $producto = $this->buscarProducto($codigoEscrito, $productos, $reporte, $numeroFila);
            if ($producto === null) {
                continue;
            }
            if (isset($vistos[$producto['id']])) {
                $this->aviso($reporte, self::ADVERTENCIA, $numeroFila, $producto['codigo'], 'Código',
                    "El producto {$producto['codigo']} ya está en la fila {$vistos[$producto['id']]}, que es la que se usa. Esta fila se ignora.");
                continue;
            }
            $vistos[$producto['id']] = $numeroFila;

            $cambio = ['fila' => $numeroFila, 'producto_id' => $producto['id'], 'codigo' => $producto['codigo'], 'nombre' => $producto['nombre'], 'precio' => null, 'imagenes' => null];

            if (isset($columnas['precio'])) {
                $cambio['precio'] = $this->analizarPrecio($precioEscrito, $producto, $reporte, $numeroFila);
            }
            if (isset($columnas['imagenes']) && $imagenesEscritas !== '') {
                $actuales = $imagenesActuales[$producto['id']] ?? [];
                $cambio['imagenes'] = $this->analizarImagenes($imagenesEscritas, $actuales, $codigoPorPath, $indice, $producto, $reporte, $numeroFila);
            }

            if ($cambio['precio'] !== null || $cambio['imagenes'] !== null) {
                $reporte['cambios'][] = $cambio;
                $reporte['resumen']['precios'] += $cambio['precio'] !== null ? 1 : 0;
                $reporte['resumen']['imagenes'] += $cambio['imagenes'] !== null ? 1 : 0;
            } else {
                $reporte['resumen']['sin_cambios']++;
            }
        }

        $reporte['resumen']['productos'] = count($reporte['cambios']);
        foreach ($reporte['avisos'] as $aviso) {
            $reporte['resumen'][$aviso['nivel'] === self::ERROR ? 'errores' : ($aviso['nivel'] === self::ADVERTENCIA ? 'advertencias' : 'infos')]++;
        }

        return $reporte;
    }

    /**
     * Aplica los cambios de un reporte (recién analizado) en una transacción.
     *
     * @return array{precios: int, imagenes: int}
     */
    public function aplicar(array $reporte): array
    {
        $hechos = ['precios' => 0, 'imagenes' => 0];

        DB::transaction(function () use ($reporte, &$hechos): void {
            $ahora = now();
            foreach ($reporte['cambios'] as $cambio) {
                if ($cambio['precio'] !== null) {
                    DB::table('productos')->where('id', $cambio['producto_id'])->update(['precio' => $cambio['precio']['despues']]);
                    $hechos['precios']++;
                }
                if ($cambio['imagenes'] !== null) {
                    // Sólo cambia qué fotos muestra el producto: los archivos quedan.
                    DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $cambio['producto_id'])->delete();
                    $filas = [];
                    foreach ($cambio['imagenes']['paths_despues'] as $posicion => $path) {
                        $filas[] = [
                            'path' => $path,
                            'sector' => 'producto',
                            'producto_id' => $cambio['producto_id'],
                            'tipo' => $posicion === 0 ? 'portada' : 'imagen',
                            'orden' => BibliotecaImagenes::orden($posicion),
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }
                    if ($filas !== []) {
                        DB::table('imagenes')->insert($filas);
                    }
                    $hechos['imagenes']++;
                }
            }
        });

        return $hechos;
    }

    // ------------------------------------------------------------ privado

    /** @return array<string, mixed> */
    private function reporteVacio(string $nombreArchivo): array
    {
        return [
            'archivo' => $nombreArchivo,
            'fatal' => null,
            'columnas' => [],
            'filas_leidas' => 0,
            'cambios' => [],
            'avisos' => [],
            'resumen' => ['productos' => 0, 'precios' => 0, 'imagenes' => 0, 'sin_cambios' => 0, 'errores' => 0, 'advertencias' => 0, 'infos' => 0],
        ];
    }

    /**
     * Fila de títulos (entre las primeras 5) y la columna de cada dato.
     *
     * @return array{int, array<string, int>|null}
     */
    private function columnas(array $filas): array
    {
        foreach (array_slice($filas, 0, 5, true) as $i => $valores) {
            $columnas = [];
            foreach ($valores as $indice => $titulo) {
                $clave = (string) preg_replace('/[^a-z]/', '', Str::lower(Str::ascii(self::texto($titulo))));
                $dato = match (true) {
                    $clave === 'codigo' || $clave === 'cod' => 'codigo',
                    str_starts_with($clave, 'precio') => 'precio',
                    in_array($clave, ['imagenes', 'imagen', 'fotos', 'foto'], true) => 'imagenes',
                    default => null,
                };
                if ($dato !== null && ! isset($columnas[$dato])) {
                    $columnas[$dato] = (int) $indice;
                }
            }
            if (isset($columnas['codigo'])) {
                return [(int) $i, $columnas];
            }
        }

        return [0, null];
    }

    /** @return array<string, list<array{id: int, codigo: string, nombre: string, precio: ?float}>> */
    private function productosPorCodigo(): array
    {
        $porCodigo = [];
        foreach (DB::table('productos')->get(['id', 'codigo', 'nombre', 'precio']) as $p) {
            $clave = mb_strtoupper(trim((string) $p->codigo));
            if ($clave !== '') {
                $porCodigo[$clave][] = ['id' => (int) $p->id, 'codigo' => trim((string) $p->codigo), 'nombre' => (string) $p->nombre, 'precio' => $p->precio === null ? null : (float) $p->precio];
            }
        }

        return $porCodigo;
    }

    private function buscarProducto(string $escrito, array $productos, array &$reporte, int $fila): ?array
    {
        $clave = mb_strtoupper($escrito);
        $encontrados = $productos[$clave] ?? [];

        // Excel suele sacarle los ceros de adelante a los códigos numéricos: 0094 → 94.
        if ($encontrados === [] && ctype_digit($escrito)) {
            $candidatos = array_filter(array_keys($productos), fn ($c) => ctype_digit((string) $c) && ltrim((string) $c, '0') === ltrim($escrito, '0'));
            if (count($candidatos) === 1) {
                $real = (string) reset($candidatos);
                $encontrados = $productos[$real];
                $this->aviso($reporte, self::INFO, $fila, $real, 'Código', "Excel le sacó los ceros de adelante al código: «{$escrito}» se tomó como {$real}.");
            }
        }

        if ($encontrados === []) {
            $this->aviso($reporte, self::ERROR, $fila, $escrito, 'Código', "No hay ningún producto con el código «{$escrito}» en la web. Esta fila se ignora (los productos nuevos se crean desde Productos → Nuevo producto).");

            return null;
        }
        if (count($encontrados) > 1) {
            $this->aviso($reporte, self::ERROR, $fila, $escrito, 'Código', 'Hay '.count($encontrados)." productos con el código «{$escrito}» en la web y no se sabe a cuál aplicarlo. Esta fila se ignora: cambiale el código a uno de ellos en el admin y volvé a subir el Excel.");

            return null;
        }

        return $encontrados[0];
    }

    /** @return array{antes: ?float, despues: float}|null */
    private function analizarPrecio(mixed $escrito, array $producto, array &$reporte, int $fila): ?array
    {
        $texto = self::texto($escrito);
        if ($texto === '') {
            return null;
        }

        $precio = self::numero($escrito);
        if ($precio === null) {
            $this->aviso($reporte, self::ADVERTENCIA, $fila, $producto['codigo'], 'Precio', "«{$texto}» no es un precio válido: el precio no se cambia. Usá números, por ejemplo 2591 o 2591,50.");

            return null;
        }
        if ($precio < 0) {
            $this->aviso($reporte, self::ADVERTENCIA, $fila, $producto['codigo'], 'Precio', 'El precio no puede ser negativo: no se cambia.');

            return null;
        }
        if ($producto['precio'] !== null && abs($producto['precio'] - $precio) < 0.005) {
            return null;
        }
        if ($precio == 0.0) {
            $this->aviso($reporte, self::ADVERTENCIA, $fila, $producto['codigo'], 'Precio', 'El precio queda en 0: revisá que sea lo que querés.');
        }

        return ['antes' => $producto['precio'], 'despues' => round($precio, 2)];
    }

    /**
     * @param  list<string>  $actuales  paths actuales, principal primero
     * @return array{antes: list<string>, despues: list<string>, paths_despues: list<string>, quitar: bool}|null
     */
    private function analizarImagenes(string $escrito, array $actuales, array $codigoPorPath, array $indice, array $producto, array &$reporte, int $fila): ?array
    {
        $codigosActuales = array_map(fn ($p) => $codigoPorPath[mb_strtolower($p)] ?? $p, $actuales);

        if ($escrito === BibliotecaImagenes::QUITAR) {
            return $actuales === [] ? null : ['antes' => $codigosActuales, 'despues' => [], 'paths_despues' => [], 'quitar' => true];
        }

        $resuelto = $this->biblioteca->resolverCelda($escrito, $indice);

        foreach ($resuelto['por_nombre'] as $nombre => $codigo) {
            $this->aviso($reporte, self::INFO, $fila, $producto['codigo'], 'Imágenes', "«{$nombre}» se reconoció por el nombre del archivo: es la imagen {$codigo}. Conviene escribir el código.");
        }
        if ($resuelto['desconocidos'] !== []) {
            $lista = implode('», «', $resuelto['desconocidos']);
            $this->aviso($reporte, self::ERROR, $fila, $producto['codigo'], 'Imágenes', (count($resuelto['desconocidos']) === 1
                ? "El código de imagen «{$lista}» no existe en la biblioteca."
                : "Los códigos de imagen «{$lista}» no existen en la biblioteca.").' Las fotos de este producto no se cambian. Revisá cómo está escrito en Productos → Imágenes.');

            return null;
        }

        $nuevos = array_column($resuelto['imagenes'], 'path');
        // Se compara con lo que muestra el Excel exportado: las fotos cuyo
        // archivo no existe no tienen código y no aparecen ahí, así que una
        // fila que no se tocó no tiene que generar un cambio por ellas.
        $actualesConCodigo = array_values(array_filter($actuales, fn ($p) => isset($codigoPorPath[mb_strtolower($p)])));
        if (array_map('mb_strtolower', $nuevos) === array_map('mb_strtolower', $actualesConCodigo)) {
            return null;
        }

        return ['antes' => $codigosActuales, 'despues' => array_column($resuelto['imagenes'], 'codigo'), 'paths_despues' => $nuevos, 'quitar' => false];
    }

    /** @return array<int, list<string>> producto_id => paths, principal primero */
    private function imagenesActuales(): array
    {
        $porProducto = [];
        $filas = DB::table('imagenes')->where('sector', 'producto')->whereNotNull('producto_id')
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')->orderBy('id')
            ->get(['producto_id', 'path']);
        foreach ($filas as $fila) {
            if ((string) $fila->path !== '') {
                $porProducto[(int) $fila->producto_id][] = (string) $fila->path;
            }
        }

        return $porProducto;
    }

    private function aviso(array &$reporte, string $nivel, int $fila, string $codigo, string $columna, string $mensaje): void
    {
        $reporte['avisos'][] = ['nivel' => $nivel, 'fila' => $fila, 'codigo' => $codigo, 'columna' => $columna, 'mensaje' => $mensaje];
    }

    /** Texto de una celda: los números enteros sin «.0» (1060.0 → «1060»). */
    private static function texto(mixed $valor): string
    {
        if (is_float($valor) && floor($valor) === $valor && abs($valor) < 1e15) {
            return (string) (int) $valor;
        }

        return trim((string) $valor);
    }

    /** «2591», «2591,50», «2.591,50», «$ 2591.5» → número; si no se entiende, null. */
    private static function numero(mixed $valor): ?float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }
        $texto = (string) preg_replace('/[\s$]/u', '', (string) $valor);
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace(['.', ','], ['', '.'], $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            $texto = str_replace('.', '', $texto);
        }

        return is_numeric($texto) ? (float) $texto : null;
    }

    /** Posición → orden de dos letras (aa, ab, …), como el admin. */
    private static function letra(int $indice): string
    {
        $letra = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letra = chr(65 + ($n - 1) % 26).$letra;
        }

        return $letra;
    }
}
