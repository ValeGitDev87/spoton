<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class NearbyLocationCandidatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_provider' => $this->boolean('include_provider'),
        ]);
    }

    public function rules(): array
    {
        return [
            'include_provider' => ['sometimes', 'boolean'],
        ];
    }
}
