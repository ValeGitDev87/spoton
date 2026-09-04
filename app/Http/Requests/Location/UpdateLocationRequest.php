<?php

namespace App\Http\Requests\Location;

use App\Models\Location;
use App\Support\LocationIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $coordinates = [];

        foreach (['latitude', 'longitude'] as $field) {
            if ($this->exists($field)) {
                $coordinates[$field] = $this->normalizeCoordinate($this->input($field));
            }
        }

        $this->merge($coordinates);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'short' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'string', 'max:80'],
            'tier' => ['sometimes', Rule::in([
                Location::TIER_COMMUNITY,
                Location::TIER_PARTNER,
            ])],
            'moderation_status' => ['sometimes', Rule::in([
                Location::MODERATION_PENDING,
                Location::MODERATION_APPROVED,
                Location::MODERATION_REJECTED,
                Location::MODERATION_SUSPENDED,
            ])],
            'moderation_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['sometimes', 'integer', 'min:1', 'max:200000'],
            'icon' => ['sometimes', 'required', 'string', Rule::in(LocationIcon::codes())],
            'is_active' => ['sometimes', 'boolean'],
            'is_locked' => ['sometimes', 'boolean'],
            'access_password' => [
                Rule::requiredIf(function (): bool {
                    $location = $this->route('location');

                    return $this->boolean('is_locked')
                        && (! $location instanceof Location
                            || ! $location->access_password_hash);
                }),
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
