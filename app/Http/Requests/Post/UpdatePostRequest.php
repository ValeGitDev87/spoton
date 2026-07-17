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
            'audio' => ['sometimes', 'nullable', 'file', 'max:1024', 'mimetypes:audio/mp4,audio/aac,audio/mpeg,audio/webm,video/mp4'],
            'audio_duration_seconds' => ['required_with:audio', 'nullable', 'numeric', 'min:0.1', 'max:10'],
            'remove_audio' => ['sometimes', 'boolean'],
            'sighting_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'secret_question' => ['sometimes', 'nullable', 'string', 'max:500', 'required_with:secret_answer'],
            'secret_answer' => ['sometimes', 'nullable', 'string', 'max:255', 'required_with:secret_question'],
        ];
    }
}
