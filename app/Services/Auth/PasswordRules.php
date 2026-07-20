<?php

namespace App\Services\Auth;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function rules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(8)->max(72)];
    }
}
