<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class AttachSessionBearerToken
{
    /**
     * Copy a same-origin session token into the Authorization header.
     *
     * Requests that already send a bearer token, including Postman and the test suite, skip this path.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() || ! $request->hasSession()) {
            return $next($request);
        }

        $stored = $request->session()->get('api_token');

        if (! is_string($stored) || $stored === '') {
            return $next($request);
        }

        try {
            $token = Crypt::decryptString($stored);
        } catch (DecryptException) {
            $request->session()->forget('api_token');

            return $next($request);
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $sent = (string) $request->header('X-CSRF-TOKEN', '');

            if ($sent === '' || ! hash_equals((string) $request->session()->token(), $sent)) {
                abort(419, 'Page expired. Refresh and try again.');
            }
        }

        $request->headers->set('Authorization', 'Bearer '.$token);

        return $next($request);
    }
}
