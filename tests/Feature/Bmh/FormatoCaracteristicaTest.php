<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Support\Caracteristicas\FormatoCaracteristica;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Cómo se escribe el valor de una característica según su configuración. */
final class FormatoCaracteristicaTest extends TestCase
{
    /** @return array<string, array{FormatoCaracteristica, ?string, ?string}> */
    public static function casos(): array
    {
        $voltaje = new FormatoCaracteristica(true, true, 'V');
        $diametro = new FormatoCaracteristica(true, true, 'MM');
        $texto = new FormatoCaracteristica();

        return [
            'voltaje en minúscula' => [$voltaje, '12v', '12V'],
            'voltaje con espacio' => [$voltaje, ' 24 V ', '24V'],
            'voltaje doble' => [$voltaje, '12/24v', '12/24V'],
            'voltaje sin unidad' => [$voltaje, '6', '6V'],
            'diámetro con coma y ceros' => [$diametro, '59,50', '59.5MM'],
            'diámetro con unidad' => [$diametro, '046.0 mm', '46MM'],
            'texto a mayúsculas' => [$texto, 'rueda libre ', 'RUEDA LIBRE'],
            'texto con espacios de más' => [$texto, 'C3   /2A', 'C3 /2A'],
            'guion es sin dato' => [$texto, ' - ', null],
            'vacío' => [$voltaje, '', null],
        ];
    }

    #[DataProvider('casos')]
    public function test_normaliza(FormatoCaracteristica $formato, ?string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, $formato->normalizar($entrada, $error));
        $this->assertNull($error);
    }

    public function test_una_numerica_rechaza_texto_pero_al_migrar_lo_conserva(): void
    {
        $voltaje = new FormatoCaracteristica(true, true, 'V');

        $this->assertNull($voltaje->normalizar('380vac 3-fases', $error));
        $this->assertStringContainsString('La unidad V se agrega sola', (string) $error);
        $this->assertSame('380VAC 3-FASES', $voltaje->normalizarSinPerder('380vac 3-fases'));
    }

    public function test_sin_mayusculas_respeta_el_texto(): void
    {
        $this->assertSame('Rueda libre', (new FormatoCaracteristica(false))->normalizar('Rueda libre'));
    }
}
