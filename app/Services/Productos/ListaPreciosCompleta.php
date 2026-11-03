<?php

declare(strict_types=1);

namespace App\Services\Productos;

use App\Models\Producto;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Lista de precios completa para los clientes (Zona de Clientes → Lista de
 * precios): todos los productos publicados en una sola hoja, sin solapas por
 * rubro, generada en el momento para que siempre esté actualizada.
 *
 * Las listas que prepara BMH (una solapa por rubro) siguen igual; esta es una
 * opción más. Como esas listas, lleva sólo el precio de lista: es la misma
 * para todos los clientes.
 */
final class ListaPreciosCompleta
{
    private const ESTADOS = [1 => 'Nuevo', 2 => 'Reconstruido'];

    public function generar(): Spreadsheet
    {
        $productos = Producto::query()
            ->with('categoria:id,nombre')
            // Publicados: todo salvo los ocultos (estado 0), como en el catálogo.
            ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
            ->get(['id', 'codigo', 'nombre', 'categoria_id', 'estado', 'precio'])
            // Por rubro y, dentro de cada uno, por código (A2 antes que A10).
            ->sort(fn (Producto $a, Producto $b) => strcasecmp((string) $a->categoria?->nombre, (string) $b->categoria?->nombre)
                ?: strnatcasecmp((string) $a->codigo, (string) $b->codigo))
            ->values();

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Lista de precios');
        $hoja->fromArray(['Código', 'Producto', 'Rubro', 'Estado', 'Precio de lista'], null, 'A1');

        $fila = 2;
        foreach ($productos as $producto) {
            // Texto explícito: si no, Excel le saca los ceros a «0094».
            $hoja->setCellValueExplicit("A{$fila}", (string) $producto->codigo, DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", (string) $producto->nombre);
            $hoja->setCellValue("C{$fila}", (string) ($producto->categoria?->nombre ?? ''));
            $hoja->setCellValue("D{$fila}", self::ESTADOS[(int) $producto->estado] ?? '');
            $hoja->setCellValue("E{$fila}", round((float) $producto->precio(), 2));
            $fila++;
        }

        $ultimaFila = max(1, $fila - 1);

        // Encabezado destacado, fijo al bajar y con filtros (por rubro, estado…).
        $hoja->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0098DA']],
        ]);
        $hoja->freezePane('A2');
        $hoja->setAutoFilter("A1:E{$ultimaFila}");
        $hoja->getStyle("E2:E{$ultimaFila}")->getNumberFormat()->setFormatCode('"$" #,##0.00');
        foreach (['A' => 16, 'B' => 70, 'C' => 30, 'D' => 15, 'E' => 18] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        return $libro;
    }
}
