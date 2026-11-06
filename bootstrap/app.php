<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'stop-impersonate',
            'users/*/impersonate',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class, // Jika di Laravel 11
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi telah kedaluwarsa, silakan refresh halaman.'], 419);
            }
            return redirect()->back()
                ->withInput($request->except('_token', 'password'))
                ->with('error', 'Sesi halaman telah kedaluwarsa. Halaman telah disegarkan otomatis, silakan coba kirim kembali.');
        });
    })->create();


