<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use SerializesUsers;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        event(new Registered($user));
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $user->createToken('spoton-api')->plainTextToken,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenziali non valide.'],
            ]);
        }

        abort_if($user->is_suspended, 403, 'Account sospeso. Contatta l\'assistenza.');

        return response()->json([
            'message' => 'OK',
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $user->createToken('spoton-api')->plainTextToken,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => [
                'user' => $this->userPayload($request->user()),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'logged_out' => true,
            ],
        ]);
    }
}
