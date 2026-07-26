<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $docenteId = session('user_id');
        $fecha = $request->fecha ?? now()->format('Y-m-d');

        $vista = ($request->filled('grado_id') && $request->filled('seccion')) ? 'lista' : 'paneles';

        if ($vista === 'paneles') {
            $paneles = $this->construirPanelesAsistencia($docenteId, $fecha);
            return view('asistencias.index', compact('vista', 'paneles', 'fecha'));
        }

        $gradoId = $request->grado_id;
        $seccion = $request->seccion;
        $grado = DB::table('grados')->where('id', $gradoId)->first();

        $cursos = DB::table('asignaciones_docente as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->where('a.docente_id', $docenteId)->where('a.grado_id', $gradoId)->where('a.activo', 1)
            ->select('c.id', 'c.nombre')
            ->distinct()->orderBy('c.nombre')->get();

        $cursoId = $request->curso_id ?: optional($cursos->first())->id;

        $estudiantes = DB::table('estudiantes as e')
            ->join('matriculas as m', function ($join) {
                $join->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1);
            })
            ->where('m.grado_id', $gradoId)->where('m.seccion', $seccion)
            ->select('e.id', DB::raw("CONCAT(e.nombre, ' ', e.apellido) as estudiante"), 'm.grado_id', 'm.seccion')
            ->orderBy('e.apellido')->get();

        $asistencias = DB::table('asistencias')
            ->where('fecha', $fecha)
            ->where('docente_id', $docenteId)
            ->where('curso_id', $cursoId)
            ->get()->keyBy('estudiante_id');

        return view('asistencias.index', compact('vista', 'estudiantes', 'cursos', 'fecha', 'asistencias', 'cursoId', 'gradoId', 'seccion', 'grado'));
    }

    /** Paneles por Grado + Sección para que el docente elija a qué salón le toca tomar asistencia hoy. */
    private function construirPanelesAsistencia(string $docenteId, string $fecha)
    {
        $gradosDocente = DB::table('asignaciones_docente as a')
            ->join('grados as g', 'g.id', '=', 'a.grado_id')
            ->where('a.docente_id', $docenteId)->where('a.activo', 1)->where('a.anio_escolar', date('Y'))
            ->select('g.id', 'g.nombre', 'g.nivel')
            ->distinct()->orderBy('g.nivel')->orderBy('g.nombre')->get();

        $paneles = collect();
        foreach ($gradosDocente as $g) {
            $secciones = DB::table('secciones')->where('grado_id', $g->id)->where('activo', 1)->orderBy('nombre')->get();
            foreach ($secciones as $s) {
                $total = DB::table('matriculas')->where('grado_id', $g->id)->where('seccion', $s->nombre)->where('activo', 1)->count();
                $registrados = DB::table('asistencias')
                    ->where('docente_id', $docenteId)->where('grado_id', $g->id)->where('seccion', $s->nombre)->where('fecha', $fecha)
                    ->distinct('estudiante_id')->count('estudiante_id');

                $paneles->push((object) [
                    'grado_id' => $g->id, 'grado_nombre' => $g->nombre, 'nivel' => $g->nivel, 'seccion' => $s->nombre,
                    'total_estudiantes' => $total, 'registrados_hoy' => $registrados,
                ]);
            }
        }

        return $paneles->groupBy('nivel');
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'estudiante_id' => 'required',
            'estado' => 'required|in:presente,falta,tardanza',
        ]);

        $cursoId = $request->curso_id;
        $gradoId = $request->grado_id;
        $estudianteId = $request->estudiante_id;
        $docenteId = session('user_id');

        $existente = DB::table('asistencias')
            ->where('fecha', $request->fecha)
            ->where('estudiante_id', $estudianteId)
            ->where('docente_id', $docenteId)
            ->first();

        $valores = [
            'curso_id' => $cursoId,
            'grado_id' => $gradoId,
            'seccion' => $request->seccion,
            'estado' => $request->estado,
            'observacion' => $request->observacion,
            'updated_at' => now(),
        ];

        if ($existente) {
            DB::table('asistencias')->where('id', $existente->id)->update($valores);
        } else {
            DB::table('asistencias')->insert(array_merge($valores, [
                'id' => (string) Str::uuid(),
                'fecha' => $request->fecha,
                'estudiante_id' => $estudianteId,
                'docente_id' => $docenteId,
                'created_at' => now(),
            ]));
        }

        return back()->with('success', 'Asistencia registrada.');
    }

    /** Control de asistencia a nivel institucional (promotor): resumen por estudiante, con filtro por grado. */
    public function control(Request $request)
    {
        $anio = now()->year;
        $grados = DB::table('grados')->where('activo', 1)->where('anio_escolar', $anio)->orderBy('nombre')->get();
        $gradoId = $request->grado_id ?: null;

        $query = DB::table('estudiantes as e')
            ->join('matriculas as m', function ($j) use ($anio) {
                $j->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1)->where('m.anio_escolar', $anio);
            })
            ->leftJoin('grados as g', 'g.id', '=', 'm.grado_id')
            ->leftJoin('asistencias as a', 'a.estudiante_id', '=', 'e.id')
            ->where('e.activo', 1)
            ->select(
                'e.id',
                DB::raw("CONCAT(e.apellido, ' ', e.nombre) as nombre"),
                'g.nombre as grado',
                'm.seccion',
                DB::raw('COUNT(a.id) as total_registros'),
                DB::raw('SUM(CASE WHEN a.estado = "presente" THEN 1 ELSE 0 END) as presentes'),
                DB::raw('SUM(CASE WHEN a.estado = "falta" THEN 1 ELSE 0 END) as faltas'),
                DB::raw('SUM(CASE WHEN a.estado = "tardanza" THEN 1 ELSE 0 END) as tardanzas'),
                DB::raw('SUM(CASE WHEN a.estado = "justificado" THEN 1 ELSE 0 END) as justificadas')
            )
            ->groupBy('e.id', 'e.apellido', 'e.nombre', 'g.nombre', 'm.seccion');

        if ($gradoId) {
            $query->where('m.grado_id', $gradoId);
        }

        $resumen = $query->orderByDesc('faltas')->orderBy('nombre')->get()->map(function ($r) {
            $r->porcentaje_asistencia = $r->total_registros > 0
                ? round((($r->presentes + $r->tardanzas) / $r->total_registros) * 100, 1)
                : null;
            return $r;
        });

        $totalFaltas = $resumen->sum('faltas');
        $totalTardanzas = $resumen->sum('tardanzas');
        $enRiesgo = $resumen->filter(fn ($r) => $r->porcentaje_asistencia !== null && $r->porcentaje_asistencia < 85)->count();

        if ($request->get('export') === 'csv') {
            return csv_response(
                'control_asistencia.csv',
                ['Estudiante', 'Grado', 'Sección', 'Presentes', 'Faltas', 'Tardanzas', 'Justificadas', '% Asistencia'],
                $resumen->map(fn($r) => [$r->nombre, $r->grado, $r->seccion, $r->presentes, $r->faltas, $r->tardanzas, $r->justificadas, $r->porcentaje_asistencia])
            );
        }

        return view('asistencias.control', compact('grados', 'gradoId', 'resumen', 'totalFaltas', 'totalTardanzas', 'enRiesgo'));
    }

    public function historial()
    {
        $estudiante = DB::table('estudiantes')->where('usuario_id', session('user_id'))->first();
        if (!$estudiante) {
            return back()->with('error', 'No tienes un perfil de estudiante asociado.');
        }

        $asistencias = DB::table('asistencias')
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('fecha')
            ->get();

        $stats = [
            'presente' => $asistencias->where('estado', 'presente')->count(),
            'falta' => $asistencias->where('estado', 'falta')->count(),
            'tardanza' => $asistencias->where('estado', 'tardanza')->count(),
        ];

        return view('asistencias.historial', compact('asistencias', 'stats'));
    }
}
