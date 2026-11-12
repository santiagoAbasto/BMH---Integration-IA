<?php

declare(strict_types=1);

namespace App\Support\Caracteristicas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pasa las características de los tres lugares donde estaban a las tablas relacionales nuevas, sin borrar nada:
 *
 *  1. Columnas sueltas: `categorias.columna_N` (el nombre, igual en todas las categorías que usan el slot N) y
 *     `productos.columna_N` (el valor). Cada slot pasa a la característica de su nombre.
 *  2. `categoria_caracteristica` y `producto_caracteristica` (el sistema de características anterior).
 *
 * Destino: `caracteristica_categoria` y `caracteristica_producto`. Las columnas y tablas viejas quedan como
 * estaban (respaldo para comparar); se sacan en una migración posterior, cuando esto esté verificado.
 *
 * Idempotente: se puede correr de nuevo y no duplica (las tablas nuevas tienen clave primaria por par).
 * Con $aplicar = false sólo arma el reporte.
 */
final class MigracionCaracteristicas
{
    /** Voltaje y diámetro: la regla que antes estaba en el código (UnidadesProducto) pasa a la configuración. */
    private const UNIDADES = [
        '/\bvoltaje\b/iu' => 'V',
        '/\bdi[aá]metro\b/iu' => 'MM',
    ];

    /** @var array<string, mixed> */
    private array $reporte = [];

    /** @return array<string, mixed> */
    public function ejecutar(bool $aplicar): array
    {
        $this->reporte = [
            'caracteristicas_creadas' => [],
            'slots_vinculados' => [],
            'configuradas_con_unidad' => [],
            'ordenes_asignados' => 0,
            'categorias_vinculos' => ['desde_columnas' => 0, 'desde_tabla_vieja' => 0, 'total' => 0],
            'productos_valores' => ['desde_columnas' => 0, 'desde_tabla_vieja' => 0, 'total' => 0],
            'sin_dato_omitidos' => 0,
            'conflictos' => [],
            'para_revisar' => [],
        ];

        $runner = function () use ($aplicar): void {
            $slots = $this->slots();
            $porSlot = $this->caracteristicasDeSlots($slots, $aplicar);
            $this->configurar($aplicar);
            $this->asignarOrden($aplicar);
            $this->vincularCategorias($porSlot, $aplicar);
            $this->cargarValores($porSlot, $aplicar);
        };

        $aplicar ? DB::transaction($runner) : $runner();

        return $this->reporte;
    }

    /** @return array<int, string> slot → nombre (el mismo en todas las categorías que lo usan) */
    private function slots(): array
    {
        $slots = [];
        foreach (DB::table('categorias')->get() as $categoria) {
            foreach ((array) $categoria as $campo => $nombre) {
                if (str_starts_with(strtolower($campo), 'columna_') && trim((string) $nombre) !== '') {
                    $slots[(int) substr($campo, 8)] ??= mb_strtoupper(trim((string) $nombre), 'UTF-8');
                }
            }
        }
        ksort($slots);

        return $slots;
    }

    /**
     * Cada slot es una característica: la que ya existía con el mismo nombre (sin distinguir tildes ni signos)
     * o una nueva.
     *
     * @param  array<int, string>  $slots
     * @return array<int, int> slot → caracteristica_id
     */
    private function caracteristicasDeSlots(array $slots, bool $aplicar): array
    {
        $porNombre = [];
        foreach (DB::table('caracteristicas')->get(['id', 'nombre']) as $c) {
            $porNombre[self::clave($c->nombre)] ??= (int) $c->id;
        }
        $existian = $porNombre;

        $porSlot = [];
        $simulado = -1;
        foreach ($slots as $slot => $nombre) {
            $clave = self::clave($nombre);
            if (isset($porNombre[$clave])) {
                // Ya existía (o ya se creó para otra columna con el mismo nombre, como EQUIVALENCIA).
                $porSlot[$slot] = $porNombre[$clave];
                $this->reporte['slots_vinculados'][] = isset($existian[$clave])
                    ? "columna_{$slot} «{$nombre}» → característica existente"
                    : "columna_{$slot} «{$nombre}» → la misma que otra columna con ese nombre";

                continue;
            }

            $this->reporte['caracteristicas_creadas'][] = "columna_{$slot} → «{$nombre}»";
            $porNombre[$clave] = $porSlot[$slot] = $aplicar
                ? (int) DB::table('caracteristicas')->insertGetId(['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()])
                : $simulado--;
        }
        $this->primerSlot = [];
        foreach ($porSlot as $slot => $id) {
            $this->primerSlot[$id] ??= $slot;
        }

        return $porSlot;
    }

    /** @var array<int, int> caracteristica_id → primera columna_N donde aparecía (para el orden inicial) */
    private array $primerSlot = [];

    private function configurar(bool $aplicar): void
    {
        foreach (DB::table('caracteristicas')->whereNull('unidad')->get(['id', 'nombre']) as $c) {
            foreach (self::UNIDADES as $patron => $unidad) {
                if (preg_match($patron, $c->nombre) === 1) {
                    $this->reporte['configuradas_con_unidad'][] = "{$c->nombre}: numérica, unidad {$unidad}";
                    if ($aplicar) {
                        DB::table('caracteristicas')->where('id', $c->id)->update(['numerica' => true, 'unidad' => $unidad]);
                    }

                    break;
                }
            }
        }
    }

    /** Orden alfanumérico de 2 caracteres (aa, ab, …), como el resto del sitio: primero los slots en su orden. */
    private function asignarOrden(bool $aplicar): void
    {
        $todas = DB::table('caracteristicas')->get(['id', 'orden', 'nombre']);
        $usados = $todas->pluck('orden')->filter(fn ($o) => is_string($o) && preg_match('/^[a-z0-9]{2}$/', $o) === 1)->flip();
        $sinOrden = $todas->reject(fn ($c) => is_string($c->orden) && preg_match('/^[a-z0-9]{2}$/', $c->orden) === 1)
            ->sortBy(fn ($c) => [isset($this->primerSlot[$c->id]) ? 0 : 1, $this->primerSlot[$c->id] ?? 0, $c->nombre]);

        $codigos = (function () use ($usados) {
            foreach (range('a', 'z') as $a) {
                foreach (range('a', 'z') as $b) {
                    if (! isset($usados[$a.$b])) {
                        yield $a.$b;
                    }
                }
            }
        })();

        foreach ($sinOrden as $c) {
            $codigo = $codigos->current();
            $codigos->next();
            $this->reporte['ordenes_asignados']++;
            if ($aplicar) {
                DB::table('caracteristicas')->where('id', $c->id)->update(['orden' => $codigo]);
            }
        }
    }

    /** @param  array<int, int>  $porSlot */
    private function vincularCategorias(array $porSlot, bool $aplicar): void
    {
        $filas = [];
        foreach (DB::table('categorias')->get() as $categoria) {
            foreach ($porSlot as $slot => $caracteristicaId) {
                if (trim((string) ($categoria->{'columna_'.$slot} ?? '')) !== '') {
                    $filas[$categoria->id.'-'.$caracteristicaId] = ['categoria_id' => $categoria->id, 'caracteristica_id' => $caracteristicaId];
                    $this->reporte['categorias_vinculos']['desde_columnas']++;
                }
            }
        }
        foreach (DB::table('categoria_caracteristica')->whereNull('deleted_at')->get(['categoria_id', 'caracteristica_id']) as $v) {
            $clave = $v->categoria_id.'-'.$v->caracteristica_id;
            if (! isset($filas[$clave])) {
                $filas[$clave] = ['categoria_id' => $v->categoria_id, 'caracteristica_id' => $v->caracteristica_id];
                $this->reporte['categorias_vinculos']['desde_tabla_vieja']++;
            }
        }
        $this->reporte['categorias_vinculos']['total'] = count($filas);

        if ($aplicar) {
            foreach (array_chunk(array_values($filas), 500) as $lote) {
                DB::table('caracteristica_categoria')->insertOrIgnore(array_map(fn ($f) => $f + ['created_at' => now(), 'updated_at' => now()], $lote));
            }
        }
    }

    /** @param  array<int, int>  $porSlot */
    private function cargarValores(array $porSlot, bool $aplicar): void
    {
        $formatos = DB::table('caracteristicas')->get(['id', 'nombre', 'mayusculas', 'numerica', 'unidad'])->keyBy('id');
        $formato = function (int $id, string $nombre) use ($formatos): FormatoCaracteristica {
            $c = $formatos->get($id);
            if ($c !== null) {
                return new FormatoCaracteristica((bool) $c->mayusculas, (bool) $c->numerica, $c->unidad);
            }
            // Simulación: la característica todavía no existe; se infiere la unidad por el nombre.
            foreach (self::UNIDADES as $patron => $unidad) {
                if (preg_match($patron, $nombre) === 1) {
                    return new FormatoCaracteristica(true, true, $unidad);
                }
            }

            return new FormatoCaracteristica();
        };
        $nombres = $formatos->pluck('nombre', 'id')->all();
        foreach ($this->slots() as $slot => $nombre) {
            $nombres[$porSlot[$slot]] ??= $nombre;
        }

        $valores = [];
        $columnas = array_map(fn ($s) => 'columna_'.$s, array_keys($porSlot));
        DB::table('productos')->select(array_merge(['id', 'codigo'], $columnas))->orderBy('id')
            ->chunk(500, function ($productos) use ($porSlot, $formato, $nombres, &$valores): void {
                foreach ($productos as $p) {
                    foreach ($porSlot as $slot => $caracteristicaId) {
                        $crudo = $p->{'columna_'.$slot};
                        if ($crudo === null || trim((string) $crudo) === '') {
                            continue;
                        }
                        if (FormatoCaracteristica::vacio($crudo)) {
                            $this->reporte['sin_dato_omitidos']++;

                            continue;
                        }
                        $valor = $this->valor($formato($caracteristicaId, $nombres[$caracteristicaId] ?? ''), (string) $crudo, $p->codigo, $nombres[$caracteristicaId] ?? '');
                        $valores[$p->id.'-'.$caracteristicaId] = ['producto_id' => $p->id, 'caracteristica_id' => $caracteristicaId, 'valor' => $valor];
                        $this->reporte['productos_valores']['desde_columnas']++;
                    }
                }
            });

        // Sistema anterior: la última fila de cada producto/característica (como lo mostraba el sitio).
        $ultimas = DB::table('producto_caracteristica as pc')
            ->join('productos as p', 'p.id', '=', 'pc.producto_id')
            ->whereNull('pc.deleted_at')
            ->whereIn('pc.id', fn ($q) => $q->selectRaw('MAX(id)')->from('producto_caracteristica')->whereNull('deleted_at')->groupBy('producto_id', 'caracteristica_id'))
            ->get(['pc.producto_id', 'pc.caracteristica_id', 'pc.valor', 'p.codigo']);
        foreach ($ultimas as $v) {
            if (FormatoCaracteristica::vacio($v->valor)) {
                $this->reporte['sin_dato_omitidos']++;

                continue;
            }
            $clave = $v->producto_id.'-'.$v->caracteristica_id;
            $nombre = $nombres[$v->caracteristica_id] ?? '';
            $valor = $this->valor($formato((int) $v->caracteristica_id, $nombre), (string) $v->valor, $v->codigo, $nombre);
            if (isset($valores[$clave])) {
                // Las columnas son la fuente principal del catálogo (y no estaban cortadas a 50 caracteres).
                if ($valores[$clave]['valor'] !== $valor) {
                    $this->reporte['conflictos'][] = "{$v->codigo} · {$nombre}: se queda «{$valores[$clave]['valor']}» (columnas), no «{$valor}»";
                }

                continue;
            }
            $valores[$clave] = ['producto_id' => $v->producto_id, 'caracteristica_id' => (int) $v->caracteristica_id, 'valor' => $valor];
            $this->reporte['productos_valores']['desde_tabla_vieja']++;
        }
        $this->reporte['productos_valores']['total'] = count($valores);

        if ($aplicar) {
            foreach (array_chunk(array_values($valores), 500) as $lote) {
                DB::table('caracteristica_producto')->insertOrIgnore(array_map(fn ($f) => $f + ['created_at' => now(), 'updated_at' => now()], $lote));
            }
        }
    }

    private function valor(FormatoCaracteristica $formato, string $crudo, ?string $codigo, string $nombre): string
    {
        $formato->normalizar($crudo, $error);
        if ($error !== null && count($this->reporte['para_revisar']) < 200) {
            $this->reporte['para_revisar'][] = "{$codigo} · {$nombre}: «".trim($crudo).'» no es numérico; se conserva como texto';
        }

        return Str::limit((string) $formato->normalizarSinPerder($crudo), 255, '');
    }

    /** Nombre para comparar: sin tildes, mayúsculas y sólo letras y números. */
    public static function clave(?string $nombre): string
    {
        return (string) preg_replace('/[^A-Z0-9]+/', '', strtoupper(Str::ascii((string) $nombre)));
    }
}
