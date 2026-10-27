<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin', 'admin/*')) {
                return '/admin/login';
            }

            if ($request->hasSession()) {
                $request->session()->flash('login_notice', $request->routeIs('cart', 'checkout')
                    ? 'Silakan login terlebih dahulu untuk membuka keranjang.'
                    : 'Silakan login terlebih dahulu.');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(fn (): string => route('account.orders'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
