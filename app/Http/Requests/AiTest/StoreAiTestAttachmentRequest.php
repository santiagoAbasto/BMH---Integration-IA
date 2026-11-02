<?php

declare(strict_types=1);

namespace App\Http\Requests\AiTest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class StoreAiTestAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        $maxKb = (int) config('bmh.attachments.max_size_kb', 12288);

        return [
            'images' => ['required', 'array', 'min:1', 'max:'.config('bmh.ai.limits.max_images_per_message', 3)],
            'images.*' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif'],
        ];
    }
}
