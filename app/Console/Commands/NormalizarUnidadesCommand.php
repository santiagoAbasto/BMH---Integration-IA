<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Catalogo\NormalizadorUnidades;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Unifica voltaje y diámetro ya cargados: «12», «12V», «12 V» → «12v»;
 * «60», «60mm» → «60MM» (el formato de la carga nueva). Lo que no es un
 * número queda igual y va a un CSV para revisarlo a mano.
 */
class NormalizarUnidadesCommand extends Command
{
    protected $signature = 'catalogo:normalizar-unidades
        {--dry-run : Sólo informa qué cambiaría; no toca nada}';

    protected $description = 'Unifica el formato de voltaje (12v) y diámetro (60MM) de los productos ya cargados';

    public function handle(NormalizadorUnidades $normalizador): int
    {
        $aplicar = ! $this->option('dry-run');
        $resultado = $normalizador->normalizar($aplicar);

        $prefijo = $aplicar ? '' : '[prueba, no se cambió nada] ';
        $this->info(sprintf('%s%d valores en columnas y %d en características %s · %d ya estaban bien',
            $prefijo, $resultado['columnas'], $resultado['caracteristicas'], $aplicar ? 'unificados' : 'a unificar', $resultado['ya_estaban']));

        if ($resultado['revisar'] !== []) {
            $ruta = storage_path('app/unidades-a-revisar-'.now()->format('Y-m-d_His').'.csv');
            $csv = fopen($ruta, 'w');
            fwrite($csv, "\xEF\xBB\xBF"); // que Excel lea los acentos
            fputcsv($csv, ['Código', 'Producto', 'Campo', 'Valor'], ';');
            foreach ($resultado['revisar'] as $fila) {
                fputcsv($csv, [$fila['codigo'], $fila['nombre'], $fila['campo'], $fila['valor']], ';');
            }
            fclose($csv);
            $this->warn(count($resultado['revisar']).' valores no son un número y quedaron como estaban (rangos, medidas dobles…). Para revisarlos a mano: '.$ruta);
        }

        return self::SUCCESS;
    }
}
