<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'role'  => \App\Http\Middleware\RequireRole::class,
            'perm'  => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419（CSRF不一致）は専用ページで案内
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'セッションが切れました。画面を再読み込みしてからもう一度お試しください。',
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });
    })
    ->create();
