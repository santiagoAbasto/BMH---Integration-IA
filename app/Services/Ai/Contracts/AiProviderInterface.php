<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\DTO\AiMessage;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\ImageAnalysis;

/**
 * Contrato de proveedor de IA.
 *
 * La aplicación no se acopla a Gemini ni a OpenAI. Se programa contra esta
 * interfaz y el provider concreto se elige por configuración. Sin API key,
 * el manager devuelve MockAiProvider y la demo funciona igual.
 */
interface AiProviderInterface
{
    public function name(): string;

    /**
     * Conversación con tool calling.
     *
     * @param  list<AiMessage>       $messages
     * @param  list<array<string,mixed>> $tools definiciones JSON-Schema
     */
    public function chat(array $messages, array $tools = [], array $options = []): AiResponse;

    /**
     * Análisis de una imagen. Devuelve características observadas, NUNCA una
     * identificación de catálogo: eso lo decide la aplicación contra la base.
     *
     * @param string $imagePath ruta absoluta a la versión optimizada
     */
    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis;

    /**
     * Extracción estructurada validada contra un JSON Schema.
     *
     * @param  array<string,mixed> $schema
     * @return array<string,mixed>
     */
    public function structuredOutput(string $prompt, array $schema, array $options = []): array;

    /** @return list<float> */
    public function embed(string $text): array;

    /** ¿Está utilizable ahora mismo? (key presente, feature flag encendido) */
    public function isAvailable(): bool;
}
