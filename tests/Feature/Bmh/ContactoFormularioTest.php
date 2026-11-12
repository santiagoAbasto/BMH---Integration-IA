<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Middleware\EnsureHumanFormSubmission;
use App\Mail\ContactoAcuse;
use App\Mail\ContactoAviso;
use App\Models\ContactoMensaje;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesApplication;

/**
 * Formulario de /contacto sin reCAPTCHA: se guarda en Contacto › Mensajes, manda el aviso y el acuse en formato
 * BMH, y el anti-bots (honeypot, token de tiempo firmado y de un solo uso, origen y rate limits) frena los envíos
 * automáticos. Base local `bmh_legacy`: los mensajes creados se borran y la fila de `contacto` se restaura.
 */
final class ContactoFormularioTest extends BaseTestCase
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
        DB::table('contacto')->where('id', 1)->update(['mail' => 'contacto@bmh.test', 'emails_aviso' => null, 'acuse_recibo' => true]);
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

    /** Los campos ocultos como los arma <x-anti-bots />, con el formulario abierto hace unos segundos. */
    private function antiBots(): array
    {
        $token = EnsureHumanFormSubmission::token();
        $this->travel(3)->seconds();

        return [EnsureHumanFormSubmission::HONEYPOT => '', EnsureHumanFormSubmission::TOKEN => $token];
    }

    private function datos(array $extra = []): array
    {
        return [
            'nombre' => 'Juana Prueba',
            'mail' => 'juana.antibots@example.com',
            'telefono' => '11 4482-2609',
            'empresa' => 'Taller Prueba',
            'motivo' => 'Consulta técnica',
            'mensaje' => "Hola, busco un inducido.\nGracias.",
            ...$extra,
        ];
    }

    private function enviar(array $datos, array $servidor = [])
    {
        return $this->withServerVariables($servidor)->postJson(route('enviar.mail'), $datos);
    }

    private function nuevos(): int
    {
        return ContactoMensaje::query()->where('id', '>', $this->ultimoId)->count();
    }

    public function test_un_envio_legitimo_se_guarda_y_manda_el_aviso_y_el_acuse(): void
    {
        Mail::fake();

        $this->enviar($this->datos($this->antiBots()))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertHeader(EnsureHumanFormSubmission::HEADER);

        $mensaje = ContactoMensaje::query()->where('id', '>', $this->ultimoId)->sole();
        $this->assertSame('Juana Prueba', $mensaje->nombre);
        $this->assertSame('Consulta técnica', $mensaje->motivo);
        $this->assertNull($mensaje->leido_at);
        $this->assertNotNull($mensaje->fresh()->aviso_enviado_at);

        // Sin destinatarios propios, el aviso va al email de contacto del sitio; responderlo le escribe a Juana.
        Mail::assertSent(ContactoAviso::class, fn (ContactoAviso $mail) => $mail->hasTo('contacto@bmh.test')
            && $mail->hasReplyTo('juana.antibots@example.com'));
        Mail::assertSent(ContactoAcuse::class, fn (ContactoAcuse $mail) => $mail->hasTo('juana.antibots@example.com'));
    }

    public function test_los_avisos_van_a_los_emails_configurados_y_el_acuse_se_puede_apagar(): void
    {
        Mail::fake();
        DB::table('contacto')->where('id', 1)->update(['emails_aviso' => 'ventas@bmh.test, tecnica@bmh.test', 'acuse_recibo' => false]);

        $this->enviar($this->datos($this->antiBots()))->assertOk();

        Mail::assertSent(ContactoAviso::class, fn (ContactoAviso $mail) => $mail->hasTo('ventas@bmh.test') && $mail->hasTo('tecnica@bmh.test') && ! $mail->hasTo('contacto@bmh.test'));
        Mail::assertNotSent(ContactoAcuse::class);
    }

    public function test_los_mails_tienen_la_marca_de_bmh_y_version_de_texto(): void
    {
        $mensaje = new ContactoMensaje($this->datos(['email' => 'juana.antibots@example.com']));
        $mensaje->created_at = now();

        $aviso = new ContactoAviso($mensaje);
        $aviso->assertSeeInHtml('#0a0e14', false)
            ->assertSeeInHtml('Juana Prueba te escribió desde la web')
            ->assertSeeInHtml('mailto:juana.antibots@example.com', false)
            ->assertSeeInText('busco un inducido')
            ->assertSeeInText('Juana Prueba te escribió desde la web');

        (new ContactoAcuse($mensaje))
            ->assertSeeInHtml('¡Gracias, Juana!')
            ->assertSeeInText('Recibimos tu consulta');
    }

    public function test_sin_js_vuelve_al_formulario_con_el_aviso_de_exito(): void
    {
        Mail::fake();

        $this->from(route('contacto'))->post(route('enviar.mail'), $this->datos($this->antiBots()))
            ->assertRedirect(route('contacto'))
            ->assertSessionHas('success');
        $this->assertSame(1, $this->nuevos());
    }

    public function test_si_el_smtp_falla_la_consulta_queda_guardada_y_el_formulario_responde_ok(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
        Log::spy();

        $this->enviar($this->datos($this->antiBots()))->assertOk()->assertJson(['success' => true]);

        $mensaje = ContactoMensaje::query()->where('id', '>', $this->ultimoId)->sole();
        $this->assertNull($mensaje->aviso_enviado_at);
        Log::shouldHaveReceived('error')->withArgs(fn (string $texto, array $contexto) => str_contains($texto, 'No se pudo enviar el aviso')
            && $contexto['mensaje_id'] === $mensaje->id);
    }

    public function test_el_honeypot_finge_exito_pero_no_guarda_ni_manda_mails(): void
    {
        Mail::fake();

        $this->enviar($this->datos([...$this->antiBots(), EnsureHumanFormSubmission::HONEYPOT => 'https://spam.example']))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $this->nuevos());
        Mail::assertNothingSent();
    }

    public function test_un_envio_demasiado_rapido_se_rechaza(): void
    {
        Mail::fake();

        $this->enviar($this->datos([EnsureHumanFormSubmission::TOKEN => EnsureHumanFormSubmission::token()]))
            ->assertStatus(422)
            ->assertJsonPath('errors.formulario.0', EnsureHumanFormSubmission::MESSAGE)
            ->assertJsonStructure(['formToken']);

        $this->assertSame(0, $this->nuevos());
        Mail::assertNothingSent();
    }

    public function test_sin_token_o_con_un_token_alterado_o_falsificado_se_rechaza(): void
    {
        Mail::fake();
        $valido = $this->antiBots()[EnsureHumanFormSubmission::TOKEN];

        $intentos = [
            'sin token' => [],
            'alterado' => [EnsureHumanFormSubmission::TOKEN => substr($valido, 0, -4).'AAAA'],
            'timestamp en claro' => [EnsureHumanFormSubmission::TOKEN => (string) now()->subMinute()->timestamp],
            // Encriptado con la clave de la app pero sin nonce: tampoco sirve.
            'sin nonce' => [EnsureHumanFormSubmission::TOKEN => Crypt::encryptString((string) now()->subMinute()->timestamp)],
        ];

        foreach ($intentos as $caso => $campos) {
            $this->enviar($this->datos($campos))->assertStatus(422, $caso);
        }

        $this->assertSame(0, $this->nuevos());
        Mail::assertNothingSent();
    }

    public function test_un_token_no_se_puede_reutilizar(): void
    {
        Mail::fake();
        $campos = $this->antiBots();

        $this->enviar($this->datos($campos))->assertOk();
        $this->enviar($this->datos($campos))->assertStatus(422);

        $this->assertSame(1, $this->nuevos());
    }

    public function test_un_formulario_abierto_hace_horas_se_rechaza(): void
    {
        $token = EnsureHumanFormSubmission::token();
        $this->travel(EnsureHumanFormSubmission::MAX_SECONDS + 60)->seconds();

        $this->enviar($this->datos([EnsureHumanFormSubmission::TOKEN => $token]))->assertStatus(422);
        $this->assertSame(0, $this->nuevos());
    }

    public function test_un_envio_desde_otro_sitio_se_rechaza(): void
    {
        $this->withHeaders(['Origin' => 'https://otro-sitio.example'])
            ->postJson(route('enviar.mail'), $this->datos($this->antiBots()))
            ->assertStatus(422);

        $this->assertSame(0, $this->nuevos());
    }

    public function test_los_rechazos_se_loguean_sin_datos_personales(): void
    {
        Log::spy();

        $this->enviar($this->datos())->assertStatus(422);

        Log::shouldHaveReceived('info')->withArgs(function (string $texto, array $contexto): bool {
            $plano = json_encode($contexto);

            return $contexto['motivo'] === 'token'
                && ! str_contains($plano, 'juana') && ! str_contains($plano, '127.0.0.1') && ! str_contains($plano, '4482');
        });
    }

    public function test_el_exceso_de_envios_desde_una_ip_devuelve_429(): void
    {
        Mail::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->enviar($this->datos([...$this->antiBots(), 'mail' => "persona{$i}@example.com"]))->assertOk();
        }

        $this->enviar($this->datos([...$this->antiBots(), 'mail' => 'otra@example.com']))
            ->assertStatus(429)
            ->assertJsonStructure(['errors' => ['formulario']]);
        $this->assertSame(5, $this->nuevos());
    }

    public function test_el_exceso_de_envios_con_el_mismo_email_devuelve_429_aunque_cambie_la_ip(): void
    {
        Mail::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->enviar($this->datos($this->antiBots()), ['REMOTE_ADDR' => "10.0.0.{$i}"])->assertOk();
        }

        $this->enviar($this->datos($this->antiBots()), ['REMOTE_ADDR' => '10.0.0.99'])->assertStatus(429);
        $this->assertSame(5, $this->nuevos());
    }

    public function test_la_pagina_de_contacto_ya_no_carga_recaptcha_y_trae_los_campos_anti_bots(): void
    {
        $html = $this->get(route('contacto'))->assertOk()->getContent();

        $this->assertStringNotContainsString('recaptcha', $html);
        $this->assertStringNotContainsString('grecaptcha', $html);
        $this->assertMatchesRegularExpression('/name="website"[^>]*tabindex="-1"[^>]*autocomplete="off"/', $html);
        $this->assertStringContainsString('name="_form_token"', $html);
    }
}
