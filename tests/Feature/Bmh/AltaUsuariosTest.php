<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Middleware\EnsureHumanFormSubmission;
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
                $t->decimal('descuento', 8, 2)->nullable();
                $t->rememberToken();
                $t->timestamps();
            });
        }
    }

    private function datosAdmin(string $rol): array
    {
        return ['rol' => $rol, 'name' => 'Nuevo', 'username' => 'nuevo'.$rol, 'email' => 'nuevo'.$rol.'@example.invalid', 'password' => 'Clave-Segura-123', 'password_confirmation' => 'Clave-Segura-123'];
    }

    /** Los campos del anti-bots (sin sesión del panel, la ruta exige <x-anti-bots />): formulario abierto hace segundos. */
    private function antiBots(): array
    {
        $token = EnsureHumanFormSubmission::token();
        $this->travel(3)->seconds();

        return [EnsureHumanFormSubmission::HONEYPOT => '', EnsureHumanFormSubmission::TOKEN => $token];
    }

    public function test_sin_sesion_de_administrador_no_se_puede_crear_un_acceso_al_panel(): void
    {
        foreach (['administrador', 'usuario', 'vendedor', 'clienteadm', ''] as $rol) {
            $this->post(route('crear.usuario'), [...$this->datosAdmin($rol), ...$this->antiBots()])->assertForbidden();
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

    /**
     * Rol «Cliente» del formulario del panel (clienteadm): no lleva email (el formulario lo oculta). El arreglo del
     * 16/11 que le pidió email a los usuarios del panel se lo pedía también a este rol y no dejaba crear clientes.
     */
    public function test_un_administrador_crea_un_cliente_sin_email(): void
    {
        $admin = Admin::query()->forceCreate(['name' => 'BMH', 'username' => 'bmh', 'rol' => 'administrador']);
        $datos = ['rol' => 'clienteadm', 'username' => 'ricciardi', 'descuento' => '10', 'password' => 'Clave-Segura-123', 'password_confirmation' => 'Clave-Segura-123'];

        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), $datos)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $cliente = User::query()->where('username', 'ricciardi')->first();
        $this->assertNotNull($cliente, 'crea el cliente');
        $this->assertSame('cliente', $cliente->rol);
        $this->assertEquals(10, $cliente->descuento);
        $this->assertSame(0, Admin::query()->where('username', 'ricciardi')->count(), 'no crea un usuario del panel');

        // El usuario no se puede repetir, y el error vuelve con el rol elegido.
        $this->actingAs($admin, 'admin')->post(route('crear.usuario'), $datos)
            ->assertSessionHasErrors('username')
            ->assertSessionHasInput('rol', 'clienteadm');
        $this->assertSame(1, User::query()->where('username', 'ricciardi')->count());
    }

    public function test_el_registro_publico_de_breeze_ya_no_existe(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->datosAdmin('administrador'))->assertNotFound();
        $this->assertSame(0, Admin::query()->count());
    }
}
