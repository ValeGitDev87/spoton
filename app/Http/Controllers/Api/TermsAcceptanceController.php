<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesUsers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermsAcceptanceController extends Controller
{
    use SerializesUsers;

    public function store(Request $request): JsonResponse
    {
        $request->validate(['accepted' => ['required', 'accepted']]);

        $request->user()->update([
            'terms_accepted_at' => now(),
            'terms_version' => config('spoton.terms.version'),
        ]);

        return response()->json([
            'message' => 'Termini accettati.',
            'data' => ['user' => $this->userPayload($request->user()->fresh())],
        ]);
    }
}
