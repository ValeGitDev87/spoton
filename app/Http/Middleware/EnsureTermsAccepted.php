<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $currentVersion = (string) config('spoton.terms.version');

        if (! $user?->terms_accepted_at || $user->terms_version !== $currentVersion) {
            return new JsonResponse([
                'message' => 'Per continuare devi accettare i Termini di utilizzo correnti.',
                'data' => [
                    'code' => 'terms_acceptance_required',
                    'terms_version' => $currentVersion,
                    'terms_url' => url('/terms'),
                ],
            ], 403);
        }

        return $next($request);
    }
}
