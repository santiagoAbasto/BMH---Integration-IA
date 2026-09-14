<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'orden' => ['required', 'string', 'max:20'],
            'desktop_image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,svg,mp4,avi,mov,wmv,flv,mkv', 'max:51200'],
            'mobile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:10240'],
            'baner_texto' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'desktop_image' => 'imagen desktop',
            'mobile_image' => 'imagen mobile',
        ];
    }
}
