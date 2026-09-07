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
     * Compara la foto del cliente contra varias fotos de catálogo.
     *
     * Se usa como último paso del embudo visual, con pocos candidatos ya
     * filtrados por la base. Cada imagen de catálogo va numerada por su índice
     * en `$catalogImagePaths`, y eso es lo que devuelve `best_match`.
     *
     * @param  list<string>        $catalogImagePaths rutas absolutas
     * @param  array<string,mixed> $schema
     * @return array<string,mixed>
     */
    public function compareImages(
        string $customerImagePath,
        array $catalogImagePaths,
        string $systemPrompt,
        array $schema,
    ): array;

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
