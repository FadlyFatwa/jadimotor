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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin_or_owner' => \App\Http\Middleware\EnsureUserIsAdminOrOwner::class,
        ]);
        // Cookie "theme" ditulis langsung oleh JS (document.cookie) supaya
        // server bisa membaca preferensi dark-mode sejak request pertama;
        // harus dikecualikan dari enkripsi, kalau tidak nilainya jadi null.
        $middleware->encryptCookies(except: ['theme']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
