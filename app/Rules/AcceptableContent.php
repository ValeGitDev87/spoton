<?php

namespace App\Rules;

use App\Services\Moderation\ContentModerationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AcceptableContent implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $result = app(ContentModerationService::class)->evaluate($value);

        if ($result['decision'] !== ContentModerationService::ALLOW) {
            $fail('Il testo contiene espressioni non consentite. Modificalo e riprova.');
        }
    }
}
