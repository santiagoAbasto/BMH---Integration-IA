<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Ai\AiKnowledge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Arma el bloque de conocimiento que viaja al prompt del modelo.
 *
 * Es el corazón de la base de conocimiento: toma lo que el equipo de BMH enseñó
 * y aprobó (términos, reglas, notas) y lo convierte en un texto que el asesor LEE
 * y aplica en cada conversación. Así lo enseñado rige al instante, sin reentrenar
 * al proveedor: ése es el "aprendizaje autocontinuo" honesto.
 */
final class KnowledgeBaseService
{
    /**
     * Tope de seguridad para no inflar el prompt. Con la base chica entra todo;
     * cuando crezca, el camino es filtrar por el rubro detectado (ya se acepta
     * $categoryIds) o pasar a recuperación por embeddings (OpenAiProvider::embed).
     *
     * ponytail: inyección completa con tope fijo; filtrar por rubro o RAG cuando
     * la base no entre en el presupuesto de contexto.
     */
    private const MAX_ENTRIES = 150;

    private const MAX_CHARS = 8000;

    private const LABELS = [
        AiKnowledge::KIND_TERM => 'Término',
        AiKnowledge::KIND_RULE => 'Regla',
        AiKnowledge::KIND_NOTE => 'Nota',
    ];

    /**
     * Bloque Markdown con el conocimiento aprobado y activo, o '' si no hay nada.
     *
     * @param  list<int>  $categoryIds  si llega, además de las globales incluye las
     *                                  entradas acotadas a esos rubros
     */
    public function promptSection(array $categoryIds = []): string
    {
        $query = AiKnowledge::query()->approved()->active();

        if ($categoryIds !== []) {
            $query->where(static function ($q) use ($categoryIds): void {
                $q->whereNull('category_id')->orWhereIn('category_id', $categoryIds);
            });
        }

        // El conocimiento es aditivo: si la consulta falla (por ejemplo, la tabla
        // todavía no migró en la ventana de un deploy), el asesor sigue andando
        // sin glosario en vez de caerse en cada turno.
        try {
            $entries = $query->orderByDesc('id')->limit(self::MAX_ENTRIES)->get();
        } catch (\Throwable $e) {
            Log::warning('No se pudo leer la base de conocimiento del asesor.', ['error' => $e->getMessage()]);

            return '';
        }

        if ($entries->isEmpty()) {
            return '';
        }

        $lines = [];
        $chars = 0;

        foreach ($entries as $entry) {
            $label = self::LABELS[$entry->kind] ?? 'Nota';
            $title = trim((string) $entry->title);
            $body = trim((string) $entry->body);
            $line = $title !== ''
                ? "- [{$label}] {$title}: {$body}"
                : "- [{$label}] {$body}";

            $chars += mb_strlen($line);
            if ($chars > self::MAX_CHARS) {
                break;
            }

            $lines[] = $line;
        }

        if ($lines === []) {
            return '';
        }

        return "## Glosario y reglas del negocio (enseñado por BMH)\n\n"
            ."Lo que sigue lo enseñó y aprobó el equipo de BMH. Son equivalencias, reglas y "
            ."notas válidas del negocio: usalas para interpretar lo que dice el cliente y para "
            ."no preguntar de más. No contradice tu regla principal: los códigos, precios, "
            ."stock y compatibilidades siguen saliendo de las herramientas, y una equivalencia "
            ."que no esté en esta lista no la inventes.\n\n"
            .implode("\n", $lines)."\n";
    }

    /**
     * Ejemplos visuales enseñados (Fase 4): entradas aprobadas y activas que
     * tienen fotos, para pasarlas al análisis de la foto del cliente y que el
     * modelo aplique la diferencia que le enseñaron.
     *
     * @param  list<int>  $categoryIds
     * @return list<array{label:string,description:string,images:list<string>}>
     */
    public function visualExamples(array $categoryIds = [], int $limit = 4): array
    {
        $query = AiKnowledge::query()->approved()->active()
            ->where(static function ($q): void {
                $q->whereNotNull('reference_path')->orWhereNotNull('reference_paths');
            });

        if ($categoryIds !== []) {
            $query->where(static function ($q) use ($categoryIds): void {
                $q->whereNull('category_id')->orWhereIn('category_id', $categoryIds);
            });
        }

        try {
            $rows = $query->orderByDesc('id')->limit($limit)->get();
        } catch (\Throwable $e) {
            Log::warning('No se pudieron leer los ejemplos visuales enseñados.', ['error' => $e->getMessage()]);

            return [];
        }

        $disk = Storage::disk(config('bmh.attachments.disk', 'ai_private'));
        $examples = [];

        foreach ($rows as $row) {
            $images = [];
            foreach ($row->imagePaths() as $path) {
                if ($disk->exists($path)) {
                    $images[] = $disk->path($path);
                }
            }

            if ($images === []) {
                continue;
            }

            $examples[] = [
                'label' => (string) $row->title,
                'description' => (string) $row->body,
                'images' => $images,
            ];
        }

        return $examples;
    }
}
