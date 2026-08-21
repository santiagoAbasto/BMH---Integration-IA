<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->check();
    }

    public function rules(): array
    {
        return [
            // Un mensaje puede ser sólo una foto, así que el texto es opcional,
            // pero acotado: no queremos que entre un prompt de 50 KB.
            'message'          => ['nullable', 'string', 'max:2000'],
            'attachment_ids'   => ['array', 'max:' . config('bmh.ai.limits.max_images_per_message', 3)],
            'attachment_ids.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.max' => 'El mensaje es demasiado largo. Contame lo esencial y seguimos.',
        ];
    }
}
