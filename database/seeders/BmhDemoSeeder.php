<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Escenarios de demo.
 *
 * Crea clientes de prueba con credenciales conocidas sobre la COPIA LOCAL de la
 * base de BMH. Los clientes reales del dump conservan sus hashes de producción
 * y nunca se tocan: nadie tiene que conocer una contraseña real para ver la
 * demo.
 *
 *     php artisan db:seed --class=BmhDemoSeeder
 *
 * Se niega a correr si la base no parece una copia local. No queremos que esto
 * se ejecute por accidente contra producción.
 */
final class BmhDemoSeeder extends Seeder
{
    private const PASSWORD = 'demo1234';

    /** Bases donde este seeder tiene permitido escribir. */
    private const ALLOWED_DATABASES = ['bmh_legacy', 'bmh_legacy_demo', 'bmh_local'];

    public function run(): void
    {
        $this->guardAgainstProduction();

        $customers = [
            [
                'codigo'    => 'DEMO-001',
                'name'      => 'Taller Demo BMH',
                'username'  => 'demo',
                'email'     => 'demo@bmh.local',
                'descuento' => 14,
                'reventa'   => 45,
                'nota'      => 'Cliente mayorista con acuerdo del 14 %.',
            ],
            [
                'codigo'    => 'DEMO-002',
                'name'      => 'Rectificaciones Sur',
                'username'  => 'demo-lista',
                'email'     => 'demo-lista@bmh.local',
                'descuento' => 0,
                'reventa'   => null,
                'nota'      => 'Cliente sin acuerdo: paga precio de lista.',
            ],
            [
                'codigo'    => 'DEMO-003',
                'name'      => 'Electricidad Norte',
                'username'  => 'demo-pendiente',
                'email'     => 'demo-pendiente@bmh.local',
                'descuento' => 0,
                'reventa'   => null,
                'habilitado'=> 0,
                'nota'      => 'Cuenta NO habilitada: no puede ver precios.',
            ],
        ];

        $created = [];

        foreach ($customers as $customer) {
            $id = $this->upsertCustomer($customer);

            $created[] = [$customer['username'], self::PASSWORD, $customer['nota']];

            // Al mayorista se le arma historial para poder demostrar
            // "el que compré la última vez".
            if ($customer['codigo'] === 'DEMO-001') {
                $this->seedOrderHistory($id, $customer);
            }
        }

        $this->command?->newLine();
        $this->command?->info('Usuarios de demo listos (Zona de Clientes → /asesor):');
        $this->command?->table(['Usuario', 'Contraseña', 'Escenario'], $created);
        $this->command?->newLine();
    }

    /**
     * Guarda de seguridad.
     *
     * La demo trabaja sobre una copia. Si alguien apunta el .env a producción,
     * este seeder no corre.
     */
    private function guardAgainstProduction(): void
    {
        $database = (string) config('database.connections.mysql.database');

        if (! in_array($database, self::ALLOWED_DATABASES, true)) {
            throw new RuntimeException(
                "BmhDemoSeeder sólo corre sobre una copia local. La conexión apunta a '{$database}'. "
                . 'Si es una copia, agregá el nombre a BmhDemoSeeder::ALLOWED_DATABASES.'
            );
        }

        if (app()->environment('production')) {
            throw new RuntimeException('BmhDemoSeeder no corre en producción.');
        }
    }

    private function upsertCustomer(array $customer): int
    {
        $existing = DB::table('users')->where('username', $customer['username'])->first();

        $payload = [
            'codigo'     => $customer['codigo'],
            'name'       => $customer['name'],
            'email'      => $customer['email'],
            'rol'        => 'cliente',
            'habilitado' => $customer['habilitado'] ?? 1,
            'descuento'  => $customer['descuento'],
            'reventa'    => $customer['reventa'],
            'provincia'  => 'Buenos Aires',
            'localidad'  => 'San Martín',
            'password'   => Hash::make(self::PASSWORD),
            'updated_at' => now(),
        ];

        if ($existing !== null) {
            DB::table('users')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        return (int) DB::table('users')->insertGetId([
            ...$payload,
            'username'   => $customer['username'],
            'created_at' => now(),
        ]);
    }

    /**
     * Historial de compras con productos REALES del catálogo.
     *
     * Se eligen artículos de rubros distintos para que "el rotor que compro
     * siempre" tenga con qué discriminar.
     */
    private function seedOrderHistory(int $customerId, array $customer): void
    {
        // Si ya tiene pedidos de demo, no se duplican: el seeder es idempotente.
        $existing = DB::table('pedidos')
            ->where('cliente_id', $customerId)
            ->where('notas', 'like', 'DEMO%')
            ->pluck('id');

        if ($existing->isNotEmpty()) {
            DB::table('pedido_producto')->whereIn('pedido_id', $existing)->delete();
            DB::table('pedidos')->whereIn('id', $existing)->delete();
        }

        // Un rotor y un inducido con precio real, para que el historial sirva.
        $products = DB::table('productos')
            ->whereIn('categoria_id', [41, 40])
            ->where('precio', '>', 1000)
            ->where('estado', '>', 0)
            ->orderBy('categoria_id')
            ->limit(4)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        foreach ([['2026-05-14', 0], ['2026-07-02', 2]] as $index => [$date, $offset]) {
            $lines = $products->slice($offset, 2);

            if ($lines->isEmpty()) {
                continue;
            }

            $subtotal = 0.0;

            $orderId = DB::table('pedidos')->insertGetId([
                'fecha'             => $date,
                'nombre'            => $customer['name'],
                'dni'               => '30000000',
                'mail'              => $customer['email'],
                'provincia'         => 'Buenos Aires',
                'localidad'         => 'San Martín',
                'direccion'         => 'Av. Demo 1234',
                'celular'           => '1100000000',
                'cp'                => '1650',
                'tipo_envio'        => 'Retiro cliente',
                'tipo_pago'         => 'Transferencia',
                'descuento_cliente' => (string) $customer['descuento'],
                'total_pedido'      => '0',
                'estado'            => 'Pago realizado',
                'estado_orden'      => $index === 0 ? 'Procesado' : 'Pendiente',
                'notas'             => 'DEMO — pedido de ejemplo generado por BmhDemoSeeder',
                'cliente_id'        => $customerId,
                'created_at'        => $date . ' 10:00:00',
                'updated_at'        => $date . ' 10:00:00',
            ]);

            foreach ($lines as $product) {
                $unit       = (float) $product->precio;
                $discounted = $unit * (1 - ($customer['descuento'] / 100));
                $quantity   = 1 + ($index % 2);

                $subtotal += $discounted * $quantity;

                DB::table('pedido_producto')->insert([
                    'cantidad'           => (string) $quantity,
                    'precio_unitario'    => number_format($unit, 2, ',', '.'),
                    'precio_descontado'  => number_format($discounted, 2, ',', '.'),
                    'descuento_producto' => '0',
                    'producto_id'        => $product->id,
                    'pedido_id'          => $orderId,
                    'created_at'         => $date . ' 10:00:00',
                    'updated_at'         => $date . ' 10:00:00',
                ]);
            }

            DB::table('pedidos')->where('id', $orderId)->update([
                'total_pedido' => number_format($subtotal, 2, ',', '.'),
            ]);
        }
    }
}
