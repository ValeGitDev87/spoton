<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['sometimes', 'string', 'min:3', 'max:2000'],
            'musica' => ['sometimes', 'nullable', 'string', 'max:255'],
            'song_quote' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sighting_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'secret_question' => ['sometimes', 'nullable', 'string', 'max:500', 'required_with:secret_answer'],
            'secret_answer' => ['sometimes', 'nullable', 'string', 'max:255', 'required_with:secret_question'],
        ];
    }
}
