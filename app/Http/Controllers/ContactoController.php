<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureHumanFormSubmission;
use App\Http\Requests\ContactoMensajeRequest;
use App\Models\Contacto;
use App\Models\ContactoMensaje;
use App\Services\AvisosContacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ContactoController extends Controller
{
    public const GRACIAS = '¡Gracias por contactarnos! Te respondemos a la brevedad.';

    public function index(Request $request){
        $contacto = Contacto::find(1);
        $ventana = 'contacto-nav';
        $producto = $request->input('producto');
        return view('frontend/contacto', compact('contacto', 'ventana', 'producto'));
    }

    /** Admin → Contacto › Contenido: datos, mapa, redes y a quién le llegan las consultas. */
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'direccion' => ['nullable', 'string', 'max:255'],
            'iframe' => ['nullable', 'string', 'max:5000'],
            'tel' => ['nullable', 'string', 'max:255'],
            'mail' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'emails_aviso' => ['nullable', 'string', 'max:500', function (string $atributo, mixed $valor, \Closure $falla): void {
                $emails = Contacto::separarEmails($valor);
                if (count($emails) > 10) {
                    $falla('Cargá hasta 10 emails.');
                }
                foreach ($emails as $email) {
                    if (validator(['e' => $email], ['e' => 'email:rfc'])->fails()) {
                        $falla("«{$email}» no es un email válido. Separá los emails con comas.");
                    }
                }
            }],
            'asunto_aviso' => ['nullable', 'string', 'max:150'],
            'acuse_recibo' => ['nullable', Rule::in(['0', '1'])],
        ], [
            'mail.email' => 'Revisá el email de contacto: por ejemplo, ventas@bmh.com.ar.',
            'asunto_aviso.max' => 'El asunto admite hasta 150 caracteres.',
        ]);

        $contacto = Contacto::find(1) ?? new Contacto();
        $contacto->forceFill([
            ...collect($datos)->except(['emails_aviso', 'acuse_recibo', 'iframe'])->map(fn ($valor) => filled($valor) ? trim($valor) : null)->all(),
            'iframe' => trim((string) ($datos['iframe'] ?? '')),
            'emails_aviso' => implode(', ', Contacto::separarEmails($datos['emails_aviso'] ?? null)) ?: null,
            'acuse_recibo' => ($datos['acuse_recibo'] ?? '0') === '1',
        ])->save();

        return redirect()->route('dashboard.contacto')->with('success', 'Los datos de contacto se guardaron.');
    }

    /**
     * Consulta desde /contacto. Antes pasa por throttle:contacto y human-form (EnsureHumanFormSubmission).
     * Se guarda siempre en Contacto › Mensajes; los mails (aviso a BMH y acuse a quien escribió) salen después de
     * responder, así un SMTP lento o caído no frena ni rompe el formulario.
     */
    public function mailContacto(ContactoMensajeRequest $request): JsonResponse|RedirectResponse
    {
        // A un robot que cayó en el campo trampa se le responde igual, pero no se guarda ni se avisa nada.
        if (! EnsureHumanFormSubmission::isTrapped($request)) {
            $mensaje = new ContactoMensaje([
                'nombre' => $request->validated('nombre'),
                'email' => mb_strtolower($request->validated('mail')),
                'telefono' => $request->validated('telefono'),
                'empresa' => $request->validated('empresa') ?: null,
                'motivo' => $request->validated('motivo') ?: null,
                'mensaje' => $request->validated('mensaje') ?: null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent() ? Str::limit($request->userAgent(), 250, '') : null,
            ]);

            try {
                $mensaje->save();
            } catch (Throwable $e) {
                // Sin la tabla (migración pendiente) o sin base: igual sale el mail, para no perder la consulta.
                Log::error('No se pudo guardar una consulta de contacto.', ['error' => $e::class]);
                $mensaje->created_at = now();
            }

            dispatch(fn () => app(AvisosContacto::class)->enviar($mensaje))->afterResponse();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => self::GRACIAS]);
        }

        return redirect()->back(303)->with('success', self::GRACIAS);
    }
}
