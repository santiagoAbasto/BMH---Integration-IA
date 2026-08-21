<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\Legacy\ProductImageService;
use App\Domain\Catalog\LegacyAttributeMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría de calidad de datos sobre la base legacy.
 *
 * No arregla nada ni escribe en la legacy: detecta, clasifica y reporta. El
 * objetivo es que BMH tenga una lista accionable, no que el chatbot esconda los
 * problemas.
 *
 *     php artisan bmh:data-audit
 *     php artisan bmh:data-audit --json
 */
final class DataAuditCommand extends Command
{
    protected $signature = 'bmh:data-audit {--json : Salida en JSON} {--limit=10 : Ejemplos por hallazgo}';

    protected $description = 'Audita la calidad de los datos del catálogo legacy de BMH';

    private const SEVERITY_HIGH   = 'alta';
    private const SEVERITY_MEDIUM = 'media';
    private const SEVERITY_LOW    = 'baja';

    public function handle(CatalogRepositoryInterface $catalog, ProductImageService $images): int
    {
        $limit    = (int) $this->option('limit');
        $legacy   = DB::connection('mysql_legacy');
        $findings = [];

        $total = (int) $legacy->table('productos')->count();

        // --- Códigos duplicados ------------------------------------------
        $duplicates = $catalog->duplicateCodes();

        $findings[] = $this->finding(
            'codigos_duplicados',
            self::SEVERITY_HIGH,
            'Códigos que aparecen en más de un producto',
            count($duplicates),
            array_map(
                static fn (string $code, array $ids): string => sprintf('%s → productos %s', $code, implode(', ', $ids)),
                array_keys($duplicates),
                $duplicates
            ),
            $limit,
            '`codigo` no es único. El asesor usa DuplicateProductResolver y desambigua en vez de elegir el primero.',
        );

        // --- Precio -------------------------------------------------------
        $sinPrecio = $legacy->table('productos')
            ->where(fn ($q) => $q->whereNull('precio')->orWhere('precio', '<=', 1))
            ->get(['id', 'codigo']);

        $findings[] = $this->finding(
            'productos_sin_precio',
            self::SEVERITY_HIGH,
            'Productos sin precio real (NULL o ≤ 1)',
            $sinPrecio->count(),
            $sinPrecio->map(static fn ($r): string => "[{$r->codigo}] #{$r->id}")->all(),
            $limit,
            'El PricingEngine los marca `requires_validation` y el asesor no cotiza.',
        );

        // --- Semántica de stock -------------------------------------------
        $distinctStock = (int) $legacy->table('productos')->distinct()->count('stock');

        $findings[] = $this->finding(
            'stock_sin_semantica',
            self::SEVERITY_HIGH,
            'Valores distintos en `productos.stock`',
            $distinctStock,
            [sprintf('%d valor(es) distinto(s) en %d productos', $distinctStock, $total)],
            $limit,
            $distinctStock <= 1
                ? 'Un único valor en toda la tabla: no puede ser cantidad. InventoryService devuelve `unknown`.'
                : 'Hay variación: revisar si ya se puede activar BMH_STOCK_SEMANTICS_VERIFIED.',
        );

        // --- Columnas muertas ---------------------------------------------
        $precioNNull = (int) $legacy->table('productos')->whereNull('precioN')->count();

        $findings[] = $this->finding(
            'columnas_muertas',
            self::SEVERITY_LOW,
            'Columnas sin ningún dato útil',
            $precioNNull === $total ? 1 : 0,
            $precioNNull === $total ? ['`productos.precioN` es NULL en las ' . $total . ' filas'] : [],
            $limit,
            'Candidatas a eliminarse en la Fase C de la modernización.',
        );

        // --- Descuento / aumento no aplicados -------------------------------
        $conAumento = (int) $legacy->table('productos')->where('aumento', '>', 0)->count();

        $findings[] = $this->finding(
            'aumento_cargado_no_aplicado',
            self::SEVERITY_HIGH,
            'Productos con `aumento` cargado que producción NO aplica',
            $conAumento,
            [sprintf('%d productos tienen aumento > 0; `Producto::precio()` tiene la lógica comentada', $conAumento)],
            $limit,
            'Decisión de negocio pendiente: ¿el aumento debe aplicarse? Ver docs/pricing-rules.md §Pendientes.',
        );

        // --- Datos faltantes -------------------------------------------------
        foreach ([
            ['marca', 'productos_sin_marca', 'Productos sin marca', self::SEVERITY_MEDIUM],
            ['modelo', 'productos_sin_modelo', 'Productos sin modelo', self::SEVERITY_LOW],
            ['descripcion', 'productos_sin_descripcion', 'Productos sin descripción', self::SEVERITY_LOW],
        ] as [$column, $key, $title, $severity]) {
            $rows = $legacy->table('productos')
                ->where(fn ($q) => $q->whereNull($column)->orWhere($column, ''))
                ->get(['id', 'codigo']);

            $findings[] = $this->finding(
                $key,
                $severity,
                $title,
                $rows->count(),
                $rows->map(static fn ($r): string => "[{$r->codigo}] #{$r->id}")->all(),
                $limit,
                $column === 'descripcion'
                    ? 'El texto de búsqueda se arma con nombre + atributos + equivalencias, no con descripción.'
                    : 'Reduce el poder de desambiguación del asesor.',
            );
        }

        // --- Relaciones rotas ---------------------------------------------
        $huerfanos = $legacy->table('productos as p')
            ->leftJoin('categorias as c', 'c.id', '=', 'p.categoria_id')
            ->whereNull('c.id')
            ->get(['p.id', 'p.codigo']);

        $findings[] = $this->finding(
            'productos_sin_categoria',
            self::SEVERITY_HIGH,
            'Productos con categoría inexistente o nula',
            $huerfanos->count(),
            $huerfanos->map(static fn ($r): string => "[{$r->codigo}] #{$r->id}")->all(),
            $limit,
            'Sin rubro no se pueden mapear los atributos legacy: quedan sin características.',
        );

        $caracteristicasHuerfanas = $legacy->table('producto_caracteristica as pc')
            ->leftJoin('productos as p', 'p.id', '=', 'pc.producto_id')
            ->whereNull('p.id')
            ->count();

        $findings[] = $this->finding(
            'caracteristicas_huerfanas',
            self::SEVERITY_MEDIUM,
            'Filas de `producto_caracteristica` que apuntan a productos inexistentes',
            $caracteristicasHuerfanas,
            [],
            $limit,
            'Se ignoran al hidratar; conviene limpiarlas en la Fase B.',
        );

        // --- Slots legacy sin etiqueta -------------------------------------
        $orphanSlots = [];

        foreach (LegacyAttributeMap::ORPHAN_SLOTS as $slot) {
            $column = LegacyAttributeMap::column($slot);
            $count  = (int) $legacy->table('productos')
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->count();

            if ($count > 0) {
                $orphanSlots[] = sprintf('%s tiene %d valores pero ninguna categoría lo etiqueta', $column, $count);
            }
        }

        $findings[] = $this->finding(
            'atributos_sin_etiqueta',
            self::SEVERITY_MEDIUM,
            'Slots legacy con datos pero sin etiqueta en ninguna categoría',
            count($orphanSlots),
            $orphanSlots,
            $limit,
            'Datos no interpretables: no se muestran ni se usan para buscar. Hay que preguntarle a BMH qué son.',
        );

        // --- Categorías vacías ---------------------------------------------
        $vacias = $legacy->table('categorias as c')
            ->leftJoin('productos as p', 'p.categoria_id', '=', 'c.id')
            ->whereNull('p.id')
            ->get(['c.id', 'c.nombre']);

        $findings[] = $this->finding(
            'categorias_sin_producto',
            self::SEVERITY_LOW,
            'Categorías sin ningún producto',
            $vacias->count(),
            $vacias->map(static fn ($r): string => "{$r->nombre} (#{$r->id})")->all(),
            $limit,
            '',
        );

        // --- Imágenes -------------------------------------------------------
        $imageReport = $images->integrityReport();

        $findings[] = $this->finding(
            'imagenes_faltantes',
            self::SEVERITY_MEDIUM,
            'Imágenes referenciadas en la base que no están en el filesystem',
            $imageReport['missing'],
            $imageReport['missing_examples'],
            $limit,
            sprintf('%d referencias en total. ProductImageService verifica existencia y degrada a placeholder.', $imageReport['referenced']),
        );

        $sinImagen = (int) $legacy->table('productos as p')
            ->leftJoin('imagenes as i', function ($join): void {
                $join->on('i.producto_id', '=', 'p.id')->where('i.sector', '=', 'producto');
            })
            ->whereNull('i.id')
            ->count();

        $findings[] = $this->finding(
            'productos_sin_imagen',
            self::SEVERITY_MEDIUM,
            'Productos sin ninguna imagen registrada',
            $sinImagen,
            [],
            $limit,
            'Limita la comparación visual y la card del chat.',
        );

        // --- Placeholders "-" en el EAV ------------------------------------
        $placeholders = (int) $legacy->table('producto_caracteristica')
            ->whereIn(DB::raw("TRIM(valor)"), ['-', '--', '', 'N/A'])
            ->count();

        $findings[] = $this->finding(
            'valores_placeholder',
            self::SEVERITY_LOW,
            'Valores de característica que son placeholders ("-", vacío)',
            $placeholders,
            [],
            $limit,
            'Se filtran en el semantic layer: un "-" no es una equivalencia.',
        );

        // --- Salida ---------------------------------------------------------
        $findings = array_values(array_filter($findings, static fn (array $f): bool => $f['count'] > 0));

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'generated_at'   => now()->toIso8601String(),
                'total_products' => $total,
                'findings'       => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->renderReport($findings, $total);

        return self::SUCCESS;
    }

    private function finding(
        string $key,
        string $severity,
        string $title,
        int $count,
        array $examples,
        int $limit,
        string $note,
    ): array {
        return [
            'key'      => $key,
            'severity' => $severity,
            'title'    => $title,
            'count'    => $count,
            'examples' => array_slice(array_values($examples), 0, $limit),
            'note'     => $note,
        ];
    }

    private function renderReport(array $findings, int $total): void
    {
        $this->newLine();
        $this->line('<options=bold>BMH — Auditoría de calidad de datos</>');
        $this->line(sprintf('Base legacy: %s · %d productos', config('database.connections.mysql_legacy.database'), $total));
        $this->newLine();

        $bySeverity = [self::SEVERITY_HIGH => [], self::SEVERITY_MEDIUM => [], self::SEVERITY_LOW => []];

        foreach ($findings as $finding) {
            $bySeverity[$finding['severity']][] = $finding;
        }

        foreach ($bySeverity as $severity => $group) {
            if ($group === []) {
                continue;
            }

            $color = match ($severity) {
                self::SEVERITY_HIGH   => 'red',
                self::SEVERITY_MEDIUM => 'yellow',
                default               => 'gray',
            };

            $this->line("<fg={$color};options=bold>Severidad " . mb_strtoupper($severity) . "</>");
            $this->newLine();

            foreach ($group as $finding) {
                $this->line(sprintf('  <options=bold>%s</> — %d', $finding['title'], $finding['count']));

                if ($finding['note'] !== '') {
                    $this->line('    <fg=gray>' . $finding['note'] . '</>');
                }

                foreach ($finding['examples'] as $example) {
                    $this->line('      · ' . $example);
                }

                $this->newLine();
            }
        }

        $this->line(sprintf(
            '<options=bold>%d hallazgos</> · %d de severidad alta',
            count($findings),
            count($bySeverity[self::SEVERITY_HIGH]),
        ));
        $this->newLine();
    }
}
