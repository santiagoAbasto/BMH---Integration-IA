<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Apariencia;
use App\Models\Contacto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Barra de contacto de arriba del header (admin → Extras → Barra superior).
 *
 * Guarda en dos lugares: los colores en `apariencia` y los datos en
 * `contacto`, que son los mismos que muestran el footer y la página Contacto.
 */
class ActualizarBarraSuperiorRequest extends FormRequest
{
    public const DATOS = ['tel', 'mail'];

    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = array_fill_keys(Apariencia::CAMPOS_BARRA, ['required', 'regex:/^#[0-9A-F]{6}$/']);

        $reglas['tel'] = ['nullable', 'string', 'max:40', 'regex:/\d/'];
        $reglas['mail'] = ['nullable', 'string', 'email', 'max:255'];

        foreach (array_keys(Contacto::REDES_BARRA) as $red) {
            $reglas[$red] = ['nullable', 'string', 'url:http,https', 'max:255'];
        }

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'regex' => 'El color «:attribute» no es válido: tiene que tener el formato #RRGGBB.',
            'required' => 'Falta el color «:attribute».',
            'tel.regex' => 'El teléfono tiene que tener al menos un número.',
            'tel.max' => 'El teléfono no puede tener más de 40 caracteres.',
            'mail.email' => 'El mail no es válido. Revisá que tenga la forma nombre@dominio.com.',
            'url' => 'El link de :attribute no es válido. Copialo desde el navegador, con el perfil abierto.',
            'max' => 'El :attribute es demasiado largo.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'barra_fondo' => 'fondo',
            'barra_texto' => 'texto e íconos',
            'barra_hover' => 'texto e íconos al pasar el mouse',
            'tel' => 'teléfono',
            'mail' => 'mail',
            ...Contacto::REDES_BARRA,
        ];
    }

    /**
     * Espacios afuera, colores en mayúsculas y links sin protocolo completados:
     * pegar «instagram.com/bmh» tiene que funcionar igual que la URL entera.
     */
    protected function prepareForValidation(): void
    {
        $normalizados = [];

        foreach (Apariencia::CAMPOS_BARRA as $campo) {
            if (is_string($this->input($campo))) {
                $normalizados[$campo] = strtoupper(trim($this->input($campo)));
            }
        }

        foreach ([...self::DATOS, ...array_keys(Contacto::REDES_BARRA)] as $campo) {
            $valor = is_string($this->input($campo)) ? trim($this->input($campo)) : null;
            $normalizados[$campo] = $valor === '' ? null : $valor;
        }

        foreach (array_keys(Contacto::REDES_BARRA) as $red) {
            $url = $normalizados[$red];
            if ($url !== null && ! preg_match('#^https?://#i', $url)) {
                $normalizados[$red] = 'https://'.ltrim($url, '/');
            }
        }

        $this->merge($normalizados);
    }

    /** @return array<string, string> */
    public function colores(): array
    {
        return $this->safe()->only(Apariencia::CAMPOS_BARRA);
    }

    /** @return array<string, string|null> vacío = null, como lo guarda Contacto */
    public function datosContacto(): array
    {
        return $this->safe()->only([...self::DATOS, ...array_keys(Contacto::REDES_BARRA)]);
    }
}
