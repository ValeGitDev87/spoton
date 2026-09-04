<?php

namespace App\Http\Requests\Location;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                Location::MODERATION_PENDING,
                Location::MODERATION_APPROVED,
                Location::MODERATION_REJECTED,
                Location::MODERATION_SUSPENDED,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
