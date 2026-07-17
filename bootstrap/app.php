<?php

use App\Http\Middleware\EnsureDonorSession;
use App\Http\Middleware\ResolveDonationElement;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrustCloudflare;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/app.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/stripe/webhook',
            '/webhooks/mailgun',
            '/webhooks/postmark',
        ]);

        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->prepend(TrustCloudflare::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->alias([
            'resolve.element' => ResolveDonationElement::class,
            'donor.auth' => EnsureDonorSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
