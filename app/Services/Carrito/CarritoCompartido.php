<?php

declare(strict_types=1);

namespace App\Services\Carrito;

use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;

/**
 * El carrito del cliente guardado en la base, para que sea el mismo en todos
 * sus dispositivos.
 *
 * El paquete del carrito (codersfree/shoppingcart) lo guarda en la sesión,
 * que es de cada navegador. Acá la base es la fuente de verdad: en cada
 * request se carga en la sesión (SincronizarCarrito) y, si cambió, se
 * guarda. No se usa Cart::store/restore del paquete: guarda objetos PHP
 * serializados y restore mezcla en vez de reemplazar, así que un producto
 * sacado en un dispositivo volvería a aparecer.
 */
final class CarritoCompartido
{
    private const TABLA = 'carritos_clientes';

    /**
     * Base → sesión, reemplazando lo que hubiera. Si el cliente todavía no
     * tiene carrito guardado (por ejemplo, el primer request después de que
     * existiera esto), se guarda el de la sesión para no perderlo.
     */
    public function cargar(int $userId): void
    {
        $guardado = DB::table(self::TABLA)->where('user_id', $userId)->value('contenido');

        if ($guardado === null) {
            if (Cart::count() > 0) {
                $this->guardar($userId);
            }

            return;
        }

        $items = json_decode((string) $guardado, true);
        $this->reemplazar(is_array($items) ? $items : []);
    }

    /** Sesión → base. Un carrito vacío también se guarda: así se vacía en todos lados. */
    public function guardar(int $userId): void
    {
        $ahora = now();

        DB::table(self::TABLA)->upsert(
            [[
                'user_id' => $userId,
                'contenido' => json_encode($this->items(), JSON_UNESCAPED_UNICODE),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]],
            ['user_id'],
            ['contenido', 'updated_at'],
        );
    }

    /**
     * Al iniciar sesión: lo que el cliente agregó sin estar logueado se suma
     * a lo que tenía guardado. Si un producto está en los dos, queda la
     * cantidad mayor (sumarlas duplicaría lo que ya había cargado antes).
     */
    public function unirAlIngresar(int $userId): void
    {
        $guardado = DB::table(self::TABLA)->where('user_id', $userId)->value('contenido');
        $items = $guardado === null ? [] : (json_decode((string) $guardado, true) ?: []);

        $porClave = [];
        foreach ([...$items, ...$this->items()] as $item) {
            $clave = self::clave($item);
            if (! isset($porClave[$clave]) || $item['qty'] > $porClave[$clave]['qty']) {
                $porClave[$clave] = $item;
            }
        }

        $this->reemplazar(array_values($porClave));
        $this->guardar($userId);
    }

    /**
     * Saca del carrito los productos que ya no existen. Si un producto se
     * borra desde el admin, quedaba en el carrito guardado del cliente y
     * rompía la página del carrito y el pedido en todos sus dispositivos.
     *
     * @return bool si sacó alguno
     */
    public function quitarProductosInexistentes(): bool
    {
        $contenido = Cart::content();
        if ($contenido->isEmpty()) {
            return false;
        }

        $existentes = DB::table('productos')
            ->whereIn('id', $contenido->pluck('id')->unique()->values()->all())
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => true]);

        $quitados = false;
        foreach ($contenido as $item) {
            if (! $existentes->has((string) $item->id)) {
                Cart::remove($item->rowId);
                $quitados = true;
            }
        }

        return $quitados;
    }

    /** Para saber si un request cambió el carrito. */
    public function huella(): string
    {
        return md5((string) json_encode($this->items()));
    }

    /**
     * El carrito de la sesión como datos simples.
     *
     * @return list<array{id: mixed, name: string, qty: int|float, price: float, options: array<string, mixed>}>
     */
    private function items(): array
    {
        return Cart::content()
            ->map(fn ($item): array => [
                'id' => $item->id,
                'name' => (string) $item->name,
                'qty' => $item->qty,
                'price' => (float) $item->price,
                'options' => $item->options->toArray(),
            ])
            ->values()
            ->all();
    }

    /** @param  list<array<string, mixed>>  $items */
    private function reemplazar(array $items): void
    {
        Cart::destroy();

        foreach ($items as $item) {
            // Un ítem roto (producto sin nombre, cantidad inválida) se descarta
            // en vez de romper la página.
            if (empty($item['id']) || empty($item['name']) || ! is_numeric($item['qty'] ?? null) || $item['qty'] <= 0) {
                continue;
            }
            Cart::add($item['id'], $item['name'], $item['qty'], (float) ($item['price'] ?? 0), (array) ($item['options'] ?? []));
        }
    }

    /** Mismo producto con las mismas opciones = mismo ítem (como el rowId del paquete). */
    private static function clave(array $item): string
    {
        $opciones = (array) ($item['options'] ?? []);
        ksort($opciones);

        return $item['id'].'|'.serialize($opciones);
    }
}
