<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

/**
 * Diccionario semántico de las características de BMH para el asistente.
 *
 * Las características viven en tablas relacionales (`caracteristicas`, `caracteristica_categoria`,
 * `caracteristica_producto`). Antes eran 78 columnas sueltas (`columna_N`): cada «slot» tenía la misma
 * etiqueta en todas las categorías y la migración lo pasó a la característica de ese nombre. El número
 * de slot quedó como id interno de cada definición (lo usan el ranking y la visión); lo que lo une con
 * la base es el NOMBRE de la característica (último elemento), comparado sin tildes ni signos.
 *
 * Si en el admin se renombra una de estas características, hay que actualizar el nombre acá para que
 * el asistente la siga reconociendo.
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
     * slot => [clave canónica, etiqueta mostrable, tipo, unidad|null, nombre de la característica|null]
     *
     * Los slots ausentes (3, 11, 19, 20, 23, 52) y el 39 no tenían etiqueta en ninguna categoría: sus
     * valores no se migraron (no se podían interpretar).
     */
    private const SLOTS = [
        1  => ['voltage',                   'Voltaje',                              self::TYPE_ELECTRICAL, 'V', 'VOLTAJE'],
        2  => ['diameter',                  'Diámetro',                             self::TYPE_DIMENSION, 'mm', 'DIAMETRO'],
        4  => ['pole_mass',                 'Masa polar',                           self::TYPE_TEXT, null, 'MASA POLAR'],
        5  => ['rotation',                  'Giro',                                 self::TYPE_TEXT, null, 'GIRO'],
        6  => ['type',                      'Tipo',                                 self::TYPE_TEXT, null, 'TIPO'],
        7  => ['circuit_type',              'Circuito tipo',                        self::TYPE_TEXT, null, 'CIRCUITO TIPO'],
        8  => ['length',                    'Largo',                                self::TYPE_DIMENSION, 'mm', 'LARGO'],
        9  => ['width',                     'Ancho',                                self::TYPE_DIMENSION, 'mm', 'ANCHO'],
        10 => ['amperes',                   'Amperes',                              self::TYPE_ELECTRICAL, 'A', 'AMPERES'],
        12 => ['collector_ring',            'Aro colector',                         self::TYPE_TEXT,       null, 'ARO COLECTOR'],
        13 => ['teeth',                     'Dientes',                              self::TYPE_COUNT,      null, 'DIENTES'],
        14 => ['splines',                   'Estrías',                              self::TYPE_COUNT,      null, 'ESTRIAS'],
        15 => ['slots',                     'Ranuras',                              self::TYPE_COUNT,      null, 'RANURAS'],
        16 => ['bushing_collector_side',    'Buje lado colector',                   self::TYPE_CROSS_REF,  null, 'BUJE LADO COLECTOR'],
        17 => ['bushing_bendix_side',       'Buje lado bendix',                     self::TYPE_CROSS_REF,  null, 'BUJE LADO BENDIX'],
        18 => ['mounting_diameter',         'Diámetro fijación',                    self::TYPE_DIMENSION,  'mm', 'DIAMETRO FIJACION'],
        21 => ['bearing_collector',         'Rodamiento colector',                  self::TYPE_CROSS_REF,  null, 'RODAMIENTO COLECTOR'],
        22 => ['bearing_pulley',            'Rodamiento polea',                     self::TYPE_CROSS_REF,  null, 'RODAMIENTO POLEA'],
        24 => ['mounting_hole_distance',    'Distancia entre orificios de montaje', self::TYPE_DIMENSION,  'mm', 'DISTANCIA ENTRE ORIFICIOS DE MONTAJE'],
        25 => ['terminals',                 'Terminales',                           self::TYPE_COUNT,      null, 'TERMINALES'],
        26 => ['threads',                   'Roscas',                               self::TYPE_TEXT,       null, 'ROSCAS'],
        27 => ['application',               'Aplicación',                           self::TYPE_TEXT,       null, 'APLICACIÓN'],
        28 => ['height',                    'Altura',                               self::TYPE_DIMENSION,  'mm', 'ALTURA'],
        29 => ['series_type',               'Tipo serie',                           self::TYPE_TEXT,       null, 'TIPO SERIE'],
        30 => ['circuit',                   'Circuito',                             self::TYPE_TEXT,       null, 'CIRCUITO'],
        31 => ['distance',                  'Distancia',                            self::TYPE_DIMENSION,  'mm', 'DISTANCIA'],
        32 => ['pins',                      'Pines',                                self::TYPE_COUNT,      null, 'PINES'],
        33 => ['tolerance',                 'Tolerancia',                           self::TYPE_TEXT,       null, 'TOLERANCIA'],
        34 => ['shielding',                 'Blindaje',                             self::TYPE_TEXT,       null, 'BLINDAJE'],
        35 => ['inner_diameter',            'Diámetro interno',                     self::TYPE_DIMENSION,  'mm', 'DIAMETRO INTERNO'],
        36 => ['outer_diameter',            'Diámetro externo',                     self::TYPE_DIMENSION,  'mm', 'DIAMETRO EXTERNO'],
        37 => ['finish',                    'Terminación',                          self::TYPE_TEXT,       null, 'TERMINACION'],
        38 => ['weight',                    'Peso',                                 self::TYPE_TEXT,       null, 'PESO'],
        39 => ['terminals_alt',             'Terminales',                           self::TYPE_COUNT,      null, null],
        40 => ['quantity',                  'Cantidad',                             self::TYPE_COUNT,      null, 'CANTIDAD'],
        41 => ['function',                  'Función',                              self::TYPE_TEXT,       null, 'FUNCION'],
        42 => ['equiv_unipoint',            'Equivalencia Unipoint',                self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA UNIPOINT'],
        43 => ['equiv_tamatel',             'Equivalencia Tamatel',                 self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA TAMATEL'],
        44 => ['equiv_nosso',               'Equivalencia Nosso',                   self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA NOSSO'],
        45 => ['plug',                      'Ficha',                                self::TYPE_TEXT,       null, 'FICHA'],
        46 => ['band_width',                'Ancho de banda',                       self::TYPE_DIMENSION,  'mm', 'ANCHO DE BANDA'],
        47 => ['fan_diameter',              'Diámetro ventilador',                  self::TYPE_DIMENSION,  'mm', 'DIAMETRO VENTILADOR'],
        48 => ['fan_length',                'Largo ventilador',                     self::TYPE_DIMENSION,  'mm', 'LARGO VENTILADOR'],
        49 => ['total_length',              'Largo total',                          self::TYPE_DIMENSION,  'mm', 'LARGO TOTAL'],
        50 => ['attr_brand',                'Marca',                                self::TYPE_TEXT,       null, 'MARCA'],
        51 => ['equivalence',               'Equivalencia',                         self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA'],
        53 => ['diode_count',               'Cantidad de diodos',                   self::TYPE_COUNT,      null, 'CANTIDAD DE DIODOS'],
        54 => ['diode_type',                'Tipo de diodos',                       self::TYPE_TEXT,       null, 'TIPO DE DIODOS'],
        55 => ['diode_amperage',            'Amperaje de diodos',                   self::TYPE_ELECTRICAL, 'A', 'AMPERAJE DE DIODOS'],
        56 => ['terminal_count',            'Terminales cantidad',                  self::TYPE_COUNT,      null, 'TERMINALES CANTIDAD'],
        57 => ['terminal_description',      'Terminales descripción',               self::TYPE_TEXT,       null, 'TERMINALES DESCRIPCION'],
        58 => ['extra_diode',               'Diodo adicional',                      self::TYPE_TEXT,       null, 'DIODO ADICIONAL'],
        59 => ['pinion_outer_diameter',     'Diámetro externo piñón',               self::TYPE_DIMENSION,  'mm', 'DIAMETRO EXTERNO PIÑON'],
        60 => ['code_zen',                  'Código ZEN',                           self::TYPE_CROSS_REF,  null, 'CODIGO ZEN'],
        61 => ['code_gv',                   'Código GV',                            self::TYPE_CROSS_REF,  null, 'CODIGO GV'],
        62 => ['code_ph',                   'Código PH',                            self::TYPE_CROSS_REF,  null, 'CODIGO PH'],
        63 => ['code_dipra',                'Código DIPRA',                         self::TYPE_CROSS_REF,  null, 'CODIGO DIPRA'],
        64 => ['measures',                  'Medidas',                              self::TYPE_TEXT,       null, 'MEDIDAS'],
        65 => ['bearing_collector_side',    'Rodamiento lado colector',             self::TYPE_CROSS_REF,  null, 'RODAMIENTO LADO COLECTOR'],
        66 => ['bearing_pulley_side',       'Rodamiento lado polea',                self::TYPE_CROSS_REF,  null, 'RODAMIENTO LADO POLEA'],
        67 => ['code_zm',                   'Código ZM',                            self::TYPE_CROSS_REF,  null, 'CODIGO ZM'],
        68 => ['mounting_hole_diameter',    'Diámetro de orificios de montaje',     self::TYPE_DIMENSION,  'mm', 'DIAMETRO DE ORIFICIOS DE MONTAJE'],
        69 => ['carbon',                    'Carbón',                               self::TYPE_CROSS_REF,  null, 'CARBON'],
        70 => ['equivalence_alt',           'Equivalencia',                         self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA'],
        71 => ['pars',                      'PARS',                                 self::TYPE_TEXT,       null, 'PARS'],
        72 => ['equiv_bmh',                 'Equivalencia BMH',                     self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA BMH'],
        73 => ['equiv_nf',                  'Equivalencia NF',                      self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA NF'],
        74 => ['outputs',                   'Salidas',                              self::TYPE_COUNT,      null, 'SALIDAS'],
        75 => ['equiv_new_armature',        'Equivalencia inducido nuevo',          self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA INDUCIDO NUEVO'],
        76 => ['equiv_new_stator',          'Equivalencia estator nuevo',           self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA ESTATOR NUEVO'],
        77 => ['equiv_new_rotor',           'Equivalencia rotor nuevo',             self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA ROTOR NUEVO'],
        78 => ['equiv_new_solenoid',        'Equivalencia solenoide nuevo',         self::TYPE_CROSS_REF,  null, 'EQUIVALENCIA SOLENOIDE NUEVO'],
    ];

    /** Slots sin etiqueta en ninguna categoría: valores no interpretables. */
    public const ORPHAN_SLOTS = [3, 11, 19, 20, 23, 52];

    /** El total de slots físicos del esquema legacy. */
    public const MAX_SLOT = 78;

    /** @return array<int, array{0:string,1:string,2:string,3:?string,4:?string}> */
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

    /** El nombre de la característica (tabla `caracteristicas`) que corresponde al slot. */
    public static function nombreCaracteristica(int $slot): ?string
    {
        return self::SLOTS[$slot][4] ?? null;
    }

    /**
     * El slot de una característica por su nombre (sin tildes, mayúsculas ni signos: «Diámetro» =
     * «DIAMETRO»), o null si el asistente no la conoce. EQUIVALENCIA estaba en dos slots: vale el primero.
     */
    public static function slotDeCaracteristica(?string $nombre): ?int
    {
        static $porClave = null;
        if ($porClave === null) {
            $porClave = [];
            foreach (self::SLOTS as $slot => $definicion) {
                if ($definicion[4] !== null) {
                    $porClave[\App\Support\Caracteristicas\MigracionCaracteristicas::clave($definicion[4])] ??= $slot;
                }
            }
        }

        return $porClave[\App\Support\Caracteristicas\MigracionCaracteristicas::clave($nombre)] ?? null;
    }

    /**
     * @deprecated Columna física vieja. Sólo la usa el comando bmh:normalize-catalog-attributes (de otro
     *             desarrollo), mientras las columnas existan.
     */
    public static function column(int $slot): string
    {
        // `categorias` tiene el slot 39 con C mayúscula. Cortesía del esquema legacy.
        return 'columna_' . $slot;
    }

    /** @deprecated Como column(). */
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
