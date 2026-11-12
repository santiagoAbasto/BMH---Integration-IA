<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ContactoMensaje;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Consulta desde /contacto. Antes pasa por throttle:contacto y human-form (honeypot + token de tiempo). */
final class ContactoMensajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $limpio = [];

        foreach (['nombre', 'mail', 'telefono', 'empresa', 'motivo'] as $campo) {
            if (is_string($valor = $this->input($campo))) {
                $limpio[$campo] = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
            }
        }

        if (is_string($mensaje = $this->input('mensaje'))) {
            $limpio['mensaje'] = trim(str_replace("\r\n", "\n", $mensaje));
        }

        $this->merge($limpio);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'mail' => ['required', 'string', 'email:rfc', 'max:150'],
            'telefono' => ['required', 'string', 'max:40', 'regex:/^[\d\s()+\-.]{6,40}$/'],
            'empresa' => ['nullable', 'string', 'max:120'],
            'motivo' => ['nullable', 'string', Rule::in(ContactoMensaje::MOTIVOS)],
            'mensaje' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribí tu nombre y apellido.',
            'nombre.max' => 'El nombre admite hasta 120 caracteres.',
            'mail.required' => 'Escribí tu email.',
            'mail.email' => 'Revisá el email: por ejemplo, nombre@empresa.com.',
            'mail.max' => 'El email admite hasta 150 caracteres.',
            'telefono.required' => 'Dejanos un celular para contactarte.',
            'telefono.regex' => 'Revisá el celular: sólo números, espacios, paréntesis, guiones o +.',
            'telefono.max' => 'El celular admite hasta 40 caracteres.',
            'empresa.max' => 'La empresa admite hasta 120 caracteres.',
            'motivo.in' => 'Elegí un motivo de la lista.',
            'mensaje.max' => 'El mensaje admite hasta 5000 caracteres.',
        ];
    }
}
