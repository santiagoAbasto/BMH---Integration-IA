<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Alta de usuarios (dashboard/crear-usuario). La ruta es pública porque la usa
 * el formulario de registro de clientes de la home, pero el resto (vendedores,
 * clientes cargados por BMH y usuarios del panel) sólo lo puede hacer un
 * administrador: antes cualquiera podía crearse un acceso al panel.
 * SQLite en memoria.
 */
final class AltaUsuariosTest extends TestCase
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
                $t->rememberToken();
                $t->timestamps();
            });
        }
    }

    private function datosAdmin(string $rol): array
    {
        return ['rol' => $rol, 'name' => 'Nuevo', 'username' => 'nuevo'.$rol, 'password' => 'Clave-Segura-123', 'password_confirmation' => 'Clave-Segura-123'];
    }

    public function test_sin_sesion_de_administrador_no_se_puede_crear_un_acceso_al_panel(): void
    {
        foreach (['administrador', 'usuario', 'vendedor', 'clienteadm', ''] as $rol) {
            $this->post(route('crear.usuario'), $this->datosAdmin($rol))->assertForbidden();
        }
        $this->assertSame(0, Admin::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_un_administrador_sigue_creando_usuarios_del_panel(): void
    {
        $admin = Admin::query()->forceCreate(['name' => 'BMH', 'username' => 'bmh', 'rol' => 'administrador']);

        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), $this->datosAdmin('usuario'))->assertRedirect();

        $creado = Admin::query()->where('username', 'nuevousuario')->first();
        $this->assertNotNull($creado);
        $this->assertSame('usuario', $creado->rol);
        $this->assertSame(0, User::query()->count(), 'no crea un cliente en su lugar');

        // Sólo los roles del panel.
        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), $this->datosAdmin('superadmin'))->assertStatus(422);
    }

    public function test_el_registro_publico_de_breeze_ya_no_existe(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->datosAdmin('administrador'))->assertNotFound();
        $this->assertSame(0, Admin::query()->count());
    }
}
