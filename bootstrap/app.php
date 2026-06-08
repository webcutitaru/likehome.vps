<?php

use App\Http\Controllers\Api\IcalExportController;
use App\Http\Middleware\LegacyRedirects;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::get('/ical/{token}.ics', [IcalExportController::class, 'show']);
            require __DIR__.'/../routes/legacy_compat.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/maib/callback',
            'ajax/maib_callback.php',
        ]);
        $middleware->web(prepend: [
            LegacyRedirects::class,
        ]);
        $middleware->alias([
            'locale' => SetLocale::class,
            'legacy.redirects' => LegacyRedirects::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
