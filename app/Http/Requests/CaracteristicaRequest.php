<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Caracteristica;
use App\Support\Caracteristicas\FormatoCaracteristica;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación compartida del alta y la edición de características (admin → Características, y el alta
 * rápida desde Categorías y Productos).
 *
 * El `id` viaja en el formulario porque las rutas del ABM no tienen parámetro. Que la característica
 * exista se resuelve en el controlador, que responde con un aviso en vez de romper.
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
            'orden' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{2}$/'],
            'tipo' => ['nullable', 'in:texto,numero'],
            'mayusculas' => ['nullable', 'boolean'],
            'unidad' => ['nullable', 'string', 'max:10', 'regex:/^[^\s\d,.\/]+$/u'],
            'aplicar_formato' => ['nullable', 'boolean'],
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
            'nombre.required' => 'Escribí el nombre de la característica.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'orden.regex' => 'El orden son 2 letras o números, por ejemplo «ab».',
            'tipo.in' => 'Elegí si se escribe como texto o como número.',
            'unidad.max' => 'La unidad no puede superar los 10 caracteres (por ejemplo V, MM, KW).',
            'unidad.regex' => 'La unidad va sin espacios ni números: por ejemplo V, MM o KW.',
        ];
    }

    /**
     * Se recorta antes de validar: así "   " no pasa como nombre y no se guardan nombres con espacios de
     * más, que era una fuente de duplicados.
     */
    protected function prepareForValidation(): void
    {
        $recortar = fn (string $campo) => is_string($this->input($campo)) ? trim($this->input($campo)) : $this->input($campo);

        $this->merge([
            'nombre' => $recortar('nombre'),
            'orden' => $recortar('orden') === '' ? null : $recortar('orden'),
            'unidad' => $recortar('unidad') === '' ? null : $recortar('unidad'),
        ]);
    }

    public function nombre(): string
    {
        return Caracteristica::normalizarNombre((string) $this->validated('nombre'));
    }

    public function orden(): ?string
    {
        return Caracteristica::normalizarOrden($this->validated('orden'));
    }

    public function caracteristicaId(): ?int
    {
        $id = $this->validated('id');

        return $id === null ? null : (int) $id;
    }

    /** El formato elegido. Sin los campos (un alta con sólo el nombre): texto en mayúsculas. */
    public function formato(): FormatoCaracteristica
    {
        $numerica = $this->validated('tipo') === 'numero';
        $unidad = $numerica ? $this->validated('unidad') : null;

        return new FormatoCaracteristica(
            mayusculas: $this->has('mayusculas') ? $this->boolean('mayusculas') : true,
            numerica: $numerica,
            unidad: $unidad !== null ? (string) $unidad : null,
        );
    }

    /** @return array{mayusculas: bool, numerica: bool, unidad: ?string} */
    public function atributosDeFormato(): array
    {
        $formato = $this->formato();

        return ['mayusculas' => $formato->mayusculas, 'numerica' => $formato->numerica, 'unidad' => $formato->unidad];
    }
}
