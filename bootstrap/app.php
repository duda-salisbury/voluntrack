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
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Runs after SubstituteBindings so route model binding is complete.
        // Shares $org with every Blade view rendered during the request.
        $middleware->appendToGroup('web', \App\Http\Middleware\ShareOrganizationWithViews::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
