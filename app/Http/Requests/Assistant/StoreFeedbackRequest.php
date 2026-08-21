<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->check();
    }

    public function rules(): array
    {
        return [
            'was_correct' => ['required', 'boolean'],
            'message_id'  => ['nullable', 'integer', 'min:1'],
            'product_id'  => ['nullable', 'integer', 'min:1'],
            'comment'     => ['nullable', 'string', 'max:500'],
        ];
    }
}
