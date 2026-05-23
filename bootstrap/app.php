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
            'merchant.auth' => \App\Http\Middleware\MerchantAuthMiddleware::class,
            'merchant.throttle' => \App\Http\Middleware\MerchantThrottleMiddleware::class,
            'provider.callback' => \App\Http\Middleware\ProviderCallbackMiddleware::class,
            'request.logging' => \App\Http\Middleware\RequestLoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
