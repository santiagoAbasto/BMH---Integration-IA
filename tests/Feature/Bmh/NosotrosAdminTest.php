<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Admin → Nosotros guarda en las columnas que lee la página (antes el texto de la historia iba a `info_home` y no se
 * veía), con los títulos editables y las tarjetas vacías ocultas. Base local; la fila se deja como estaba.
 */
final class NosotrosAdminTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    /** @var array<string, mixed> */
    private array $original = [];

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
        $this->original = (array) DB::table('nosotros_contenido')->where('id', 1)->first();
    }

    protected function tearDown(): void
    {
        DB::table('nosotros_contenido')->where('id', 1)->update($this->original);
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

    public function test_lo_que_se_guarda_en_el_admin_se_ve_en_la_pagina(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('nosotros.update'), [
                'historia_titulo' => 'Historia de prueba del admin',
                'info' => '<p>Texto de la historia de prueba</p>',
                'valores_titulo' => 'Valores de prueba',
                'mision' => '<p>Misión de prueba</p>',
                'vision' => '<p><br></p>',
                'valores' => '<p>Servicios de prueba</p>',
            ])
            ->assertRedirect(route('dashboard.nosotros'))
            ->assertSessionHasNoErrors();

        $fila = DB::table('nosotros_contenido')->where('id', 1)->first();
        $this->assertSame('', $fila->vision, 'Una tarjeta borrada en el editor queda vacía.');

        $html = $this->get(route('nosotros'))->assertOk()->getContent();
        $this->assertStringContainsString('Historia de prueba del admin', $html);
        $this->assertStringContainsString('Texto de la historia de prueba', $html);
        $this->assertStringContainsString('Valores de prueba', $html);
        $this->assertStringContainsString('Misión de prueba', $html);
        $this->assertStringContainsString('Servicios de prueba', $html);
        $this->assertStringNotContainsString('>Objetivos<', $html);

        // Sin títulos, los del Figma.
        $this->actingAs($this->admin(), 'admin')->put(route('nosotros.update'), ['historia_titulo' => '', 'valores_titulo' => '', 'info' => '<p>x</p>']);
        $html = $this->get(route('nosotros'))->getContent();
        $this->assertStringContainsString('Compromiso y dedicación, todos los días', $html);
        $this->assertStringContainsString('Lo que nos mueve', $html);
    }

    public function test_la_pantalla_abre_y_un_visitante_no_puede_guardar(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.nosotros'))->assertOk()->assertSee('Mapa de la página');

        auth('admin')->logout();
        $this->put(route('nosotros.update'), ['historia_titulo' => 'Intruso'])->assertRedirect();
        $this->assertNotSame('Intruso', DB::table('nosotros_contenido')->where('id', 1)->value('historia_titulo'));
    }
}
