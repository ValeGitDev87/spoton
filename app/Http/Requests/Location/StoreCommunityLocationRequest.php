<?php

namespace App\Http\Requests\Location;

use App\Rules\AcceptableContent;
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
        $prepared = [];

        if ($this->has('name')) {
            $prepared['name'] = Str::squish((string) $this->input('name'));
        }

        if ($this->has('city')) {
            $prepared['city'] = Str::squish((string) $this->input('city'));
        }

        if ($this->has('type')) {
            $prepared['type'] = Str::lower(trim((string) $this->input('type')));
        }

        if ($this->has('latitude')) {
            $prepared['latitude'] = $this->normalizeCoordinate($this->input('latitude'));
        }

        if ($this->has('longitude')) {
            $prepared['longitude'] = $this->normalizeCoordinate($this->input('longitude'));
        }

        $this->merge($prepared);
    }

    public function rules(): array
    {
        return [
            'candidate_token' => ['nullable', 'string', 'max:4096'],
            'name' => [Rule::requiredIf(! $this->filled('candidate_token')), 'string', 'min:3', 'max:100', new AcceptableContent],
            'city' => [Rule::requiredIf(! $this->filled('candidate_token')), 'string', 'min:2', 'max:120', new AcceptableContent],
            'type' => [Rule::requiredIf(! $this->filled('candidate_token')), 'string', Rule::in(LocationType::codes())],
            'latitude' => [Rule::requiredIf(! $this->filled('candidate_token')), 'numeric', 'between:-90,90'],
            'longitude' => [Rule::requiredIf(! $this->filled('candidate_token')), 'numeric', 'between:-180,180'],
            'location_kind' => ['nullable', 'string', Rule::in(['poi', 'area'])],
            'provider' => ['prohibited'],
            'provider_place_id' => ['prohibited'],
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
                if (! $this->filled('candidate_token')
                    && (float) $this->input('latitude') === 0.0
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
