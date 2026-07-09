<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'text' => ['required', 'string', 'min:3', 'max:2000'],
            'musica' => ['nullable', 'string', 'max:255'],
            'sighting_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
