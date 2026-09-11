<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Apariencia;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarFooterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = array_fill_keys(Apariencia::CAMPOS_FOOTER, ['required', 'regex:/^#[0-9A-F]{6}$/']);
        $reglas['logo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'];

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'regex' => 'El color «:attribute» no es válido: tiene que tener el formato #RRGGBB.',
            'required' => 'Falta el color «:attribute».',
            'mimes' => 'El :attribute tiene que ser PNG, JPG, WEBP o SVG.',
            'max' => 'El :attribute no puede pesar más de 2 MB.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'footer_fondo' => 'fondo',
            'footer_texto' => 'textos, links e íconos',
            'footer_texto_hover' => 'links al pasar el mouse',
            'logo' => 'logo del footer',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizados = [];
        foreach (Apariencia::CAMPOS_FOOTER as $campo) {
            if (is_string($this->input($campo))) {
                $normalizados[$campo] = strtoupper(trim($this->input($campo)));
            }
        }

        $this->merge($normalizados);
    }

    /** @return array<string, string> */
    public function valores(): array
    {
        return $this->safe()->only(Apariencia::CAMPOS_FOOTER);
    }
}
