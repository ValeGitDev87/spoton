<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:DELETE'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! Hash::check((string) $this->input('current_password'), $this->user()->password)) {
                    $validator->errors()->add('current_password', 'La password attuale non e corretta.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.in' => 'Per confermare la cancellazione scrivi DELETE.',
        ];
    }
}
