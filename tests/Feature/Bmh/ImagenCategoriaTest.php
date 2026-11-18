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
        $this->assertStringContainsString('name="emparejar_bordes" value="1"', $html);
        $this->assertStringContainsString('Así se ve en el sitio', $html);
        $this->assertStringContainsString('cimg__tarjeta--vecina', $html);
        $this->assertStringNotContainsString('recomendada 260x260', $html);
        // Los estilos y el script, una sola vez.
        $this->assertSame(1, substr_count($html, 'SUPERFICIE = 0.52'));
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

    /** Una foto de $w × $h: fondo gris claro y el «producto» (un rectángulo oscuro) de $pw × $ph en el medio. */
    private function foto(string $nombre, int $w = 1254, int $h = 1254, int $pw = 1100, int $ph = 500): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 238, 239, 241));
        $x = intdiv($w - $pw, 2);
        $y = intdiv($h - $ph, 2);
        imagefilledrectangle($img, $x, $y, $x + $pw - 1, $y + $ph - 1, imagecolorallocate($img, 40, 45, 55));
        imagepng($img, $this->directorio.'/'.$nombre);
        imagedestroy($img);

        return $nombre;
    }

    public function test_al_guardar_con_emparejar_bordes_queda_la_emparejada_y_la_original(): void
    {
        $id = $this->categoria();
        $png = UploadedFile::fake()->createWithContent('motor.png', (string) file_get_contents($this->directorio.'/'.$this->foto('subida.png')));

        $this->actingAs($this->admin(), 'admin')->put(route('categoria.update'), [
            'id' => $id, 'orden' => 'zzz', 'nombre' => 'ZZ', 'portada' => $png, 'emparejar_bordes' => '1',
        ])->assertSessionHasNoErrors()->assertSessionMissing('warning');

        $portada = DB::table('categorias')->where('id', $id)->value('portada');
        $this->assertStringEndsWith('-parejo.webp', $portada);
        $this->assertSame([1080, 1080], array_slice(getimagesize($this->directorio.'/'.$portada), 0, 2));
        $original = substr($portada, 0, -strlen('-parejo.webp'));
        $this->assertNotEmpty(glob($this->directorio.'/'.$original.'.*'), 'la original queda guardada');
    }

    public function test_sin_emparejar_bordes_se_guarda_tal_cual(): void
    {
        $id = $this->categoria();
        $png = UploadedFile::fake()->createWithContent('motor.png', (string) file_get_contents($this->directorio.'/'.$this->foto('subida.png')));

        $this->actingAs($this->admin(), 'admin')->put(route('categoria.update'), [
            'id' => $id, 'orden' => 'zzz', 'nombre' => 'ZZ', 'portada' => $png, 'emparejar_bordes' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertStringEndsNotWith('-parejo.webp', DB::table('categorias')->where('id', $id)->value('portada'));
    }

    public function test_se_empareja_la_foto_guardada_y_se_vuelve_a_la_original(): void
    {
        $id = $this->categoria($this->foto('media_zzprueba1.png'));

        $this->actingAs($this->admin(), 'admin')->from(route('dashboard.categorias'))
            ->post(route('categoria.bordes', $id), ['accion' => 'emparejar'])->assertRedirect(route('dashboard.categorias'))->assertSessionHas('success');
        $this->assertSame('media_zzprueba1-parejo.webp', DB::table('categorias')->where('id', $id)->value('portada'));

        $this->actingAs($this->admin(), 'admin')->post(route('categoria.bordes', $id), ['accion' => 'original'])->assertSessionHas('success');
        $this->assertSame('media_zzprueba1.png', DB::table('categorias')->where('id', $id)->value('portada'));
        // Las dos quedan: se puede volver a emparejar sin procesar de nuevo nada que se haya perdido.
        $this->assertFileExists($this->directorio.'/media_zzprueba1-parejo.webp');
    }

    public function test_una_foto_de_escena_no_se_empareja_y_se_dice_por_que(): void
    {
        // Esquinas de un mismo lado de colores distintos: una escena, no un fondo liso.
        $img = imagecreatetruecolor(800, 800);
        imagefill($img, 0, 0, imagecolorallocate($img, 230, 230, 235));
        imagefilledrectangle($img, 0, 400, 300, 799, imagecolorallocate($img, 30, 30, 35));
        imagepng($img, $this->directorio.'/media_zzescena.png');
        imagedestroy($img);
        $id = $this->categoria('media_zzescena.png');

        $this->actingAs($this->admin(), 'admin')->post(route('categoria.bordes', $id), ['accion' => 'emparejar'])
            ->assertSessionHas('warning', fn (string $m) => str_contains($m, 'El fondo no es liso'));
        $this->assertSame('media_zzescena.png', DB::table('categorias')->where('id', $id)->value('portada'));
    }

    public function test_emparejar_todas_y_volver_todas(): void
    {
        $a = $this->categoria($this->foto('media_zzprueba2.png'));
        $b = $this->categoria($this->foto('media_zzprueba3.png', 900, 900, 300, 600));

        $this->actingAs($this->admin(), 'admin')->post(route('categoria.bordes.todas'), ['accion' => 'emparejar'])->assertSessionHas('success');
        $this->assertSame('media_zzprueba2-parejo.webp', DB::table('categorias')->where('id', $a)->value('portada'));
        $this->assertSame('media_zzprueba3-parejo.webp', DB::table('categorias')->where('id', $b)->value('portada'));

        $this->actingAs($this->admin(), 'admin')->post(route('categoria.bordes.todas'), ['accion' => 'original'])->assertSessionHas('success');
        $this->assertSame('media_zzprueba2.png', DB::table('categorias')->where('id', $a)->value('portada'));
        $this->assertSame('media_zzprueba3.png', DB::table('categorias')->where('id', $b)->value('portada'));
    }

    public function test_un_visitante_no_puede_emparejar(): void
    {
        $id = $this->categoria($this->foto('media_zzprueba4.png'));
        $this->post(route('categoria.bordes', $id), ['accion' => 'emparejar']);
        $this->post(route('categoria.bordes.todas'), ['accion' => 'emparejar']);
        $this->assertSame('media_zzprueba4.png', DB::table('categorias')->where('id', $id)->value('portada'));
    }
}
