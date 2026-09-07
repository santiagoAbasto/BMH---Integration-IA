<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\CatalogSemanticLayer;
use App\Domain\Catalog\CatalogFamilyResolver;
use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Catalog\Legacy\ProductImageService;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Search\DTO\SearchQuery;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\ImageAnalysis;
use App\Services\Ai\Support\ImageAnalysisSchema;
use Illuminate\Support\Facades\Log;

/**
 * Convierte una foto en candidatos reales del catálogo.
 *
 * El embudo, en orden de autoridad decreciente:
 *
 *   1. CÓDIGO LEÍDO      → búsqueda exacta / normalizada / por equivalencia.
 *                          Es un match DURO: la base confirma o descarta.
 *   2. RUBRO + ATRIBUTOS → filtro estructurado sobre lo que la visión observó
 *                          (terminales, pines, dientes, voltaje impreso…).
 *   3. COMPARACIÓN VISUAL → recién con pocos candidatos, se le muestran al
 *                          modelo la foto del cliente y las fotos de catálogo
 *                          para que ordene por parecido.
 *
 * El paso 3 va último a propósito. Mandarle 5.054 imágenes a un modelo sería
 * caro, lento y peor: la similitud visual entre dos rotores es altísima y no
 * discrimina. Sirve para desempatar 4 candidatos, no para buscar entre miles.
 *
 * Y en ningún caso la foto decide sola: el resultado entra al ranking como una
 * señal más, con el peso más bajo de la tabla (`vision_similarity`), muy por
 * debajo de un código exacto.
 *
 * @see docs/ai-architecture.md §"Visión"
 */
final class VisionMatchService
{
    /** Cuántos candidatos como máximo se mandan a comparar visualmente. */
    /**
     * Desde qué confianza de la visión se le cree a la foto por encima de un
     * código leído que apunta a otra familia de piezas.
     */
    private const CONFIANZA_PARA_DUDAR_DEL_CODIGO = 0.7;

    /**
     * Cuántas fotos del catálogo se comparan contra la del cliente.
     *
     * Con cuatro se quedaba corto: si la pieza correcta caía sexta en el orden
     * previo —cosa normal cuando lo único seguro es el rubro— la comparación ni
     * la miraba. Las imágenes van en `detail: low`, así que ampliar cuesta poco.
     */
    private const MAX_COMPARACION = 8;



    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly HybridProductSearchService $search,
        private readonly CandidateRankingService $ranking,
        private readonly ProductImageService $images,
        private readonly CatalogFamilyResolver $familias,
    ) {
    }

    /**
     * @return array{
     *     candidates: list<Candidate>,
     *     strategy: string,
     *     code_hits: list<string>,
     *     compared: bool,
     *     notes: list<string>
     * }
     */
    public function match(
        ImageAnalysis $analysis,
        SearchQuery $baseQuery,
        ?AiProviderInterface $provider = null,
        ?string $customerImagePath = null,
    ): array {
        $notes            = [];
        $codigoDescartado = false;
        $notaDelCodigo    = null;

        if (! $analysis->imageUsable) {
            return [
                'candidates' => [],
                'strategy'   => 'unusable_image',
                'code_hits'  => [],
                'compared'   => false,
                'notes'      => [$analysis->unusableReason ?? 'La foto no permite identificar la pieza.'],
            ];
        }

        /*
         * El rubro se resuelve primero, aunque el código venga después en el
         * orden de autoridad: sirve para completar el código y para descartar
         * aciertos de otra familia.
         */
        $rubros = $baseQuery->categoryIds !== [] ? $baseQuery->categoryIds : $this->resolverRubros($analysis);

        // --- 1. Códigos leídos: la señal más fuerte -----------------------
        [$porCodigo, $codigosQueExisten, $codigoEfectivo] = $this->buscarPorCodigosLeidos($analysis, $rubros);

        $porCodigo = $this->familias->preferirDelRubro($porCodigo, $rubros);

        /*
         * Un código leído por OCR es una interpretación, no un hecho.
         *
         * El hecho es que ese código existe en el catálogo; que sea EL de esta
         * pieza es otra cosa. Un dígito mal leído no devuelve "nada", devuelve
         * OTRO artículo, y con confianza máxima: una foto de un regulador de 5
         * pines se leyó como "1175" —dice 17705— y el catálogo contestó, muy
         * seguro de sí, con un inducido de arranque para Dodge.
         *
         * Cuando la foto muestra claramente qué tipo de pieza es y el código
         * apunta a otra familia, la equivocada es la lectura. Se descarta y se
         * sigue con lo que la foto sí muestra, que acá era mucho: regulador,
         * cinco pines.
         */
        if ($porCodigo !== [] && $this->elCodigoContradiceLaFoto($porCodigo, $rubros, $analysis)) {
            $notes[] = sprintf(
                'Leí algo parecido a %s, pero en el catálogo ese código es otra pieza, no %s. '
                . 'Debo haber leído mal algún número, así que busco por lo que se ve en la foto.',
                $this->enumerar(array_slice($codigosQueExisten, 0, 2)),
                $analysis->partType !== null ? 'la que se ve acá' : 'la de la foto',
            );

            $porCodigo         = [];
            $codigosQueExisten = [];
            $codigoDescartado  = true;
            $notaDelCodigo     = array_key_last($notes);
        }

        if ($porCodigo !== []) {
            $query = clone $baseQuery;
            $query->code       = $codigoEfectivo ?? $codigosQueExisten[0] ?? null;
            $query->fromVision = true;

            $notes[] = sprintf(
                'Leí %s en la pieza y %s en el catálogo.',
                $this->enumerar($codigosQueExisten),
                count($codigosQueExisten) === 1 ? 'está' : 'están',
            );

            return [
                'candidates' => $this->ranking->rank($porCodigo, $query),
                'strategy'   => 'vision_code',
                'code_hits'  => $codigosQueExisten,
                'compared'   => false,
                'notes'      => $notes,
            ];
        }

        // Si el código ya se descartó por contradecir la foto, no hay que
        // volver a hablar de él: ya se explicó, y decir además que "no figura"
        // sería confuso —figuraba, pero era otra pieza—.
        if ($analysis->hasCode() && ! $codigoDescartado) {
            $notes[] = sprintf(
                'Leí %s en la pieza, pero no figura en el catálogo de BMH.',
                $this->enumerar(array_slice($analysis->visibleCodes, 0, 3)),
            );
        }

        // --- 2. Rubro + atributos observados ------------------------------
        $query = $this->construirQuery($analysis, $baseQuery, $rubros);

        if ($query->isEmpty()) {
            return [
                'candidates' => [],
                'strategy'   => 'vision_insufficient',
                'code_hits'  => [],
                'compared'   => false,
                'notes'      => array_merge($notes, [
                    'Con esta foto no puedo determinar el rubro. Un código grabado o el tipo de pieza me alcanzaría.',
                ]),
            ];
        }

        $candidates = $this->search->search($query, true);

        /*
         * --- 2.b El número más parecido dentro del rubro -------------------
         *
         * El OCR sobre dígitos estampados en bajorrelieve se come caracteres:
         * en una pieza que dice 17705 leyó "1775". Dentro del rubro correcto la
         * diferencia de un carácter no es ambigua, y preguntarlo es lo que haría
         * cualquiera en el mostrador —"¿no será 17705?"— en vez de dar la
         * consulta por perdida.
         *
         * Se ofrece como sugerencia, no como certeza: sube al principio de la
         * lista pero no llega a la confianza de un código bien leído.
         */
        $sugeridos = $this->sugerirPorCodigoParecido($analysis, $rubros, $candidates);

        if ($sugeridos !== []) {
            $candidates = $sugeridos['candidates'];

            /*
             * Si ya se había avisado que el código no cerraba, esta nota lo
             * reemplaza: son la misma noticia y decirla dos veces, con dos
             * explicaciones distintas del mismo número, sólo confunde.
             */
            if ($notaDelCodigo !== null) {
                $notes[$notaDelCodigo] = $sugeridos['note'];
            } else {
                $notes[] = $sugeridos['note'];
            }
        }

        /*
         * Sin rubro reconocido y sin atributos, lo único que queda es el
         * `part_type` como texto libre. A veces alcanza —"bendix" aparece en
         * nombres de producto aunque no sea un rubro— pero si tampoco devuelve
         * nada, la foto sencillamente no dio información buscable y hay que
         * decirlo en vez de fingir una búsqueda.
         */
        if ($candidates === [] && $query->categoryIds === [] && $query->attributes === []) {
            return [
                'candidates' => [],
                'strategy'   => 'vision_insufficient',
                'code_hits'  => [],
                'compared'   => false,
                'notes'      => array_merge($notes, [
                    'Veo la pieza pero no logro ubicarla en el catálogo. Si tiene algún número grabado o me decís de qué equipo salió, la encuentro.',
                ]),
            ];
        }

        /*
         * --- 3. Comparación visual de los MEJORES candidatos ---------------
         *
         * No es un filtro, es un desempate. Corre siempre que haya foto y
         * candidatos, pero sólo contra los primeros del ranking: comparar 24
         * imágenes costaría 6 veces más y no serviría más, porque los que están
         * abajo ya perdieron por señales más duras que el parecido visual.
         *
         * El costo queda acotado a MAX_COMPARACION imágenes, sin importar si el
         * rubro tenía 5 candidatos o 400.
         */
        $compared = false;

        if ($provider !== null && $customerImagePath !== null && $candidates !== []) {
            $comparados = $this->compararImagenes($provider, $customerImagePath, $candidates);

            if ($comparados !== null) {
                $candidates = $comparados['candidates'];
                $compared   = true;

                if ($comparados['note'] !== null) {
                    $notes[] = $comparados['note'];
                }
            }
        }

        /*
         * Cuando el número no se pudo leer, pedirlo es lo que más acorta el
         * camino: el cliente tiene la pieza en la mano y ahí está grabado.
         *
         * En esta familia hay piezas gemelas —dos reguladores Bosch de 24V y 5
         * pines son indistinguibles en una foto— así que insistir con la imagen
         * no va a resolverlo nunca, y adivinar entre dos que se ven iguales es
         * justamente lo que no hay que hacer.
         */
        if (count($candidates) > 1 && (! $analysis->hasReliableCode() || $codigoDescartado)) {
            $notes[] = sprintf(
                'Tengo %d que encajan con lo que se ve. Estas piezas se distinguen por el número grabado, '
                . 'que suele estar en la cara del cuerpo, no en la del disipador: ¿me lo pasás, o mandás '
                . 'una foto de ese lado?',
                count($candidates),
            );
        } elseif (! $compared && count($candidates) > self::MAX_COMPARACION) {
            $notes[] = sprintf(
                'Hay %d piezas parecidas. Con un dato más te digo cuál es.',
                count($candidates),
            );
        }

        return [
            'candidates' => $candidates,
            'strategy'   => $compared ? 'vision_compared' : 'vision_attributes',
            'code_hits'  => [],
            'compared'   => $compared,
            'notes'      => $notes,
        ];
    }

    /**
     * Prueba cada código leído contra el catálogo: exacto, normalizado,
     * equivalencia y —último recurso— parcial.
     *
     * @return array{0: list<ProductView>, 1: list<string>, 2: string|null}
     */
    private function buscarPorCodigosLeidos(ImageAnalysis $analysis, array $rubros = []): array
    {
        if (! $analysis->hasReliableCode()) {
            return [[], [], null];
        }

        /** @var array<int, ProductView> $encontrados */
        $encontrados = [];
        $aciertos    = [];

        // El código que hay que usar para rankear, que no siempre es el que se
        // leyó: en la pieza dice "17705" y en el catálogo es "REG17705".
        $efectivo = null;

        foreach ($analysis->visibleCodes as $codigo) {
            $delCodigo = [];

            foreach ([
                'exacto'      => fn (): array => $this->catalog->findByCode($codigo),
                'normalizado' => fn (): array => $this->catalog->findByNormalizedCode($codigo),
                // En la pieza suele venir grabado sólo el número; el catálogo lo
                // guarda con la familia adelante. "17705" leído en un regulador
                // es REG17705, y así deja de ser una coincidencia parcial que
                // arrastra la polea POL17705 del mismo número.
                'prefijo'     => fn (): array => $this->familias->completarCodigo($codigo, $rubros),
                'equivalente' => fn (): array => $this->catalog->findByEquivalence($codigo, 10),
            ] as $nombre => $intento) {
                $delCodigo = $intento();

                if ($delCodigo !== []) {
                    // Reconstruir el código no es una coincidencia parcial: es
                    // el código exacto, escrito como lo escribe BMH. El ranking
                    // tiene que verlo así o el acierto queda en confianza baja.
                    if ($nombre === 'prefijo') {
                        $efectivo ??= $delCodigo[0]->code;
                    }

                    break;
                }
            }

            // El OCR se come caracteres: si el código completo no da, se prueba
            // como fragmento. "0120450025" mal leído sigue teniendo una raíz útil.
            if ($delCodigo === [] && mb_strlen(CatalogSemanticLayer::normalizeCode($codigo)) >= 5) {
                $delCodigo = $this->catalog->searchByPartialCode($codigo, 8);
            }

            if ($delCodigo === []) {
                continue;
            }

            $aciertos[] = $codigo;

            foreach ($delCodigo as $producto) {
                $encontrados[$producto->id] ??= $producto;
            }
        }

        $publicos = array_values(array_filter(
            $encontrados,
            static fn (ProductView $p): bool => $p->condition->isPublic(),
        ));

        return [$publicos, array_values(array_unique($aciertos)), $efectivo];
    }

    /** Arma la búsqueda estructurada a partir de lo que la visión observó. */
    private function construirQuery(ImageAnalysis $analysis, SearchQuery $baseQuery, array $rubros = []): SearchQuery
    {
        $query = clone $baseQuery;
        $query->fromVision = true;

        // El rubro primero: es lo que define qué atributos existen siquiera para
        // esta familia de piezas.
        if ($query->categoryIds === []) {
            $query->categoryIds = $rubros !== [] ? $rubros : $this->resolverRubros($analysis);
        }

        /*
         * Los atributos observados se suman a los que ya había en memoria; los
         * de la conversación mandan, porque el cliente los confirmó.
         *
         * Se normaliza el conjunto ENTERO, no sólo la mitad que vino de la foto.
         * La memoria guarda lo que se observó en turnos anteriores tal cual se
         * dijo, así que por ahí volvía a entrar lo que ya habíamos descartado:
         * un `voltage: "28V"` —que es la tensión de regulación grabada en la
         * pieza, no un valor que el catálogo use, donde los reguladores son de
         * 12V o 24V— dejaba la búsqueda en cero y arrastraba consigo al filtro
         * de pines, que era el bueno.
         */
        $observados = $this->normalizarAtributos($analysis->attributes, $query->categoryIds);

        $query->attributes = $this->normalizarAtributos(
            array_merge($observados, $query->attributes),
            $query->categoryIds,
        );

        /*
         * Se deja anotado cuáles salieron de mirar la foto y no de la boca del
         * cliente. Con la ficha vista de canto el modelo contó 3 pines donde hay
         * 5: si eso descarta, la pieza correcta desaparece del listado y no hay
         * pregunta que la recupere. Observar no puede borrar; sólo ordenar.
         */
        $query->observedAttributes = array_values(array_diff(
            array_keys($observados),
            array_keys(array_diff_key($query->attributes, $observados)),
        ));

        /*
         * La marca sólo se acepta si EXISTE en el catálogo.
         *
         * El modelo lee cualquier texto del encuadre, no sólo el de la pieza:
         * con una foto sobre el mostrador de BMH leyó el "B.M.H" escrito en la
         * madera y lo tomó como marca. Filtrar por eso deja la búsqueda en cero,
         * porque ningún producto tiene marca "BMH" —BMH es el vendedor—.
         */
        if ($query->brand === null && $analysis->brandGuess !== null) {
            $query->brand = $this->marcaQueExiste($analysis->brandGuess);
        }

        if ($query->rawText === null && $analysis->partType !== null) {
            $query->rawText = $analysis->partType;
        }

        return $query;
    }

    /**
     * Deja sólo atributos con valor COMPARABLE contra la base.
     *
     * El modelo describe en prosa: "2 principales visibles", "terminal de
     * conexión eléctrica tipo ficha". La base guarda "2", "M8", "12v". Un LIKE
     * con la frase entera no encuentra nada y —peor— descarta a todos los
     * candidatos que sí tenían el atributo cargado.
     *
     * Regla: para atributos de conteo/medida se extrae el número; para los de
     * texto se acepta sólo si es corto. Lo demás se tira: es descripción, no
     * dato.
     *
     * Además se descartan los atributos que el RUBRO no tiene definidos. Un
     * motor de arranque no tiene columna de terminales: filtrar por eso deja la
     * búsqueda en cero y arrastra al fallback, que tira también los filtros
     * buenos. Si el rubro no define el dato, el dato no filtra.
     *
     * @param  array<string,string> $atributos
     * @param  list<int>            $rubros
     * @return array<string,string>
     */
    private function normalizarAtributos(array $atributos, array $rubros = []): array
    {
        $limpios = [];
        $slotsDelRubro = $this->slotsDe($rubros);

        foreach ($atributos as $clave => $valor) {
            $slot = \App\Domain\Catalog\LegacyAttributeMap::slotForKey((string) $clave);

            if ($slot === null) {
                continue;
            }

            if ($slotsDelRubro !== [] && ! in_array($slot, $slotsDelRubro, true)) {
                continue;
            }

            $tipo  = \App\Domain\Catalog\LegacyAttributeMap::type($slot);
            $valor = trim($valor);

            if (in_array($tipo, [
                \App\Domain\Catalog\LegacyAttributeMap::TYPE_COUNT,
                \App\Domain\Catalog\LegacyAttributeMap::TYPE_DIMENSION,
                \App\Domain\Catalog\LegacyAttributeMap::TYPE_ELECTRICAL,
            ], true)) {
                if (preg_match('/\d+(?:[.,]\d+)?/', $valor, $m) !== 1) {
                    continue; // "2 principales visibles" → 2 ; "varios" → se descarta
                }

                $limpios[$clave] = str_replace(',', '.', $m[0]);
                continue;
            }

            // Texto: una frase descriptiva no sirve para filtrar.
            $palabras = count(preg_split('/\s+/', $valor) ?: []);

            if ($valor !== '' && $palabras <= 3 && mb_strlen($valor) <= 24) {
                $limpios[$clave] = $valor;
            }
        }

        return $this->losQueElCatalogoReconoce($limpios, $rubros);
    }

    /**
     * Deja sólo los atributos cuyo valor EXISTE en el rubro.
     *
     * Que el rubro tenga la columna no significa que el valor observado sea uno
     * de los que ahí se usan. El modelo describió la ficha como "5 pines" y el
     * catálogo las nombra "LIN", "L", "DFM": el filtro no achica la búsqueda, la
     * deja en cero, y entonces salta el respaldo que devuelve el rubro entero
     * —perdiendo también los filtros que sí servían—.
     *
     * Un atributo bueno como "5 pines" contamina así el resultado de "5 pines"
     * bien puesto en la columna PINES. Por eso se verifica cada valor por
     * separado antes de dejarlo entrar.
     *
     * @param  array<string,string> $atributos
     * @param  list<int>            $rubros
     * @return array<string,string>
     */
    private function losQueElCatalogoReconoce(array $atributos, array $rubros): array
    {
        if ($rubros === [] || $atributos === []) {
            return $atributos;
        }

        $reconocidos = [];

        foreach ($atributos as $clave => $valor) {
            if ($this->catalog->searchByAttributes([$clave => $valor], $rubros, 1) !== []) {
                $reconocidos[$clave] = $valor;
            }
        }

        return $reconocidos;
    }

    /**
     * Slots con etiqueta en alguno de los rubros dados.
     *
     * @param  list<int> $rubros
     * @return list<int>
     */
    private function slotsDe(array $rubros): array
    {
        $slots = [];

        foreach ($rubros as $id) {
            foreach ($this->catalog->category($id)?->attributeSlots ?? [] as $slot) {
                $slots[$slot] = true;
            }
        }

        return array_keys($slots);
    }

    /**
     * Devuelve la marca sólo si figura en el catálogo.
     *
     * Mismo principio que con los rubros: lo que la base no reconoce no es un
     * hecho y no puede filtrar.
     */
    private function marcaQueExiste(string $marca): ?string
    {
        $marca = mb_strtoupper(trim($marca));

        if ($marca === '' || mb_strlen($marca) < 2) {
            return null;
        }

        /*
         * Comparación por palabras enteras, no por substring: hay marcas de dos
         * o tres letras en el catálogo y con `str_contains` cualquier texto las
         * "contenía". "ACME MOTORS" no puede darse por válida porque alguna
         * marca corta aparezca adentro.
         */
        $palabrasLeidas = $this->familias->palabras($marca);

        if ($palabrasLeidas === []) {
            return null;
        }

        foreach ($this->catalog->brands() as $real) {
            $palabrasReales = $this->familias->palabras($real);

            if ($palabrasReales === []) {
                continue;
            }

            // Una de las dos tiene que estar entera dentro de la otra: "BOSCH"
            // vale para "TIPO BOSCH", pero "MOTORS" no valida "ACME MOTORS"
            // salvo que exista una marca cuyo nombre completo sea ése.
            if ($palabrasLeidas === $palabrasReales
                || array_intersect($palabrasReales, $palabrasLeidas) === $palabrasReales) {
                return $marca;
            }
        }

        return null;
    }

    /**
     * Acerca los códigos del rubro que se parecen al que se leyó.
     *
     * @param  list<int>       $rubros
     * @param  list<Candidate> $candidates
     * @return array{candidates: list<Candidate>, note: string}|array{}
     */
    private function sugerirPorCodigoParecido(ImageAnalysis $analysis, array $rubros, array $candidates): array
    {
        if ($rubros === [] || $analysis->visibleCodes === []) {
            return [];
        }

        foreach ($analysis->visibleCodes as $leido) {
            $parecidos = $this->familias->codigosParecidos((string) $leido, $rubros);

            if ($parecidos === []) {
                continue;
            }

            $elegido = $parecidos[0];

            // Si ya estaba en la lista se lo empuja arriba; si no, se lo trae.
            $encontrado = null;

            foreach ($candidates as $i => $candidate) {
                if ($candidate->product->code === $elegido) {
                    $encontrado = $i;
                    break;
                }
            }

            if ($encontrado === null) {
                $productos = $this->catalog->findByCode($elegido);

                if ($productos === []) {
                    continue;
                }

                $nuevos = $this->ranking->rank($productos, new SearchQuery(code: $elegido));

                if ($nuevos === []) {
                    continue;
                }

                $candidate  = $nuevos[0];
                $candidates = [$candidate, ...$candidates];
            } else {
                $candidate  = $candidates[$encontrado];
                $candidates = [$candidate, ...array_values(array_filter(
                    $candidates,
                    static fn (Candidate $c): bool => $c->product->code !== $elegido,
                ))];
            }

            $candidate->addSignal('code_near_match', (float) config('bmh.ranking.weights.partial_code', 35.0), 0.6);
            $candidate->matchedOn[] = 'número parecido al leído';

            return [
                'candidates' => $candidates,
                'note'       => sprintf(
                    'Leí "%s" en la pieza, pero ese número no da con lo que muestra la foto. '
                    . 'El más parecido que tenemos es %s. ¿Puede ser ese?',
                    $leido,
                    $elegido,
                ),
            ];
        }

        return [];
    }

    /**
     * ¿Lo que devolvió el código contradice lo que muestra la foto?
     *
     * Sólo se lo toma como contradicción cuando la foto es clara: si el modelo
     * no sabe bien qué pieza es, el código sigue siendo lo mejor que hay y el
     * equivocado bien puede ser el rubro.
     *
     * @param  list<ProductView> $encontrados
     * @param  list<int>         $rubros
     */
    private function elCodigoContradiceLaFoto(array $encontrados, array $rubros, ImageAnalysis $analysis): bool
    {
        if ($rubros === [] || $analysis->confidence < self::CONFIANZA_PARA_DUDAR_DEL_CODIGO) {
            return false;
        }

        // Los atributos que la base reconoce como valores suyos: lo que la foto
        // mostró y el catálogo sabe comparar.
        $observado = $this->normalizarAtributos($analysis->attributes, $rubros);

        foreach ($encontrados as $producto) {
            if (! $this->contradice($producto, $rubros, $observado)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ¿Este producto contradice lo que se ve en la foto?
     *
     * Dos formas de contradecir, y las dos aparecieron con fotos reales:
     *
     * - **Otra familia.** Se leyó "1175" en un regulador y el catálogo devolvió
     *   un inducido de arranque para Dodge.
     * - **Otro valor.** Se leyó "1775", que completado da REG40019: un regulador
     *   —el rubro coincide— pero de 2 pines y 12V, cuando en la foto se cuentan
     *   5 pines. Acá la familia no alcanza para darse cuenta; hay que mirar lo
     *   que se contó.
     *
     * La regla es asimétrica, como en el resto de la búsqueda: si el producto
     * tiene el dato cargado y no coincide, contradice; si no lo tiene cargado,
     * no dice nada y no se lo culpa por eso.
     *
     * @param  list<int>             $rubros
     * @param  array<string,string>  $observado
     */
    private function contradice(ProductView $producto, array $rubros, array $observado): bool
    {
        if (! in_array($producto->category?->id, $rubros, true)) {
            return true;
        }

        foreach ($producto->attributes as $atributo) {
            $esperado = $observado[$atributo->key] ?? null;

            if ($esperado === null || trim($atributo->value) === '') {
                continue;
            }

            if (! $this->mismoValor($atributo->value, $esperado)) {
                return true;
            }
        }

        return false;
    }

    /** Compara por número cuando los dos lo tienen; si no, por texto plegado. */
    private function mismoValor(string $delCatalogo, string $observado): bool
    {
        $unNumero  = preg_match('/-?\d+(?:[.,]\d+)?/', $delCatalogo, $a) === 1
            ? (float) str_replace(',', '.', $a[0]) : null;
        $otroNumero = preg_match('/-?\d+(?:[.,]\d+)?/', $observado, $b) === 1
            ? (float) str_replace(',', '.', $b[0]) : null;

        if ($unNumero !== null && $otroNumero !== null) {
            return abs($unNumero - $otroNumero) < 0.01;
        }

        $plegar = static fn (string $v): string => mb_strtolower(trim($v));

        return str_contains($plegar($delCatalogo), $plegar($observado))
            || str_contains($plegar($observado), $plegar($delCatalogo));
    }

    /**
     * Traduce los rubros que sugirió la visión a ids reales del catálogo.
     *
     * Se pasa también el `part_type`: a veces la IA no llena `category_hints`
     * pero sí nombra la pieza, y con eso alcanza.
     *
     * @return list<int>
     */
    private function resolverRubros(ImageAnalysis $analysis): array
    {
        $sugerencias = $analysis->categoryHints;

        if ($analysis->partType !== null) {
            $sugerencias[] = $analysis->partType;
        }

        return $this->familias->rubros($sugerencias);
    }

    /**
     * "A", "A" y "B", "A", "B" y "C" — para que la nota se lea como la diría
     * una persona y no como un array.
     *
     * @param list<string> $items
     */
    private function enumerar(array $items): string
    {
        $items = array_values(array_map(static fn (string $i): string => '"' . $i . '"', $items));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $ultimo = array_pop($items);

        return implode(', ', $items) . ' y ' . $ultimo;
    }

    /**
     * Segunda pasada multimodal: la foto del cliente contra las de catálogo.
     *
     * @param  list<Candidate> $candidates
     * @return array{candidates: list<Candidate>, note: ?string}|null
     */
    private function compararImagenes(
        AiProviderInterface $provider,
        string $customerImagePath,
        array $candidates,
    ): ?array {
        // Sólo candidatos que tengan imagen en disco: comparar contra un
        // placeholder no dice nada.
        $conImagen = [];

        foreach ($candidates as $candidate) {
            $archivo = $candidate->product->primaryImage();

            if ($archivo === null) {
                continue;
            }

            $ruta = $this->images->absolutePath($archivo);

            if (is_file($ruta)) {
                $conImagen[] = ['candidate' => $candidate, 'path' => $ruta];
            }

            if (count($conImagen) >= self::MAX_COMPARACION) {
                break;
            }
        }

        if (count($conImagen) < 2) {
            return null;
        }

        try {
            $resultado = $provider->compareImages(
                $customerImagePath,
                array_map(static fn (array $c): string => $c['path'], $conImagen),
                ImageAnalysisSchema::comparisonSystemPrompt(),
                ImageAnalysisSchema::comparisonJsonSchema(),
            );
        } catch (\Throwable $e) {
            Log::warning('bmh.ai.vision.compare_failed', ['exception' => $e::class]);

            return null;
        }

        if ($resultado === []) {
            return null;
        }

        $similitudes = [];

        foreach ((array) ($resultado['ranking'] ?? []) as $fila) {
            $ref = (int) ($fila['ref'] ?? -1);

            if (isset($conImagen[$ref])) {
                $similitudes[$ref] = max(0.0, min(1.0, (float) ($fila['similarity'] ?? 0)));
            }
        }

        $mejor = $resultado['best_match'];

        if (is_int($mejor) && isset($conImagen[$mejor])) {
            $similitudes[$mejor] = max(
                $similitudes[$mejor] ?? 0.0,
                max(0.0, min(1.0, (float) ($resultado['similarity'] ?? 0))),
            );
        }

        if ($similitudes === []) {
            return ['candidates' => $candidates, 'note' => 'Comparé las fotos pero ninguna coincide con claridad.'];
        }

        // La similitud entra como una señal más del ranking, con su peso bajo.
        // No reordena por sí sola: si otro candidato tiene un código exacto,
        // sigue ganando.
        $peso = (float) config('bmh.ranking.weights.vision_similarity', 3.0);

        foreach ($similitudes as $ref => $similitud) {
            $conImagen[$ref]['candidate']->addSignal('vision_similarity', $peso, $similitud);
            $conImagen[$ref]['candidate']->matchedOn[] = 'parecido visual';
        }

        usort($candidates, static fn (Candidate $a, Candidate $b): int => $b->score <=> $a->score);

        /*
         * El modelo razona sobre "el candidato 3" porque así se le presentaron
         * las fotos. Ese número no significa nada para el cliente y encima no
         * coincide con el orden final, así que se traduce al código de la pieza
         * antes de mostrarlo.
         */
        $razon = trim((string) ($resultado['reason'] ?? ''));

        foreach ($conImagen as $ref => $fila) {
            $razon = preg_replace(
                '/\b(el |la )?candidato\s*' . $ref . '\b/iu',
                'el ' . $fila['candidate']->product->code,
                $razon,
            ) ?? $razon;
        }

        // Si quedó alguna referencia por índice que no supimos traducir, mejor
        // no mostrar la explicación que mostrar una que confunde.
        if (preg_match('/\bcandidato\s*\d/iu', $razon) === 1) {
            $razon = '';
        }

        return [
            'candidates' => $candidates,
            'note'       => $razon === '' ? null : 'Comparando las fotos: ' . mb_substr($razon, 0, 180),
        ];
    }

}
