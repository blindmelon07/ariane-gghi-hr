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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'      => \App\Http\Middleware\EnsureRole::class,
            'auth.sync' => \App\Http\Middleware\AuthenticateSyncToken::class,
        ]);

        // Logged-in users who open a guest page (e.g. the installed app starting at
        // /login) go to their own dashboard. Laravel's default, /dashboard, is
        // employee-only and returns 403 for admins and other roles.
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->dashboardRoute() ?? '/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
