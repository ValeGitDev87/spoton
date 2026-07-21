<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->is_suspended, 403, 'Account sospeso. Contatta l\'assistenza.');

        return $next($request);
    }
}
