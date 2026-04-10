<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\AttachBearerTokenFromCookie::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API middleware stays token-only (no stateful/cookie auth)

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'self' => \App\Http\Middleware\CheckSelfOrRole::class,
            'store_manager' => \App\Http\Middleware\CheckStoreManager::class,
            'trial.setup' => \App\Http\Middleware\EnsureTrialSetupComplete::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'account.operational' => \App\Http\Middleware\EnsureAccountOperational::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            if ($request->is('shop*')) {
                $redirect = $request->fullUrl();
                return redirect('/customer/login?redirect=' . urlencode($redirect));
            }

            return redirect('/login');
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized.',
                    ], 403);
                }

                return redirect('/unauthorized');
            }
        });
    })
    ->create();
