<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $slug = $request->user()?->role?->slug;

        if ($slug === null || ! in_array($slug, $roles, true)) {
            abort(403, 'You do not have the required role.');
        }

        return $next($request);
    }
}
