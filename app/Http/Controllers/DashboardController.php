<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $rol   = session('user_rol');
        $stats = [];
        $conductas_recientes  = collect();
        $promedios_cursos     = [];
        $estudiantes_riesgo   = collect();
        $alertas_asistencia   = collect();
        $nivelStats           = [];

        if (in_array($rol, ['promotor', 'docente', 'psicologa'])) {

            // ── Un docente solo debe ver métricas de sus propios alumnos/cursos, nunca
            // del colegio completo. $gradoIdsDocente = null significa "sin restricción"
            // (promotor/psicóloga, vista institucional). $matchAsignacion() filtra por el
            // PAR grado+curso realmente asignado al docente (no grado y curso por separado,
            // que dejaría "fugar" cursos de otros docentes cuando el mismo profesor dicta
            // distintos cursos en distintos grados, como un tutor de Primaria). ──────────
            $gradoIdsDocente = null;
            $docenteId = session('user_id');
            if ($rol === 'docente') {
                $gradoIdsDocente = DB::table('asignaciones_docente')
                    ->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', date('Y'))
                    ->pluck('grado_id')->unique()->values()->all() ?: ['__ninguno__'];
            }
            $matchAsignacion = function ($query, string $gradoCol, string $cursoCol) use ($docenteId) {
                $query->whereExists(function ($q) use ($docenteId, $gradoCol, $cursoCol) {
                    $q->select(DB::raw(1))->from('asignaciones_docente as ad')
                        ->whereColumn('ad.grado_id', $gradoCol)
                        ->whereColumn('ad.curso_id', $cursoCol)
                        ->where('ad.docente_id', $docenteId)->where('ad.activo', 1)->where('ad.anio_escolar', date('Y'));
                });
            };

            $estudiantesQuery = DB::table('estudiantes as e')->where('e.activo', 1);
            if ($gradoIdsDocente !== null) {
                $estudiantesQuery->whereExists(function ($q) use ($gradoIdsDocente) {
                    $q->select(DB::raw(1))->from('matriculas as m')
                        ->whereColumn('m.estudiante_id', 'e.id')
                        ->where('m.activo', 1)->where('m.anio_escolar', date('Y'))
                        ->whereIn('m.grado_id', $gradoIdsDocente);
                });
            }
            $stats['total_estudiantes'] = $estudiantesQuery->count();

            $conductasQuery = DB::table('conductas as c')
                ->join('estudiantes as e', 'e.id', '=', 'c.estudiante_id');
            if ($gradoIdsDocente !== null) {
                $conductasQuery->whereExists(function ($q) use ($gradoIdsDocente) {
                    $q->select(DB::raw(1))->from('matriculas as m')
                        ->whereColumn('m.estudiante_id', 'e.id')
                        ->where('m.activo', 1)->where('m.anio_escolar', date('Y'))
                        ->whereIn('m.grado_id', $gradoIdsDocente);
                });
            }

            $stats['conductas_semana'] = (clone $conductasQuery)->where('c.fecha_hora', '>=', now()->startOfWeek())->count();

            $conductas_recientes = $conductasQuery
                ->select('c.*', DB::raw("CONCAT(e.nombre,' ',e.apellido) as estudiante_nombre"))
                ->orderByDesc('c.fecha_hora')->limit(5)->get();

            // ── Datos derivados de notas: la Psicóloga no tiene acceso al módulo de notas,
            // por lo que su panel no incluye promedios ni riesgo académico, solo conducta
            // y asistencia. ──────────────────────────────────────────────────────────────
            if (in_array($rol, ['promotor', 'docente'])) {
                $riesgoQuery = DB::table('notas')
                    ->join('estudiantes', 'notas.estudiante_id', '=', 'estudiantes.id')
                    ->leftJoin('matriculas', fn($j) => $j->on('estudiantes.id','=','matriculas.estudiante_id')->where('matriculas.activo',1))
                    ->leftJoin('grados', 'matriculas.grado_id', '=', 'grados.id');
                if ($gradoIdsDocente !== null) {
                    $matchAsignacion($riesgoQuery, 'matriculas.grado_id', 'notas.curso_id');
                }
                $estudiantes_riesgo = $riesgoQuery
                    ->select(
                        'estudiantes.id',
                        DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as nombre"),
                        'grados.nombre as grado',
                        'grados.nivel as nivel',
                        DB::raw('ROUND(SUM(notas.nota*notas.peso)/NULLIF(SUM(notas.peso),0),1) as promedio')
                    )
                    ->groupBy('estudiantes.id','estudiantes.nombre','estudiantes.apellido','grados.nombre','grados.nivel')
                    ->having('promedio', '<', 14)
                    ->orderBy('promedio')->orderBy('estudiantes.apellido')->orderBy('estudiantes.nombre')->limit(10)->get()
                    ->map(function ($e) {
                        $neg = DB::table('conductas')
                            ->where('estudiante_id', $e->id)->where('tipo','negativo')->count();
                        return [
                            'nombre'   => $e->nombre,
                            'grado'    => $e->grado ?? '—',
                            'nivel'    => $e->nivel ?? 'Sin nivel',
                            'promedio' => $e->promedio,
                            'estado'   => $e->promedio >= 11 ? 'riesgo' : 'desaprobado',
                            'conductas'=> $neg,
                        ];
                    });

                // ── Panorama separado por nivel educativo: Primaria se califica en letras
                // (AD/A/B/C) y Secundaria en números (0-20), así que cada nivel necesita su
                // propio desglose en vez de mezclar ambos en una sola estadística. Para un
                // docente, además, todo queda acotado a sus propios grados y cursos. ──────
                foreach (['Primaria', 'Secundaria'] as $nivel) {
                    $totalEstQ = DB::table('matriculas as m')
                        ->join('grados as g', 'g.id', '=', 'm.grado_id')
                        ->where('m.activo', 1)->where('m.anio_escolar', date('Y'))
                        ->where('g.nivel', $nivel);
                    if ($gradoIdsDocente !== null) $totalEstQ->whereIn('g.id', $gradoIdsDocente);
                    $totalEstNivel = $totalEstQ->distinct('m.estudiante_id')->count('m.estudiante_id');

                    $promPorEstQ = DB::table('notas as n')
                        ->join('matriculas as m', function ($j) {
                            $j->on('m.estudiante_id', '=', 'n.estudiante_id')
                              ->where('m.activo', 1)->where('m.anio_escolar', date('Y'));
                        })
                        ->join('grados as g', 'g.id', '=', 'm.grado_id')
                        ->where('g.nivel', $nivel);
                    if ($gradoIdsDocente !== null) $matchAsignacion($promPorEstQ, 'g.id', 'n.curso_id');
                    $promediosPorEstNivel = $promPorEstQ
                        ->select('n.estudiante_id', DB::raw('SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0) as promedio'))
                        ->groupBy('n.estudiante_id')->get();

                    $promCursosQ = DB::table('notas as n')
                        ->join('cursos as c', 'c.id', '=', 'n.curso_id')
                        ->join('matriculas as m', function ($j) {
                            $j->on('m.estudiante_id', '=', 'n.estudiante_id')
                              ->where('m.activo', 1)->where('m.anio_escolar', date('Y'));
                        })
                        ->join('grados as g', 'g.id', '=', 'm.grado_id')
                        ->where('g.nivel', $nivel);
                    if ($gradoIdsDocente !== null) $matchAsignacion($promCursosQ, 'g.id', 'n.curso_id');
                    $promediosCursosNivel = $promCursosQ
                        ->select('c.nombre', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio'))
                        ->groupBy('c.id', 'c.nombre')
                        ->orderBy('c.nombre')
                        ->get();

                    $nivelStats[$nivel] = [
                        'total_estudiantes' => $totalEstNivel,
                        'promedios_cursos'  => $promediosCursosNivel->map(fn($c) => [
                            'nombre'   => $c->nombre,
                            'promedio' => $c->promedio,
                            'nl'       => $nivel === 'Primaria' ? nivel_logro_letra((float) $c->promedio) : null,
                        ])->toArray(),
                    ];

                    if ($nivel === 'Primaria') {
                        // Primaria se reporta con la escala de logro (AD/A/B/C), no con aprobado/desaprobado.
                        $letras = $promediosPorEstNivel->map(fn($e) => nivel_logro_letra((float) $e->promedio));
                        $nivelStats[$nivel]['distribucion_logro'] = [
                            'AD' => $letras->filter(fn($l) => $l === 'AD')->count(),
                            'A'  => $letras->filter(fn($l) => $l === 'A')->count(),
                            'B'  => $letras->filter(fn($l) => $l === 'B')->count(),
                            'C'  => $letras->filter(fn($l) => $l === 'C')->count(),
                        ];
                    } else {
                        $nivelStats[$nivel]['aprobados']    = $promediosPorEstNivel->where('promedio', '>=', 14)->count();
                        $nivelStats[$nivel]['en_riesgo']    = $promediosPorEstNivel->filter(fn($e) => $e->promedio >= 11 && $e->promedio < 14)->count();
                        $nivelStats[$nivel]['desaprobados'] = $promediosPorEstNivel->where('promedio', '<', 11)->count();
                    }
                }
            }

            // ── Alertas de riesgo por inasistencia (para Psicóloga y Promotor) ──
            if (in_array($rol, ['promotor', 'psicologa'])) {
                $alertas_asistencia = DB::table('asistencias as a')
                    ->join('estudiantes as e', 'e.id', '=', 'a.estudiante_id')
                    ->leftJoin('matriculas as m', fn($j) => $j->on('e.id', '=', 'm.estudiante_id')->where('m.activo', 1))
                    ->leftJoin('grados as g', 'g.id', '=', 'm.grado_id')
                    ->select(
                        'e.id',
                        DB::raw("CONCAT(e.nombre,' ',e.apellido) as nombre"),
                        'g.nombre as grado',
                        'g.nivel as nivel',
                        DB::raw('COUNT(*) as total'),
                        DB::raw("SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistio")
                    )
                    ->groupBy('e.id', 'e.nombre', 'e.apellido', 'g.nombre', 'g.nivel')
                    ->havingRaw('COUNT(*) >= 3')
                    ->havingRaw("(SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) / COUNT(*)) < 0.75")
                    ->orderByRaw("(SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) / COUNT(*)) ASC")
                    ->orderBy('e.apellido')->orderBy('e.nombre')
                    ->limit(8)
                    ->get()
                    ->map(fn($r) => [
                        'nombre' => $r->nombre,
                        'grado' => $r->grado ?? '—',
                        'nivel' => $r->nivel ?? 'Sin nivel',
                        'porcentaje' => round(($r->asistio / $r->total) * 100, 1),
                    ]);
            }
        }

        if ($rol === 'estudiante') {
            $est = DB::table('estudiantes')
                ->join('users','estudiantes.usuario_id','=','users.id')
                ->where('users.id', session('user_id'))
                ->select('estudiantes.*')
                ->first();

            if ($est) {
                $promGen = DB::table('notas')->where('estudiante_id',$est->id)
                    ->selectRaw('SUM(nota*peso)/NULLIF(SUM(peso),0) as promedio')->value('promedio');
                $porCurso = DB::table('notas')
                    ->join('cursos','notas.curso_id','=','cursos.id')
                    ->where('notas.estudiante_id',$est->id)
                    ->select('cursos.nombre', DB::raw('ROUND(SUM(nota*peso)/NULLIF(SUM(peso),0),1) as promedio'))
                    ->groupBy('cursos.id','cursos.nombre')->get();

                $stats['promedio_general']  = $promGen !== null ? round($promGen, 1) : null;
                $stats['cursos_aprobados']  = $porCurso->where('promedio','>=',14)->count();
                $stats['cursos_riesgo']     = $porCurso->where('promedio','<',14)->count();
                $promedios_cursos = $porCurso->map(fn($c)=>['nombre'=>$c->nombre,'promedio'=>$c->promedio,'max'=>20])->toArray();
            }
        }

        return view('dashboard.index', compact(
            'stats','conductas_recientes','promedios_cursos','estudiantes_riesgo','alertas_asistencia','nivelStats'
        ));
    }
}
