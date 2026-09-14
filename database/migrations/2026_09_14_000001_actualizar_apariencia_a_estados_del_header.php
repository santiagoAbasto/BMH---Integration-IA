<?php

use App\Models\Apariencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pasa `apariencia` del esquema de un solo juego de colores (scroll + mobile)
     * al de cinco estados del header.
     *
     * La migración de creación se reescribió con el esquema nuevo, pero quien ya
     * la había corrido la tiene marcada como ejecutada y `migrate` no la repite:
     * el editor y el front leían columnas inexistentes y mostraban siempre los
     * valores de fábrica. Esta migración detecta la tabla vieja, la rearma y
     * conserva lo que se había guardado. En una instalación nueva no hace nada.
     */
    public function up(): void
    {
        if (! Schema::hasTable('apariencia') || ! Schema::hasColumn('apariencia', 'header_scroll_fondo')) {
            return;
        }

        $anterior = (array) DB::table('apariencia')->first();

        Schema::rename('apariencia', 'apariencia_anterior');
        (require __DIR__.'/2026_09_11_000001_create_apariencia_table.php')->up();

        $valores = $this->traducir($anterior);
        if ($valores !== []) {
            DB::table('apariencia')->update($valores);
        }

        Schema::drop('apariencia_anterior');
        Apariencia::olvidarCache();
    }

    /** El esquema viejo no se restaura: sus columnas no cubren los estados nuevos. */
    public function down(): void
    {
    }

    /**
     * Columnas viejas → nuevas. El scroll se aplicaba a todas las páginas, así
     * que va a los dos estados con scroll. «Borde y texto» toma el borde, y el
     * hover de los links, que antes no existía, arranca igual que los links.
     * Los estados en reposo no tenían columnas: quedan con los de fábrica.
     *
     * @param  array<string, mixed>  $fila
     * @return array<string, string>
     */
    private function traducir(array $fila): array
    {
        $colores = [
            'fondo' => 'fondo',
            'links' => 'links',
            'links_hover' => 'links',
            'boton' => 'boton_borde',
            'boton_relleno' => 'boton_hover_fondo',
            'boton_hover_texto' => 'boton_hover_texto',
            'logo' => 'logo',
        ];
        $origenes = [
            'transparente_scroll' => 'header_scroll',
            'blanco_scroll' => 'header_scroll',
            'celular' => 'header_mobile',
        ];

        $valores = [];

        foreach ($origenes as $set => $prefijoViejo) {
            foreach ($colores as $nuevo => $viejo) {
                $valor = $fila[$prefijoViejo.'_'.$viejo] ?? null;

                if ($this->valido($nuevo, $valor)) {
                    $valores[Apariencia::campo($set, $nuevo)] = $nuevo === 'logo' ? $valor : strtoupper($valor);
                }
            }
        }

        foreach (Apariencia::CAMPOS_FOOTER as $campo) {
            if ($this->valido($campo, $fila[$campo] ?? null)) {
                $valores[$campo] = strtoupper($fila[$campo]);
            }
        }

        return $valores;
    }

    private function valido(string $color, mixed $valor): bool
    {
        if (! is_string($valor)) {
            return false;
        }

        return $color === 'logo'
            ? in_array($valor, Apariencia::LOGOS, true)
            : (bool) preg_match('/^#[0-9A-F]{6}$/i', $valor);
    }
};
