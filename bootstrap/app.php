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
        // Percayai HANYA proxy internal (loopback + rentang alamat privat),
        // bukan '*'. Nilai '*' membuat klien mana pun dapat memalsukan alamat
        // IP-nya melalui header X-Forwarded-For/-Proto. Sesuaikan daftar ini
        // dengan alamat IP proxy/load balancer pada lingkungan produksi.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '::1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        // Tamu yang mencoba mengakses rute terproteksi diarahkan ke login panel admin.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
