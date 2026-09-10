<?php

namespace App\Services\Moderation;

use Illuminate\Support\Str;

class ContentModerationService
{
    public const ALLOW = 'allow';

    public const WARN = 'warn';

    public const BLOCK = 'block';

    /** @return array{decision: string, matched: string|null} */
    public function evaluate(?string $text): array
    {
        $normalized = $this->normalize($text ?? '');

        foreach ((array) config('content_moderation.blocked_phrases', []) as $phrase) {
            if ($this->contains($normalized, (string) $phrase)) {
                return ['decision' => self::BLOCK, 'matched' => (string) $phrase];
            }
        }

        foreach ((array) config('content_moderation.warning_phrases', []) as $phrase) {
            if ($this->contains($normalized, (string) $phrase)) {
                return ['decision' => self::WARN, 'matched' => (string) $phrase];
            }
        }

        return ['decision' => self::ALLOW, 'matched' => null];
    }

    public function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = strtr($text, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a']);
        $text = preg_replace('/(.)\1{2,}/u', '$1$1', $text) ?? $text;

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text);
    }

    private function contains(string $normalized, string $phrase): bool
    {
        $needle = $this->normalize($phrase);

        return $needle !== '' && str_contains(" {$normalized} ", " {$needle} ");
    }
}
