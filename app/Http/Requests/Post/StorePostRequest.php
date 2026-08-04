<?php

namespace App\Http\Requests\Post;

use App\Models\Location;
use App\Support\PostCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'location_password' => ['nullable', 'string', 'max:255'],
            'text' => ['required', 'string', 'min:3', 'max:2000'],
            'category' => ['sometimes', 'string', Rule::in(PostCategory::values())],
            'musica' => ['nullable', 'string', 'max:255'],
            'song_quote' => ['nullable', 'string', 'max:255'],
            'audio' => ['nullable', 'file', 'max:1024', 'mimetypes:audio/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/webm,video/mp4'],
            'audio_duration_seconds' => ['required_with:audio', 'nullable', 'numeric', 'min:0.1', 'max:10'],
            'sighting_date' => ['required', 'date', 'before_or_equal:today'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'secret_question' => ['nullable', 'string', 'max:500', 'required_with:secret_answer'],
            'secret_answer' => ['nullable', 'string', 'max:255', 'required_with:secret_question'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->input('category', PostCategory::SPOTTED_LOVE);

                if (
                    $category !== PostCategory::SPOTTED_LOVE
                    && ($this->filled('secret_question') || $this->filled('secret_answer'))
                ) {
                    $validator->errors()->add(
                        'secret_question',
                        'La domanda di verifica e disponibile solo per Spotted / Amore.',
                    );
                }

                if ($validator->errors()->has('location_id')) {
                    return;
                }

                $location = Location::query()->find($this->input('location_id'));

                if (! $location?->is_locked) {
                    return;
                }

                $password = $this->input('location_password');

                if ($location->accessPasswordMatches(is_string($password) ? $password : null)) {
                    return;
                }

                $validator->errors()->add(
                    'location_password',
                    filled($password)
                        ? 'Password del luogo non corretta.'
                        : 'Inserisci la password del luogo riservato.',
                );
            },
        ];
    }
}
