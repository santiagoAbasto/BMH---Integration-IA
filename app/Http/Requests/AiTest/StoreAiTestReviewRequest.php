<?php

declare(strict_types=1);

namespace App\Http\Requests\AiTest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StoreAiTestReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        return [
            'message_id' => ['required', 'integer', 'min:1'],
            'rating' => ['required', Rule::in(['correct', 'needs_adjustment', 'incorrect'])],
            'expected_action' => ['required', Rule::in([
                'ask_question', 'present_product', 'abstain', 'handoff', 'quote',
            ])],
            'expected_question_id' => ['nullable', 'string', 'max:120'],
            'expected_product_id' => ['nullable', 'integer', 'min:1'],
            'expected_code' => ['nullable', 'string', 'max:64'],
            'surface' => ['required', Rule::in(['private', 'public', 'both'])],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'issue_tags' => ['array', 'max:8'],
            'issue_tags.*' => ['string', Rule::in([
                'wrong_question', 'wrong_result', 'ignored_data', 'tone',
                'price', 'image', 'handoff', 'other',
            ])],
            'comment' => ['nullable', 'string', 'max:2000', 'required_unless:rating,correct'],
        ];
    }
}
