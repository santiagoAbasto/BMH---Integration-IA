<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Datos del menú desplegable de «Productos» en el header
 * (layouts.partials.menu-productos): las categorías con su imagen y cuántos
 * productos publicados tiene cada una.
 *
 * El header está en todas las páginas, así que se cachea. Categoria limpia la
 * caché al guardarse o borrarse; las cantidades se refrescan solas al vencer.
 */
final class MenuProductosComposer
{
    public const CACHE = 'menu-productos';

    private const MINUTOS = 10;

    public function compose(View $view): void
    {
        $view->with('categoriasMenu', Cache::remember(self::CACHE, now()->addMinutes(self::MINUTOS), fn () => $this->categorias()));
    }

    /** @return list<array{id: int, nombre: string, imagen: ?string, cantidad: int, destacada: bool}> */
    private function categorias(): array
    {
        $cantidades = Producto::query()
            ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
            ->groupBy('categoria_id')
            ->selectRaw('categoria_id, COUNT(*) as cantidad')
            ->pluck('cantidad', 'categoria_id');

        return Categoria::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'portada', 'destacada'])
            ->map(fn (Categoria $c) => [
                'id' => (int) $c->id,
                'nombre' => mb_convert_case((string) $c->nombre, MB_CASE_TITLE, 'UTF-8'),
                'imagen' => $c->portada && is_file(public_path('imagenes/'.$c->portada)) ? 'imagenes/'.$c->portada : null,
                'cantidad' => (int) ($cantidades[$c->id] ?? 0),
                'destacada' => (bool) $c->destacada,
            ])
            ->values()
            ->all();
    }
}
