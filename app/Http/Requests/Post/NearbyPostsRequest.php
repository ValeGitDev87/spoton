<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class NearbyPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['sometimes', 'numeric', 'min:1', 'max:200'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'location_id' => ['sometimes', 'uuid', 'exists:locations,id'],
        ];
    }
}
