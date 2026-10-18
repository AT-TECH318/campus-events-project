<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOptionalSanctum
{
    /**
     * Public routes stay open. A bearer token, when present, must be valid.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null) {
            return $next($request);
        }

        Auth::shouldUse('sanctum');

        if ($request->user() === null) {
            abort(401, 'Unauthenticated.');
        }

        return $next($request);
    }
}
