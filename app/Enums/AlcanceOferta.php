<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dónde se ve un producto en oferta (admin → Productos). Sin valor (null) = no está en oferta.
 */
enum AlcanceOferta: string
{
    /** En el sitio público (Ofertas) y en la Zona de Clientes. */
    case Publica = 'publica';

    /** Sólo para clientes con sesión iniciada (Zona de Clientes). */
    case Clientes = 'clientes';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Publica => 'Sitio y Zona de Clientes',
            self::Clientes => 'Sólo Zona de Clientes',
        };
    }

    public function ayuda(): string
    {
        return match ($this) {
            self::Publica => 'La ve cualquiera en Ofertas de la web, y también los clientes.',
            self::Clientes => 'Sólo la ven los clientes con su sesión iniciada.',
        };
    }

    /** Lo que llega de un formulario o del listado: '', '0', null → sin oferta; '1' (el sí/no de antes) → pública. */
    public static function desdeFormulario(mixed $valor): ?self
    {
        $valor = is_string($valor) ? trim($valor) : $valor;

        return match (true) {
            $valor === null, $valor === '', $valor === '0', $valor === 0, $valor === false => null,
            $valor === '1', $valor === 1, $valor === true => self::Publica,
            default => self::tryFrom((string) $valor),
        };
    }
}
