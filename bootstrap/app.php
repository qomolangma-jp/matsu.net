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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(function (Request $request) {
            $path = '/'.$request->path();

            if (preg_match('#^/(events|news)(/.*)?$#', $path)) {
                return '/?liff.state=' . urlencode($path);
            }

            return route('login');
        });

        $middleware->alias([
            'approved'      => \App\Http\Middleware\RequireApproved::class,
            'master_admin'  => \App\Http\Middleware\RequireMasterAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
