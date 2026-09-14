<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'banned' => \App\Http\Middleware\EnsureUserIsNotBanned::class,
            'session.idle' => \App\Http\Middleware\EnsureSessionIsActive::class,
        ]);

        // Resuelve el idioma del contenido de fábrica (config('site')) en
        // toda request web, antes de que cualquier controlador/vista lo lea.
        $middleware->web(append: [
            \App\Http\Middleware\ResolveSiteLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
