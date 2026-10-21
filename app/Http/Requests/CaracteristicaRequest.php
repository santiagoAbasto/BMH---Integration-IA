<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación compartida del alta y la edición de características.
 *
 * El `id` viaja por query string (`?id=…`) porque las rutas del ABM no tienen
 * parámetro; se valida acá para que un id ausente o basura no llegue al
 * controlador. Que la característica exista se resuelve en el controlador, que
 * responde con un aviso en vez de romper.
 */
class CaracteristicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo resuelve el middleware `admin` de la ruta.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => [$this->isMethod('POST') ? 'nullable' : 'required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:255'],
            'orden' => ['nullable', 'string', 'max:55'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'No se indicó qué característica editar.',
            'id.integer' => 'La característica indicada no es válida.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'orden.max' => 'El orden no puede superar los 55 caracteres.',
        ];
    }

    /**
     * Se recorta antes de validar: así "   " no pasa como nombre y no se
     * guardan nombres con espacios de más, que era una fuente de duplicados.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => is_string($this->input('nombre')) ? trim($this->input('nombre')) : $this->input('nombre'),
            'orden' => is_string($this->input('orden')) ? trim($this->input('orden')) : $this->input('orden'),
        ]);
    }

    public function nombre(): string
    {
        return (string) $this->validated('nombre');
    }

    public function orden(): ?string
    {
        $orden = $this->validated('orden');

        return $orden === '' || $orden === null ? null : (string) $orden;
    }

    public function caracteristicaId(): ?int
    {
        $id = $this->validated('id');

        return $id === null ? null : (int) $id;
    }
}
