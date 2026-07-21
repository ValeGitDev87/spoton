<?php

namespace App\Http\Requests\Location;

use App\Support\LocationIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'short' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'string', 'max:80'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['sometimes', 'integer', 'min:1', 'max:200000'],
            'icon' => ['sometimes', 'required', 'string', Rule::in(LocationIcon::codes())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
