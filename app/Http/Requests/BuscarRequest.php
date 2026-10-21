<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\BuscadorCatalogo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Buscador del header: la página /buscar y sus sugerencias.
 */
class BuscarRequest extends FormRequest
{
    public const MAXIMO_CARACTERES = 100;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
        ];
    }

    /**
     * Lo que escribió el visitante. Acepta `search`, el nombre que usaban los
     * links viejos a /buscar, y recorta en vez de rechazar: un texto largo
     * pegado por error no tiene por qué terminar en una página de error.
     */
    public function termino(): string
    {
        $termino = (string) ($this->query('q') ?? $this->query('search') ?? '');

        return mb_substr(BuscadorCatalogo::limpiar($termino), 0, self::MAXIMO_CARACTERES);
    }
}
