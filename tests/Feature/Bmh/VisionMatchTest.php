<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\VisionMatchService;
use App\Services\Ai\DTO\ImageAnalysis;
use App\Services\Ai\Support\ImageAnalysisSchema;
use Tests\TestCase;

/**
 * El embudo visual: de una foto a candidatos reales del catálogo.
 *
 * El orden de autoridad es lo que se fija acá:
 *   código leído  >  rubro + atributos observados  >  parecido de imagen
 *
 * Una foto nunca decide sola. La base confirma.
 */
final class VisionMatchTest extends TestCase
{
    private function vision(): VisionMatchService
    {
        return app(VisionMatchService::class);
    }

    public function test_un_codigo_leido_que_existe_gana_por_encima_de_todo(): void
    {
        // El modelo leyó "1833" en la pieza pero se equivocó de rubro.
        $analysis = new ImageAnalysis(
            partType: 'plaqueta rectificadora',
            confidence: 0.4,
            detectedText: ['1833', 'WAPSA'],
            visibleCodes: ['1833'],
            categoryHints: ['PLAQUETA RECTIFICADORA'],
            textConfidence: 0.8,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertContains('1833', $resultado['code_hits']);

        // El código manda: aparece el rotor real, no una plaqueta.
        $this->assertSame('1833', $resultado['candidates'][0]->product->code);
        $this->assertSame('ROTORES', $resultado['candidates'][0]->product->category?->name);
    }

    public function test_un_codigo_leido_que_no_existe_se_dice_y_no_se_inventa(): void
    {
        $analysis = new ImageAnalysis(
            partType: 'rotor',
            confidence: 0.6,
            detectedText: ['ZZ99999999'],
            visibleCodes: ['ZZ99999999'],
            categoryHints: ['ROTORES'],
            textConfidence: 0.9,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertNotSame('vision_code', $resultado['strategy']);
        $this->assertSame([], $resultado['code_hits']);

        // Y se le avisa al cliente en vez de callarlo.
        $this->assertNotEmpty(array_filter(
            $resultado['notes'],
            static fn (string $n): bool => str_contains($n, 'no figura en el catálogo'),
        ));
    }

    public function test_sin_codigo_usa_rubro_y_atributos_observados(): void
    {
        $analysis = new ImageAnalysis(
            partType: 'rotor',
            confidence: 0.6,
            attributes: ['voltage' => '24v'],
            categoryHints: ['ROTORES'],
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertContains($resultado['strategy'], ['vision_attributes', 'vision_compared']);
        $this->assertNotEmpty($resultado['candidates']);

        foreach (array_slice($resultado['candidates'], 0, 5) as $candidate) {
            $this->assertSame('ROTORES', $candidate->product->category?->name);
        }
    }

    public function test_un_rubro_que_no_existe_no_arrastra_la_busqueda(): void
    {
        // El modelo puede alucinar rubros. Si la base no lo reconoce, no es un
        // hecho y no puede filtrar.
        $analysis = new ImageAnalysis(
            partType: 'flux capacitor',
            confidence: 0.5,
            categoryHints: ['componentes_de_delorean'],
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_insufficient', $resultado['strategy']);
        $this->assertSame([], $resultado['candidates']);
        $this->assertNotEmpty($resultado['notes']);
    }

    public function test_una_foto_inusable_devuelve_el_motivo_accionable(): void
    {
        $analysis = ImageAnalysis::unusable('Está muy oscura. Probá con más luz o acercá al número grabado.');

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('unusable_image', $resultado['strategy']);
        $this->assertSame([], $resultado['candidates']);
        $this->assertStringContainsString('más luz', $resultado['notes'][0]);
    }

    public function test_rescata_codigos_que_el_modelo_dejo_solo_en_el_texto(): void
    {
        // Pasa seguido: transcribe el número en `detected_text` pero no lo
        // clasifica como código. Es la señal más fuerte que tenemos: se pesca.
        $analysis = ImageAnalysisSchema::hydrate([
            'part_type'     => 'rotor',
            'confidence'    => 0.6,
            'usable'        => true,
            'detected_text' => ['Nº 1833', 'MADE IN ARGENTINA'],
            'visible_codes' => [],
        ]);

        $this->assertContains('1833', $analysis->visibleCodes);
        // "MADE", "IN" y "ARGENTINA" no son códigos: no tienen dígitos.
        $this->assertNotContains('ARGENTINA', $analysis->visibleCodes);
    }

    public function test_no_acepta_atributos_que_no_existen_en_el_catalogo(): void
    {
        $analysis = ImageAnalysisSchema::hydrate([
            'part_type'  => 'rotor',
            'confidence' => 0.7,
            'usable'     => true,
            'attributes' => [
                'voltage'        => '12v',
                'color_favorito' => 'azul',   // inventado
                'sabor'          => 'frutilla',
            ],
        ]);

        $this->assertSame(['voltage' => '12v'], $analysis->attributes);
    }

    public function test_una_foto_ilegible_conserva_el_texto_que_si_se_leyo(): void
    {
        // Aunque la foto no sirva para identificar la pieza, un código legible
        // sigue siendo útil: no se tira.
        $analysis = ImageAnalysisSchema::hydrate([
            'usable'          => false,
            'reason'          => 'Muy borrosa.',
            'detected_text'   => ['REG40016'],
            'visible_codes'   => ['REG40016'],
            'text_confidence' => 0.7,
        ]);

        $this->assertFalse($analysis->imageUsable);
        $this->assertContains('REG40016', $analysis->visibleCodes);
        $this->assertTrue($analysis->hasReliableCode());
    }

    public function test_una_marca_que_no_existe_en_el_catalogo_no_filtra(): void
    {
        /*
         * Caso real: el cliente fotografió un motor de arranque apoyado en el
         * mostrador de BMH y el modelo leyó el "B.M.H" escrito en la madera.
         * BMH es el vendedor, no una marca del catálogo: filtrar por eso dejaba
         * la búsqueda en cero.
         */
        $analysis = new ImageAnalysis(
            partType: 'motor de arranque',
            confidence: 0.95,
            brandGuess: 'BMH',
            categoryHints: ['motores de arranque'],
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertNotEmpty($resultado['candidates'], 'una marca inexistente no puede vaciar la búsqueda');

        foreach (array_slice($resultado['candidates'], 0, 5) as $candidate) {
            $this->assertSame('MOTORES DE ARRANQUE', $candidate->product->category?->name);
        }
    }

    public function test_una_marca_que_si_existe_se_respeta(): void
    {
        $vision = $this->vision();
        $metodo = new \ReflectionMethod($vision, 'marcaQueExiste');
        $metodo->setAccessible(true);

        $this->assertSame('BOSCH', $metodo->invoke($vision, 'Bosch'));
        $this->assertNull($metodo->invoke($vision, 'BMH'));
        $this->assertNull($metodo->invoke($vision, 'ACME MOTORS'));
    }

    public function test_los_atributos_en_prosa_se_reducen_a_lo_comparable(): void
    {
        // El modelo describe; la base guarda valores. "2 principales visibles"
        // nunca va a coincidir con un LIKE, pero el 2 sí sirve.
        $vision = $this->vision();
        $metodo = new \ReflectionMethod($vision, 'normalizarAtributos');
        $metodo->setAccessible(true);

        $limpios = $metodo->invoke($vision, [
            'terminals' => '2 principales visibles',
            'plug'      => 'terminal de conexión eléctrica tipo ficha',
            'voltage'   => '12v',
        ]);

        $this->assertSame('2', $limpios['terminals']);
        $this->assertArrayNotHasKey('plug', $limpios, 'una descripción larga no puede filtrar');
        // El voltaje queda en número: `searchByAttributes` compara los
        // eléctricos numéricamente, así que "12" y "12v" filtran igual.
        $this->assertSame('12', $limpios['voltage']);
    }

    public function test_el_rubro_gana_por_mejor_coincidencia_no_por_compartir_una_palabra(): void
    {
        /*
         * "arranque" aparece en MOTORES DE ARRANQUE, PORTAESCOBILLAS DE ARRANQUE
         * y ESCOBILLAS DE ARRANQUE Y ALTERNADOR. Tomar los tres diluía la
         * búsqueda entre 545 productos de tres familias distintas.
         */
        $analysis = new ImageAnalysis(
            partType: 'motor de arranque',
            confidence: 0.9,
            categoryHints: ['motores de arranque'],
        );

        $vision = $this->vision();
        $metodo = new \ReflectionMethod($vision, 'resolverRubros');
        $metodo->setAccessible(true);

        $ids = $metodo->invoke($vision, $analysis);

        $catalog = app(\App\Domain\Catalog\Contracts\CatalogRepositoryInterface::class);
        $nombres = array_map(static fn (int $id): string => $catalog->category($id)?->name ?? '', $ids);

        $this->assertSame(['MOTORES DE ARRANQUE'], $nombres);
    }

    public function test_una_foto_sin_ningun_codigo_igual_llega_a_candidatos_del_rubro(): void
    {
        // Las tres fotos que mandó el cliente no tenían ni un número legible.
        // Sin código, el rubro y el parecido son todo lo que hay: no puede
        // devolver vacío.
        $analysis = ImageAnalysisSchema::hydrate([
            'part_type'     => 'motor de arranque',
            'confidence'    => 0.95,
            'usable'        => true,
            'brand_guess'   => 'BMH',
            'detected_text' => ['BMH'],
            'visible_codes' => [],
            'attributes'    => ['terminals' => '2 principales visibles'],
            'category_hints' => ['motores de arranque'],
        ]);

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertNotEmpty($resultado['candidates']);
        $this->assertSame([], $resultado['code_hits']);
        $this->assertSame('MOTORES DE ARRANQUE', $resultado['candidates'][0]->product->category?->name);
    }

    public function test_se_fusiona_lo_visto_en_todas_las_fotos(): void
    {
        /*
         * El cliente mandó tres fotos del mismo arranque: una del cuerpo, otra
         * del piñón —donde se cuentan los dientes— y otra de la bornera.
         * Quedarse con la primera tiraba las otras dos.
         */
        $orquestador = app(\App\Services\Ai\ConversationOrchestrator::class);
        $metodo      = new \ReflectionMethod($orquestador, 'firstUsableAnalysis');
        $metodo->setAccessible(true);

        $fusion = $metodo->invoke($orquestador, [
            new ImageAnalysis(
                partType: 'motor de arranque',
                confidence: 0.8,
                detectedText: ['BMH'],
                attributes: ['terminals' => '2'],
                categoryHints: ['motores de arranque'],
            ),
            new ImageAnalysis(
                partType: 'motor de arranque (piñón)',
                confidence: 0.95,
                detectedText: ['REG40016'],
                visibleCodes: ['REG40016'],
                attributes: ['teeth' => '9'],
            ),
            ImageAnalysis::unusable('Salió movida.'),
        ]);

        $this->assertNotNull($fusion);

        // Los dientes venían sólo de la segunda foto y el terminal de la primera.
        $this->assertSame('9', $fusion->attributes['teeth']);
        $this->assertSame('2', $fusion->attributes['terminals']);

        // El código lo vio una sola foto y es lo más valioso que hay: no se pierde.
        $this->assertContains('REG40016', $fusion->visibleCodes);
        $this->assertContains('BMH', $fusion->detectedText);

        // La identidad la pone la foto que mejor se vio.
        $this->assertSame('motor de arranque (piñón)', $fusion->partType);
        $this->assertContains('motores de arranque', $fusion->categoryHints);
    }

    public function test_ante_atributos_contradictorios_gana_la_foto_mas_confiable(): void
    {
        $orquestador = app(\App\Services\Ai\ConversationOrchestrator::class);
        $metodo      = new \ReflectionMethod($orquestador, 'firstUsableAnalysis');
        $metodo->setAccessible(true);

        $fusion = $metodo->invoke($orquestador, [
            new ImageAnalysis(partType: 'arranque', confidence: 0.4, attributes: ['teeth' => '11']),
            new ImageAnalysis(partType: 'arranque', confidence: 0.9, attributes: ['teeth' => '9']),
        ]);

        $this->assertSame('9', $fusion->attributes['teeth']);
    }

    public function test_un_numero_leido_se_completa_con_la_familia_del_rubro(): void
    {
        /*
         * En la pieza está grabado "17705"; el catálogo lo guarda como
         * REG17705, con la familia adelante. El mismo número existe además como
         * POL17705 —una polea— y ofrecer las dos hace dudar de todo lo demás.
         */
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.95,
            detectedText: ['17705', '25C21', '28V'],
            visibleCodes: ['17705'],
            categoryHints: ['regulador de voltaje'],
            textConfidence: 0.9,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertCount(1, $resultado['candidates'], 'la polea del mismo número no es una opción');
        $this->assertSame('REG17705', $resultado['candidates'][0]->product->code);

        // Reconstruir el código no es una coincidencia parcial: es exacto.
        $this->assertSame('very_high', $resultado['candidates'][0]->confidenceBand());

        // Al cliente se le repite lo que está grabado en la pieza, no el código interno.
        $this->assertNotEmpty(array_filter(
            $resultado['notes'],
            static fn (string $n): bool => str_contains($n, '17705'),
        ));
    }

    public function test_un_codigo_completo_no_se_toca(): void
    {
        // Si la pieza ya trae letras, el código está completo: no hay familia
        // que reconstruir ni razón para inventar prefijos.
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.9,
            visibleCodes: ['REG17705'],
            categoryHints: ['regulador de voltaje'],
            textConfidence: 0.9,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertSame('REG17705', $resultado['candidates'][0]->product->code);
    }

    public function test_si_ningun_acierto_cae_en_el_rubro_no_se_descarta_nada(): void
    {
        /*
         * El rubro lo puso la visión y puede estar mal. Si el código acierta
         * pero en otra familia, el equivocado es el rubro: quedarse sin
         * candidatos sería peor que ofrecer el que existe.
         */
        $analysis = new ImageAnalysis(
            partType: 'rotor',
            confidence: 0.5,
            visibleCodes: ['REG17705'],
            categoryHints: ['ROTORES'],
            textConfidence: 0.9,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertNotEmpty($resultado['candidates']);
        $this->assertSame('REG17705', $resultado['candidates'][0]->product->code);
    }

    public function test_contar_los_pines_acerca_la_pieza_sin_leer_ningun_codigo(): void
    {
        // Es el caso de la foto de atrás: no se ve ningún número, pero la ficha
        // sí. De 408 reguladores, sólo 20 tienen 5 pines.
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.9,
            attributes: ['pins' => '5'],
            categoryHints: ['regulador de voltaje'],
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertNotEmpty($resultado['candidates']);

        $codigos = array_map(
            static fn ($c): string => $c->product->code,
            array_slice($resultado['candidates'], 0, 10),
        );

        $this->assertContains('REG17705', $codigos, 'la pieza de la foto tiene que estar entre las más cercanas');
    }

    public function test_un_codigo_que_contradice_la_foto_no_se_cree(): void
    {
        /*
         * Caso real. La pieza dice "17705"; el modelo leyó "1175", que existe en
         * el catálogo pero como INDUCIDO de arranque para Dodge. Un dígito mal
         * leído no devuelve "nada": devuelve OTRA pieza, y encima con confianza
         * máxima por ser código exacto.
         *
         * La foto, en cambio, mostraba clarísimo un regulador de 5 pines. Cuando
         * la foto es clara y el código apunta a otra familia, la equivocada es
         * la lectura.
         */
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.98,
            detectedText: ['1175', '25222134V'],
            visibleCodes: ['1175', '25222134V'],
            attributes: ['pins' => '5', 'plug' => '5 pines'],
            categoryHints: ['reguladores'],
            textConfidence: 0.8,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertNotSame('vision_code', $resultado['strategy']);

        $codigos = array_map(static fn ($c): string => $c->product->code, $resultado['candidates']);

        $this->assertNotContains('1175', $codigos, 'un inducido no es un regulador de 5 pines');

        // Y con lo que la foto sí mostraba, la pieza correcta aparece primera.
        $this->assertSame('REG17705', $resultado['candidates'][0]->product->code);

        // Se le dice al cliente qué pasó, en vez de callarlo o de mentirle
        // diciendo que el código no existe.
        $this->assertNotEmpty(array_filter(
            $resultado['notes'],
            static fn (string $n): bool => str_contains($n, 'leído mal'),
        ));
    }

    public function test_si_la_foto_no_es_clara_el_codigo_sigue_mandando(): void
    {
        // Al revés: si el modelo no sabe bien qué pieza es, el código es lo mejor
        // que hay y el equivocado bien puede ser el rubro.
        $analysis = new ImageAnalysis(
            partType: 'no sé bien qué es',
            confidence: 0.3,
            visibleCodes: ['1175'],
            categoryHints: ['reguladores'],
            textConfidence: 0.9,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertSame('1175', $resultado['candidates'][0]->product->code);
    }

    public function test_un_valor_que_el_rubro_no_usa_no_puede_vaciar_la_busqueda(): void
    {
        /*
         * El modelo describió la ficha como "5 pines"; el catálogo las nombra
         * "LIN", "L", "DFM". Ese filtro no achica: deja en cero, salta el
         * respaldo que devuelve el rubro entero, y se lleva puesto el filtro de
         * PINES que sí servía.
         */
        $vision = $this->vision();
        $metodo = new \ReflectionMethod($vision, 'normalizarAtributos');
        $metodo->setAccessible(true);

        $rubros  = app(\App\Domain\Catalog\CatalogFamilyResolver::class)->rubros(['regulador de voltaje']);
        $limpios = $metodo->invoke($vision, ['pins' => '5', 'plug' => '5 pines'], $rubros);

        $this->assertSame('5', $limpios['pins']);
        $this->assertArrayNotHasKey('plug', $limpios, 'el catálogo no usa "5 pines" como ficha');
    }

    public function test_contar_es_exacto_medir_no(): void
    {
        /*
         * La búsqueda toleraba ±1 en todo valor numérico. Para un diámetro está
         * bien —la base guarda "88.8" y "89" para la misma pieza—, pero para un
         * conteo es fatal: buscando 5 pines devolvía también los de 4, y el de 4
         * llegaba a salir primero. No se cuentan 4 pines y medio.
         */
        $query              = new SearchQuery();
        $query->categoryIds = app(\App\Domain\Catalog\CatalogFamilyResolver::class)->rubros(['regulador de voltaje']);
        $query->attributes  = ['pins' => '5'];
        $query->rawText     = 'regulador de voltaje';

        $candidatos = app(\App\Domain\Search\HybridProductSearchService::class)->search($query, true);

        $this->assertNotEmpty($candidatos);

        foreach ($candidatos as $candidato) {
            $pines = $candidato->product->attribute('pins');

            if ($pines === null || trim($pines->value) === '') {
                continue; // dato faltante: no es contradicción
            }

            $this->assertSame(
                '5',
                trim($pines->value),
                'un regulador de ' . $pines->value . ' pines no puede aparecer buscando 5',
            );
        }
    }

    public function test_lo_recordado_de_turnos_previos_tambien_se_valida(): void
    {
        /*
         * La memoria guarda lo observado tal cual se dijo, y por ahí volvía a
         * entrar lo ya descartado: "28V" es la tensión de regulación grabada en
         * la pieza, no un valor que el catálogo use —los reguladores son de 12V
         * o 24V—. Al filtrar por eso la búsqueda quedaba en cero y el respaldo
         * devolvía el rubro entero, perdiendo el filtro de pines que sí servía.
         */
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.95,
            attributes: ['pins' => '5'],
            categoryHints: ['regulador de voltaje'],
        );

        $memoria             = new SearchQuery();
        $memoria->attributes = ['voltage' => '28V'];

        $vision = $this->vision();
        $metodo = new \ReflectionMethod($vision, 'construirQuery');
        $metodo->setAccessible(true);

        $query = $metodo->invoke($vision, $analysis, $memoria, []);

        $this->assertSame('5', $query->attributes['pins']);
        $this->assertArrayNotHasKey('voltage', $query->attributes, 'ningún regulador del catálogo es de 28V');
    }

    public function test_un_dato_mal_contado_no_descarta_la_pieza_correcta(): void
    {
        /*
         * Caso real: con la ficha vista de canto el modelo contó 3 pines donde
         * hay 5. Como los conteos exigen coincidencia exacta, ese error
         * DESCARTABA el regulador de 5 pines — peor que mostrarlo abajo, porque
         * ya no hay pregunta que lo recupere.
         *
         * Lo que el cliente confirma descarta; lo que una foto sugiere, no.
         *
         * Ojo con lo que esto NO promete: la pieza deja de ser descartada, pero
         * con un conteo equivocado nada la empuja hacia arriba, así que puede
         * quedar sepultada entre decenas de reguladores parecidos. El arreglo de
         * fondo está en no contar lo que no se ve, y en pedirle el número.
         */
        $query                     = new SearchQuery();
        $query->attributes         = ['pins' => '3'];
        $query->observedAttributes = ['pins'];

        $search = app(\App\Domain\Search\HybridProductSearchService::class);
        $metodo = new \ReflectionMethod($search, 'discardContradictions');
        $metodo->setAccessible(true);

        $deCinco = app(\App\Domain\Catalog\Contracts\CatalogRepositoryInterface::class)->findByCode('REG17705');

        $this->assertNotEmpty($deCinco);
        $this->assertSame($deCinco, $metodo->invoke($search, $deCinco, $query));
    }

    public function test_lo_que_el_cliente_confirma_si_descarta(): void
    {
        // Sin marcarlo como observado, el mismo filtro es un hecho y excluye.
        $query              = new SearchQuery();
        $query->categoryIds = app(\App\Domain\Catalog\CatalogFamilyResolver::class)->rubros(['regulador de voltaje']);
        $query->attributes  = ['pins' => '3'];
        $query->rawText     = 'regulador de voltaje';

        $candidatos = app(\App\Domain\Search\HybridProductSearchService::class)->search($query, true);

        foreach ($candidatos as $candidato) {
            $pines = $candidato->product->attribute('pins');

            if ($pines !== null && trim($pines->value) !== '') {
                // La base anota variantes como "3 (S, IG, L)": interesa el número.
                $this->assertSame(3.0, $pines->numeric());
            }
        }
    }

    public function test_ofrece_el_numero_mas_parecido_del_rubro(): void
    {
        /*
         * El OCR sobre dígitos estampados se come caracteres: en una pieza que
         * dice 17705 leyó "1775". Dentro del rubro, la diferencia de un carácter
         * no es ambigua, y preguntarlo es lo que haría cualquiera en el
         * mostrador en vez de dar la consulta por perdida.
         */
        $analysis = new ImageAnalysis(
            partType: 'regulador de voltaje',
            confidence: 0.95,
            detectedText: ['1775'],
            visibleCodes: ['1775'],
            attributes: ['pins' => '5'],
            categoryHints: ['regulador de voltaje'],
            textConfidence: 0.8,
        );

        $resultado = $this->vision()->match($analysis, new SearchQuery());

        $this->assertSame('REG17705', $resultado['candidates'][0]->product->code);

        // Se ofrece como pregunta, no como certeza: falta que el cliente lo confirme.
        $this->assertNotSame('very_high', $resultado['candidates'][0]->confidenceBand());
        $this->assertNotEmpty(array_filter(
            $resultado['notes'],
            static fn (string $n): bool => str_contains($n, 'REG17705') && str_contains($n, '¿Puede ser'),
        ));
    }

    public function test_no_sugiere_parecidos_de_otra_familia(): void
    {
        $familias = app(\App\Domain\Catalog\CatalogFamilyResolver::class);
        $rubros   = $familias->rubros(['regulador de voltaje']);

        foreach ($familias->codigosParecidos('1775', $rubros) as $codigo) {
            $this->assertStringStartsWith('REG', $codigo);
        }

        // Sin rubro no se sugiere nada: sería adivinar entre 5.000 códigos.
        $this->assertSame([], $familias->codigosParecidos('1775', []));
    }
}
