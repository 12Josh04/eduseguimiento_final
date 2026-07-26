<?php

return [
    'name'  => env('APP_NAME', 'EduSeguimiento'),
    'env'   => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url'   => env('APP_URL', 'http://localhost'),

    'timezone'        => 'America/Lima',
    'locale'          => 'es',
    'fallback_locale' => 'es',
    'faker_locale'    => 'es_PE',

    'cipher' => 'AES-256-CBC',
    'key'    => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    ],

    'maintenance' => ['driver' => 'file'],

    /*
    |--------------------------------------------------------------------------
    | ALIAS CRÍTICO — NO CAMBIAR
    |--------------------------------------------------------------------------
    | 'Auth' → App\Helpers\Auth  (sesión PHP simple, sin Eloquent)
    |
    | Si instalas Breeze u otro paquete de auth, este alias debe mantenerse
    | apuntando a App\Helpers\Auth para que el sistema funcione.
    */
    'aliases' => \Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'Auth'   => App\Helpers\Auth::class,
        'Carbon' => Carbon\Carbon::class,
    ])->toArray(),
];
