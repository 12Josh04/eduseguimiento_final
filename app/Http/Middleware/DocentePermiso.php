<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DocentePermiso
 *
 * Garantiza que un docente solo opere sobre cursos que
 * tiene asignados. Se registra como 'docente.permiso'.
 *
 * Aplica en: POST /notas  y  DELETE /notas/{id}
 */
class DocentePermiso
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Solo restringir a docentes; promotores y psicólogas pasan sin filtro
        if (session('user_rol') !== 'docente') {
            return $next($request);
        }

        $docenteId = session('user_id');

        // ── Verificar curso_id en el body (al registrar nota) ─────────────
        if ($request->filled('curso_id')) {
            $this->abortar_si_no_asignado($docenteId, $request->input('curso_id'));
        }

        // ── Verificar la nota existente en la ruta (al eliminar nota) ─────
        $notaId = $request->route('id') ?? $request->route('nota');
        if ($notaId) {
            $nota = DB::table('notas')->where('id', $notaId)->first();
            if ($nota) {
                $this->abortar_si_no_asignado($docenteId, $nota->curso_id);
            }
        }

        return $next($request);
    }

    private function abortar_si_no_asignado(string $docenteId, string $cursoId): void
    {
        $asignado = DB::table('asignaciones_docente')
            ->where('docente_id', $docenteId)
            ->where('curso_id',   $cursoId)
            ->where('activo', 1)
            ->exists();

        if (!$asignado) {
            abort(403, 'No tienes permiso para operar en ese curso. Solo puedes gestionar los cursos que te están asignados.');
        }
    }
}
