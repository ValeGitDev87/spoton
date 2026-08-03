<?php

namespace App\Http\Requests\Location;

use App\Support\LocationIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'latitude' => $this->normalizeCoordinate($this->input('latitude')),
            'longitude' => $this->normalizeCoordinate($this->input('longitude')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:80'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['sometimes', 'integer', 'min:1', 'max:200000'],
            'icon' => ['required', 'string', Rule::in(LocationIcon::codes())],
            'is_active' => ['sometimes', 'boolean'],
            'is_locked' => ['sometimes', 'boolean'],
            'access_password' => [
                Rule::requiredIf(fn (): bool => $this->boolean('is_locked')),
                'nullable',
                'string',
                'min:4',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'access_password.required' => 'Inserisci una password per il luogo riservato.',
            'access_password.min' => 'La password del luogo deve contenere almeno 4 caratteri.',
        ];
    }

    private function normalizeCoordinate(mixed $value): mixed
    {
        return is_string($value)
            ? str_replace(',', '.', trim($value))
            : $value;
    }
}
