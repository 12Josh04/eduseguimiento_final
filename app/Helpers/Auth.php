<?php

namespace App\Helpers;

/**
 * Auth — Autenticación por sesión PHP.
 * Reemplaza el Facade Auth de Laravel/Breeze.
 * No usa Eloquent ni tokens.
 */
class Auth
{
    public static function user(): ?object
    {
        if (!session('user_id')) return null;
        return (object) [
            'id'     => session('user_id'),
            'nombre' => session('user_name'),
            'email'  => session('user_email'),
            'rol'    => session('user_rol'),
        ];
    }

    public static function id(): mixed
    {
        return session('user_id');
    }

    public static function check(): bool
    {
        return (bool) session('user_id');
    }

    public static function rol(): ?string
    {
        return session('user_rol');
    }
}
