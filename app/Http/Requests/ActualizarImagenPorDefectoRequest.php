<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Imagen que muestran los productos sin portada (admin → Extras).
 *
 * Es una foto de catálogo, no un ícono: sin SVG (se sirve desde public y un
 * SVG puede llevar scripts) y con un mínimo de resolución, porque se ve a
 * 420 px en la card y a lo ancho de la galería en la ficha del producto.
 */
class ActualizarImagenPorDefectoRequest extends FormRequest
{
    public const LADO_MINIMO = 300;

    public const PESO_MAXIMO_KB = 5120;

    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'imagen' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::PESO_MAXIMO_KB,
                'dimensions:min_width='.self::LADO_MINIMO.',min_height='.self::LADO_MINIMO,
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'imagen.required' => 'Elegí la imagen que van a mostrar los productos sin foto.',
            'imagen.image' => 'El archivo tiene que ser una imagen JPG, PNG o WEBP.',
            'imagen.mimes' => 'El archivo tiene que ser una imagen JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 5 MB.',
            'imagen.dimensions' => 'La imagen tiene que medir al menos '.self::LADO_MINIMO.' × '.self::LADO_MINIMO.' px para no verse pixelada.',
        ];
    }
}
