<?php

declare(strict_types=1);

namespace App\Support\Panel;

use App\Models\Categoria;
use App\Models\Contacto;
use App\Models\ContactoMensaje;
use App\Models\MarcaAliada;
use App\Models\Novedad;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Datos del inicio del panel (como Conelec y Strand, pero de BMH): pedidos, facturación, clientes nuevos y
 * consultas por día en hora argentina de los últimos 180 días (los 90 del rango más largo y los 90 anteriores para
 * comparar). El filtro de 7/30/90 días recorta en el navegador (resources/js/admin/panel.js), sin volver a consultar.
 */
final class MetricasPanel
{
    public const ZONA = 'America/Argentina/Buenos_Aires';

    public const RANGOS = [7, 30, 90];

    public const RANGO = 30;

    private const DIAS = 180;

    private CarbonImmutable $hoy;

    public function __construct(private readonly Notificaciones $notificaciones)
    {
        $this->hoy = CarbonImmutable::now(self::ZONA)->startOfDay();
    }

    /** @return array<string, mixed> */
    public function todo(): array
    {
        return [
            'actividad' => $this->actividad(),
            'pendientes' => $this->pendientes(),
            'pedidos' => $this->ultimosPedidos(),
            'consultas' => $this->ultimasConsultas(),
            'estado' => $this->estado(),
            'catalogo' => $this->catalogo(),
        ];
    }

    private function actividad(): array
    {
        $pedidos = Pedido::query()->where('created_at', '>=', $this->desde(self::DIAS))->get(['id', 'created_at', 'total_pedido']);

        return [
            'fin' => $this->hoy->toDateString(),
            'dias' => self::DIAS,
            'rangos' => self::RANGOS,
            'rango' => self::RANGO,
            'series' => [
                ['key' => 'pedidos', 'label' => 'Pedidos', 'short' => 'Pedidos', 'color' => '#0098da', 'url' => route('ventas'), 'daily' => $this->porDia($pedidos->pluck('created_at'))],
                ['key' => 'clientes', 'label' => 'Clientes nuevos', 'short' => 'Clientes', 'color' => '#1baf7a', 'url' => route('dashboard.clientes'), 'daily' => $this->porDia($this->fechas(User::query()->where('rol', 'cliente')))],
                ['key' => 'consultas', 'label' => 'Consultas', 'short' => 'Consultas', 'color' => '#eb6834', 'url' => route('dashboard.contacto.mensajes'), 'daily' => $this->porDia($this->fechas(ContactoMensaje::query(), 'contacto_mensajes'))],
            ],
            // La facturación va aparte: es plata y no entra en la misma escala del gráfico.
            'facturacion' => $this->porDia($pedidos->pluck('created_at'), $pedidos->map(fn (Pedido $p): float => self::importe($p->total_pedido))->all()),
            'productos' => $this->masPedidos('productos'),
            'categorias' => $this->masPedidos('categorias'),
        ];
    }

    /**
     * Cuenta (o suma $valores) por día, del más viejo al de hoy.
     *
     * @param  Collection<int, mixed>  $fechas
     * @param  list<float>|null  $valores
     * @return list<float|int>
     */
    private function porDia(Collection $fechas, ?array $valores = null): array
    {
        $dias = array_fill(0, self::DIAS, 0);
        $primero = $this->hoy->subDays(self::DIAS - 1);
        foreach ($fechas->values() as $i => $fecha) {
            if ($fecha === null) {
                continue;
            }
            $indice = (int) $primero->diffInDays(CarbonImmutable::parse($fecha)->setTimezone(self::ZONA)->startOfDay());
            if ($indice >= 0 && $indice < self::DIAS) {
                $dias[$indice] += $valores === null ? 1 : round($valores[$i] ?? 0, 2);
            }
        }

        return $dias;
    }

    /** @return Collection<int, mixed> */
    private function fechas(Builder $consulta, ?string $tabla = null): Collection
    {
        if ($tabla !== null && ! Schema::hasTable($tabla)) {
            return collect();
        }

        return $consulta->where('created_at', '>=', $this->desde(self::DIAS))->pluck('created_at');
    }

    /**
     * Lo más pedido en cada rango: productos (por unidades) o categorías (por unidades de sus productos).
     *
     * @return array<int, list<array{label: string, value: int, url: string|null}>>
     */
    private function masPedidos(string $que): array
    {
        $filas = DB::table('pedido_producto as pp')
            ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
            ->join('productos as pr', 'pr.id', '=', 'pp.producto_id')
            ->leftJoin('categorias as c', 'c.id', '=', 'pr.categoria_id')
            ->where('pe.created_at', '>=', $this->desde(max(self::RANGOS)))
            ->get(['pe.created_at', 'pp.cantidad', 'pr.id as producto_id', 'pr.codigo', 'pr.nombre', 'c.id as categoria_id', 'c.nombre as categoria']);

        return collect(self::RANGOS)->mapWithKeys(fn (int $dias): array => [
            $dias => $filas->filter(fn (object $f): bool => CarbonImmutable::parse($f->created_at) >= $this->desde($dias))
                ->groupBy($que === 'productos' ? 'producto_id' : 'categoria_id')
                ->map(function (Collection $grupo) use ($que): array {
                    $f = $grupo->first();

                    return $que === 'productos'
                        ? ['label' => trim(($f->codigo ? $f->codigo.' · ' : '').Str::limit((string) $f->nombre, 38)), 'value' => (int) $grupo->sum('cantidad'), 'url' => route('producto.edit', ['id' => $f->producto_id])]
                        : ['label' => $f->categoria ? mb_convert_case($f->categoria, MB_CASE_TITLE, 'UTF-8') : 'Sin categoría', 'value' => (int) $grupo->sum('cantidad'), 'url' => $f->categoria_id ? route('productos', ['categoria' => $f->categoria_id]) : null];
                })
                ->sortByDesc('value')->take(6)->values()->all(),
        ])->all();
    }

    private function desde(int $dias): CarbonImmutable
    {
        return $this->hoy->subDays($dias - 1)->utc();
    }

    /** «327.290,48» (como se guarda el total del pedido) → 327290.48. */
    public static function importe(?string $texto): float
    {
        $limpio = preg_replace('/[^\d,.-]/', '', (string) $texto);
        if (str_contains($limpio, ',')) {
            $limpio = str_replace(['.', ','], ['', '.'], $limpio);
        }

        return (float) $limpio;
    }

    /** @return list<array{label: string, one: string, count: int, url: string}> */
    private function pendientes(): array
    {
        $cuentas = $this->notificaciones->cuentas();

        return array_values(array_filter([
            ['label' => 'pedidos sin abrir', 'one' => 'pedido sin abrir', 'count' => $cuentas['pedidos'], 'url' => route('ventas')],
            ['label' => 'consultas sin leer', 'one' => 'consulta sin leer', 'count' => $cuentas['consultas'], 'url' => route('dashboard.contacto.mensajes', ['filtro' => 'no-leidos'])],
            ['label' => 'clientes nuevos', 'one' => 'cliente nuevo', 'count' => $cuentas['clientes'], 'url' => route('dashboard.clientes')],
        ], fn (array $item): bool => $item['count'] > 0));
    }

    private function ultimosPedidos(): Collection
    {
        return Pedido::query()->latest('id')->limit(6)->get()->map(fn (Pedido $p): array => [
            'id' => $p->id,
            'nombre' => (string) $p->nombre,
            'total' => self::importe($p->total_pedido),
            'estado' => (string) ($p->estado_orden ?: $p->estado),
            'pago' => (string) $p->estado,
            'nuevo' => Schema::hasColumn('pedidos', 'visto_at') && $p->visto_at === null,
            'fecha' => $p->created_at,
            'url' => route('pedido.datos', ['id' => $p->id]),
        ]);
    }

    private function ultimasConsultas(): Collection
    {
        if (! Schema::hasTable('contacto_mensajes')) {
            return collect();
        }

        return ContactoMensaje::query()->latest()->latest('id')->limit(5)->get()->map(fn (ContactoMensaje $m): array => [
            'nombre' => (string) $m->nombre,
            'detalle' => Str::limit(trim(($m->motivo ? $m->motivo.' · ' : '').$m->mensaje), 80),
            'nuevo' => $m->leido_at === null,
            'fecha' => $m->created_at,
            'url' => route('dashboard.contacto.mensajes', ['mensaje' => $m->id]),
        ]);
    }

    /** Lo que conviene tener completo para que el sitio venda y se vea bien. */
    private function estado(): array
    {
        $contacto = Contacto::actual();
        $sinFoto = Producto::query()->doesntHave('portadaImagen')->count();
        $sinPrecio = Producto::query()->where(fn ($q) => $q->whereNull('precio')->orWhere('precio', '<=', 0))->count();
        $categoriasSinFoto = Categoria::query()->where(fn ($q) => $q->whereNull('portada')->orWhere('portada', ''))->count();
        $logos = Schema::hasTable('marcas_aliadas') ? MarcaAliada::query()->visibles()->count() : 0;

        return [
            ['label' => 'Avisos por email', 'ok' => $contacto->destinatariosAviso() !== [], 'detail' => $contacto->destinatariosAviso() !== [] ? 'Llegan a '.implode(', ', $contacto->destinatariosAviso()).'.' : 'Cargá a qué cuenta llegan consultas y pedidos.', 'url' => route('dashboard.contacto').'#avisos'],
            ['label' => 'WhatsApp del sitio', 'ok' => filled($contacto->whatsapp), 'detail' => filled($contacto->whatsapp) ? 'El botón flotante abre el chat.' : 'Sin número, no aparece el botón.', 'url' => route('dashboard.contacto')],
            ['label' => 'Productos con foto', 'ok' => $sinFoto === 0, 'detail' => $sinFoto === 0 ? 'Todos tienen foto de portada.' : number_format($sinFoto, 0, ',', '.').' sin foto de portada.', 'url' => route('dashboard.productos')],
            ['label' => 'Productos con precio', 'ok' => $sinPrecio === 0, 'detail' => $sinPrecio === 0 ? 'Todos tienen precio de lista.' : number_format($sinPrecio, 0, ',', '.').' sin precio: no se pueden pedir.', 'url' => route('dashboard.productos')],
            ['label' => 'Categorías con foto', 'ok' => $categoriasSinFoto === 0, 'detail' => $categoriasSinFoto === 0 ? 'Todas tienen portada.' : $categoriasSinFoto.' sin portada en la grilla.', 'url' => route('dashboard.categorias')],
            ['label' => 'Trabajamos con', 'ok' => $logos > 0, 'detail' => $logos > 0 ? $logos.' logos en la Home.' : 'Sin logos, la franja no se muestra.', 'url' => route('dashboard.home.trabajamos-con')],
        ];
    }

    private function catalogo(): array
    {
        return [
            ['label' => 'Productos', 'count' => Producto::query()->count(), 'url' => route('dashboard.productos')],
            ['label' => 'Categorías', 'count' => Categoria::query()->count(), 'url' => route('dashboard.categorias')],
            ['label' => 'En oferta', 'count' => Producto::query()->whereNotNull('oferta')->count(), 'url' => route('dashboard.productos', ['oferta' => 1])],
            ['label' => 'Clientes', 'count' => User::query()->where('rol', 'cliente')->count(), 'url' => route('dashboard.clientes')],
            ['label' => 'Novedades', 'count' => Novedad::query()->count(), 'url' => route('dashboard.novedades')],
        ];
    }
}
