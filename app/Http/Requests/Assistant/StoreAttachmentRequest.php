<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->check();
    }

    public function rules(): array
    {
        $maxKb = (int) config('bmh.attachments.max_size_kb', 12288);

        return [
            'images'   => ['required', 'array', 'min:1', 'max:' . config('bmh.ai.limits.max_images_per_message', 3)],
            // `image` valida contenido real, no sólo extensión. HEIC va aparte
            // porque getimagesize() no siempre lo reconoce.
            'images.*' => ['required', 'file', 'max:' . $maxKb, 'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.mimetypes' => 'Mandá una foto en JPG, PNG, WEBP o HEIC.',
            'images.*.max'       => 'La foto es muy pesada. Probá con una de menor tamaño.',
        ];
    }
}
