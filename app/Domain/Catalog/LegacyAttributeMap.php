<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

/**
 * Diccionario del EAV posicional de BMH.
 *
 * En la base legacy, `categorias.columna_N` guarda la ETIQUETA del atributo y
 * `productos.columna_N` guarda el VALOR. Durante el discovery se verificó que
 * cada slot tiene una única etiqueta en las 25 categorías: el slot es
 * globalmente consistente, así que un solo diccionario alcanza.
 *
 * Este es el ÚNICO lugar del sistema que sabe que existe `columna_57`.
 * De acá para arriba todo habla de "TERMINALES DESCRIPCION".
 *
 * @see docs/database-discovery.md §2
 */
final class LegacyAttributeMap
{
    public const TYPE_DIMENSION  = 'dimension';
    public const TYPE_ELECTRICAL = 'electrical';
    public const TYPE_COUNT      = 'count';
    public const TYPE_CROSS_REF  = 'cross_reference';
    public const TYPE_TEXT       = 'text';

    /**
     * slot => [clave canónica, etiqueta mostrable, tipo, unidad|null]
     *
     * Los slots ausentes (3, 11, 19, 20, 23, 52) no tienen etiqueta en ninguna
     * categoría. Si traen valor, es un dato huérfano no interpretable: se
     * reporta en el data audit y no se expone al asistente.
     */
    private const SLOTS = [
        1  => ['voltage',                   'Voltaje',                              self::TYPE_ELECTRICAL, 'V'],
        2  => ['diameter',                  'Diámetro',                             self::TYPE_DIMENSION,  'mm'],
        4  => ['pole_mass',                 'Masa polar',                           self::TYPE_TEXT,       null],
        5  => ['rotation',                  'Giro',                                 self::TYPE_TEXT,       null],
        6  => ['type',                      'Tipo',                                 self::TYPE_TEXT,       null],
        7  => ['circuit_type',              'Circuito tipo',                        self::TYPE_TEXT,       null],
        8  => ['length',                    'Largo',                                self::TYPE_DIMENSION,  'mm'],
        9  => ['width',                     'Ancho',                                self::TYPE_DIMENSION,  'mm'],
        10 => ['amperes',                   'Amperes',                              self::TYPE_ELECTRICAL, 'A'],
        12 => ['collector_ring',            'Aro colector',                         self::TYPE_TEXT,       null],
        13 => ['teeth',                     'Dientes',                              self::TYPE_COUNT,      null],
        14 => ['splines',                   'Estrías',                              self::TYPE_COUNT,      null],
        15 => ['slots',                     'Ranuras',                              self::TYPE_COUNT,      null],
        16 => ['bushing_collector_side',    'Buje lado colector',                   self::TYPE_CROSS_REF,  null],
        17 => ['bushing_bendix_side',       'Buje lado bendix',                     self::TYPE_CROSS_REF,  null],
        18 => ['mounting_diameter',         'Diámetro fijación',                    self::TYPE_DIMENSION,  'mm'],
        21 => ['bearing_collector',         'Rodamiento colector',                  self::TYPE_CROSS_REF,  null],
        22 => ['bearing_pulley',            'Rodamiento polea',                     self::TYPE_CROSS_REF,  null],
        24 => ['mounting_hole_distance',    'Distancia entre orificios de montaje', self::TYPE_DIMENSION,  'mm'],
        25 => ['terminals',                 'Terminales',                           self::TYPE_COUNT,      null],
        26 => ['threads',                   'Roscas',                               self::TYPE_TEXT,       null],
        27 => ['application',               'Aplicación',                           self::TYPE_TEXT,       null],
        28 => ['height',                    'Altura',                               self::TYPE_DIMENSION,  'mm'],
        29 => ['series_type',               'Tipo serie',                           self::TYPE_TEXT,       null],
        30 => ['circuit',                   'Circuito',                             self::TYPE_TEXT,       null],
        31 => ['distance',                  'Distancia',                            self::TYPE_DIMENSION,  'mm'],
        32 => ['pins',                      'Pines',                                self::TYPE_COUNT,      null],
        33 => ['tolerance',                 'Tolerancia',                           self::TYPE_TEXT,       null],
        34 => ['shielding',                 'Blindaje',                             self::TYPE_TEXT,       null],
        35 => ['inner_diameter',            'Diámetro interno',                     self::TYPE_DIMENSION,  'mm'],
        36 => ['outer_diameter',            'Diámetro externo',                     self::TYPE_DIMENSION,  'mm'],
        37 => ['finish',                    'Terminación',                          self::TYPE_TEXT,       null],
        38 => ['weight',                    'Peso',                                 self::TYPE_TEXT,       null],
        39 => ['terminals_alt',             'Terminales',                           self::TYPE_COUNT,      null],
        40 => ['quantity',                  'Cantidad',                             self::TYPE_COUNT,      null],
        41 => ['function',                  'Función',                              self::TYPE_TEXT,       null],
        42 => ['equiv_unipoint',            'Equivalencia Unipoint',                self::TYPE_CROSS_REF,  null],
        43 => ['equiv_tamatel',             'Equivalencia Tamatel',                 self::TYPE_CROSS_REF,  null],
        44 => ['equiv_nosso',               'Equivalencia Nosso',                   self::TYPE_CROSS_REF,  null],
        45 => ['plug',                      'Ficha',                                self::TYPE_TEXT,       null],
        46 => ['band_width',                'Ancho de banda',                       self::TYPE_DIMENSION,  'mm'],
        47 => ['fan_diameter',              'Diámetro ventilador',                  self::TYPE_DIMENSION,  'mm'],
        48 => ['fan_length',                'Largo ventilador',                     self::TYPE_DIMENSION,  'mm'],
        49 => ['total_length',              'Largo total',                          self::TYPE_DIMENSION,  'mm'],
        50 => ['attr_brand',                'Marca',                                self::TYPE_TEXT,       null],
        51 => ['equivalence',               'Equivalencia',                         self::TYPE_CROSS_REF,  null],
        53 => ['diode_count',               'Cantidad de diodos',                   self::TYPE_COUNT,      null],
        54 => ['diode_type',                'Tipo de diodos',                       self::TYPE_TEXT,       null],
        55 => ['diode_amperage',            'Amperaje de diodos',                   self::TYPE_ELECTRICAL, 'A'],
        56 => ['terminal_count',            'Terminales cantidad',                  self::TYPE_COUNT,      null],
        57 => ['terminal_description',      'Terminales descripción',               self::TYPE_TEXT,       null],
        58 => ['extra_diode',               'Diodo adicional',                      self::TYPE_TEXT,       null],
        59 => ['pinion_outer_diameter',     'Diámetro externo piñón',               self::TYPE_DIMENSION,  'mm'],
        60 => ['code_zen',                  'Código ZEN',                           self::TYPE_CROSS_REF,  null],
        61 => ['code_gv',                   'Código GV',                            self::TYPE_CROSS_REF,  null],
        62 => ['code_ph',                   'Código PH',                            self::TYPE_CROSS_REF,  null],
        63 => ['code_dipra',                'Código DIPRA',                         self::TYPE_CROSS_REF,  null],
        64 => ['measures',                  'Medidas',                              self::TYPE_TEXT,       null],
        65 => ['bearing_collector_side',    'Rodamiento lado colector',             self::TYPE_CROSS_REF,  null],
        66 => ['bearing_pulley_side',       'Rodamiento lado polea',                self::TYPE_CROSS_REF,  null],
        67 => ['code_zm',                   'Código ZM',                            self::TYPE_CROSS_REF,  null],
        68 => ['mounting_hole_diameter',    'Diámetro de orificios de montaje',     self::TYPE_DIMENSION,  'mm'],
        69 => ['carbon',                    'Carbón',                               self::TYPE_CROSS_REF,  null],
        70 => ['equivalence_alt',           'Equivalencia',                         self::TYPE_CROSS_REF,  null],
        71 => ['pars',                      'PARS',                                 self::TYPE_TEXT,       null],
        72 => ['equiv_bmh',                 'Equivalencia BMH',                     self::TYPE_CROSS_REF,  null],
        73 => ['equiv_nf',                  'Equivalencia NF',                      self::TYPE_CROSS_REF,  null],
        74 => ['outputs',                   'Salidas',                              self::TYPE_COUNT,      null],
        75 => ['equiv_new_armature',        'Equivalencia inducido nuevo',          self::TYPE_CROSS_REF,  null],
        76 => ['equiv_new_stator',          'Equivalencia estator nuevo',           self::TYPE_CROSS_REF,  null],
        77 => ['equiv_new_rotor',           'Equivalencia rotor nuevo',             self::TYPE_CROSS_REF,  null],
        78 => ['equiv_new_solenoid',        'Equivalencia solenoide nuevo',         self::TYPE_CROSS_REF,  null],
    ];

    /** Slots sin etiqueta en ninguna categoría: valores no interpretables. */
    public const ORPHAN_SLOTS = [3, 11, 19, 20, 23, 52];

    /** El total de slots físicos del esquema legacy. */
    public const MAX_SLOT = 78;

    /** @return array<int, array{0:string,1:string,2:string,3:?string}> */
    public static function all(): array
    {
        return self::SLOTS;
    }

    public static function has(int $slot): bool
    {
        return isset(self::SLOTS[$slot]);
    }

    public static function key(int $slot): ?string
    {
        return self::SLOTS[$slot][0] ?? null;
    }

    public static function label(int $slot): ?string
    {
        return self::SLOTS[$slot][1] ?? null;
    }

    public static function type(int $slot): string
    {
        return self::SLOTS[$slot][2] ?? self::TYPE_TEXT;
    }

    public static function unit(int $slot): ?string
    {
        return self::SLOTS[$slot][3] ?? null;
    }

    /** Nombre físico de la columna. El único lugar donde se arma. */
    public static function column(int $slot): string
    {
        // `categorias` tiene el slot 39 con C mayúscula. Cortesía del esquema legacy.
        return 'columna_' . $slot;
    }

    public static function categoryLabelColumn(int $slot): string
    {
        return $slot === 39 ? 'Columna_39' : 'columna_' . $slot;
    }

    /** @return list<int> */
    public static function slots(): array
    {
        return array_keys(self::SLOTS);
    }

    /** Slots cuyo valor es un código cruzado a otra pieza o proveedor. */
    public static function crossReferenceSlots(): array
    {
        return array_keys(array_filter(
            self::SLOTS,
            static fn (array $d): bool => $d[2] === self::TYPE_CROSS_REF
        ));
    }

    /** Resuelve una clave canónica ("voltage") al slot que la contiene. */
    public static function slotForKey(string $key): ?int
    {
        foreach (self::SLOTS as $slot => $definition) {
            if ($definition[0] === $key) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Mapea un término suelto del cliente ("diámetro", "12v", "estrias") a la
     * clave canónica. Se usa para convertir lo que extrae la IA en filtros
     * estructurados reales.
     */
    public static function resolveTerm(string $term): ?string
    {
        $normalized = self::normalize($term);

        foreach (self::SLOTS as $definition) {
            if (self::normalize($definition[1]) === $normalized) {
                return $definition[0];
            }
            if ($definition[0] === $normalized) {
                return $definition[0];
            }
        }

        return self::SYNONYMS[$normalized] ?? null;
    }

    /** Cómo habla el cliente vs. cómo se llama el atributo. */
    private const SYNONYMS = [
        'tension'          => 'voltage',
        'volt'             => 'voltage',
        'volts'            => 'voltage',
        'voltios'          => 'voltage',
        'amperaje'         => 'amperes',
        'amper'            => 'amperes',
        'amp'              => 'amperes',
        'diametro externo' => 'outer_diameter',
        'diametro interno' => 'inner_diameter',
        'diam'             => 'diameter',
        'largo total'      => 'total_length',
        'longitud'         => 'length',
        'estria'           => 'splines',
        'diente'           => 'teeth',
        'ranura'           => 'slots',
        'pin'              => 'pins',
        'terminal'         => 'terminals',
        'uso'              => 'application',
        'vehiculo'         => 'application',
        'auto'             => 'application',
        'sentido de giro'  => 'rotation',
    ];

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
