<?php

namespace App\Http\Requests\Location;

use App\Support\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCommunityLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'city' => Str::squish((string) $this->input('city')),
            'type' => Str::lower(trim((string) $this->input('type'))),
            'latitude' => $this->normalizeCoordinate($this->input('latitude')),
            'longitude' => $this->normalizeCoordinate($this->input('longitude')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'type' => ['required', 'string', Rule::in(LocationType::codes())],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'short' => ['prohibited'],
            'geo_radius_meters' => ['prohibited'],
            'icon' => ['prohibited'],
            'is_active' => ['prohibited'],
            'is_locked' => ['prohibited'],
            'access_password' => ['prohibited'],
            'access_password_hash' => ['prohibited'],
            'tier' => ['prohibited'],
            'moderation_status' => ['prohibited'],
            'created_by_user_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((float) $this->input('latitude') === 0.0
                    && (float) $this->input('longitude') === 0.0) {
                    $validator->errors()->add(
                        'latitude',
                        'La posizione GPS non e valida. Rileva nuovamente la posizione.',
                    );
                }
            },
        ];
    }

    private function normalizeCoordinate(mixed $value): mixed
    {
        return is_string($value)
            ? str_replace(',', '.', trim($value))
            : $value;
    }
}
