<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Contacto;
use App\Models\ContactoMensaje;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Admin → Contacto (como en Conelec): grupo de la sidebar con Mensajes (la bandeja) y Contenido (datos, mapa,
 * redes y a quién llegan las consultas). Base local `bmh_legacy`: los mensajes creados se borran y la fila de
 * `contacto` se restaura.
 */
final class ContactoAdminTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    private int $ultimoId = 0;

    /** @var array<string, mixed>|null */
    private ?array $contacto = null;

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
        $this->ultimoId = (int) DB::table('contacto_mensajes')->max('id');
        $fila = DB::table('contacto')->where('id', 1)->first();
        $this->contacto = $fila ? (array) $fila : null;
    }

    protected function tearDown(): void
    {
        DB::table('contacto_mensajes')->where('id', '>', $this->ultimoId)->delete();
        if ($this->contacto) {
            DB::table('contacto')->where('id', 1)->update($this->contacto);
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

    private function mensaje(array $datos = []): ContactoMensaje
    {
        return ContactoMensaje::query()->create([
            'nombre' => 'Pedro Bandeja', 'email' => 'pedro.bandeja@example.com', 'telefono' => '11 5555-0000',
            'motivo' => 'Consulta comercial', 'mensaje' => 'Necesito precios de inducidos.', ...$datos,
        ]);
    }

    public function test_sin_sesion_del_panel_no_se_ve_la_bandeja(): void
    {
        $this->get(route('dashboard.contacto.mensajes'))->assertRedirect();
        $this->get(route('dashboard.contacto'))->assertRedirect();
    }

    public function test_la_sidebar_tiene_el_grupo_contacto_con_mensajes_y_contenido(): void
    {
        $this->mensaje();
        $html = $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto.mensajes'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<details class="osole-admin-sidebar-v1__group"[^>]*\sopen\s*>\s*<summary[^>]*>(?:(?!<\/summary>).)*>Contacto</s', $html);
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('dashboard.contacto.mensajes'), '#').'" class="osole-admin-sidebar-v1__sublink" aria-current="page">Mensajes#', $html);
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('dashboard.contacto'), '#').'" class="osole-admin-sidebar-v1__sublink">Contenido#', $html);
        $this->assertMatchesRegularExpression('/data-contacto-sin-leer="[1-9]\d*"/', $html);

        $contenido = $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('dashboard.contacto'), '#').'" class="osole-admin-sidebar-v1__sublink" aria-current="page">Contenido#', $contenido);
        $this->assertStringNotContainsString('nav-tabs', $contenido);
    }

    public function test_la_bandeja_lista_destaca_los_no_leidos_y_abrir_uno_lo_marca_como_leido(): void
    {
        $mensaje = $this->mensaje();

        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto.mensajes'))
            ->assertOk()
            ->assertSee('Pedro Bandeja')
            ->assertSee('osole-admin-inbox-v1__row--unread', false);

        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto.mensajes', ['mensaje' => $mensaje->id]))
            ->assertOk()
            ->assertSee('Necesito precios de inducidos.')
            ->assertSee('mailto:pedro.bandeja@example.com?subject=', false);

        $this->assertNotNull($mensaje->fresh()->leido_at);
    }

    public function test_el_buscador_y_el_filtro_de_no_leidas(): void
    {
        $this->mensaje(['nombre' => 'Laura Buscable', 'email' => 'laura@example.com']);
        $this->mensaje(['nombre' => 'Mario Leido', 'email' => 'mario@example.com'])->marcarLeido();

        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto.mensajes', ['q' => 'Buscable']))
            ->assertSee('Laura Buscable')->assertDontSee('Mario Leido');

        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto.mensajes', ['filtro' => 'no-leidos']))
            ->assertSee('Laura Buscable')->assertDontSee('Mario Leido');
    }

    public function test_marcar_como_no_leido_eliminar_y_eliminar_varios(): void
    {
        $uno = $this->mensaje();
        $uno->marcarLeido();
        $dos = $this->mensaje(['nombre' => 'Dos']);
        $tres = $this->mensaje(['nombre' => 'Tres']);

        $this->actingAs($this->admin(), 'admin')->patch(route('contacto.mensajes.leido', $uno), ['leido' => 0])
            ->assertRedirect(route('dashboard.contacto.mensajes'));
        $this->assertNull($uno->fresh()->leido_at);

        $this->actingAs($this->admin(), 'admin')->delete(route('contacto.mensajes.destroy', ['mensaje' => $uno, 'filtro' => 'no-leidos']))
            ->assertRedirect(route('dashboard.contacto.mensajes', ['filtro' => 'no-leidos']));
        $this->assertNull($uno->fresh());

        $this->actingAs($this->admin(), 'admin')->delete(route('contacto.mensajes.destroy-many'), ['ids' => [$dos->id, $tres->id]])
            ->assertRedirect()->assertSessionHas('success', 'Se eliminaron 2 consultas.');
        $this->assertSame(0, ContactoMensaje::query()->whereKey([$dos->id, $tres->id])->count());
    }

    public function test_contenido_guarda_los_datos_y_los_emails_de_aviso(): void
    {
        $this->actingAs($this->admin(), 'admin')->put(route('contacto.update'), [
            'direccion' => 'Av. Siempre Viva 742', 'iframe' => '', 'tel' => '(011) 4482-2609', 'mail' => 'info@bmh.test',
            'whatsapp' => '+54 9 11 1234-5678', 'instagram' => '', 'facebook' => '', 'tiktok' => '',
            'emails_aviso' => "Ventas@BMH.test; tecnica@bmh.test\nventas@bmh.test", 'asunto_aviso' => 'Consulta web', 'acuse_recibo' => '0',
        ])->assertRedirect(route('dashboard.contacto'))->assertSessionHas('success');

        $contacto = Contacto::query()->find(1);
        $this->assertSame('ventas@bmh.test, tecnica@bmh.test', $contacto->emails_aviso);
        $this->assertSame(['ventas@bmh.test', 'tecnica@bmh.test'], $contacto->destinatariosAviso());
        $this->assertSame('Consulta web', $contacto->asuntoAviso());
        $this->assertFalse($contacto->mandaAcuse());

        $this->actingAs($this->admin(), 'admin')->get(route('dashboard.contacto'))
            ->assertOk()->assertSee('¿A qué mail querés que lleguen los mensajes?')->assertSee('ventas@bmh.test, tecnica@bmh.test');
    }

    public function test_un_email_de_aviso_invalido_no_se_guarda_y_sin_destinatarios_se_usa_el_mail_de_contacto(): void
    {
        $this->actingAs($this->admin(), 'admin')->from(route('dashboard.contacto'))->put(route('contacto.update'), [
            'mail' => 'info@bmh.test', 'emails_aviso' => 'ventas@bmh.test, esto-no-es-un-mail',
        ])->assertRedirect(route('dashboard.contacto'))->assertSessionHasErrors('emails_aviso');

        $this->actingAs($this->admin(), 'admin')->put(route('contacto.update'), ['mail' => 'info@bmh.test', 'emails_aviso' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame(['info@bmh.test'], Contacto::query()->find(1)->destinatariosAviso());
    }
}
