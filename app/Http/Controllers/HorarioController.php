<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

use Illuminate\Http\Request;

class HorarioController extends Controller
{
    private const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // HORARIO DEL DOCENTE
    // Muestra: cursos asignados · aula · día · hora
    // ─────────────────────────────────────────────────────────────────────────
    public function docente()
    {
        $docenteId = session('user_id');

        // Resumen de cursos asignados (para la tarjeta superior)
        $cursos = DB::table('asignaciones_docente AS a')
            ->join('cursos AS c', 'c.id', '=', 'a.curso_id')
            ->join('grados AS g', 'g.id', '=', 'a.grado_id')
            ->where('a.docente_id', $docenteId)
            ->where('a.activo', 1)
            ->select('c.nombre AS curso', 'g.nombre AS grado')
            ->distinct()
            ->orderBy('c.nombre')
            ->get();

        // Horario semanal agrupado por día
        $horario = DB::table('horarios AS h')
            ->join('asignaciones_docente AS a', 'a.id', '=', 'h.asignacion_id')
            ->join('cursos AS c', 'c.id', '=', 'a.curso_id')
            ->join('grados AS g', 'g.id', '=', 'a.grado_id')
            ->where('a.docente_id', $docenteId)
            ->where('a.activo', 1)
            ->select(
                'h.dia_semana',
                'h.hora_inicio',
                'h.hora_fin',
                'h.aula',
                'c.nombre AS curso',
                'g.nombre AS grado'
            )
            ->orderBy('h.dia_semana')
            ->orderBy('h.hora_inicio')
            ->get()
            ->groupBy('dia_semana');

        $dias = self::DIAS;

        return view('horario.docente', compact('horario', 'dias', 'cursos'));
    }
    public function store(Request $request)
{
    // 1. Validar los datos recibidos
    $request->validate([
        'asignacion_id' => 'required',
        'dia_semana'    => 'required|integer|between:1,5',
        'hora_inicio'   => 'required',
        'hora_fin'      => 'required|after:hora_inicio',
        'aula'          => 'required|string|max:20',
    ]);

    // 2. Obtener la asignación para identificar al docente
    $asignacion = DB::table('asignaciones_docente')
        ->where('id', $request->asignacion_id)
        ->first();

    // 3. Lógica de validación de cruce (el "avisar que está ocupado")
    $cruce = DB::table('horarios AS h')
        ->join('asignaciones_docente AS a', 'a.id', '=', 'h.asignacion_id')
        ->where('a.docente_id', $asignacion->docente_id)
        ->where('h.dia_semana', $request->dia_semana)
        ->where(function($query) use ($request) {
            $query->whereBetween('h.hora_inicio', [$request->hora_inicio, $request->hora_fin])
                  ->orWhereBetween('h.hora_fin', [$request->hora_inicio, $request->hora_fin]);
        })
        ->exists();

    if ($cruce) {
        return back()->withErrors(['error' => 'El docente ya tiene una clase asignada en este horario.']);
    }

    // 4. Guardar el nuevo horario
    DB::table('horarios')->insert([
        'id'            => \Illuminate\Support\Str::uuid(), // Si usas UUID
        'asignacion_id' => $request->asignacion_id,
        'dia_semana'    => $request->dia_semana,
        'hora_inicio'   => $request->hora_inicio,
        'hora_fin'      => $request->hora_fin,
        'aula'          => $request->aula,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    return back()->with('success', 'Horario asignado correctamente.');
}

    // ─────────────────────────────────────────────────────────────────────────
    // HORARIO DEL ALUMNO
    // Muestra: curso · docente · aula · día · hora
    // ─────────────────────────────────────────────────────────────────────────
    public function alumno()
    {
        $dias = self::DIAS;

        // Obtener id_estudiante (con caché en sesión)
        $estudianteId = session('estudiante_id');

        if (!$estudianteId) {
            $est = DB::table('estudiantes')
                ->where('usuario_id', session('user_id'))
                ->where('activo', 1)
                ->first(['id']);

            if (!$est) {
                return view('horario.alumno', [
                    'horario'    => collect(),
                    'dias'       => $dias,
                    'sinHorario' => true,
                ]);
            }

            $estudianteId = $est->id;
            session(['estudiante_id' => $estudianteId]);
        }

        // Obtener matrícula activa del alumno
        $matricula = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->where('activo', 1)
            ->first();

        if (!$matricula) {
            return view('horario.alumno', [
                'horario'    => collect(),
                'dias'       => $dias,
                'sinHorario' => true,
            ]);
        }

        // Horario basado en el grado donde está matriculado
        $horario = DB::table('horarios AS h')
            ->join('asignaciones_docente AS a', 'a.id', '=', 'h.asignacion_id')
            ->join('cursos AS c', 'c.id', '=', 'a.curso_id')
            ->join('users  AS u', 'u.id', '=', 'a.docente_id')
            ->join('grados AS g', 'g.id', '=', 'a.grado_id')
            ->where('a.grado_id',     $matricula->grado_id)
            ->where('a.anio_escolar', $matricula->anio_escolar)
            ->where('a.activo', 1)
            ->select(
                'h.dia_semana',
                'h.hora_inicio',
                'h.hora_fin',
                'h.aula',
                'c.nombre AS curso',
                'u.nombre AS docente',
                'g.nombre AS grado'
            )
            ->orderBy('h.dia_semana')
            ->orderBy('h.hora_inicio')
            ->get()
            ->groupBy('dia_semana');

        return view('horario.alumno', compact('horario', 'dias'));
    }
}
