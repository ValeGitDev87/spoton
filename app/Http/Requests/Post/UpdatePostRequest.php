<?php

namespace App\Http\Requests\Post;

use App\Support\PostCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['sometimes', 'string', Rule::in(PostCategory::values())],
            'musica' => ['sometimes', 'nullable', 'string', 'max:255'],
            'song_quote' => ['sometimes', 'nullable', 'string', 'max:255'],
            'audio' => ['sometimes', 'nullable', 'file', 'max:1024', 'mimetypes:audio/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/webm,video/mp4'],
            'audio_duration_seconds' => ['required_with:audio', 'nullable', 'numeric', 'min:0.1', 'max:10'],
            'remove_audio' => ['sometimes', 'boolean'],
            'sighting_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'secret_question' => ['sometimes', 'nullable', 'string', 'max:500', 'required_with:secret_answer'],
            'secret_answer' => ['sometimes', 'nullable', 'string', 'max:255', 'required_with:secret_question'],
        ];
    }
}
