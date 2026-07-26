<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * SISTEMA DE AUTH PROPIO POR SESIÓN.
         * NO se usa el middleware 'auth' de Laravel/Breeze/Sanctum.
         * Único alias activo: 'auth.session' → AuthMiddleware propio.
         */
        $middleware->alias([
            'auth.session'   => \App\Http\Middleware\AuthMiddleware::class,
            'docente.permiso' => \App\Http\Middleware\DocentePermiso::class,
            'alumno.permiso'  => \App\Http\Middleware\AlumnoPermiso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
