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

        if (in_array($rol, ['promotor', 'docente', 'psicologa'])) {

            $stats['total_estudiantes'] = DB::table('estudiantes')->where('activo', 1)->count();

            $promediosPorEst = DB::table('notas')
                ->select('estudiante_id', DB::raw('AVG(nota) as promedio'))
                ->groupBy('estudiante_id')->get();

            $stats['aprobados']    = $promediosPorEst->where('promedio', '>=', 14)->count();
            $stats['en_riesgo']    = $promediosPorEst->filter(fn($e) => $e->promedio >= 11 && $e->promedio < 14)->count();
            $stats['desaprobados'] = $promediosPorEst->where('promedio', '<', 11)->count();
            $stats['conductas_semana'] = DB::table('conductas')
                ->where('fecha_hora', '>=', now()->startOfWeek())->count();

            $conductas_recientes = DB::table('conductas')
                ->join('estudiantes', 'conductas.estudiante_id', '=', 'estudiantes.id')
                ->select('conductas.*',
                    DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as estudiante_nombre"))
                ->orderByDesc('conductas.fecha_hora')->limit(5)->get();

            $promedios_cursos = DB::table('notas')
                ->join('cursos', 'notas.curso_id', '=', 'cursos.id')
                ->select('cursos.nombre', DB::raw('ROUND(AVG(nota),1) as promedio'))
                ->groupBy('cursos.id', 'cursos.nombre')->get()
                ->map(fn($r) => ['nombre' => $r->nombre, 'promedio' => $r->promedio, 'max' => 20])
                ->toArray();

            $estudiantes_riesgo = DB::table('notas')
                ->join('estudiantes', 'notas.estudiante_id', '=', 'estudiantes.id')
                ->leftJoin('matriculas', fn($j) => $j->on('estudiantes.id','=','matriculas.estudiante_id')->where('matriculas.activo',1))
                ->leftJoin('grados', 'matriculas.grado_id', '=', 'grados.id')
                ->select(
                    'estudiantes.id',
                    DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as nombre"),
                    'grados.nombre as grado',
                    DB::raw('ROUND(AVG(notas.nota),1) as promedio')
                )
                ->groupBy('estudiantes.id','estudiantes.nombre','estudiantes.apellido','grados.nombre')
                ->having('promedio', '<', 14)
                ->orderBy('promedio')->limit(6)->get()
                ->map(function ($e) {
                    $neg = DB::table('conductas')
                        ->where('estudiante_id', $e->id)->where('tipo','negativo')->count();
                    return [
                        'nombre'   => $e->nombre,
                        'grado'    => $e->grado ?? '—',
                        'promedio' => $e->promedio,
                        'estado'   => $e->promedio >= 11 ? 'riesgo' : 'desaprobado',
                        'conductas'=> $neg,
                    ];
                });

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
                        DB::raw('COUNT(*) as total'),
                        DB::raw("SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistio")
                    )
                    ->groupBy('e.id', 'e.nombre', 'e.apellido', 'g.nombre')
                    ->havingRaw('COUNT(*) >= 3')
                    ->havingRaw("(SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) / COUNT(*)) < 0.75")
                    ->orderByRaw("(SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) / COUNT(*)) ASC")
                    ->limit(6)
                    ->get()
                    ->map(fn($r) => [
                        'nombre' => $r->nombre,
                        'grado' => $r->grado ?? '—',
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
                $promGen = DB::table('notas')->where('estudiante_id',$est->id)->avg('nota');
                $porCurso = DB::table('notas')
                    ->join('cursos','notas.curso_id','=','cursos.id')
                    ->where('notas.estudiante_id',$est->id)
                    ->select('cursos.nombre', DB::raw('ROUND(AVG(nota),1) as promedio'))
                    ->groupBy('cursos.id','cursos.nombre')->get();

                $stats['promedio_general']  = round($promGen, 1);
                $stats['cursos_aprobados']  = $porCurso->where('promedio','>=',14)->count();
                $stats['cursos_riesgo']     = $porCurso->where('promedio','<',14)->count();
                $promedios_cursos = $porCurso->map(fn($c)=>['nombre'=>$c->nombre,'promedio'=>$c->promedio,'max'=>20])->toArray();
            }
        }

        return view('dashboard.index', compact(
            'stats','conductas_recientes','promedios_cursos','estudiantes_riesgo','alertas_asistencia'
        ));
    }
}
