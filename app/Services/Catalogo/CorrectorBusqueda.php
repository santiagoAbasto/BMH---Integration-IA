<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

use App\Services\BuscadorCatalogo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Errores de tipeo en lo buscado: «alternadro» → «alternador», «inducdo» →
 * «inducido». Compara cada palabra con las que aparecen en el catálogo
 * (nombres, marcas, rubros y vehículos) y, si no está, la cambia por la más
 * parecida que sí está.
 *
 * Sólo se usa cuando la búsqueda no encontró nada: corregir algo que ya
 * trae resultados le cambiaría la búsqueda a quien escribió bien.
 */
final class CorrectorBusqueda
{
    /** Palabras más cortas no se corrigen: con una letra de diferencia ya son otra palabra. */
    private const MINIMO_LETRAS = 4;

    private const CACHE = 'catalogo:vocabulario:v1';

    /** @var array<string, int>|null */
    private ?array $vocabulario = null;

    /** El texto con las palabras corregidas; igual que antes si no había nada para corregir. */
    public function corregir(string $termino): string
    {
        $palabras = preg_split('/(\s+)/u', trim($termino), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        return implode('', array_map(fn (string $p) => $this->corregirPalabra($p), $palabras));
    }

    private function corregirPalabra(string $palabra): string
    {
        $plana = BuscadorCatalogo::plano($palabra);

        // Códigos, medidas y palabras cortas quedan como están.
        if (mb_strlen($plana) < self::MINIMO_LETRAS || preg_match('/^[a-z]+$/', $plana) !== 1) {
            return $palabra;
        }

        $vocabulario = $this->vocabulario();
        $actual = $vocabulario[$plana] ?? 0;

        // Hasta 1 letra de diferencia, 2 en las palabras largas; y la misma
        // primera letra, que casi nunca es la que se escribe mal.
        $tolerancia = mb_strlen($plana) >= 8 ? 2 : 1;
        // Una palabra que el catálogo tiene se respeta, salvo que sea un error
        // de carga: «altenador» está una vez y «alternador» cientos.
        $frecuenciaMinima = $actual === 0 ? 1 : max(20, $actual * 20);
        $mejor = null;
        $mejorDistancia = $tolerancia + 1;
        $mejorFrecuencia = 0;

        foreach ($vocabulario as $candidata => $frecuencia) {
            $candidata = (string) $candidata;
            if ($frecuencia < $frecuenciaMinima || $candidata[0] !== $plana[0]
                || abs(strlen($candidata) - strlen($plana)) > $tolerancia) {
                continue;
            }
            $distancia = levenshtein($plana, $candidata);
            if ($distancia > $tolerancia) {
                continue;
            }
            if ($distancia < $mejorDistancia || ($distancia === $mejorDistancia && $frecuencia > $mejorFrecuencia)) {
                [$mejor, $mejorDistancia, $mejorFrecuencia] = [$candidata, $distancia, $frecuencia];
            }
        }

        return $mejor ?? $palabra;
    }

    /**
     * Palabras del catálogo => cuántas veces aparecen. Se arma una vez por
     * hora: cambia sólo cuando se cargan productos.
     *
     * @return array<string, int>
     */
    private function vocabulario(): array
    {
        return $this->vocabulario ??= Cache::remember(self::CACHE, now()->addHour(), function (): array {
            $textos = DB::table('productos')
                ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
                ->get(['nombre', 'marca', 'modelo'])
                ->flatMap(fn (object $p) => [$p->nombre, $p->marca, $p->modelo])
                ->merge(DB::table('categorias')->pluck('nombre'))
                ->merge(DB::table('aplicaciones')->distinct()->pluck('nombre'));

            $vocabulario = [];
            foreach ($textos as $texto) {
                preg_match_all('/[a-z]{'.self::MINIMO_LETRAS.',}/', BuscadorCatalogo::plano((string) $texto), $palabras);
                foreach ($palabras[0] as $palabra) {
                    $vocabulario[$palabra] = ($vocabulario[$palabra] ?? 0) + 1;
                }
            }

            return $vocabulario;
        });
    }
}
