<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AlumnoPermiso
 *
 * Garantiza que un estudiante solo pueda ver sus propios datos.
 * Almacena estudiante_id en sesión para evitar consultas repetidas.
 * Se registra como 'alumno.permiso'.
 *
 * Aplica en: todas las rutas del grupo /alumno/*
 */
class AlumnoPermiso
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (session('user_rol') !== 'estudiante') {
            return $next($request);
        }

        // Resolver y cachear estudiante_id en sesión
        if (!session('estudiante_id')) {
            $est = DB::table('estudiantes')
                ->where('usuario_id', session('user_id'))
                ->where('activo', 1)
                ->first(['id']);

            if (!$est) {
                return redirect('/login')
                    ->with('error', 'Tu cuenta de estudiante no está configurada. Contacta al administrador.');
            }

            session(['estudiante_id' => $est->id]);
        }

        // Verificar acceso a nota específica ajena
        $routeId = $request->route('id');

        if ($routeId && str_contains($request->path(), 'notas')) {
            $nota = DB::table('notas')->where('id', $routeId)->value('estudiante_id');
            if ($nota && $nota !== session('estudiante_id')) {
                abort(403, 'No tienes permiso para ver esta nota.');
            }
        }

        // Verificar acceso a conducta específica ajena
        if ($routeId && str_contains($request->path(), 'conductas')) {
            $cond = DB::table('conductas')->where('id', $routeId)->value('estudiante_id');
            if ($cond && $cond !== session('estudiante_id')) {
                abort(403, 'No tienes permiso para ver esta conducta.');
            }
        }

        return $next($request);
    }
}
