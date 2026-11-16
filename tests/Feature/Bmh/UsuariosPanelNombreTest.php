<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Un usuario del panel creado desde el admin se llama como su usuario (antes el formulario mandaba siempre «alberto»
 * en un campo oculto y el panel mostraba ese nombre). Base local; el usuario de prueba se borra.
 */
final class UsuariosPanelNombreTest extends BaseTestCase
{
    use CreatesApplication;

    private const USUARIO = 'zz-prueba-nombre';

    private string $origDefault = '';

    private array $origMysql = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->origDefault = config('database.default');
        $this->origMysql = config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');
        DB::table('admins')->where('username', self::USUARIO)->delete();
    }

    protected function tearDown(): void
    {
        DB::table('admins')->where('username', self::USUARIO)->delete();
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    public function test_el_usuario_nuevo_del_panel_se_llama_como_su_usuario(): void
    {
        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;

        $this->actingAs($admin, 'admin')
            ->get(route('nuevo.usuario'))->assertOk()->assertDontSee('value="alberto"', false);

        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), [
            'username' => self::USUARIO,
            'rol' => 'usuario',
            'email' => 'zz-prueba-nombre@example.invalid',
            'password' => 'Clave-de-prueba-123',
            'password_confirmation' => 'Clave-de-prueba-123',
        ])->assertSessionHasNoErrors();

        $this->assertSame(self::USUARIO, DB::table('admins')->where('username', self::USUARIO)->value('name'));
        $this->assertSame(0, DB::table('admins')->where('name', 'alberto')->count());

        // Sin email no se crea (la tabla lo exige): vuelve con el error en su campo, no con un 500.
        DB::table('admins')->where('username', self::USUARIO)->delete();
        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), ['username' => self::USUARIO, 'rol' => 'usuario', 'password' => 'Clave-de-prueba-123', 'password_confirmation' => 'Clave-de-prueba-123'])
            ->assertSessionHasErrors('email');
    }
}
