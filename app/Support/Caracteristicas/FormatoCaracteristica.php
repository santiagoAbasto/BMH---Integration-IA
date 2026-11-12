<?php

declare(strict_types=1);

namespace App\Support\Caracteristicas;

/**
 * Cómo se escribe el valor de una característica según su configuración (admin → Características):
 *  - mayúsculas: el texto se guarda en mayúsculas (hoy, todas);
 *  - numérica + unidad: sólo números (o «12/24»), con coma o punto, y la unidad al final siempre igual
 *    (VOLTAJE «12 v» → «12V», DIÁMETRO «59,50» → «59.5MM»).
 * Un valor vacío o «-» (lo que usaban las columnas viejas para «sin dato») no se guarda.
 */
final class FormatoCaracteristica
{
    public function __construct(
        public readonly bool $mayusculas = true,
        public readonly bool $numerica = false,
        public readonly ?string $unidad = null,
    ) {}

    public static function vacio(?string $valor): bool
    {
        $valor = trim((string) $valor);

        return $valor === '' || trim($valor, " -–—") === '';
    }

    /** El valor como se guarda, o null si está vacío. Si es numérica y no es un número, null + $error. */
    public function normalizar(?string $valor, ?string &$error = null): ?string
    {
        $error = null;
        if (self::vacio($valor)) {
            return null;
        }
        $valor = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        if ($this->numerica) {
            $numero = $this->numero($valor);
            if ($numero === null) {
                $error = $this->unidad
                    ? "Tiene que ser un número (o dos separados por «/», como 12/24). La unidad {$this->unidad} se agrega sola."
                    : 'Tiene que ser un número (o dos separados por «/», como 12/24).';

                return null;
            }

            return $numero.($this->unidad ?? '');
        }

        return $this->mayusculas ? mb_strtoupper($valor, 'UTF-8') : $valor;
    }

    /**
     * Para datos que ya estaban cargados: si una numérica no se puede interpretar se conserva el texto (en
     * mayúsculas si corresponde) en lugar de perderlo; queda para revisar en el admin.
     */
    public function normalizarSinPerder(?string $valor): ?string
    {
        $normalizado = $this->normalizar($valor, $error);
        if ($error === null) {
            return $normalizado;
        }
        $valor = trim((string) $valor);

        return $this->mayusculas ? mb_strtoupper($valor, 'UTF-8') : $valor;
    }

    /** «12», «12,5», «12.50 v», «12/24V» → «12», «12.5», «12.5», «12/24» (sin la unidad). */
    private function numero(string $valor): ?string
    {
        $unidad = $this->unidad !== null && $this->unidad !== '' ? '(?:\s*'.preg_quote($this->unidad, '/').')?' : '';
        $num = '\d+(?:[.,]\d+)?';
        if (preg_match('/^('.$num.')\s*(?:\/\s*('.$num.'))?'.$unidad.'$/iu', $valor, $m) !== 1) {
            return null;
        }

        return self::numeroCanonico($m[1]).(isset($m[2]) && $m[2] !== '' ? '/'.self::numeroCanonico($m[2]) : '');
    }

    /**
     * Un mismo número siempre se escribe igual, así en el filtro del catálogo es una sola opción: coma
     * decimal a punto y sin ceros de más («89,50» → 89.5, «12.0» → 12, «012» → 12).
     */
    public static function numeroCanonico(string $numero): string
    {
        $numero = str_replace(',', '.', trim($numero));
        if (str_contains($numero, '.')) {
            $numero = rtrim(rtrim($numero, '0'), '.');
        }
        $numero = ltrim($numero, '0');

        return $numero === '' || $numero[0] === '.' ? '0'.$numero : $numero;
    }

    /** El formato en palabras, para el admin: «numérica · se guarda con V», «texto en mayúsculas». */
    public function descripcion(): string
    {
        if ($this->numerica) {
            return $this->unidad !== null ? "numérica · se guarda con {$this->unidad}" : 'numérica';
        }

        return $this->mayusculas ? 'texto en mayúsculas' : 'texto libre';
    }
}
