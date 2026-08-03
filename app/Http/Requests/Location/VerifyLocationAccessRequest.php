<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLocationAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Inserisci la password del luogo.',
        ];
    }
}
