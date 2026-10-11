<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
    public function index()
    {
        $anio = date('Y');

        $docentes = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.rol_id')
            ->where('r.nombre', 'docente')
            ->where('u.activo', 1)
            ->select('u.id', 'u.nombre', 'u.email')
            ->orderBy('u.nombre')
            ->get();

        $paneles = ['Primaria' => [], 'Secundaria' => []];

        foreach ($docentes as $d) {
            $asignaciones = DB::table('asignaciones_docente as a')
                ->join('cursos as c', 'c.id', '=', 'a.curso_id')
                ->join('grados as g', 'g.id', '=', 'a.grado_id')
                ->where('a.docente_id', $d->id)->where('a.activo', 1)->where('a.anio_escolar', $anio)
                ->select('c.nombre as curso', 'g.nombre as grado', 'g.nivel')
                ->orderBy('g.nivel')->orderBy('g.nombre')
                ->get();

            if ($asignaciones->isEmpty()) {
                continue;
            }

            $cursosUnicos = $asignaciones->pluck('curso')->unique()->values();
            $gradosUnicos = $asignaciones->pluck('grado')->unique()->values();
            $nivelPrincipal = $asignaciones->first()->nivel;

            $totalEstudiantes = DB::table('asignaciones_docente as a')
                ->join('matriculas as m', 'm.grado_id', '=', 'a.grado_id')
                ->where('a.docente_id', $d->id)->where('a.activo', 1)->where('a.anio_escolar', $anio)
                ->where('m.activo', 1)->where('m.anio_escolar', $anio)
                ->distinct('m.estudiante_id')
                ->count('m.estudiante_id');

            $d->cursos = $cursosUnicos;
            $d->grados = $gradosUnicos;
            $d->total_cursos = $cursosUnicos->count();
            $d->total_grados = $gradosUnicos->count();
            $d->total_estudiantes = $totalEstudiantes;

            $paneles[$nivelPrincipal][] = $d;
        }

        return view('docentes.index', compact('paneles'));
    }

    public function show($id)
    {
        $anio = date('Y');

        $docente = DB::table('users')->where('id', $id)->first();
        abort_if(!$docente, 404);

        $asignaciones = DB::table('asignaciones_docente as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('grados as g', 'g.id', '=', 'a.grado_id')
            ->leftJoin('horarios as h', 'h.asignacion_id', '=', 'a.id')
            ->where('a.docente_id', $id)->where('a.activo', 1)->where('a.anio_escolar', $anio)
            ->select(
                'a.id as asignacion_id', 'c.nombre as curso', 'g.id as grado_id', 'g.nombre as grado', 'g.nivel',
                'h.dia_semana', 'h.hora_inicio', 'h.hora_fin', 'h.aula',
                DB::raw('(SELECT COUNT(DISTINCT m.estudiante_id) FROM matriculas m WHERE m.grado_id = a.grado_id AND m.activo = 1 AND m.anio_escolar = ' . $anio . ') as total_estudiantes'),
                DB::raw('(SELECT ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) FROM notas n JOIN matriculas m2 ON m2.estudiante_id = n.estudiante_id
                          WHERE n.curso_id = a.curso_id AND m2.grado_id = a.grado_id AND m2.activo = 1) as promedio')
            )
            ->orderByRaw("FIELD(g.nivel,'Primaria','Secundaria')")
            ->orderBy('g.nombre')
            ->get();

        return view('docentes.show', compact('docente', 'asignaciones'));
    }
}
