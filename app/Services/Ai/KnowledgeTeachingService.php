<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Ai\AiKnowledge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Enseñar al asesor CONVERSANDO desde el chat de una cuenta habilitada.
 *
 * Toma el mensaje libre («arr. es lo mismo que inducido o bobina de arranque»),
 * le pide al proveedor que lo ordene en una entrada estructurada y la guarda en
 * la base de conocimiento como PENDIENTE. No la activa para todos: un admin la
 * aprueba desde el panel (ver KnowledgeBaseService y AiLearningController).
 */
final class KnowledgeTeachingService
{
    public function __construct(private readonly AiProviderManager $providers)
    {
    }

    /**
     * @return array{entry: ?AiKnowledge, message: string}
     */
    public function teach(string $message, ?string $imagePath, int $customerId): array
    {
        $parsed = $this->parse($message);

        if ($parsed === null) {
            return [
                'entry' => null,
                'message' => 'No me quedó claro qué me querés enseñar. Probá algo como: '
                    .'«arr. es lo mismo que inducido o bobina de arranque», o contame una regla del negocio.',
            ];
        }

        $entry = AiKnowledge::query()->create([
            'kind' => $parsed['kind'],
            'title' => $parsed['title'],
            'body' => $parsed['body'],
            'synonyms' => $parsed['synonyms'] !== '' ? $parsed['synonyms'] : null,
            'reference_path' => $this->storeImage($imagePath),
            'status' => AiKnowledge::STATUS_PENDING,
            'source' => 'demo_chat',
            'created_by' => $customerId,
            'active' => true,
        ]);

        $label = [
            AiKnowledge::KIND_TERM => 'Término',
            AiKnowledge::KIND_RULE => 'Regla',
            AiKnowledge::KIND_NOTE => 'Nota',
        ][$entry->kind] ?? 'Nota';

        return [
            'entry' => $entry,
            'message' => "Lo anoté para aprenderlo:\n[{$label}] {$entry->title}: {$entry->body}\n"
                .'Un administrador de BMH lo revisa y, si lo aprueba, lo uso en todas las conversaciones.',
        ];
    }

    /** @return array{kind:string,title:string,body:string,synonyms:string}|null */
    private function parse(string $message): ?array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'is_teaching' => ['type' => 'boolean'],
                'kind' => ['type' => 'string', 'enum' => ['term', 'rule', 'note']],
                'title' => ['type' => 'string'],
                'body' => ['type' => 'string'],
                'synonyms' => ['type' => 'string'],
            ],
            'required' => ['is_teaching', 'kind', 'title', 'body'],
        ];

        $system = <<<'SYS'
            Sos un asistente que ordena lo que el equipo de BMH Bobinajes le enseña a su asesor técnico.
            BMH repara electricidad del automotor: alternadores, arranques, rotores, inducidos, estatores, reguladores, etc.
            Te llega un mensaje de una persona del equipo. Si está ENSEÑANDO algo —una equivalencia o sinónimo,
            una regla del negocio, o una nota sobre un rubro o producto— devolvé is_teaching=true y:
              - kind: "term" para equivalencias/sinónimos; "rule" para reglas; "note" para notas de rubro o producto.
              - title: un título corto (por ejemplo el término: "arr.").
              - body: la enseñanza redactada clara y completa, en español.
              - synonyms: si es un término, los sinónimos separados por coma; si no, cadena vacía.
            Si el mensaje NO enseña nada (es una pregunta, un pedido de producto, un saludo), devolvé is_teaching=false.
            No inventes nada: usá sólo lo que dice el mensaje.
            SYS;

        try {
            $out = $this->providers->primary()->structuredOutput($message, $schema, ['system' => $system]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo interpretar la enseñanza del chat.', ['error' => $e->getMessage()]);

            return null;
        }

        if (($out['is_teaching'] ?? false) !== true) {
            return null;
        }

        $kind = in_array($out['kind'] ?? '', [AiKnowledge::KIND_TERM, AiKnowledge::KIND_RULE, AiKnowledge::KIND_NOTE], true)
            ? (string) $out['kind']
            : AiKnowledge::KIND_NOTE;
        $title = trim((string) ($out['title'] ?? ''));
        $body = trim((string) ($out['body'] ?? ''));

        if ($title === '' || $body === '') {
            return null;
        }

        return [
            'kind' => $kind,
            'title' => mb_substr($title, 0, 120),
            'body' => mb_substr($body, 0, 2000),
            'synonyms' => mb_substr(trim((string) ($out['synonyms'] ?? '')), 0, 255),
        ];
    }

    private function storeImage(?string $imagePath): ?string
    {
        if ($imagePath === null || ! is_file($imagePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'knowledge/'.Str::uuid().'.'.$ext;
        Storage::disk(config('bmh.attachments.disk', 'ai_private'))->put($path, (string) file_get_contents($imagePath));

        return $path;
    }
}
