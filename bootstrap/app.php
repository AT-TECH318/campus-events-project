<?php

use App\Exceptions\EventRegistrationException;
use App\Http\Middleware\AttachSessionBearerToken;
use App\Http\Middleware\AuthenticateOptionalSanctum;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'auth.optional' => AuthenticateOptionalSanctum::class,
        ]);

        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AttachSessionBearerToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (EventRegistrationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match ($exception->reason) {
                EventRegistrationException::ALREADY_REGISTERED => 409,
                EventRegistrationException::NOT_REGISTERED => 404,
                default => 422,
            };

            return response()->json([
                'message' => $exception->getMessage(),
                'reason' => $exception->reason,
            ], $status);
        });

        $exceptions->render(function (InvalidArgumentException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });
    })->create();
