<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

/** Lo que pasó al optimizar una imagen: si se escribió el WebP, y por qué no. */
final class ResultadoOptimizacion
{
    public const CON_PERDIDA = 'con pérdida';

    public const SIN_PERDIDA = 'sin pérdida';

    private function __construct(
        public readonly bool $optimizada,
        public readonly string $motivo,
        public readonly int $bytesOriginales,
        public readonly int $bytesNuevos = 0,
        public readonly int $ancho = 0,
        public readonly int $alto = 0,
        public readonly ?string $modo = null,
        public readonly ?int $calidad = null,
        public readonly ?float $psnr = null,
    ) {
    }

    public static function optimizada(int $bytesOriginales, int $bytesNuevos, int $ancho, int $alto, string $modo, ?int $calidad, ?float $psnr): self
    {
        return new self(true, 'ok', $bytesOriginales, $bytesNuevos, $ancho, $alto, $modo, $calidad, $psnr);
    }

    public static function omitida(string $motivo, int $bytesOriginales = 0): self
    {
        return new self(false, $motivo, $bytesOriginales);
    }

    /** Porcentaje ahorrado sobre el original (0 si no se optimizó). */
    public function ahorro(): float
    {
        if (! $this->optimizada || $this->bytesOriginales === 0) {
            return 0.0;
        }

        return 100 * (1 - $this->bytesNuevos / $this->bytesOriginales);
    }
}
