<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BoletaController extends Controller
{
    public function show($id, \Illuminate\Http\Request $request)
    {
        // El promotor puede autorizar o no, por alumno y año de matrícula, que el propio
        // estudiante vea su Libreta de Notas. Promotor y docente (cuando corresponda)
        // siempre pueden consultarla; la restricción solo aplica a la sesión del alumno.
        if (session('user_rol') === 'estudiante') {
            $habilitada = DB::table('matriculas')
                ->where('estudiante_id', $id)->where('activo', 1)
                ->value('libreta_habilitada');
            if (!$habilitada) {
                return view('boletas.no-habilitada');
            }
        }

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
            ->select('c.id as curso_id', 'c.nombre as curso', 'c.area_curricular', 'n.periodo_id', 'n.nota', 'n.peso', 'n.observacion')
            ->get();

        // Llenado manual de la libreta (Nivel de Logro y conclusión que el docente escribe a mano
        // desde el curso): tiene prioridad sobre el cálculo automático a partir de las evaluaciones.
        $libretaManual = DB::table('libreta_notas')
            ->where('estudiante_id', $id)
            ->get()
            ->groupBy('curso_id');

        // Agrupar por área curricular > curso, con el nivel de logro y la conclusión descriptiva de
        // cada bimestre. El promedio final de cada curso es la media de sus bimestres (no un promedio
        // de todas las evaluaciones mezcladas), para que sea coherente con los bimestres mostrados.
        $areas = [];
        $promediosCursoParaGeneral = [];
        foreach ($notas->groupBy('area_curricular') as $areaNombre => $notasArea) {
            $cursosArea = [];
            foreach ($notasArea->groupBy('curso') as $cursoNombre => $notasCurso) {
                $cursoId = $notasCurso->first()->curso_id;
                $manualCurso = $libretaManual->get($cursoId, collect())->keyBy('periodo_id');

                $bimestres = [];
                $ultimoNl = null;
                foreach ($periodos as $periodo) {
                    $manual = $manualCurso->get($periodo->id);
                    $notasBimestre = $notasCurso->where('periodo_id', $periodo->id);
                    $promedio = $notasBimestre->isNotEmpty() ? promedio_ponderado($notasBimestre) : null;

                    if ($manual && ($manual->nivel_logro || $manual->conclusion)) {
                        $nl = $manual->nivel_logro;
                        $conclusion = $manual->conclusion;
                    } else {
                        $nl = $promedio !== null ? nivel_logro_letra($promedio) : null;
                        $conclusion = $notasBimestre->isNotEmpty() ? $notasBimestre->last()->observacion : null;
                    }

                    if ($nl) $ultimoNl = $nl;
                    $bimestres[$periodo->id] = ['nl' => $nl, 'conclusion' => $conclusion, 'promedio' => $promedio];
                }

                // NL final del curso: el del último bimestre evaluado (situación más reciente),
                // igual que en la libreta impresa; el promedio numérico general sigue siendo el
                // cálculo automático de las evaluaciones registradas.
                $nlFinal = $ultimoNl;
                $promediosBimestre = array_filter(array_column($bimestres, 'promedio'), fn ($v) => $v !== null);
                if ($promediosBimestre) {
                    $promediosCursoParaGeneral[] = round(array_sum($promediosBimestre) / count($promediosBimestre), 1);
                }

                $cursosArea[] = [
                    'nombre' => $cursoNombre,
                    'bimestres' => $bimestres,
                    'nl_final' => $nlFinal,
                ];
            }
            $areas[$areaNombre ?: 'Otras áreas'] = $cursosArea;
        }

        $promedioGeneral = $promediosCursoParaGeneral ? round(array_sum($promediosCursoParaGeneral) / count($promediosCursoParaGeneral), 1) : null;
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

        // Conducta por bimestre: además de las notas académicas, la libreta refleja el
        // comportamiento del estudiante en cada bimestre (parte del reporte del docente/tutor).
        $conductaPorBimestre = [];
        foreach ($periodos as $periodo) {
            $registros = DB::table('conductas')
                ->where('estudiante_id', $id)
                ->whereBetween('fecha_hora', [$periodo->fecha_inicio, $periodo->fecha_fin . ' 23:59:59'])
                ->get();

            $positivas = $registros->where('tipo', 'positivo')->count();
            $negativas = $registros->where('tipo', 'negativo')->count();
            $observaciones = $registros->where('tipo', 'observacion')->count();

            $conductaPorBimestre[] = [
                'periodo' => $periodo->nombre,
                'positivas' => $positivas,
                'negativas' => $negativas,
                'observaciones' => $observaciones,
                'nl' => $negativas === 0 ? 'AD' : ($negativas === 1 ? 'A' : ($negativas <= 3 ? 'B' : 'C')),
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
            'conductaPorBimestre' => $conductaPorBimestre,
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
            ->select('e.id', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),2) as promedio'))
            ->groupBy('e.id', 'e.apellido', 'e.nombre')
            ->havingRaw('SUM(n.peso) is not null')
            ->orderByDesc('promedio')->orderBy('e.apellido')->orderBy('e.nombre')
            ->get();

        $total = $ranking->count();
        $puesto = $ranking->search(fn ($r) => $r->id === $estudianteId);

        if ($puesto === false) return null;

        return ['puesto' => $puesto + 1, 'total' => $total];
    }
}
