<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Mail\AvisoClienteNuevo;
use App\Mail\AvisoPedidoNuevo;
use App\Mail\CompraMail;
use App\Mail\PedidoClienteMail;
use App\Mail\RespuestaContacto;
use App\Models\Admin;
use App\Models\ContactoMensaje;
use App\Models\ContactoRespuesta;
use App\Models\Pedido;
use App\Models\User;
use App\Services\AvisosBmh;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesApplication;

/**
 * Emails del panel con el formato de BMH: responder una consulta desde Contacto › Mensajes (queda en la conversación
 * y se puede reenviar si falló), y los avisos a BMH de clientes nuevos y pedidos, a las cuentas que se configuran en
 * Contacto › Contenido › Avisos por email. Base local; lo creado se borra y la fila de contacto se restaura.
 */
final class EmailsPanelTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    private int $ultimoMensaje = 0;

    /** @var array<string, mixed> */
    private array $contacto = [];

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
        $this->ultimoMensaje = (int) DB::table('contacto_mensajes')->max('id');
        $this->contacto = (array) DB::table('contacto')->where('id', 1)->first();
        DB::table('contacto')->where('id', 1)->update(['emails_aviso' => 'consultas@bmh.test', 'emails_aviso_registro' => null, 'emails_aviso_pedidos' => null]);
    }

    protected function tearDown(): void
    {
        DB::table('contacto_mensajes')->where('id', '>', $this->ultimoMensaje)->delete();
        DB::table('contacto')->where('id', 1)->update($this->contacto);
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function admin(): Admin
    {
        $admin = new Admin(['name' => 'Bruno', 'rol' => 'administrador']);
        $admin->id = 1;

        return $admin;
    }

    private function mensaje(): ContactoMensaje
    {
        return ContactoMensaje::query()->create([
            'nombre' => 'Pedro Respuesta', 'email' => 'pedro.respuesta@example.com', 'telefono' => '11 5555-0000',
            'motivo' => 'Consulta técnica', 'mensaje' => "¿Tienen el inducido 11830?\nGracias.",
        ]);
    }

    public function test_responder_una_consulta_desde_el_panel(): void
    {
        Mail::fake();
        $mensaje = $this->mensaje();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('contacto.mensajes.responder', $mensaje), ['cuerpo' => "Sí, lo tenemos.\nTe paso el precio."])
            ->assertRedirect()
            ->assertSessionHas('success');

        $respuesta = ContactoRespuesta::query()->where('contacto_mensaje_id', $mensaje->id)->sole();
        $this->assertNotNull($respuesta->enviado_at);
        $this->assertSame('Bruno', $respuesta->autor);
        $this->assertSame('Re: tu consulta a BMH (Consulta técnica)', $respuesta->asunto);

        Mail::assertSent(RespuestaContacto::class, fn (RespuestaContacto $mail) => $mail->hasTo('pedro.respuesta@example.com')
            && $mail->hasReplyTo('consultas@bmh.test')
            && $mail->hasSubject('Re: tu consulta a BMH (Consulta técnica)'));

        // El mail lleva el saludo, la respuesta y la consulta citada.
        $html = (new RespuestaContacto($respuesta))->render();
        $this->assertStringContainsString('Hola Pedro,', $html);
        $this->assertStringContainsString('Te paso el precio.', $html);
        $this->assertStringContainsString('¿Tienen el inducido 11830?', $html);

        // Y en la bandeja se ve la conversación.
        $this->actingAs($this->admin(), 'admin')
            ->get(route('dashboard.contacto.mensajes', ['mensaje' => $mensaje->id]))
            ->assertOk()
            ->assertSee('Enviada por email')
            ->assertSee('Te paso el precio.');
    }

    public function test_si_el_email_no_sale_queda_guardada_y_se_puede_reenviar(): void
    {
        $mensaje = $this->mensaje();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $this->actingAs($this->admin(), 'admin')
            ->post(route('contacto.mensajes.responder', $mensaje), ['cuerpo' => 'Respuesta que no sale'])
            ->assertSessionHas('warning');

        $respuesta = ContactoRespuesta::query()->where('contacto_mensaje_id', $mensaje->id)->sole();
        $this->assertNull($respuesta->enviado_at);
        $this->assertNotNull($respuesta->fallido_at);

        Mail::swap(new \Illuminate\Support\Testing\Fakes\MailFake(new \Illuminate\Mail\MailManager(app())));
        $this->actingAs($this->admin(), 'admin')
            ->post(route('contacto.mensajes.reenviar', [$mensaje, $respuesta]))
            ->assertSessionHas('success');
        $this->assertNotNull($respuesta->fresh()->enviado_at);
    }

    public function test_una_respuesta_vacia_vuelve_con_error_y_un_visitante_no_puede_responder(): void
    {
        Mail::fake();
        $mensaje = $this->mensaje();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('contacto.mensajes.responder', $mensaje), ['cuerpo' => '  '])
            ->assertSessionHasErrors('cuerpo');

        auth('admin')->logout();
        $this->post(route('contacto.mensajes.responder', $mensaje), ['cuerpo' => 'Hola'])->assertRedirect();
        $this->assertSame(0, ContactoRespuesta::query()->where('contacto_mensaje_id', $mensaje->id)->count());
        Mail::assertNothingSent();
    }

    public function test_los_avisos_de_cliente_nuevo_y_pedido_van_a_su_lista_o_a_la_de_consultas(): void
    {
        Mail::fake();
        $cliente = User::query()->where('rol', 'cliente')->firstOrFail();
        $pedido = Pedido::query()->whereHas('productos')->latest('id')->firstOrFail();
        $avisos = app(AvisosBmh::class);

        // Sin lista propia, al mismo destino que las consultas.
        $this->assertTrue($avisos->clienteNuevo($cliente));
        $this->assertTrue($avisos->pedidoNuevo($pedido));
        Mail::assertSent(AvisoClienteNuevo::class, fn ($m) => $m->hasTo('consultas@bmh.test'));
        Mail::assertSent(AvisoPedidoNuevo::class, fn ($m) => $m->hasTo('consultas@bmh.test'));

        // Con lista propia, a esas cuentas.
        Mail::fake();
        DB::table('contacto')->where('id', 1)->update(['emails_aviso_registro' => 'altas@bmh.test', 'emails_aviso_pedidos' => 'pedidos@bmh.test, ventas@bmh.test']);
        $avisos->clienteNuevo($cliente);
        $avisos->pedidoNuevo($pedido);
        Mail::assertSent(AvisoClienteNuevo::class, fn ($m) => $m->hasTo('altas@bmh.test') && ! $m->hasTo('consultas@bmh.test'));
        Mail::assertSent(AvisoPedidoNuevo::class, fn ($m) => $m->hasTo('pedidos@bmh.test') && $m->hasTo('ventas@bmh.test'));
    }

    public function test_los_mails_se_arman_con_el_formato_de_bmh(): void
    {
        $cliente = User::query()->where('rol', 'cliente')->firstOrFail();
        $pedido = Pedido::query()->whereHas('productos')->latest('id')->firstOrFail();

        foreach ([
            new AvisoClienteNuevo($cliente),
            new AvisoPedidoNuevo($pedido),
            new PedidoClienteMail($pedido, '<p>Envío por transporte.</p>'),
            new CompraMail('¡Bienvenido!', '<p>Gracias por registrarte.</p>'),
        ] as $mail) {
            $html = $mail->render();
            $this->assertStringContainsString('BMH Distribuidora', $html, $mail::class);
            $this->assertStringNotContainsString('bootstrap', $html, $mail::class);
        }

        $this->assertStringContainsString('#'.$pedido->id, (new AvisoPedidoNuevo($pedido))->render());
    }

    public function test_el_admin_configura_a_que_cuentas_llegan_los_avisos(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('contacto.update'), ['emails_aviso' => 'consultas@bmh.test', 'emails_aviso_registro' => 'Altas@BMH.test; otra@bmh.test', 'emails_aviso_pedidos' => 'no-es-un-mail'])
            ->assertSessionHasErrors('emails_aviso_pedidos');

        $this->actingAs($this->admin(), 'admin')
            ->put(route('contacto.update'), ['emails_aviso' => 'consultas@bmh.test', 'emails_aviso_registro' => 'Altas@BMH.test; otra@bmh.test', 'emails_aviso_pedidos' => ''])
            ->assertSessionHasNoErrors();

        $fila = DB::table('contacto')->where('id', 1)->first();
        $this->assertSame('altas@bmh.test, otra@bmh.test', $fila->emails_aviso_registro);
        $this->assertNull($fila->emails_aviso_pedidos);
    }
}
