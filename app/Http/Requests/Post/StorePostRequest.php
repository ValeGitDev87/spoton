<?php

namespace App\Http\Requests\Post;

use App\Models\Location;
use App\Models\User;
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
            'mention_user_ids' => ['sometimes', 'array', 'max:10'],
            'mention_user_ids.*' => [
                'uuid',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('is_admin', false)
                    ->where('is_suspended', false)),
            ],
            'mention_everyone' => ['sometimes', 'boolean'],
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

                $this->validateMentions($validator);

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

    private function validateMentions(Validator $validator): void
    {
        $text = (string) $this->input('text', '');
        $mentionUserIds = array_values(array_unique(array_filter(
            (array) $this->input('mention_user_ids', []),
            'is_string',
        )));
        $mentionsEveryone = $this->boolean('mention_everyone');
        $containsEveryone = preg_match('/(^|\s)@tutti(?![\p{L}\p{N}_])/iu', $text) === 1;

        if ($containsEveryone && ! $this->user()?->can_mention_everyone) {
            $validator->errors()->add('text', 'Non sei autorizzato a usare @tutti.');
        }

        if ($mentionsEveryone && ! $this->user()?->can_mention_everyone) {
            $validator->errors()->add('mention_everyone', 'Non sei autorizzato a menzionare tutti.');
        }

        if ($mentionsEveryone && ! $containsEveryone) {
            $validator->errors()->add('mention_everyone', 'Inserisci @tutti nel testo dell’annuncio.');
        }

        if ($containsEveryone && $this->user()?->can_mention_everyone && ! $mentionsEveryone) {
            $validator->errors()->add('mention_everyone', 'Seleziona @tutti dai suggerimenti.');
        }

        if ($mentionUserIds === [] || $validator->errors()->has('mention_user_ids')) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $mentionUserIds)
            ->get()
            ->keyBy('id');

        foreach ($mentionUserIds as $userId) {
            if ($userId === $this->user()?->id) {
                $validator->errors()->add('mention_user_ids', 'Non puoi taggare te stesso.');

                continue;
            }

            $mentionedUser = $users->get($userId);

            if (! $mentionedUser || ! $this->textContainsMention($text, $mentionedUser->display_name)) {
                $validator->errors()->add(
                    'mention_user_ids',
                    'Ogni persona selezionata deve comparire nel testo con la chiocciola.',
                );
            }
        }
    }

    private function textContainsMention(string $text, string $displayName): bool
    {
        return preg_match(
            '/(^|\s)@'.preg_quote(trim($displayName), '/').'(?![\p{L}\p{N}_])/iu',
            $text,
        ) === 1;
    }
}
