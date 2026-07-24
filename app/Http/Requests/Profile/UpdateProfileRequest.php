<?php

namespace App\Http\Requests\Profile;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'avatar_url' => [
                'sometimes',
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (
                        is_string($value)
                        && ! str_starts_with($value, '/storage/')
                        && ! filter_var($value, FILTER_VALIDATE_URL)
                    ) {
                        $fail('Il campo avatar non contiene un URL valido.');
                    }
                },
            ],
        ];
    }
}
