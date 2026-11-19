<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'message_id' => ['required', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:500'],
            // Un 👎 pide el motivo O el código correcto: con el código alcanza para
            // que el asesor aprenda, y es lo que un probador sabe responder.
            'reason' => ['nullable', 'string', 'max:500', Rule::requiredIf(
                fn (): bool => ! $this->boolean('was_correct') && trim((string) $this->input('expected_code', '')) === '',
            )],
            'expected_product_id' => ['nullable', 'integer', 'min:1'],
            'expected_code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
