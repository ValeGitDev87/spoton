<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePublicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'motto' => ['sometimes', 'nullable', 'string', 'max:160'],
            'favorite_song' => ['sometimes', 'nullable', 'string', 'max:255'],
            'show_bio' => ['sometimes', 'boolean'],
            'show_motto' => ['sometimes', 'boolean'],
            'show_favorite_song' => ['sometimes', 'boolean'],
            'public_photo_urls' => ['sometimes', 'array', 'max:10'],
            'public_photo_urls.*' => ['required', 'string', 'max:2048', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $ownedPhotos = $this->user()->photos ?? [];

            foreach ($this->input('public_photo_urls', []) as $index => $url) {
                if (! in_array($url, $ownedPhotos, true)) {
                    $validator->errors()->add(
                        "public_photo_urls.{$index}",
                        'Puoi rendere pubbliche soltanto foto presenti nella tua galleria.',
                    );
                }
            }
        }];
    }
}
