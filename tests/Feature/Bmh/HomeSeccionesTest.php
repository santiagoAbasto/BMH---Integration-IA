<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\HomeNosotros;
use App\Models\MarcaAliada;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\CreatesApplication;

/**
 * Admin → Home › Trabajamos con (logos con link opcional) y Home › Sección Nosotros (imagen, título, descripción y
 * botón). Base local, como OfertasTest: lo que se crea se borra y la sección se deja como estaba.
 */
final class HomeSeccionesTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    /** @var array<string, mixed>|null */
    private ?array $seccionOriginal = null;

    /** @var list<int> */
    private array $marcasPrevias = [];

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
        $this->seccionOriginal = (array) (DB::table('home_nosotros')->first() ?? []);
        $this->marcasPrevias = DB::table('marcas_aliadas')->pluck('id')->all();
    }

    protected function tearDown(): void
    {
        foreach (MarcaAliada::query()->whereNotIn('id', $this->marcasPrevias)->get() as $marca) {
            File::delete(public_path($marca->logo));
            $marca->delete();
        }
        if ($this->seccionOriginal !== []) {
            $actual = HomeNosotros::query()->first();
            if ($actual && $actual->imagen !== $this->seccionOriginal['imagen'] && $actual->imagenSubida()) {
                File::delete(public_path($actual->imagen));
            }
            DB::table('home_nosotros')->where('id', $this->seccionOriginal['id'])->update($this->seccionOriginal);
        }
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function admin(): Admin
    {
        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;

        return $admin;
    }

    public function test_el_admin_agrega_un_logo_con_link_y_aparece_en_la_home(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('dashboard.home.trabajamos-con.store'), [
                'nombre' => 'Proveedor de prueba',
                'url' => 'www.proveedor-prueba.com',
                'orden' => 'zz',
                'visible' => '1',
                'logo' => UploadedFile::fake()->image('logo.png', 300, 160),
            ])
            ->assertRedirect(route('dashboard.home.trabajamos-con'))
            ->assertSessionHasNoErrors();

        $marca = MarcaAliada::query()->where('nombre', 'Proveedor de prueba')->firstOrFail();
        $this->assertSame('https://www.proveedor-prueba.com', $marca->url);
        $this->assertMatchesRegularExpression('#^imagenes/marca_[a-f0-9]{12}\.png$#', $marca->logo);
        $this->assertFileExists(public_path($marca->logo));

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('alt="Proveedor de prueba"', $home);
        $this->assertStringContainsString('href="https://www.proveedor-prueba.com"', $home);

        // Oculto, no se ve; eliminado, se borra también el archivo.
        $this->actingAs($this->admin(), 'admin')->patch(route('dashboard.home.trabajamos-con.visible', $marca), ['visible' => '0']);
        $this->assertStringNotContainsString('alt="Proveedor de prueba"', $this->get(route('home'))->getContent());

        $archivo = public_path($marca->logo);
        $this->actingAs($this->admin(), 'admin')->delete(route('dashboard.home.trabajamos-con.destroy', $marca));
        $this->assertModelMissing($marca);
        $this->assertFileDoesNotExist($archivo);
    }

    public function test_un_logo_sin_archivo_o_con_link_invalido_vuelve_con_errores(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->from(route('dashboard.home.trabajamos-con'))
            ->post(route('dashboard.home.trabajamos-con.store'), ['nombre' => '', 'url' => 'no es un link', 'orden' => 'abc'])
            ->assertSessionHasErrors(['nombre', 'logo', 'url', 'orden']);
    }

    public function test_la_seccion_nosotros_guarda_titulo_descripcion_y_boton(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('dashboard.home.seccion-nosotros.update'), [
                'titulo' => 'Título de prueba de la sección',
                'descripcion' => '<p>Texto <strong>destacado</strong> de prueba<script>alert(1)</script></p>',
                'boton_texto' => 'Ver la planta',
                'boton_url' => '/nosotros',
            ])
            ->assertRedirect(route('dashboard.home.seccion-nosotros'))
            ->assertSessionHasNoErrors();

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('Título de prueba de la sección', $home);
        $this->assertStringContainsString('<strong>destacado</strong>', $home);
        $this->assertStringNotContainsString('alert(1)', $home);
        $this->assertMatchesRegularExpression('#href="[^"]*/nosotros" class="osole-info-split-v1__button">Ver la planta#', $home);

        // Sin texto ni link, la sección queda sin botón.
        $this->actingAs($this->admin(), 'admin')->put(route('dashboard.home.seccion-nosotros.update'), ['titulo' => 'Sin botón de prueba', 'boton_texto' => '', 'boton_url' => '']);
        $this->assertStringNotContainsString('osole-info-split-v1__button', $this->get(route('home'))->getContent());

        // Sólo uno de los dos: error.
        $this->actingAs($this->admin(), 'admin')
            ->put(route('dashboard.home.seccion-nosotros.update'), ['titulo' => 'Título', 'boton_texto' => 'Ver', 'boton_url' => ''])
            ->assertSessionHasErrors('boton_url');
    }

    public function test_las_pantallas_del_admin_abren_y_un_visitante_no_entra(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.home.trabajamos-con'))->assertOk()->assertSee('Así se ve en la Home');
        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.home.seccion-nosotros'))->assertOk()->assertSee('Vista previa');
        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.logo'))->assertRedirect(route('dashboard.home.seccion-nosotros'));

        auth('admin')->logout();
        $this->get(route('dashboard.home.trabajamos-con'))->assertRedirect();
        $this->post(route('dashboard.home.trabajamos-con.store'), ['nombre' => 'X'])->assertRedirect();
        $this->assertDatabaseMissing('marcas_aliadas', ['nombre' => 'X']);
    }
}
