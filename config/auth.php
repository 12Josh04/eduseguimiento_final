<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NOTA IMPORTANTE — EduSeguimiento
    |--------------------------------------------------------------------------
    | Este archivo existe para satisfacer paquetes de Laravel que lo requieran,
    | pero el sistema NO usa el guard de Laravel ni Eloquent para autenticar.
    | La autenticación se hace con sesión PHP en AuthController + AuthMiddleware.
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
