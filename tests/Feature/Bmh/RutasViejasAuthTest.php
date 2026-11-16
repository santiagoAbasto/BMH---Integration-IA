<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Las rutas viejas de Breeze ya no existen; el cierre de sesión de los clientes sigue andando. */
final class RutasViejasAuthTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function rutasViejas(): array
    {
        return [
            'login de Breeze' => ['GET', '/login'],
            'login de Breeze (POST)' => ['POST', '/login'],
            'recuperar contraseña' => ['GET', '/forgot-password'],
            'recuperar contraseña (POST)' => ['POST', '/forgot-password'],
            'restablecer contraseña' => ['POST', '/reset-password'],
            'verificar email' => ['GET', '/verify-email'],
            'confirmar contraseña' => ['GET', '/confirm-password'],
            'perfil' => ['GET', '/profile'],
            'borrar la cuenta' => ['DELETE', '/profile'],
            'registro' => ['GET', '/register'],
        ];
    }

    #[DataProvider('rutasViejas')]
    public function test_la_ruta_vieja_no_existe(string $metodo, string $url): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->call($metodo, $url)->assertNotFound();
    }

    public function test_el_cliente_cierra_la_sesion(): void
    {
        $cliente = new User(['name' => 'Cliente', 'rol' => 'cliente']);
        $cliente->id = 999999;

        $this->actingAs($cliente, 'web')
            ->from('/')
            ->post(route('logout', ['ventana' => 'home']))
            ->assertRedirect('/');

        $this->assertGuest('web');
    }
}
