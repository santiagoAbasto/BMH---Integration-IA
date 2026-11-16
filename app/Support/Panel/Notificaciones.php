<?php

declare(strict_types=1);

namespace App\Support\Panel;

use App\Models\ContactoMensaje;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Campana del panel (como en Conelec): pedidos que todavía no se abrieron, consultas de contacto sin leer y clientes
 * nuevos sin revisar, del más nuevo al más viejo. Cada aviso lleva a su lugar, que lo marca como visto.
 */
final class Notificaciones
{
    public const LIMITE = 8;

    private ?array $cuentas = null;

    /** @return array{pedidos: int, consultas: int, clientes: int} */
    public function cuentas(): array
    {
        return $this->cuentas ??= [
            'pedidos' => $this->contar(fn () => Pedido::query()->whereNull('visto_at')->count(), 'pedidos', 'visto_at'),
            'consultas' => $this->contar(fn () => ContactoMensaje::query()->noLeidos()->count(), 'contacto_mensajes', 'leido_at'),
            'clientes' => $this->contar(fn () => User::query()->where('rol', 'cliente')->whereNull('revisado_at')->count(), 'users', 'revisado_at'),
        ];
    }

    public function sinVer(): int
    {
        return array_sum($this->cuentas());
    }

    /** @return Collection<int, array{key: string, tipo: string, etiqueta: string, nombre: string, detalle: string, url: string, fecha: Carbon}> */
    public function ultimas(): Collection
    {
        $cuentas = $this->cuentas();
        if (array_sum($cuentas) === 0) {
            return collect();
        }

        $pedidos = $cuentas['pedidos'] === 0 ? collect() : Pedido::query()->whereNull('visto_at')->latest()->latest('id')->limit(self::LIMITE)->get()
            ->map(fn (Pedido $pedido): array => [
                'key' => 'pedido-'.$pedido->id,
                'tipo' => 'pedido',
                'etiqueta' => 'Pedido #'.$pedido->id,
                'nombre' => (string) $pedido->nombre,
                'detalle' => '$ '.$pedido->total_pedido.($pedido->tipo_envio ? ' · '.$pedido->tipo_envio : ''),
                'url' => route('pedido.datos', ['id' => $pedido->id]),
                'fecha' => $pedido->created_at,
            ]);

        $consultas = $cuentas['consultas'] === 0 ? collect() : ContactoMensaje::query()->noLeidos()->latest()->latest('id')->limit(self::LIMITE)->get()
            ->map(fn (ContactoMensaje $mensaje): array => [
                'key' => 'consulta-'.$mensaje->id,
                'tipo' => 'consulta',
                'etiqueta' => 'Consulta'.($mensaje->motivo ? ' · '.$mensaje->motivo : ''),
                'nombre' => (string) $mensaje->nombre,
                'detalle' => Str::limit((string) $mensaje->mensaje, 70),
                'url' => route('dashboard.contacto.mensajes', ['mensaje' => $mensaje->id]),
                'fecha' => $mensaje->created_at,
            ]);

        $clientes = $cuentas['clientes'] === 0 ? collect() : User::query()->where('rol', 'cliente')->whereNull('revisado_at')->latest()->latest('id')->limit(self::LIMITE)->get()
            ->map(fn (User $cliente): array => [
                'key' => 'cliente-'.$cliente->id,
                'tipo' => 'cliente',
                'etiqueta' => 'Cliente nuevo',
                'nombre' => (string) $cliente->name,
                'detalle' => collect([$cliente->localidad, $cliente->provincia])->filter()->implode(', ') ?: 'Se registró en la Zona de Clientes.',
                'url' => route('panel.cliente.revisar', $cliente),
                'fecha' => $cliente->created_at,
            ]);

        return $pedidos->concat($consultas)->concat($clientes)
            ->filter(fn (array $aviso): bool => $aviso['fecha'] !== null)
            ->sortByDesc('fecha')->take(self::LIMITE)->values();
    }

    /** Sin la columna (migración sin correr) o sin base, la campana queda en 0 en vez de romper el panel. */
    private function contar(\Closure $consulta, string $tabla, string $columna): int
    {
        try {
            return Schema::hasColumn($tabla, $columna) ? (int) $consulta() : 0;
        } catch (Throwable) {
            return 0;
        }
    }
}
