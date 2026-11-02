<?php

declare(strict_types=1);

namespace App\Http\Requests\AiTest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class SendAiTestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:2000', 'required_without:attachment_ids'],
            'attachment_ids' => ['array', 'max:'.config('bmh.ai.limits.max_images_per_message', 3)],
            'attachment_ids.*' => ['integer', 'min:1'],
        ];
    }
}
