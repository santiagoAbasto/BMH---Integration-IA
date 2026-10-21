<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BuscarRequest;
use App\Http\Resources\SugerenciaProductoResource;
use App\Models\Categoria;
use App\Services\BuscadorCatalogo;
use Illuminate\Http\JsonResponse;

/**
 * Sugerencias del buscador del header mientras se escribe: un solo campo que
 * encuentra por código, marca, modelo, equivalencia, rubro o atributo.
 *
 * Al enviar, el buscador lleva al catálogo (CatalogoController) con el texto
 * como filtro.
 */
class BuscadorController extends Controller
{
    private const SUGERENCIAS = 6;

    public function __construct(
        private readonly BuscadorCatalogo $buscador,
    ) {
    }

    public function sugerencias(BuscarRequest $request): JsonResponse
    {
        $termino = $request->termino();

        if (mb_strlen($termino) < BuscadorCatalogo::MINIMO_CARACTERES) {
            return response()->json(['termino' => $termino, 'total' => 0, 'productos' => [], 'categorias' => [], 'ver_todos' => null]);
        }

        ['total' => $total, 'productos' => $productos, 'categorias' => $categorias] = $this->buscador->sugerencias($termino, self::SUGERENCIAS);

        return response()->json([
            'termino' => $termino,
            'total' => $total,
            'productos' => SugerenciaProductoResource::collection($productos)->resolve($request),
            'categorias' => $categorias->map(fn (Categoria $c) => [
                'id' => $c->id,
                'nombre' => mb_convert_case($c->nombre, MB_CASE_TITLE, 'UTF-8'),
                'url' => route('productos', ['categoria' => $c->id]),
            ])->values(),
            'ver_todos' => route('search', ['q' => $termino]),
        ]);
    }
}
