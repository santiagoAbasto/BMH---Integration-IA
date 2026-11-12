<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Middleware\EnsureHumanFormSubmission;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Anti-bots de los logins (clientes por AJAX y panel) y del registro público de clientes: honeypot, token de
 * tiempo y rate limits, sin romper el flujo de cada formulario. SQLite en memoria.
 */
final class LoginsAntiBotsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        foreach (['users', 'admins'] as $tabla) {
            Schema::create($tabla, function (Blueprint $t): void {
                $t->id();
                $t->string('name')->nullable();
                $t->string('username')->nullable();
                $t->string('email')->nullable();
                $t->string('password')->nullable();
                $t->string('rol')->nullable();
                $t->boolean('habilitado')->nullable();
                foreach (['dni', 'direccion', 'localidad', 'provincia', 'cp', 'celular'] as $columna) {
                    $t->string($columna)->nullable();
                }
                $t->rememberToken();
                $t->timestamps();
            });
        }

        // La pantalla de login del panel lee el logo.
        Schema::create('imagenes', function (Blueprint $t): void {
            $t->id();
            $t->string('sector')->nullable();
            $t->string('path')->nullable();
            $t->timestamps();
        });

        User::query()->forceCreate(['name' => 'Cliente', 'username' => 'cliente-antibots', 'rol' => 'cliente', 'habilitado' => true, 'password' => Hash::make('Clave-Segura-123')]);
        Admin::query()->forceCreate(['name' => 'Admin', 'username' => 'admin-antibots', 'rol' => 'administrador', 'password' => Hash::make('Clave-Segura-123')]);
    }

    private function antiBots(): array
    {
        $token = EnsureHumanFormSubmission::token();
        $this->travel(3)->seconds();

        return [EnsureHumanFormSubmission::HONEYPOT => '', EnsureHumanFormSubmission::TOKEN => $token];
    }

    // --- Login de clientes (AJAX desde el header) ---

    public function test_el_login_de_clientes_sigue_devolviendo_la_url_de_redireccion(): void
    {
        $respuesta = $this->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots()])
            ->assertOk();

        $this->assertSame(route('productos.home'), $respuesta->getContent());
        $this->assertTrue(Auth::guard('web')->check());
    }

    public function test_un_login_de_clientes_fallido_trae_el_error_y_un_token_nuevo_para_reintentar(): void
    {
        $respuesta = $this->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'otra', ...$this->antiBots()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertHeader(EnsureHumanFormSubmission::HEADER);

        // El JS del header pone el token nuevo en el formulario y reintenta: entra.
        $this->travel(3)->seconds();
        $this->postJson(route('user.login'), [
            'input_type' => 'cliente-antibots', 'password' => 'Clave-Segura-123',
            EnsureHumanFormSubmission::TOKEN => $respuesta->headers->get(EnsureHumanFormSubmission::HEADER),
        ])->assertOk();
    }

    public function test_el_login_de_clientes_sin_token_o_con_el_honeypot_no_entra(): void
    {
        $this->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'Clave-Segura-123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', EnsureHumanFormSubmission::MESSAGE);

        $this->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots(), 'website' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_el_exceso_de_intentos_de_login_de_clientes_devuelve_429_con_el_error_para_el_formulario(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(route('user.login'), ['input_type' => "nadie{$i}", 'password' => 'x', ...$this->antiBots()])->assertStatus(422);
        }

        $this->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots()])
            ->assertStatus(429)
            ->assertJsonStructure(['errors' => ['username']]);
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_el_rate_limit_por_cuenta_frena_aunque_cambie_la_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'mal', ...$this->antiBots()]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson(route('user.login'), ['input_type' => 'cliente-antibots', 'password' => 'mal', ...$this->antiBots()])
            ->assertStatus(429);
    }

    // --- Login del panel ---

    public function test_el_login_del_panel_sigue_funcionando(): void
    {
        $this->post(route('admin.login'), ['input_type' => 'admin-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots()])
            ->assertRedirect('/dashboard');
        $this->assertTrue(Auth::guard('admin')->check());
    }

    public function test_el_login_del_panel_sin_token_o_con_el_honeypot_vuelve_con_el_error(): void
    {
        $this->from(route('admin'))->post(route('admin.login'), ['input_type' => 'admin-antibots', 'password' => 'Clave-Segura-123'])
            ->assertRedirect(route('admin'))
            ->assertSessionHasErrors(['username' => EnsureHumanFormSubmission::MESSAGE]);

        $this->from(route('admin'))->post(route('admin.login'), ['input_type' => 'admin-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots(), 'website' => 'x'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);

        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_el_exceso_de_intentos_en_el_login_del_panel_se_frena(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('admin.login'), ['input_type' => "nadie{$i}", 'password' => 'x', ...$this->antiBots()]);
        }

        $this->from(route('admin'))->post(route('admin.login'), ['input_type' => 'admin-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots()])
            ->assertRedirect(route('admin'))
            ->assertSessionHasErrors('username');
        $this->postJson(route('admin.login'), ['input_type' => 'admin-antibots', 'password' => 'Clave-Segura-123', ...$this->antiBots()])
            ->assertStatus(429);
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_la_pantalla_de_login_del_panel_trae_los_campos_anti_bots(): void
    {
        $html = $this->get(route('admin'))->assertOk()->getContent();

        $this->assertStringContainsString('name="website"', $html);
        $this->assertStringContainsString('name="_form_token"', $html);
    }

    // --- Registro público de clientes ---

    private function registro(): array
    {
        return [
            'nombre' => 'Taller Nuevo', 'username' => 'taller-nuevo', 'email' => 'taller@example.com',
            'password' => 'Clave-Segura-123', 'password_confirmation' => 'Clave-Segura-123',
            'dni' => '20123456', 'direccion' => 'Calle 1', 'localidad' => 'Morón', 'provincia' => 'Buenos Aires', 'celular' => '1144822609', 'cp' => '1708',
        ];
    }

    public function test_el_registro_legitimo_crea_el_cliente(): void
    {
        $this->post(route('crear.usuario', ['rol' => 'cliente']), [...$this->registro(), ...$this->antiBots()])
            ->assertRedirect(route('home', ['registro' => true]));

        $this->assertSame(1, User::query()->where('username', 'taller-nuevo')->count());
    }

    public function test_el_registro_con_el_honeypot_finge_el_alta_pero_no_crea_nada(): void
    {
        $this->post(route('crear.usuario', ['rol' => 'cliente']), [...$this->registro(), ...$this->antiBots(), 'website' => 'spam'])
            ->assertRedirect(route('home', ['registro' => true]));

        $this->assertSame(0, User::query()->where('username', 'taller-nuevo')->count());
    }

    public function test_el_registro_demasiado_rapido_o_repetido_se_rechaza_y_el_exceso_devuelve_429(): void
    {
        $this->post(route('crear.usuario', ['rol' => 'cliente']), [...$this->registro(), EnsureHumanFormSubmission::TOKEN => EnsureHumanFormSubmission::token()])
            ->assertSessionHasErrors(['username' => EnsureHumanFormSubmission::MESSAGE]);

        for ($i = 2; $i <= 5; $i++) {
            $this->post(route('crear.usuario', ['rol' => 'cliente']), [...$this->registro(), 'username' => "bot{$i}", 'email' => "bot{$i}@example.com", ...$this->antiBots()]);
        }

        $this->postJson(route('crear.usuario', ['rol' => 'cliente']), [...$this->registro(), 'username' => 'bot6', ...$this->antiBots()])
            ->assertStatus(429);
        $this->assertSame(4, User::query()->where('username', 'like', 'bot%')->count());
    }
}
