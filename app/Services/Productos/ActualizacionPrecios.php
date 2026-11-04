<?php

declare(strict_types=1);

namespace App\Services\Productos;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Admin → Productos → «Actualizar desde Excel: Precios». El Excel trae el
 * código en la columna A y el precio en la B (la fila 1 son los encabezados).
 *
 * Además de actualizar, arma un reporte: cuántos productos quedaron con el
 * precio del Excel y qué filas no se pudieron aplicar y por qué. Cuando un
 * código no existe pero hay uno igual salvo puntos, guiones, espacios o ceros
 * a la izquierda («70.505» → «70505»), lo sugiere; no lo aplica solo, para no
 * cambiarle el precio a un producto equivocado.
 */
final class ActualizacionPrecios
{
    public const NO_EXISTE = 'El código no existe en la web';
    public const SIN_PRECIO = 'La fila no tiene precio';
    public const PRECIO_INVALIDO = 'El precio no es un número válido';
    public const PRECIO_NEGATIVO = 'El precio es negativo';
    public const SIN_CODIGO = 'La fila no tiene código';

    /**
     * @return array{archivo: string, fecha: string, filas: int, actualizados: int, sin_cambios: int,
     *               no_actualizados: list<array{fila: int, codigo: string, precio: string, motivo: string, sugerencia: ?string}>}
     */
    public function aplicar(string $ruta, string $nombreArchivo): array
    {
        $hoja = IOFactory::load($ruta)->getActiveSheet();
        // Los códigos como se ven en la celda (respeta formatos como «00094»);
        // los precios con su valor real (un «$ 1.234,50» formateado es 1234.5).
        $vistos = $hoja->toArray(null, true, true, false);
        $valores = $hoja->toArray(null, true, false, false);

        [$porCodigo, $porParecido] = $this->productos();

        $reporte = [
            'archivo' => $nombreArchivo,
            'fecha' => now()->format('d/m/Y H:i'),
            'filas' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'no_actualizados' => [],
        ];
        $cambios = [];

        foreach ($vistos as $i => $fila) {
            if ($i === 0) {
                continue; // encabezados
            }

            $codigo = self::texto($fila[0] ?? null);
            $precioEscrito = self::texto($fila[1] ?? null);
            if ($codigo === '' && $precioEscrito === '') {
                continue; // fila vacía
            }

            $reporte['filas']++;
            $numeroFila = $i + 1;
            $falla = function (string $motivo, ?string $sugerencia = null) use (&$reporte, $numeroFila, $codigo, $precioEscrito): void {
                $reporte['no_actualizados'][] = [
                    'fila' => $numeroFila, 'codigo' => $codigo, 'precio' => $precioEscrito, 'motivo' => $motivo, 'sugerencia' => $sugerencia,
                ];
            };

            if ($codigo === '') {
                $falla(self::SIN_CODIGO);
                continue;
            }

            $productos = $porCodigo[mb_strtoupper($codigo)] ?? null;
            if ($productos === null) {
                $parecidos = $porParecido[self::parecido($codigo)] ?? [];
                $falla(self::NO_EXISTE, count($parecidos) === 1 ? $parecidos[0] : null);
                continue;
            }

            if ($precioEscrito === '') {
                $falla(self::SIN_PRECIO);
                continue;
            }
            $precio = self::numero($valores[$i][1] ?? null);
            if ($precio === null) {
                $falla(self::PRECIO_INVALIDO);
                continue;
            }
            if ($precio < 0) {
                $falla(self::PRECIO_NEGATIVO);
                continue;
            }

            $reporte['actualizados']++;
            $precio = round($precio, 2);
            $distintos = array_filter($productos, fn (array $p) => $p['precio'] === null || round($p['precio'], 2) !== $precio);
            if ($distintos === []) {
                $reporte['sin_cambios']++;
            }
            foreach ($distintos as $p) {
                $cambios[$p['id']] = $precio; // si el código se repite en el Excel, vale la última fila
            }
        }

        DB::transaction(function () use ($cambios): void {
            foreach ($cambios as $id => $precio) {
                DB::table('productos')->where('id', $id)->update(['precio' => $precio]);
            }
        });

        return $reporte;
    }

    /** Excel con las filas que no se actualizaron, para corregirlas. */
    public function excelNoActualizados(array $reporte): Spreadsheet
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('No actualizados');
        $hoja->fromArray(['Fila del Excel', 'Código', 'Precio', 'Motivo', 'Código parecido en la web'], null, 'A1');

        $fila = 2;
        foreach ($reporte['no_actualizados'] as $item) {
            $hoja->setCellValue("A{$fila}", $item['fila']);
            $hoja->setCellValueExplicit("B{$fila}", $item['codigo'], DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("C{$fila}", $item['precio'], DataType::TYPE_STRING);
            $hoja->setCellValue("D{$fila}", $item['motivo']);
            $hoja->setCellValueExplicit("E{$fila}", (string) $item['sugerencia'], DataType::TYPE_STRING);
            $fila++;
        }

        $hoja->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0098DA']],
        ]);
        $hoja->freezePane('A2');
        foreach (['A' => 14, 'B' => 22, 'C' => 16, 'D' => 34, 'E' => 26] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        return $libro;
    }

    /**
     * @return array{0: array<string, list<array{id: int, precio: ?float}>>, 1: array<string, list<string>>}
     *         productos por código exacto (sin distinguir mayúsculas) y códigos por «parecido»
     */
    private function productos(): array
    {
        $porCodigo = [];
        $porParecido = [];
        foreach (DB::table('productos')->get(['id', 'codigo', 'precio']) as $p) {
            $codigo = trim((string) $p->codigo);
            if ($codigo === '') {
                continue;
            }
            $porCodigo[mb_strtoupper($codigo)][] = ['id' => (int) $p->id, 'precio' => $p->precio === null ? null : (float) $p->precio];
            $clave = self::parecido($codigo);
            if (! in_array($codigo, $porParecido[$clave] ?? [], true)) {
                $porParecido[$clave][] = $codigo;
            }
        }

        return [$porCodigo, $porParecido];
    }

    /** «70.505», «70-505», «070 505» → «70505»: sólo letras y números, sin ceros adelante. */
    private static function parecido(string $codigo): string
    {
        $limpio = (string) preg_replace('/[^\pL\pN]+/u', '', mb_strtoupper($codigo));

        return ltrim($limpio, '0') ?: $limpio;
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
}
