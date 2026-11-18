<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\CreatesApplication;

/**
 * Categorías › imagen de la tarjeta: el admin ve la medida recomendada y la vista previa al lado de otra categoría;
 * la imagen se valida, se guarda optimizada (WebP) y la anterior se borra. Base local con una categoría propia que
 * se borra al terminar; las imágenes van a un directorio temporal (config imagenes.directorio).
 */
final class ImagenCategoriaTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    private string $directorio;

    /** @var list<int> */
    private array $categorias = [];

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

        $this->directorio = sys_get_temp_dir().'/bmh_categorias_'.uniqid();
        File::ensureDirectoryExists($this->directorio);
        config(['imagenes.directorio' => $this->directorio]);
    }

    protected function tearDown(): void
    {
        DB::table('categorias')->whereIn('id', $this->categorias)->delete();
        File::deleteDirectory($this->directorio);
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

    private function categoria(?string $portada = null): int
    {
        return $this->categorias[] = DB::table('categorias')->insertGetId([
            'nombre' => 'ZZ IMAGEN DE PRUEBA '.uniqid(),
            'orden' => 'zzz',
            'portada' => $portada,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_el_formulario_dice_la_medida_y_muestra_la_tarjeta_con_su_vecina(): void
    {
        $this->categoria();

        $html = $this->actingAs($this->admin(), 'admin')->get(route('dashboard.categorias'))->assertOk()->getContent();

        $this->assertStringContainsString('Cuadrada, de 1080 × 1080 px', $html);
        $this->assertStringContainsString('unos dos tercios del ancho', $html);
        $this->assertStringContainsString('Así se ve en el sitio', $html);
        $this->assertStringContainsString('cimg__tarjeta--vecina', $html);
        $this->assertStringNotContainsString('recomendada 260x260', $html);
        // Los estilos y el script, una sola vez.
        $this->assertSame(1, substr_count($html, 'const ANCHO_IDEAL'));
    }

    public function test_la_imagen_se_guarda_optimizada_y_reemplaza_a_la_anterior(): void
    {
        File::put($this->directorio.'/media_vieja.png', 'x');
        $id = $this->categoria('media_vieja.png');

        $this->actingAs($this->admin(), 'admin')->from(route('dashboard.categorias'))->put(route('categoria.update'), [
            'id' => $id,
            'orden' => 'zzz',
            'nombre' => 'ZZ IMAGEN DE PRUEBA',
            'portada' => UploadedFile::fake()->image('motor.png', 1254, 1254),
        ])->assertRedirect(route('dashboard.categorias'))->assertSessionHasNoErrors();

        $portada = DB::table('categorias')->where('id', $id)->value('portada');
        $this->assertMatchesRegularExpression('/^media_[a-f0-9]+\.(webp|png)$/', $portada);
        $this->assertFileExists($this->directorio.'/'.$portada);
        $this->assertFileDoesNotExist($this->directorio.'/media_vieja.png');
    }

    public function test_la_imagen_compartida_con_otra_categoria_no_se_borra(): void
    {
        File::put($this->directorio.'/media_compartida.png', 'x');
        $id = $this->categoria('media_compartida.png');
        $this->categoria('media_compartida.png');

        $this->actingAs($this->admin(), 'admin')->put(route('categoria.update'), [
            'id' => $id, 'orden' => 'zzz', 'nombre' => 'ZZ',
            'portada' => UploadedFile::fake()->image('nueva.jpg', 1080, 1080),
        ])->assertSessionHasNoErrors();

        $this->assertFileExists($this->directorio.'/media_compartida.png');
    }

    public function test_solo_acepta_imagenes(): void
    {
        $id = $this->categoria('media_actual.webp');

        $this->actingAs($this->admin(), 'admin')->put(route('categoria.update'), [
            'id' => $id, 'orden' => 'zzz', 'nombre' => 'ZZ',
            'portada' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
        ])->assertSessionHasErrors('portada');

        $this->assertSame('media_actual.webp', DB::table('categorias')->where('id', $id)->value('portada'));
    }
}
