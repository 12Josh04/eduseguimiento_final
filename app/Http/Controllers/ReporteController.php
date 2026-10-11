<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function asistencia()
    {
        $rol = session('user_rol');

        $query = DB::table('asistencias as a')->select(DB::raw('DATE_FORMAT(a.fecha, "%Y-%m") as mes'), DB::raw('COUNT(*) as total'));

        // Un docente solo debe ver la asistencia de los alumnos de sus propios grados
        // asignados, no el consolidado de todo el colegio.
        if ($rol === 'docente') {
            $gradoIds = DB::table('asignaciones_docente')
                ->where('docente_id', session('user_id'))->where('activo', 1)->where('anio_escolar', date('Y'))
                ->pluck('grado_id')->unique()->values()->all() ?: ['__ninguno__'];

            $query->whereExists(function ($q) use ($gradoIds) {
                $q->select(DB::raw(1))->from('matriculas as m')
                    ->whereColumn('m.estudiante_id', 'a.estudiante_id')
                    ->where('m.activo', 1)->where('m.anio_escolar', date('Y'))
                    ->whereIn('m.grado_id', $gradoIds);
            });
        }

        $porMes = $query->groupBy('mes')->orderBy('mes')->get();

        return view('reportes.asistencia', compact('porMes'));
    }

    public function academico()
    {
        $promedios = DB::table('notas as n')
            ->join('cursos as c', 'c.id', '=', 'n.curso_id')
            ->select('c.nombre', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0), 1) as promedio'))
            ->groupBy('c.id', 'c.nombre')
            ->get();

        return view('reportes.academico', compact('promedios'));
    }

    public function ordenMerito(\Illuminate\Http\Request $request)
    {
        $anio = now()->year;
        $usuario = session('user_rol');
        $docenteId = session('user_id');

        $grados = DB::table('grados')->where('activo', 1)->where('anio_escolar', $anio)->orderBy('nombre')->get();

        if ($usuario === 'docente') {
            $gradosPermitidos = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', $anio)
                ->pluck('grado_id')->toArray();
            $grados = $grados->whereIn('id', $gradosPermitidos)->values();
        }

        $gradoId = $request->grado_id ?: optional($grados->first())->id;
        $secciones = $gradoId
            ? DB::table('secciones')->where('grado_id', $gradoId)->where('activo', 1)->orderBy('nombre')->get()
            : collect();
        $seccion = $request->seccion ?: optional($secciones->first())->nombre;

        $ranking = collect();
        if ($gradoId && $seccion) {
            $ranking = DB::table('matriculas as m')
                ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
                ->leftJoin('notas as n', 'n.estudiante_id', '=', 'e.id')
                ->where('m.grado_id', $gradoId)
                ->where('m.seccion', $seccion)
                ->where('m.anio_escolar', $anio)
                ->where('m.activo', 1)->where('e.activo', 1)
                ->select(
                    'e.id',
                    DB::raw("CONCAT(e.apellido, ' ', e.nombre) as nombre"),
                    'e.codigo',
                    DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),2) as promedio'),
                    DB::raw('(SELECT COUNT(*) FROM conductas c WHERE c.estudiante_id = e.id AND c.tipo = "negativo") as conductas_neg')
                )
                ->groupBy('e.id', 'e.apellido', 'e.nombre', 'e.codigo')
                ->orderByDesc('promedio')->orderBy('e.apellido')->orderBy('e.nombre')
                ->get()
                ->values()
                ->map(function ($row, $i) {
                    $row->puesto = $i + 1;
                    $row->nl = $row->promedio !== null ? nivel_logro_letra((float) $row->promedio) : null;
                    return $row;
                });
        }

        if ($request->get('export') === 'csv') {
            $gradoNombre = optional($grados->firstWhere('id', $gradoId))->nombre;
            return csv_response(
                'orden_merito_' . str_replace(['°', ' '], ['', '_'], $gradoNombre ?? 'grado') . '_' . $seccion . '.csv',
                ['Puesto', 'Estudiante', 'Código', 'Promedio', 'Nivel de logro', 'Conductas negativas'],
                $ranking->map(fn($r) => [$r->puesto, $r->nombre, $r->codigo, $r->promedio, $r->nl, $r->conductas_neg])
            );
        }

        return view('reportes.orden_merito', compact('grados', 'secciones', 'gradoId', 'seccion', 'ranking'));
    }

    public function comparativoSecciones(\Illuminate\Http\Request $request)
    {
        $anio = now()->year;
        $usuario = session('user_rol');
        $docenteId = session('user_id');

        $grados = DB::table('grados')->where('activo', 1)->where('anio_escolar', $anio)->orderBy('nivel')->orderBy('nombre')->get();

        if ($usuario === 'docente') {
            $gradosPermitidos = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', $anio)
                ->pluck('grado_id')->toArray();
            $grados = $grados->whereIn('id', $gradosPermitidos)->values();
        }

        $gradoId = $request->grado_id ?: optional($grados->first())->id;

        $comparativo = collect();
        if ($gradoId) {
            $comparativo = DB::table('secciones as s')
                ->leftJoin('matriculas as m', function ($j) use ($anio) {
                    $j->on('m.grado_id', '=', 's.grado_id')->on('m.seccion', '=', 's.nombre')
                      ->where('m.activo', 1)->where('m.anio_escolar', $anio);
                })
                ->leftJoin('notas as n', 'n.estudiante_id', '=', 'm.estudiante_id')
                ->where('s.grado_id', $gradoId)
                ->where('s.activo', 1)
                ->select(
                    's.nombre as seccion',
                    DB::raw('COUNT(DISTINCT m.estudiante_id) as total_estudiantes'),
                    DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),2) as promedio'),
                    DB::raw('SUM(CASE WHEN n.nota >= 14 THEN 1 ELSE 0 END) as notas_aprobadas'),
                    DB::raw('COUNT(n.id) as total_notas'),
                    DB::raw('(SELECT COUNT(*) FROM conductas c JOIN matriculas m2 ON m2.estudiante_id = c.estudiante_id
                              WHERE m2.grado_id = s.grado_id AND m2.seccion = s.nombre AND m2.activo = 1
                              AND c.tipo = "negativo") as conductas_neg'),
                    DB::raw('(SELECT ROUND(AVG(CASE WHEN a.estado IN ("presente","tardanza") THEN 100 ELSE 0 END),1)
                              FROM asistencias a JOIN matriculas m3 ON m3.estudiante_id = a.estudiante_id
                              WHERE m3.grado_id = s.grado_id AND m3.seccion = s.nombre AND m3.activo = 1) as pct_asistencia')
                )
                ->groupBy('s.grado_id', 's.nombre')
                ->orderBy('s.nombre')
                ->get()
                ->map(function ($row) {
                    $row->pct_aprobados = $row->total_notas > 0 ? round(($row->notas_aprobadas / $row->total_notas) * 100, 1) : null;
                    $row->nl = $row->promedio !== null ? nivel_logro_letra((float) $row->promedio) : null;
                    return $row;
                });
        }

        return view('reportes.comparativo_secciones', compact('grados', 'gradoId', 'comparativo'));
    }
}
