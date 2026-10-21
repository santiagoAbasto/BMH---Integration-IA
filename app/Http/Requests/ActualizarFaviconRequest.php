<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFaviconRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'favicon' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,ico', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Elegí el archivo del favicon.',
            'mimes' => 'El favicon tiene que ser PNG, JPG, WEBP, SVG o ICO.',
            'max' => 'El favicon no puede pesar más de 2 MB.',
        ];
    }
}
