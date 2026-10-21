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
        $reglas = array_fill_keys(Apariencia::camposHeader(), ['required', 'regex:/^#[0-9A-F]{6}$/']);

        foreach (Apariencia::camposLogo() as $campo) {
            $reglas[$campo] = ['required', Rule::in(Apariencia::LOGOS)];
        }

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
            'in' => 'El logo elegido para «:attribute» no existe.',
            'mimes' => 'El :attribute tiene que ser PNG, JPG, WEBP o SVG.',
            'max' => 'El :attribute no puede pesar más de 2 MB.',
        ];
    }

    /**
     * Nombre legible de cada campo: «Home al hacer scroll · Fondo», para que
     * el aviso de error diga de qué estado se trata.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $nombres = [];

        foreach (Apariencia::SETS as $set => $config) {
            $estado = Apariencia::MODOS[$config['modo']].' · '.$config['titulo'];

            foreach (Apariencia::coloresDe($set) as $color) {
                $nombres[Apariencia::campo($set, $color)] = $estado.' · '.Apariencia::etiqueta($set, $color);
            }

            $nombres[Apariencia::campo($set, 'logo')] = $estado.' · logo a mostrar';
        }

        return $nombres + [
            'logo_transparente' => 'logo para fondo transparente',
            'logo_blanco' => 'logo para fondo blanco',
        ];
    }

    /** Se normaliza a mayúsculas para que #0098da y #0098DA sean lo mismo. */
    protected function prepareForValidation(): void
    {
        $normalizados = [];

        foreach (Apariencia::camposHeader() as $campo) {
            if (is_string($this->input($campo))) {
                $normalizados[$campo] = strtoupper(trim($this->input($campo)));
            }
        }

        $this->merge($normalizados);
    }

    /** @return array<string, string> */
    public function valores(): array
    {
        return $this->safe()->only([...Apariencia::camposHeader(), ...Apariencia::camposLogo()]);
    }
}
