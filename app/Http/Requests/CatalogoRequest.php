<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Caracteristica;
use App\Services\BuscadorCatalogo;
use App\Services\Catalogo\CatalogoFiltrado;
use App\Services\Catalogo\FiltrosCatalogo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Catálogo (/productos y /buscar): convierte la URL en FiltrosCatalogo.
 *
 * No rechaza nada: estas URLs se comparten y quedan en favoritos, así que un
 * valor que no corresponde se ignora en vez de terminar en una página de
 * error.
 */
class CatalogoRequest extends FormRequest
{
    private const MAXIMO = 100;

    /** Páginas que se pueden pedir de una vez al volver a un listado. */
    public const MAXIMO_PAGINAS_JUNTAS = 10;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function filtros(): FiltrosCatalogo
    {
        $q = $this->texto('q') ?? $this->texto('search') ?? '';
        $categoria = filter_var($this->query('categoria'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $categoria = $categoria === false ? null : $categoria;

        $estados = collect((array) $this->query('estado', []))
            ->map(fn ($e) => FiltrosCatalogo::ESTADOS[is_string($e) ? strtolower($e) : ''] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Medidas de la categoría: c[ID de la característica]=valor.
        $atributos = [];
        if ($categoria !== null && is_array($this->query('c'))) {
            foreach ($this->query('c') as $id => $valor) {
                $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id !== false && is_string($valor)) {
                    $valor = CatalogoFiltrado::normalizar(mb_substr($valor, 0, self::MAXIMO));
                    if ($valor !== '') {
                        $atributos[$id] = $valor;
                    }
                }
            }
        }

        $orden = $this->query('orden');

        return new FiltrosCatalogo(
            q: mb_strlen($q) >= BuscadorCatalogo::MINIMO_CARACTERES ? $q : '',
            categoria: $categoria,
            estados: $estados,
            marca: $this->normalizado('marca'),
            vehiculo: $this->normalizado('vehiculo', vehiculo: true),
            // El modelo vale solo: sin marca busca ese modelo en cualquiera.
            modelo: $this->normalizado('modelo'),
            equivalencia: $this->texto('equivalencia'),
            atributos: $atributos,
            orden: is_string($orden) && isset(FiltrosCatalogo::ORDENES[$orden]) ? $orden : null,
            soloOfertas: $this->route()?->getName() === 'ofertas',
        );
    }

    /**
     * Un link de antes de las características relacionales filtra con atributo[columna_N]=valor: los
     * parámetros nuevos (c[ID]=valor) para redirigirlo, o null si no hay nada que traducir.
     *
     * @return array<string, mixed>|null
     */
    public function queryConFiltrosNuevos(): ?array
    {
        $viejos = $this->query('atributo');
        if (! is_array($viejos) || $viejos === []) {
            return null;
        }

        $query = $this->query();
        unset($query['atributo']);
        foreach ($viejos as $columna => $valor) {
            $caracteristica = is_string($valor) ? Caracteristica::desdeParametro((string) $columna) : null;
            if ($caracteristica !== null) {
                $query['c'][$caracteristica->id] = $valor;
            }
        }

        return $query;
    }

    /** Cuántas páginas del listado mostrar de entrada (al volver con «atrás»). */
    public function paginas(): int
    {
        $paginas = filter_var($this->query('paginas'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $paginas === false ? 1 : min($paginas, self::MAXIMO_PAGINAS_JUNTAS);
    }

    /** Página pedida por el scroll infinito. */
    public function pagina(): int
    {
        $pagina = filter_var($this->query('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $pagina === false ? 1 : $pagina;
    }

    private function texto(string $clave): ?string
    {
        $valor = $this->query($clave);

        if (! is_string($valor)) {
            return null;
        }

        $valor = mb_substr(BuscadorCatalogo::limpiar($valor), 0, self::MAXIMO);

        return $valor === '' ? null : $valor;
    }

    private function normalizado(string $clave, bool $vehiculo = false): ?string
    {
        $valor = $this->texto($clave);

        if ($valor === null) {
            return null;
        }

        $valor = $vehiculo ? CatalogoFiltrado::normalizarVehiculo($valor) : CatalogoFiltrado::normalizar($valor);

        return $valor === '' ? null : $valor;
    }
}
