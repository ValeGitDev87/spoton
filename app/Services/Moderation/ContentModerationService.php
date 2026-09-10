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
        $deobfuscated = $this->collapseArtificialLetterSpacing($normalized);

        $match = $this->matchPatterns($normalized, (array) config('content_moderation.blocked_patterns', []));

        if ($match === null && $deobfuscated !== $normalized) {
            $match = $this->matchPatterns(
                $deobfuscated,
                (array) config('content_moderation.blocked_patterns', []),
            );
        }

        if ($match !== null) {
            return ['decision' => self::BLOCK, 'matched' => $match];
        }

        $compactMatch = $this->matchPatterns(
            $deobfuscated,
            (array) config('content_moderation.compact_blocked_patterns', []),
        );

        if ($compactMatch !== null) {
            return ['decision' => self::BLOCK, 'matched' => $compactMatch];
        }

        foreach ((array) config('content_moderation.blocked_phrases', []) as $phrase) {
            if ($this->contains($normalized, (string) $phrase)) {
                return ['decision' => self::BLOCK, 'matched' => (string) $phrase];
            }
        }

        $warningMatch = $this->matchPatterns(
            $normalized,
            (array) config('content_moderation.warning_patterns', []),
        );

        if ($warningMatch === null && $deobfuscated !== $normalized) {
            $warningMatch = $this->matchPatterns(
                $deobfuscated,
                (array) config('content_moderation.warning_patterns', []),
            );
        }

        if ($warningMatch !== null) {
            return ['decision' => self::WARN, 'matched' => $warningMatch];
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

    private function collapseArtificialLetterSpacing(string $text): string
    {
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = [];
        $singleLetters = [];

        $flushLetters = function () use (&$result, &$singleLetters): void {
            if ($singleLetters === []) {
                return;
            }

            if (count($singleLetters) >= 3) {
                $result[] = implode('', $singleLetters);
            } else {
                array_push($result, ...$singleLetters);
            }

            $singleLetters = [];
        };

        foreach ($tokens as $token) {
            if (strlen($token) === 1) {
                $singleLetters[] = $token;

                continue;
            }

            $flushLetters();
            $result[] = $token;
        }

        $flushLetters();

        return implode(' ', $result);
    }

    /** @param array<string, string> $patterns */
    private function matchPatterns(string $text, array $patterns): ?string
    {
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return (string) $name;
            }
        }

        return null;
    }
}
