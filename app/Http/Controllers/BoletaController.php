<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BoletaController extends Controller
{
    public function show($id, \Illuminate\Http\Request $request)
    {
        $data = $this->construirLibreta($id);

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('boletas.pdf', $data)->setPaper('a4', 'portrait');
            $nombreArchivo = 'Libreta-' . str_replace(' ', '_', $data['estudiante']->apellido . '-' . $data['estudiante']->nombre) . '.pdf';
            return $pdf->download($nombreArchivo);
        }

        return view('boletas.show', $data);
    }

    private function construirLibreta(string $id): array
    {
        $estudiante = DB::table('estudiantes as e')
            ->leftJoin('matriculas as m', function ($join) {
                $join->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1);
            })
            ->leftJoin('grados as g', 'g.id', '=', 'm.grado_id')
            ->select('e.*', 'g.id as grado_id', 'g.nombre as grado', 'm.anio_escolar as anio_escolar', 'm.seccion as seccion')
            ->where('e.id', $id)
            ->first();

        abort_if(!$estudiante, 404);

        $periodos = DB::table('periodos')
            ->where('anio_escolar', $estudiante->anio_escolar ?? now()->year)
            ->orderBy('fecha_inicio')
            ->get();

        $notas = DB::table('notas as n')
            ->join('cursos as c', 'c.id', '=', 'n.curso_id')
            ->where('n.estudiante_id', $id)
            ->select('c.nombre as curso', 'c.area_curricular', 'n.periodo_id', 'n.nota', 'n.observacion')
            ->get();

        // Agrupar por área curricular > curso, con el promedio y la conclusión descriptiva de cada bimestre
        $areas = [];
        foreach ($notas->groupBy('area_curricular') as $areaNombre => $notasArea) {
            $cursosArea = [];
            foreach ($notasArea->groupBy('curso') as $cursoNombre => $notasCurso) {
                $bimestres = [];
                foreach ($periodos as $periodo) {
                    $notasBimestre = $notasCurso->where('periodo_id', $periodo->id);
                    if ($notasBimestre->isEmpty()) {
                        $bimestres[$periodo->id] = ['nl' => null, 'conclusion' => null];
                        continue;
                    }
                    $promedio = round($notasBimestre->avg('nota'), 1);
                    $nl = nivel_logro_letra($promedio);
                    $conclusion = $notasBimestre->last()->observacion;
                    $bimestres[$periodo->id] = ['nl' => $nl, 'conclusion' => $conclusion, 'promedio' => $promedio];
                }

                $todasLasNotas = $notasCurso->pluck('nota');
                $nlFinal = $todasLasNotas->isNotEmpty() ? nivel_logro_letra(round($todasLasNotas->avg(), 1)) : null;

                $cursosArea[] = [
                    'nombre' => $cursoNombre,
                    'bimestres' => $bimestres,
                    'nl_final' => $nlFinal,
                ];
            }
            $areas[$areaNombre ?: 'Otras áreas'] = $cursosArea;
        }

        $promedioGeneral = $notas->count() ? round($notas->avg('nota'), 1) : null;
        $nlGeneral = nivel_logro_letra($promedioGeneral);

        // Asistencia por bimestre
        $asistenciaPorBimestre = [];
        foreach ($periodos as $periodo) {
            $registros = DB::table('asistencias')
                ->where('estudiante_id', $id)
                ->whereBetween('fecha', [$periodo->fecha_inicio, $periodo->fecha_fin])
                ->get();

            $asistenciaPorBimestre[] = [
                'periodo' => $periodo->nombre,
                'inasistencias_justificadas' => $registros->where('estado', 'justificado')->count(),
                'inasistencias_injustificadas' => $registros->where('estado', 'falta')->count(),
                'tardanzas_justificadas' => 0,
                'tardanzas_injustificadas' => $registros->where('estado', 'tardanza')->count(),
            ];
        }

        // Orden de mérito dentro del grado y sección
        $ordenMerito = $this->ordenMeritoDe($id, $estudiante->grado_id ?? null, $estudiante->seccion ?? null, $estudiante->anio_escolar ?? now()->year);

        $config = DB::table('configuraciones_institucionales')->pluck('valor', 'clave');
        $docente = DB::table('asignaciones_docente as a')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.grado_id', $estudiante->grado_id ?? null)
            ->where('a.activo', 1)
            ->value('u.nombre');

        return [
            'estudiante' => $estudiante,
            'areas' => $areas,
            'periodos' => $periodos,
            'promedioGeneral' => $promedioGeneral,
            'nlGeneral' => $nlGeneral,
            'asistenciaPorBimestre' => $asistenciaPorBimestre,
            'ordenMerito' => $ordenMerito,
            'colegio' => $config['nombre_colegio'] ?? 'Colegio José Tola Pasquel',
            'dre' => $config['dre'] ?? 'DRE ANCASH',
            'ugel' => $config['ugel'] ?? 'UGEL Huaraz',
            'codigoModular' => $config['codigo_modular'] ?? '',
            'nivelEducativo' => $config['nivel_educativo'] ?? 'Secundaria',
            'anioEscolar' => $estudiante->anio_escolar ?? now()->year,
            'docenteTutor' => $docente,
        ];
    }

    /** Calcula el puesto de un estudiante dentro de su grado y sección, según su promedio general. */
    public function ordenMeritoDe(string $estudianteId, ?string $gradoId, ?string $seccion, $anioEscolar): ?array
    {
        if (!$gradoId) return null;

        $ranking = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->leftJoin('notas as n', 'n.estudiante_id', '=', 'e.id')
            ->where('m.grado_id', $gradoId)
            ->where('m.seccion', $seccion)
            ->where('m.anio_escolar', $anioEscolar)
            ->where('m.activo', 1)
            ->where('e.activo', 1)
            ->select('e.id', DB::raw('ROUND(AVG(n.nota),2) as promedio'))
            ->groupBy('e.id')
            ->havingRaw('AVG(n.nota) is not null')
            ->orderByDesc('promedio')
            ->get();

        $total = $ranking->count();
        $puesto = $ranking->search(fn ($r) => $r->id === $estudianteId);

        if ($puesto === false) return null;

        return ['puesto' => $puesto + 1, 'total' => $total];
    }
}
