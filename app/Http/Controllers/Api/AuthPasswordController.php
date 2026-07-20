<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Services\Auth\PasswordRules;
use App\Services\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthPasswordController extends Controller
{
    private const FORGOT_RESPONSE = 'Se l\'indirizzo e associato a un account, riceverai le istruzioni per reimpostare la password.';

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        Password::sendResetLink([
            'email' => mb_strtolower(trim($data['email'])),
        ]);

        return response()->json([
            'message' => self::FORGOT_RESPONSE,
        ]);
    }

    public function reset(Request $request, ResetsPasswords $resetsPasswords): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => PasswordRules::rules(),
        ]);

        $status = $resetsPasswords->reset([
            'email' => mb_strtolower(trim($data['email'])),
            'token' => $data['token'],
            'password' => $data['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ]);

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Il link di reset non e valido o e scaduto.'],
            ]);
        }

        return response()->json([
            'message' => 'Password aggiornata. Effettua nuovamente il login.',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [...PasswordRules::rules(), 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La password attuale non e corretta.'],
            ]);
        }

        $currentTokenId = $user->currentAccessToken()?->id;

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();

        $user->notify(new PasswordChangedNotification);

        return response()->json([
            'message' => 'Password aggiornata.',
            'data' => [
                'other_tokens_revoked' => true,
            ],
        ]);
    }
}
