<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Models\Ai\AiKnowledge;
use Illuminate\Support\Facades\Log;

/**
 * Expande el texto de una búsqueda con los sinónimos que el equipo enseñó.
 *
 * Fase 3 de la base de conocimiento: hasta ahora lo enseñado («arr.» = inducido,
 * bobina de arranque) sólo lo LEÍA el modelo. Acá lo usa también el BUSCADOR: si
 * el cliente escribe «arr», se buscan además «inducido» y «bobina de arranque» y
 * aparecen los productos reales, que el catálogo guarda con esas palabras.
 *
 * Devuelve frases para buscar POR SEPARADO (no se agregan al texto): la búsqueda
 * por palabras clave es AND entre términos, así que sumar palabras al texto
 * achicaría el resultado en vez de ampliarlo.
 *
 * Sólo mira términos (kind=term) aprobados y activos con sinónimos. Todo lo demás
 * (reglas, notas) sigue viajando al prompt, no a la búsqueda.
 */
final class KnowledgeSynonymsService
{
    /**
     * Si el texto menciona un término enseñado, devuelve sus otras palabras
     * equivalentes (las que no están ya en el texto) para buscarlas aparte.
     *
     * @return list<string>
     */
    public function expansions(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $grupos = $this->terms();

        if ($grupos === []) {
            return [];
        }

        $lower = mb_strtolower($text);
        $frases = [];

        foreach ($grupos as $tokens) {
            // ¿El texto menciona alguna palabra de este grupo?
            $mencionado = false;
            foreach ($tokens as $tok) {
                if ($this->contiene($lower, $tok)) {
                    $mencionado = true;
                    break;
                }
            }

            if (! $mencionado) {
                continue;
            }

            // Las otras palabras del grupo, que todavía no están en el texto.
            foreach ($tokens as $tok) {
                if (! $this->contiene($lower, $tok) && ! in_array($tok, $frases, true)) {
                    $frases[] = $tok;
                }
            }
        }

        return $frases;
    }

    private function contiene(string $lowerText, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        // Palabra completa: «arr» no matchea dentro de «arranque» ni de «barra».
        return (bool) preg_match(
            '/(?<![\p{L}\p{N}])'.preg_quote($token, '/').'(?![\p{L}\p{N}])/u',
            $lowerText,
        );
    }

    /**
     * Grupos de palabras equivalentes (título + sinónimos), ya normalizadas.
     * Se consulta en cada búsqueda a propósito: la tabla es chica y así una regla
     * recién desactivada deja de expandir al instante.
     *
     * ponytail: consulta por llamada; cachear por request si pesara.
     *
     * @return list<list<string>>
     */
    private function terms(): array
    {
        try {
            $rows = AiKnowledge::query()
                ->approved()
                ->active()
                ->where('kind', AiKnowledge::KIND_TERM)
                ->get(['title', 'synonyms']);
        } catch (\Throwable $e) {
            Log::warning('No se pudieron leer los sinónimos enseñados.', ['error' => $e->getMessage()]);

            return [];
        }

        $grupos = [];

        foreach ($rows as $row) {
            $tokens = [];

            foreach (array_merge([(string) $row->title], explode(',', (string) $row->synonyms)) as $raw) {
                $tok = $this->normalizar($raw);
                if ($tok !== '' && ! in_array($tok, $tokens, true)) {
                    $tokens[] = $tok;
                }
            }

            // Hace falta al menos un sinónimo además del título para expandir.
            if (count($tokens) >= 2) {
                $grupos[] = $tokens;
            }
        }

        return $grupos;
    }

    private function normalizar(string $raw): string
    {
        // Minúsculas y sin puntuación en los extremos: «arr.» -> «arr».
        return trim(mb_strtolower(trim($raw)), " \t\n\r\0\x0B.,;:·—-");
    }
}
