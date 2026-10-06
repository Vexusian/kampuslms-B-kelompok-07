<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Tambahkan session middleware ke API agar browser bisa
        // mengakses API route dengan session/cookie (login via URL)
        $middleware->prependToGroup('api', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API unauthenticated → selalu JSON 401 untuk api/* dan wantsJson()
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // 403 Forbidden → AuthorizationException
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Anda tidak memiliki akses ke sumber daya ini.',
                ], 403);
            }
        });

        // 403 Forbidden → AccessDeniedHttpException
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Anda tidak memiliki akses ke sumber daya ini.',
                ], 403);
            }
        });

        // HTTP Exception 401 & 403 (abort(403), abort(401), dll)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                if ($e->getStatusCode() === 403) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Anda tidak memiliki akses ke sumber daya ini.',
                    ], 403);
                }

                if ($e->getStatusCode() === 401) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Unauthenticated.',
                    ], 401);
                }
            }
        });

        // 422 Validation
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
