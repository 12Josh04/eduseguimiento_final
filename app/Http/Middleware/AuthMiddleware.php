<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!session('user_id')) {
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para continuar.');
        }

        if (!empty($roles)) {
            $allowed = [];
            foreach ($roles as $r) {
                foreach (explode(',', $r) as $single) {
                    $allowed[] = trim($single);
                }
            }
            if (!in_array(session('user_rol'), $allowed)) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }
        }

        return $next($request);
    }
}
