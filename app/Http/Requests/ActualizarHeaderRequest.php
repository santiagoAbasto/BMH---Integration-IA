<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Apariencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = array_fill_keys(Apariencia::CAMPOS_HEADER, ['required', 'regex:/^#[0-9A-F]{6}$/']);

        $reglas['header_scroll_logo'] = ['required', Rule::in(Apariencia::LOGOS)];
        $reglas['header_mobile_logo'] = ['required', Rule::in(Apariencia::LOGOS)];
        $reglas['logo_transparente'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'];
        $reglas['logo_blanco'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'];

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
            'header_scroll_fondo' => 'Scroll · fondo',
            'header_scroll_links' => 'Scroll · links',
            'header_scroll_boton_texto' => 'Scroll · texto del botón',
            'header_scroll_boton_borde' => 'Scroll · borde del botón',
            'header_scroll_boton_hover_fondo' => 'Scroll · fondo del botón al pasar el mouse',
            'header_scroll_boton_hover_texto' => 'Scroll · texto del botón al pasar el mouse',
            'header_mobile_fondo' => 'Mobile · fondo',
            'header_mobile_links' => 'Mobile · links',
            'header_mobile_boton_texto' => 'Mobile · texto del botón',
            'header_mobile_boton_borde' => 'Mobile · borde del botón',
            'header_mobile_boton_hover_fondo' => 'Mobile · fondo del botón al tocarlo',
            'header_mobile_boton_hover_texto' => 'Mobile · texto del botón al tocarlo',
            'logo_transparente' => 'logo para fondo transparente',
            'logo_blanco' => 'logo para fondo blanco',
        ];
    }

    /** Se normaliza a mayúsculas para que #0098da y #0098DA sean lo mismo. */
    protected function prepareForValidation(): void
    {
        $normalizados = [];
        foreach (Apariencia::CAMPOS_HEADER as $campo) {
            if (is_string($this->input($campo))) {
                $normalizados[$campo] = strtoupper(trim($this->input($campo)));
            }
        }

        $this->merge($normalizados);
    }

    /** @return array<string, string> */
    public function valores(): array
    {
        return $this->safe()->only([...Apariencia::CAMPOS_HEADER, 'header_scroll_logo', 'header_mobile_logo']);
    }
}
