<?php

use App\Exceptions\DomainRuleException;
use App\Exceptions\InvalidTransitionException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'must.change.password' => EnsurePasswordChanged::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [AssignRequestId::class]);

        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DomainRuleException $e, Request $request) {
            return back()->withErrors($e->messages, 'domain');
        });
        $exceptions->render(function (InvalidTransitionException $e, Request $request) {
            Log::warning('Transisi status tidak sah', ['message' => $e->getMessage()]);

            return back()->with('error', 'Status laporan sudah berubah. Muat ulang halaman.');
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
