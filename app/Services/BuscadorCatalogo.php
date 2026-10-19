<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\Catalogo\CorrectorBusqueda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
 * Es permisivo con cómo se escribe: no distingue mayúsculas ni tildes
 * («bateria» = «BATERÍA»), los códigos se comparan sin espacios ni guiones
 * («usx2122» = «USX21-22») y cada palabra trae también sus sinónimos
 * (config/catalogo.php: «bendix» = «impulsor»). Si aun así no hay nada,
 * buscarParecidos() da lo más cercano.
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

    public function __construct(
        private readonly CorrectorBusqueda $corrector,
    ) {
    }

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
     * Cuando buscar() no encontró nada, lo más parecido a lo que se escribió.
     * La primera de estas que traiga algo:
     *  1. el texto con los errores de tipeo corregidos («alternadro»);
     *  2. un código con algún carácter de más al final («rp0331559» → «rp033155»);
     *  3. los productos que tienen más de las palabras buscadas, aunque no todas.
     *
     * @param  list<string>  $columnasExtra
     * @return array{productos: Collection<int, Producto>, termino: string|null, parcial: bool}
     *         termino: lo que se buscó en lugar del texto original (en 1 y 2);
     *         parcial: coinciden sólo algunas de las palabras (en 3)
     */
    public function buscarParecidos(string $termino, array $columnasExtra = []): array
    {
        $termino = self::limpiar($termino);
        $nada = ['productos' => collect(), 'termino' => null, 'parcial' => false];

        if (mb_strlen($termino) < self::MINIMO_CARACTERES) {
            return $nada;
        }

        $corregido = $this->corrector->corregir($termino);
        if (self::plano($corregido) !== self::plano($termino)) {
            $productos = $this->buscar($corregido, $columnasExtra);
            if ($productos->isNotEmpty()) {
                return ['productos' => $productos, 'termino' => $corregido, 'parcial' => false];
            }
        }

        if (self::pareceCodigo($termino) && ! str_contains($termino, ' ')) {
            for ($sobran = 1; $sobran <= 3 && mb_strlen($termino) - $sobran >= 3; $sobran++) {
                $recortado = mb_substr($termino, 0, -$sobran);
                $productos = $this->buscar($recortado, $columnasExtra);
                if ($productos->isNotEmpty()) {
                    return ['productos' => $productos, 'termino' => $recortado, 'parcial' => false];
                }
            }
        }

        $tokens = self::tokens($corregido);
        if (count($tokens) < 2) {
            return $nada;
        }

        // Cuántas de las palabras tiene cada producto: una consulta por palabra.
        $coincidencias = [];
        foreach ($tokens as $token) {
            $ids = Producto::query()
                ->where(fn (Builder $q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
                ->where(fn (Builder $sub) => $this->coincidePalabra($sub, $token))
                ->pluck('productos.id');
            foreach ($ids as $id) {
                $coincidencias[$id] = ($coincidencias[$id] ?? 0) + 1;
            }
        }

        if ($coincidencias === []) {
            return $nada;
        }
        // Sólo los que tienen más palabras: con una sola de cinco ya no se parecen.
        $maximo = max($coincidencias);
        $coincidencias = array_filter($coincidencias, fn (int $n) => $n === $maximo);

        $productos = Producto::query()
            ->select([...self::COLUMNAS_RANKING, ...$columnasExtra])
            ->with('categoria:id,nombre')
            ->whereIn('productos.id', array_keys($coincidencias))
            ->orderBy('orden', 'desc')
            ->get();

        return ['productos' => $this->ordenarPorRelevancia($productos, $corregido), 'termino' => null, 'parcial' => true];
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
                foreach ($tokens as $token) {
                    $q->whereRaw(self::sinTildes('nombre').' LIKE ?', ['%'.self::raiz($token).'%']);
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

    /** La palabra o alguno de sus sinónimos. */
    private function coincidePalabra(Builder $sub, string $token): void
    {
        foreach (self::variantes($token) as $root) {
            $sub->orWhere(fn (Builder $q) => $this->coincideRaiz($q, $root));
        }
    }

    private function coincideRaiz(Builder $sub, string $root): void
    {
        $rootSinEspacios = str_replace(' ', '', $root);
        $like = "%{$root}%";
        // Los códigos se escriben con y sin espacios o guiones: «USX21-22», «usx 2122».
        $codigo = "%{$rootSinEspacios}%";
        $nombre = self::sinTildes('nombre');

        // NOMBRE (LIKE: también lo abreviado, arr → arranque), MARCA, MODELO, CÓDIGO
        $sub->whereRaw("{$nombre} LIKE ?", [$like])
            ->orWhereRaw(self::sinTildes('marca').' LIKE ?', [$like])
            ->orWhereRaw(self::sinTildes('modelo').' LIKE ?', [$like])
            ->orWhereRaw(self::compacto('codigo').' LIKE ?', [$codigo])
            // RUBRO
            ->orWhereExists(function (QueryBuilder $qq) use ($like): void {
                $qq->select(DB::raw(1))->from('categorias')
                    ->whereColumn('categorias.id', 'productos.categoria_id')
                    ->whereRaw(self::sinTildes('categorias.nombre').' LIKE ?', [$like]);
            })
            // ATRIBUTOS Y MEDIDAS: columnas viejas y características
            ->when($this->columnasAtributo() !== [], function (Builder $q) use ($like): void {
                $q->orWhereRaw(self::sinTildes("CONCAT_WS(' ', ".implode(', ', $this->columnasAtributo()).')').' LIKE ?', [$like]);
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($like): void {
                $qq->select(DB::raw(1))->from('producto_caracteristica')
                    ->whereColumn('producto_caracteristica.producto_id', 'productos.id')
                    ->whereNull('producto_caracteristica.deleted_at')
                    ->whereRaw(self::sinTildes('producto_caracteristica.valor').' LIKE ?', [$like]);
            })
            // TABLAS NUEVAS: equivalencias, aplicaciones, partes_relacionadas
            ->orWhereExists(function (QueryBuilder $qq) use ($like, $codigo): void {
                $qq->select(DB::raw(1))->from('equivalencias')
                    ->whereColumn('equivalencias.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($like, $codigo): void {
                        $w->whereRaw(self::sinTildes('equivalencias.valor').' LIKE ?', [$like])
                            ->orWhereRaw(self::compacto('equivalencias.valor').' LIKE ?', [$codigo])
                            ->orWhereRaw(self::sinTildes('equivalencias.nombre').' LIKE ?', [$like]);
                    });
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($like, $codigo): void {
                $qq->select(DB::raw(1))->from('aplicaciones')
                    ->whereColumn('aplicaciones.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($like, $codigo): void {
                        $w->whereRaw(self::sinTildes('aplicaciones.valor').' LIKE ?', [$like])
                            ->orWhereRaw(self::compacto('aplicaciones.valor').' LIKE ?', [$codigo])
                            ->orWhereRaw(self::sinTildes('aplicaciones.nombre').' LIKE ?', [$like]);
                    });
            })
            ->orWhereExists(function (QueryBuilder $qq) use ($like, $codigo): void {
                $qq->select(DB::raw(1))->from('partes_relacionadas')
                    ->join('productos as p2', 'p2.id', '=', 'partes_relacionadas.parte_id')
                    ->whereColumn('partes_relacionadas.producto_id', 'productos.id')
                    ->where(function (QueryBuilder $w) use ($like, $codigo): void {
                        $w->whereRaw(self::compacto('p2.codigo').' LIKE ?', [$codigo])
                            ->orWhereRaw(self::sinTildes('p2.nombre').' LIKE ?', [$like]);
                    });
            });
    }

    /**
     * La columna comparable sin mayúsculas ni tildes. Las columnas tienen
     * collations distintas (utf8mb4_unicode_ci, utf8mb3_bin, armscii8_bin) y
     * comparar una armscii8 con un texto con tildes hacía fallar la consulta.
     */
    private static function sinTildes(string $columna): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? "CONVERT({$columna} USING utf8mb4) COLLATE utf8mb4_unicode_ci"
            : "LOWER({$columna})";
    }

    /** Como sinTildes(), y además sin espacios, guiones, puntos ni barras. */
    private static function compacto(string $columna): string
    {
        $sinSeparadores = $columna;
        foreach ([' ', '-', '.', '/'] as $separador) {
            $sinSeparadores = "REPLACE({$sinSeparadores}, '{$separador}', '')";
        }

        return self::sinTildes($sinSeparadores);
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
        $busqueda = self::plano(self::limpiar($termino));

        if ($busqueda === '') {
            return 0;
        }

        $codigo = self::plano($producto->codigo ?? '');
        $nombre = self::plano($producto->nombre ?? '');
        $marca = self::plano($producto->marca ?? '');
        $modelo = self::plano($producto->modelo ?? '');
        $rubro = self::plano($producto->categoria?->nombre ?? '');

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

    /** Minúsculas y sin tildes: «Batería» → «bateria». */
    public static function plano(string $texto): string
    {
        return mb_strtolower(Str::ascii($texto));
    }

    /**
     * La raíz de la palabra y las de sus sinónimos (config/catalogo.php):
     * «bendix» → [bendix, impulsor].
     *
     * @return list<string>
     */
    private static function variantes(string $token): array
    {
        $raiz = self::raiz($token);
        $plana = self::plano($raiz);
        $variantes = [$raiz];

        foreach (config('catalogo.sinonimos', []) as $grupo) {
            foreach ($grupo as $palabra) {
                if ($plana === $palabra || (mb_strlen($plana) >= 5 && str_starts_with($palabra, $plana))) {
                    array_push($variantes, ...$grupo);
                    break;
                }
            }
        }

        return array_values(array_unique($variantes));
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
