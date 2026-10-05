<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Búsqueda de texto libre sobre el catálogo del sitio.
 *
 * La usan el buscador del header (sugerencias y la página /buscar) y el campo
 * «Marca / Modelo / Equivalencias / Atributo» del filtro de productos, así que
 * los dos encuentran exactamente lo mismo.
 *
 * Cada palabra tiene que aparecer en algún lado del producto —código, nombre,
 * marca, modelo, rubro, atributos (columnas viejas y características),
 * equivalencias, aplicaciones o partes— y entre palabras se exige que estén
 * todas. Si el texto es un código, además entran los productos equivalentes.
 *
 * El orden lo decide relevancia(): el código manda, después nombre, marca,
 * modelo y rubro.
 */
final class BuscadorCatalogo
{
    /** Por debajo de esto no se busca: una letra trae medio catálogo. */
    public const MINIMO_CARACTERES = 2;

    private const STOP_WORDS = ['de', 'del', 'la', 'las', 'los', 'y', 'en', 'el', 'para', 'por', 'con', 'un', 'una', 'unos', 'unas', 'a'];

    /** Columnas livianas para rankear sin hidratar relaciones. */
    private const COLUMNAS_RANKING = ['productos.id', 'codigo', 'nombre', 'marca', 'modelo', 'categoria_id', 'orden', 'estado'];

    /** @var list<string>|null */
    private ?array $columnasAtributo = null;

    // ------------------------------------------------------------ consultas

    /**
     * Productos publicados que coinciden, del más al menos relevante. Trae
     * sólo las columnas del ranking (más las que se pidan, por ejemplo los
     * atributos que filtra el catálogo); hidratar es trabajo de quien pagina.
     *
     * @param  list<string>  $columnasExtra
     * @return Collection<int, Producto>
     */
    public function buscar(string $termino, array $columnasExtra = []): Collection
    {
        $termino = self::limpiar($termino);

        if (mb_strlen($termino) < self::MINIMO_CARACTERES) {
            return collect();
        }

        $query = Producto::query()
            ->select([...self::COLUMNAS_RANKING, ...$columnasExtra])
            ->with('categoria:id,nombre')
            // Los ocultos (estado = 0) no se le ofrecen al visitante.
            ->where(fn (Builder $q) => $q->whereNull('estado')->orWhere('estado', '!=', 0));

        $this->aplicarTexto($query, $termino);

        return $this->ordenarPorRelevancia($query->orderBy('orden', 'desc')->get(), $termino);
    }

    /**
     * Lo que muestra el desplegable del header mientras se escribe.
     *
     * @return array{total: int, productos: Collection<int, Producto>, categorias: Collection<int, Categoria>}
     */
    public function sugerencias(string $termino, int $limite = 6): array
    {
        $ranking = $this->buscar($termino);
        $ids = $ranking->take($limite)->pluck('id')->all();

        $productos = Producto::with(['portadaImagen', 'categoria:id,nombre'])
            ->whereIn('id', $ids)
            ->get(self::COLUMNAS_RANKING)
            ->sortBy(fn (Producto $p) => array_search($p->id, $ids, true))
            ->values();

        return [
            'total' => $ranking->count(),
            'productos' => $productos,
            'categorias' => $this->categoriasQueCoinciden($termino),
        ];
    }

    /** @return Collection<int, Categoria> */
    public function categoriasQueCoinciden(string $termino, int $limite = 3): Collection
    {
        $tokens = self::tokens(self::limpiar($termino));

        if ($tokens === []) {
            return collect();
        }

        return Categoria::query()
            ->where(function (Builder $q) use ($tokens): void {
                // `categorias.nombre` es utf8mb3_bin: sin LOWER distingue mayúsculas.
                foreach ($tokens as $token) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ['%'.self::raiz($token).'%']);
                }
            })
            ->orderBy('nombre')
            ->limit($limite)
            ->get(['id', 'nombre']);
    }

    // ------------------------------------------------------------- filtro

    /**
     * Agrega el texto libre a una consulta de productos.
     */
    public function aplicarTexto(Builder $query, string $termino): void
    {
        $termino = self::limpiar($termino);
        $tokens = self::tokens($termino);

        // Los códigos equivalentes entran como un OR dentro del mismo where,
        // no como una consulta aparte: así los filtros que vengan después
        // (categoría, modelo, estado, atributos) también los alcanzan.
        $codigosEquivNorm = array_values(array_unique(array_map(
            fn ($c) => mb_strtolower(preg_replace('/\s+/', '', (string) $c)),
            self::pareceCodigo($termino) ? $this->codigosRelacionados($termino) : []
        )));

        if ($tokens === [] && $codigosEquivNorm === []) {
            return;
        }

        $query->where(function (Builder $grupo) use ($tokens, $codigosEquivNorm): void {
            if ($codigosEquivNorm !== []) {
                // Un solo IN y no un OR por código: con un término corto como
                // «11» son cientos de códigos y los OR tardaban más de medio segundo.
                $grupo->orWhereRaw(
                    "LOWER(REPLACE(codigo,' ','')) IN (".implode(',', array_fill(0, count($codigosEquivNorm), '?')).')',
                    $codigosEquivNorm
                );
            }

            if ($tokens === []) {
                return;
            }

            // Cada palabra genera un grupo OR; entre palabras, AND.
            $grupo->orWhere(function (Builder $q) use ($tokens): void {
                foreach ($tokens as $token) {
                    $q->where(fn (Builder $sub) => $this->coincidePalabra($sub, $token));
                }
            });
        });
    }

    private function coincidePalabra(Builder $sub, string $token): void
    {
        $root = self::raiz($token);
        $pattern = '\\b'.preg_quote($root, '/').'s?\\b';
        $rootSinEspacios = str_replace(' ', '', $root);
        $like = "%{$root}%";

        // NOMBRE: palabra completa + LIKE por si es abreviado (arr → arranque)
        $sub->whereRaw('LOWER(nombre) REGEXP ?', [$pattern])
            ->orWhereRaw('LOWER(nombre) LIKE ?', [$like])
            ->orWhereRaw('LOWER(marca) LIKE ?', [$like])
            ->orWhereRaw('LOWER(modelo) LIKE ?', [$like])
            ->orWhereRaw("LOWER(REPLACE(codigo,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
            // RUBRO
            ->orWhereExists(function (QueryBuilder $qq) use ($like): void {
                $qq->select(DB::raw(1))->from('categorias')
                    ->whereColumn('categorias.id', 'productos.categoria_id')
                    ->whereRaw('LOWER(categorias.nombre) LIKE ?', [$like]);
            })
            // ATRIBUTOS Y MEDIDAS: columnas viejas y características
            ->when($this->columnasAtributo() !== [], function (Builder $q) use ($like): void {
                $q->orWhereRaw("LOWER(CONCAT_WS(' ', ".implode(', ', $this->columnasAtributo()).')) LIKE ?', [$like]);
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($like): void {
                $qq->select(DB::raw(1))->from('producto_caracteristica')
                    ->whereColumn('producto_caracteristica.producto_id', 'productos.id')
                    ->whereNull('producto_caracteristica.deleted_at')
                    ->whereRaw('LOWER(producto_caracteristica.valor) LIKE ?', [$like]);
            })
            // TABLAS NUEVAS: equivalencias, aplicaciones, partes_relacionadas
            ->orWhereExists(function (QueryBuilder $qq) use ($root, $rootSinEspacios): void {
                $qq->select(DB::raw(1))->from('equivalencias')
                    ->whereColumn('equivalencias.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($root, $rootSinEspacios): void {
                        $w->whereRaw('LOWER(equivalencias.valor) LIKE ?', ["%{$root}%"])
                            ->orWhereRaw("LOWER(REPLACE(equivalencias.valor,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                            ->orWhereRaw('LOWER(equivalencias.nombre) LIKE ?', ["%{$root}%"]);
                    });
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($root, $rootSinEspacios): void {
                $qq->select(DB::raw(1))->from('aplicaciones')
                    ->whereColumn('aplicaciones.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($root, $rootSinEspacios): void {
                        $w->whereRaw('LOWER(aplicaciones.valor) LIKE ?', ["%{$root}%"])
                            ->orWhereRaw("LOWER(REPLACE(aplicaciones.valor,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                            ->orWhereRaw('LOWER(aplicaciones.nombre) LIKE ?', ["%{$root}%"]);
                    });
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($root, $rootSinEspacios): void {
                $qq->select(DB::raw(1))->from('partes_relacionadas')
                    ->join('productos as p2', 'p2.id', '=', 'partes_relacionadas.parte_id')
                    ->whereColumn('partes_relacionadas.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($root, $rootSinEspacios): void {
                        $w->whereRaw("LOWER(REPLACE(p2.codigo,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                            ->orWhereRaw('LOWER(p2.nombre) LIKE ?', ["%{$root}%"]);
                    });
            });
    }

    /**
     * Si el texto es el código de un producto, sus equivalencias; y los
     * productos que tienen ese código entre sus equivalencias.
     *
     * @return list<string>
     */
    private function codigosRelacionados(string $termino): array
    {
        $buscado = preg_replace('/\s+/', '', mb_strtolower($termino));
        $like = "%{$buscado}%";
        $colsEquiv = $this->columnasAtributo();
        $codigos = [];

        $agregarValores = function (iterable $valores) use (&$codigos): void {
            foreach ($valores as $valor) {
                foreach (preg_split('/[,\s]+/', (string) $valor) as $c) {
                    if (($c = trim($c)) !== '') {
                        $codigos[] = $c;
                    }
                }
            }
        };

        // 1) El producto base y todo lo que declara como equivalente.
        $base = Producto::whereRaw("LOWER(REPLACE(codigo,' ','')) = ?", [$buscado])->first();

        if ($base) {
            $agregarValores(array_map(fn ($col) => $base->{$col}, $colsEquiv));
            $agregarValores(DB::table('producto_caracteristica')->where('producto_id', $base->id)->pluck('valor'));
            $agregarValores(DB::table('equivalencias')->where('producto_id', $base->id)->pluck('valor'));
            $agregarValores(DB::table('aplicaciones')->where('producto_id', $base->id)->pluck('valor'));
            $agregarValores(
                DB::table('partes_relacionadas as pr')
                    ->join('productos as p2', 'p2.id', '=', 'pr.parte_id')
                    ->where('pr.producto_id', $base->id)
                    ->pluck('p2.codigo')
            );
        }

        // 2) Productos donde alguna equivalencia contiene el código buscado.
        $relacionados = Producto::query()
            ->where(function (Builder $q) use ($colsEquiv, $like): void {
                foreach ($colsEquiv as $col) {
                    $q->orWhereRaw("LOWER(REPLACE({$col},' ','')) LIKE ?", [$like]);
                }
                foreach (['producto_caracteristica', 'equivalencias', 'aplicaciones'] as $tabla) {
                    $q->orWhereIn('id', function (QueryBuilder $sub) use ($tabla, $like): void {
                        $sub->select('producto_id')->from($tabla)
                            ->whereRaw("LOWER(REPLACE(valor,' ','')) LIKE ?", [$like])
                            ->when($tabla !== 'producto_caracteristica', fn ($w) => $w->orWhereRaw("LOWER(REPLACE(nombre,' ','')) LIKE ?", [$like]));
                    });
                }
                $q->orWhereIn('id', function (QueryBuilder $sub) use ($like): void {
                    $sub->select('pr.producto_id')->from('partes_relacionadas as pr')
                        ->join('productos as p2', 'p2.id', '=', 'pr.parte_id')
                        ->whereRaw("LOWER(REPLACE(p2.codigo,' ','')) LIKE ?", [$like]);
                });
            })
            ->pluck('codigo');

        $agregarValores($relacionados);

        return array_values(array_unique($codigos));
    }

    /** @return list<string> columna_1 … columna_N de la tabla productos */
    private function columnasAtributo(): array
    {
        return $this->columnasAtributo ??= array_values(array_filter(
            Schema::getColumnListing('productos'),
            fn (string $c) => (bool) preg_match('/^columna_\d+$/', $c)
        ));
    }

    // ----------------------------------------------------------- relevancia

    /**
     * @param  Collection<int, Producto>  $productos
     * @return Collection<int, Producto>
     */
    public function ordenarPorRelevancia(Collection $productos, string $termino, array $extra = []): Collection
    {
        return $productos
            ->each(fn (Producto $p) => $p->relevancia_score = self::relevancia($p, $termino) + ($extra[$p->id] ?? 0))
            ->sortByDesc('relevancia_score')
            ->values();
    }

    /**
     * Puntaje de un producto para el texto buscado: la frase completa pesa
     * por campo (código > nombre > marca > modelo > rubro) y cada palabra
     * suma un poco, para que «rotores bosch 24v» ordene bien aunque la frase
     * no aparezca tal cual en ningún campo.
     */
    public static function relevancia(Producto $producto, string $termino): int
    {
        $busqueda = mb_strtolower(self::limpiar($termino));

        if ($busqueda === '') {
            return 0;
        }

        $codigo = mb_strtolower($producto->codigo ?? '');
        $nombre = mb_strtolower($producto->nombre ?? '');
        $marca = mb_strtolower($producto->marca ?? '');
        $modelo = mb_strtolower($producto->modelo ?? '');
        $rubro = mb_strtolower($producto->categoria?->nombre ?? '');

        $score = 0;

        // Código (también sin espacios: «RP 033155» = «rp033155»)
        $codigoSinEspacios = str_replace(' ', '', $codigo);
        $busquedaSinEspacios = str_replace(' ', '', $busqueda);
        if ($codigo === $busqueda || $codigoSinEspacios === $busquedaSinEspacios) {
            $score += 1000;
        } elseif (str_starts_with($codigoSinEspacios, $busquedaSinEspacios)) {
            $score += 900;
        } elseif (str_contains($codigoSinEspacios, $busquedaSinEspacios)) {
            $score += 800;
        }

        $score += self::puntajeCampo($nombre, $busqueda, 700, 650, 600);
        $score += self::puntajeCampo($marca, $busqueda, 500, 450, 400);

        if ($modelo !== '' && str_contains($modelo, $busqueda)) {
            $score += 300;
        }
        if ($rubro !== '' && str_contains($rubro, $busqueda)) {
            $score += 200;
        }

        foreach (self::tokens($busqueda) as $token) {
            $raiz = self::raiz($token);
            $score += match (true) {
                str_contains($codigoSinEspacios, $raiz) => 60,
                str_contains($nombre, $raiz) => 40,
                str_contains($marca, $raiz), str_contains($modelo, $raiz) => 30,
                str_contains($rubro, $raiz) => 20,
                default => 0,
            };
        }

        return $score;
    }

    private static function puntajeCampo(string $campo, string $busqueda, int $exacto, int $empieza, int $contiene): int
    {
        return match (true) {
            $campo === '' => 0,
            $campo === $busqueda => $exacto,
            str_starts_with($campo, $busqueda) => $empieza,
            str_contains($campo, $busqueda) => $contiene,
            default => 0,
        };
    }

    // ----------------------------------------------------------- utilidades

    public static function limpiar(string $termino): string
    {
        // Sin comodines de LIKE: «%» o «\» escritos a mano no deben traer todo.
        return trim((string) preg_replace('/\s+/u', ' ', str_replace(['%', '\\'], '', $termino)));
    }

    /** @return list<string> palabras de al menos 2 letras, sin artículos ni preposiciones */
    public static function tokens(string $termino): array
    {
        $tokens = preg_split('/[\s,;.\-\/]+/', mb_strtolower($termino)) ?: [];

        return array_values(array_unique(array_filter(
            array_map('trim', $tokens),
            fn (string $t) => mb_strlen($t) >= 2 && ! in_array($t, self::STOP_WORDS, true)
        )));
    }

    /** Alfanumérico sin espacios y con al menos un dígito: «IMPO1747», «1061». */
    public static function pareceCodigo(string $termino): bool
    {
        $sinEspacios = preg_replace('/\s+/', '', mb_strtolower($termino));

        return (bool) preg_match('/^[0-9a-z\-]+$/', $sinEspacios) && (bool) preg_match('/[0-9]/', $sinEspacios);
    }

    /**
     * Singular simple: escobillas → escobilla, alternadores → alternador.
     * Se busca con LIKE, así que alcanza con que la raíz quede contenida en el
     * singular: fusibles → «fusibl» también encuentra «fusible».
     */
    private static function raiz(string $token): string
    {
        return match (true) {
            mb_strlen($token) > 5 && str_ends_with($token, 'es') => mb_substr($token, 0, -2),
            mb_strlen($token) > 4 && str_ends_with($token, 's') => mb_substr($token, 0, -1),
            default => $token,
        };
    }
}
