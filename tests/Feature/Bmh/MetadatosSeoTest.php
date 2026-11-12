<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Metadatos;
use App\Models\SeoSetting;
use App\Support\Seo\SeoPages;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\CreatesApplication;

/**
 * Metadatos SEO (como en Conelec): el <head> de las páginas y el admin. Base local; lo que toca en `metadatos`
 * y `seo_settings` se restaura al terminar.
 */
final class MetadatosSeoTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    /** @var array<int, array<string, mixed>> */
    private array $metadatos = [];

    /** @var array<int, array<string, mixed>> */
    private array $ajustes = [];

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
        $this->metadatos = DB::table('metadatos')->get()->map(fn ($r) => (array) $r)->all();
        $this->ajustes = DB::table('seo_settings')->get()->map(fn ($r) => (array) $r)->all();
        SeoPages::forget();
    }

    protected function tearDown(): void
    {
        foreach (SeoSetting::query()->pluck('imagen_path')->filter() as $path) {
            if (! in_array($path, array_column($this->ajustes, 'imagen_path'), true)) {
                File::delete(public_path('imagenes/'.$path));
            }
        }
        DB::table('metadatos')->delete();
        DB::table('metadatos')->insert($this->metadatos);
        DB::table('seo_settings')->delete();
        DB::table('seo_settings')->insert($this->ajustes);
        SeoPages::forget();
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

    public function test_cada_pagina_tiene_titulo_descripcion_e_imagen_para_compartir(): void
    {
        $html = $this->get(route('nosotros'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Nosotros · Bobinajes San Francisco desde 1966 | BMH</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Empresa familiar fundada en 1966', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('img/bmh-compartir.jpg').'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('nosotros').'">', $html);
        $this->assertStringNotContainsString("name='keyword'", $html);
    }

    public function test_la_home_publica_los_datos_de_la_empresa(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"legalName":"B.M.H Bobinajes San Francisco S.R.L."', $html);
    }

    public function test_el_admin_personaliza_una_pagina_y_puede_volver_a_lo_sugerido(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('metadatos.pagina', 'contacto'), ['titulo' => 'Contacto BMH', 'descripcion' => '', 'palabras_clave' => 'bmh, contacto, BMH'])
            ->assertRedirect();

        $fila = Metadatos::query()->where('seccion', 'contacto')->firstOrFail();
        $this->assertSame('Contacto BMH', $fila->titulo);
        $this->assertNull($fila->descripcion);
        $this->assertSame('bmh, contacto', $fila->keyword);
        $this->assertStringContainsString('<title>Contacto BMH</title>', $this->get(route('contacto'))->getContent());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('metadatos.pagina', 'contacto'), ['titulo' => '', 'descripcion' => '', 'palabras_clave' => ''])
            ->assertRedirect();
        $this->assertFalse(SeoPages::find('contacto')['custom']);
        $this->assertStringContainsString('<title>Contacto · BMH Distribuidora, San Justo</title>', $this->get(route('contacto'))->getContent());
    }

    public function test_la_verificacion_acepta_la_etiqueta_completa_y_sale_en_el_head(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('metadatos.verificacion'), ['google_verificacion' => '<meta name="google-site-verification" content="abc123_XYZ-9" />', 'bing_verificacion' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertStringContainsString('<meta name="google-site-verification" content="abc123_XYZ-9">', $this->get(route('home'))->getContent());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('metadatos.verificacion'), ['google_verificacion' => '"><script>', 'bing_verificacion' => ''])
            ->assertSessionHasErrors('google_verificacion');
    }

    public function test_la_imagen_subida_se_recorta_a_1200_por_630_y_se_puede_quitar(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('metadatos.imagen'), ['imagen' => UploadedFile::fake()->image('cartel.png', 1600, 1600)])
            ->assertRedirect()->assertSessionHasNoErrors();

        $path = SeoSetting::current()->imagen_path;
        $this->assertNotNull($path);
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('imagenes/'.$path)), 0, 2));
        $this->assertStringContainsString(asset('imagenes/'.$path), $this->get(route('home'))->getContent());

        $this->actingAs($this->admin(), 'admin')->delete(route('metadatos.imagen.quitar'))->assertRedirect();
        $this->assertFileDoesNotExist(public_path('imagenes/'.$path));
        $this->assertNull(SeoSetting::current()->imagen_path);
    }

    public function test_el_admin_muestra_las_paginas_y_un_visitante_no_entra(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('metadatos'))->assertOk()
            ->assertSee('Metadatos SEO')->assertSee('Imagen para compartir')->assertSee('Verificación de buscadores');

        auth('admin')->logout();
        $this->post(route('metadatos.pagina', 'home'), ['titulo' => 'Hackeado']);
        $this->assertNotSame('Hackeado', SeoPages::find('home')['title']);
    }

    public function test_el_sitemap_lista_paginas_categorias_y_productos(): void
    {
        $xml = $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<loc>'.route('nosotros').'</loc>', $xml);
        $this->assertStringContainsString(route('productos').'?categoria=', $xml);
        $this->assertStringContainsString(route('producto').'?id=', $xml);
    }
}
