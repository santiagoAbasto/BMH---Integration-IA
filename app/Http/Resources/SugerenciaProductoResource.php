<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\DTO\ProductCondition;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un producto en el desplegable del buscador del header. Sólo lo que se ve:
 * nada de precios, que dependen de quién mira.
 *
 * @mixin Producto
 */
class SugerenciaProductoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $estado = ProductCondition::fromLegacy($this->estado);

        return [
            'id' => $this->id,
            'codigo' => (string) $this->codigo,
            'nombre' => (string) $this->nombre,
            'marca' => $this->marca ?: null,
            'categoria' => $this->categoria?->nombre,
            'estado' => $estado->isPublic() ? $estado->label() : null,
            'imagen' => $this->portadaUrl(),
            'url' => route('producto', ['id' => $this->id]),
        ];
    }
}
