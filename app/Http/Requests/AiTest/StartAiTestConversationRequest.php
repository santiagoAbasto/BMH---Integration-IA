<?php

declare(strict_types=1);

namespace App\Http\Requests\AiTest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class StartAiTestConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        return [
            'family' => ['required', 'string', 'max:80'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
