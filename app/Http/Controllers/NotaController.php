<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

class NotaController extends Controller
{
    /** Sesiones de clase esperadas en el año (referencia para el % de "clases desarrolladas" completadas). */
    private const META_CLASES_ANIO = 16;

    public function index(Request $request)
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');

        $cursos = DB::table('cursos')->where('activo', 1)->orderBy('nombre')->get();
        $periodos = DB::table('periodos')->where('activo', 1)->orderBy('nombre')->get();
        $grados = DB::table('grados')->where('activo', 1)->orderBy('nivel')->orderBy('nombre')->get();

        $gradoIdsPermitidos = [];
        if ($rol === 'docente') {
            $gradoIdsPermitidos = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', date('Y'))
                ->pluck('grado_id')->toArray();
            $grados = $grados->whereIn('id', $gradoIdsPermitidos)->values();
        }

        // ── Determinar vista: ficha de alumno, cursos de una sección, o paneles ──
        if ($rol === 'estudiante' || $request->filled('estudiante')) {
            $vista = 'lista';
        } elseif ($request->filled('grado_id') && $request->filled('seccion')) {
            $vista = 'cursos';
        } else {
            $vista = 'paneles';
        }

        $notas = collect();
        $paneles = collect();
        $cursosSeccion = collect();
        $estudiantes = collect();

        if ($vista === 'lista') {
            $query = DB::table('notas')
                ->join('estudiantes', 'notas.estudiante_id', '=', 'estudiantes.id')
                ->join('cursos', 'notas.curso_id', '=', 'cursos.id')
                ->join('periodos', 'notas.periodo_id', '=', 'periodos.id')
                ->select('notas.*',
                    DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as estudiante_nombre"),
                    'cursos.nombre as curso_nombre',
                    'periodos.nombre as periodo_nombre');

            if ($request->curso_id)   $query->where('notas.curso_id', $request->curso_id);
            if ($request->periodo_id) $query->where('notas.periodo_id', $request->periodo_id);
            if ($request->tipo)       $query->where('notas.tipo_evaluacion', $request->tipo);
            if ($request->estudiante) $query->where('notas.estudiante_id', $request->estudiante);

            if ($rol === 'estudiante') {
                $est = DB::table('estudiantes')->where('usuario_id', $docenteId)->first();
                if ($est) $query->where('notas.estudiante_id', $est->id);
            }

            // Un docente solo debe ver evaluaciones de los cursos que él mismo dicta al
            // alumno (par grado+curso realmente asignado), aunque esté consultando la
            // ficha de un estudiante que también lleva otros cursos con otros docentes.
            if ($rol === 'docente') {
                $query->whereExists(function ($q) use ($docenteId) {
                    $q->select(DB::raw(1))->from('asignaciones_docente as ad')
                        ->join('matriculas as m', function ($j) {
                            $j->on('m.estudiante_id', '=', 'notas.estudiante_id')
                              ->where('m.activo', 1)->where('m.anio_escolar', date('Y'));
                        })
                        ->whereColumn('ad.grado_id', 'm.grado_id')->whereColumn('ad.curso_id', 'notas.curso_id')
                        ->where('ad.docente_id', $docenteId)->where('ad.activo', 1)->where('ad.anio_escolar', date('Y'));
                });
            }

            $notas = $query->orderByDesc('notas.fecha')->paginate(20);
        } elseif ($vista === 'cursos') {
            $cursosSeccion = $this->construirCursosPorSeccion($request->grado_id, $request->seccion, $rol, $docenteId);
        } else {
            $paneles = $this->construirPanelesNotas($rol, $gradoIdsPermitidos, $docenteId);
        }

        return view('notas.index', compact('notas', 'cursos', 'periodos', 'grados', 'estudiantes', 'vista', 'paneles', 'cursosSeccion', 'gradoIdsPermitidos'));
    }

    /** Agrupa por Nivel → Grado → Sección: total de estudiantes y promedio de notas registradas
     *  (para un docente, solo cuenta las notas del curso que él mismo dicta en ese grado). */
    private function construirPanelesNotas(string $rol, array $gradoIdsPermitidos, ?string $docenteId = null)
    {
        $query = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->leftJoin('matriculas as m', function ($j) {
                $j->on('m.grado_id', '=', 's.grado_id')
                  ->on('m.seccion', '=', 's.nombre')
                  ->where('m.activo', 1)
                  ->where('m.anio_escolar', date('Y'));
            })
            ->leftJoin('notas as n', function ($j) use ($rol, $docenteId) {
                $j->on('n.estudiante_id', '=', 'm.estudiante_id');
                if ($rol === 'docente') {
                    $j->whereExists(function ($q) use ($docenteId) {
                        $q->select(DB::raw(1))->from('asignaciones_docente as ad')
                            ->whereColumn('ad.grado_id', 'g.id')->whereColumn('ad.curso_id', 'n.curso_id')
                            ->where('ad.docente_id', $docenteId)->where('ad.activo', 1)->where('ad.anio_escolar', date('Y'));
                    });
                }
            })
            ->where('s.activo', 1)
            ->where('g.activo', 1)
            ->where('g.anio_escolar', date('Y'));

        if ($rol === 'docente') {
            $query->whereIn('g.id', $gradoIdsPermitidos ?: ['__ninguno__']);
        }

        return $query
            ->select(
                'g.id as grado_id', 'g.nombre as grado_nombre', 'g.nivel',
                's.nombre as seccion',
                DB::raw('COUNT(DISTINCT m.estudiante_id) as total_estudiantes'),
                DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio_seccion'),
                DB::raw('COUNT(n.id) as total_notas')
            )
            ->groupBy('g.id', 'g.nombre', 'g.nivel', 's.nombre')
            ->orderByRaw("FIELD(g.nivel, 'Primaria','Secundaria')")
            ->orderBy('g.nombre')
            ->orderBy('s.nombre')
            ->get()
            ->groupBy('nivel');
    }

    /** Lista los cursos dictados en un grado (con su docente), con conteo de clases desarrolladas y promedio de la sección. */
    private function construirCursosPorSeccion(string $gradoId, string $seccion, string $rol, string $docenteId)
    {
        $query = DB::table('asignaciones_docente as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.grado_id', $gradoId)
            ->where('a.activo', 1)
            ->where('a.anio_escolar', date('Y'));

        if ($rol === 'docente') {
            $query->where('a.docente_id', $docenteId);
        }

        $query->select(
            'c.id as curso_id', 'c.nombre as curso_nombre', 'c.area_curricular',
            'u.nombre as docente_nombre'
        );

        $query->addSelect(['total_clases' => DB::table('clases_desarrolladas as cd')
            ->selectRaw('COUNT(*)')
            ->whereColumn('cd.curso_id', 'c.id')
            ->where('cd.grado_id', $gradoId)
            ->where(function ($q) use ($seccion) {
                $q->where('cd.seccion', $seccion)->orWhereNull('cd.seccion');
            }),
        ]);

        $query->addSelect(['total_notas' => DB::table('notas as n')
            ->join('matriculas as m', 'm.estudiante_id', '=', 'n.estudiante_id')
            ->selectRaw('COUNT(*)')
            ->whereColumn('n.curso_id', 'c.id')
            ->where('m.grado_id', $gradoId)
            ->where('m.seccion', $seccion)
            ->where('m.activo', 1),
        ]);

        $query->addSelect(['promedio' => DB::table('notas as n')
            ->join('matriculas as m', 'm.estudiante_id', '=', 'n.estudiante_id')
            ->selectRaw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1)')
            ->whereColumn('n.curso_id', 'c.id')
            ->where('m.grado_id', $gradoId)
            ->where('m.seccion', $seccion)
            ->where('m.activo', 1),
        ]);

        return $query->orderBy('c.nombre')->get();
    }

    /** Detalle de un curso dentro de un grado/sección: clases desarrolladas + notas de trabajo. */
    public function curso(Request $request, $cursoId)
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');
        $gradoId = $request->query('grado_id');
        $seccion = $request->query('seccion');

        abort_if(!$gradoId || !$seccion, 400, 'Falta indicar grado y sección.');

        $curso = DB::table('cursos')->where('id', $cursoId)->first();
        abort_if(!$curso, 404);

        $grado = DB::table('grados')->where('id', $gradoId)->first();
        abort_if(!$grado, 404);

        $asignacion = DB::table('asignaciones_docente as a')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.curso_id', $cursoId)->where('a.grado_id', $gradoId)->where('a.activo', 1)
            ->select('a.docente_id', 'u.nombre as docente_nombre')
            ->first();

        if ($rol === 'docente' && (!$asignacion || $asignacion->docente_id !== $docenteId)) {
            abort(403, 'No tienes asignado este curso en este grado.');
        }

        $clases = DB::table('clases_desarrolladas as cd')
            ->where('cd.curso_id', $cursoId)
            ->where('cd.grado_id', $gradoId)
            ->where(function ($q) use ($seccion) {
                $q->where('cd.seccion', $seccion)->orWhereNull('cd.seccion');
            })
            ->orderByDesc('cd.fecha')
            ->get();

        $periodos = DB::table('periodos')->where('activo', 1)->orderBy('fecha_inicio')->get();

        // ── Actividades del bimestre (trabajos/exámenes con indicaciones, calificados 1 a 1) ──
        $actividades = DB::table('actividades as act')
            ->join('periodos as p', 'p.id', '=', 'act.periodo_id')
            ->where('act.curso_id', $cursoId)
            ->where('act.grado_id', $gradoId)
            ->where('act.activo', 1)
            ->where(function ($q) use ($seccion) {
                $q->where('act.seccion', $seccion)->orWhereNull('act.seccion');
            })
            ->select('act.*', 'p.nombre as periodo_nombre')
            ->orderByDesc('act.fecha_entrega')
            ->get();

        foreach ($actividades as $act) {
            $conteos = DB::table('entregas_actividad')->where('actividad_id', $act->id)
                ->selectRaw("estado, COUNT(*) as total")->groupBy('estado')->pluck('total', 'estado');
            $act->total_estudiantes = $conteos->sum();
            $act->total_calificados = $conteos->get('calificado', 0);
            $act->total_entregados = $conteos->get('entregado', 0);
            $act->total_pendientes = $conteos->get('pendiente', 0);
        }

        // ── Libro de calificaciones: cada estudiante de la sección x cada bimestre ──
        $gradebook = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->where('m.grado_id', $gradoId)->where('m.seccion', $seccion)->where('m.activo', 1)
            ->select('e.id', DB::raw("CONCAT(e.nombre,' ',e.apellido) as nombre"))
            ->orderBy('e.apellido')
            ->get();

        $notasCurso = DB::table('notas')
            ->where('curso_id', $cursoId)
            ->whereIn('estudiante_id', $gradebook->pluck('id'))
            ->get()
            ->groupBy('estudiante_id');

        // ── Libreta manual: Nivel de Logro y conclusión descriptiva que el docente llena a mano ──
        $libretaExistente = DB::table('libreta_notas')
            ->where('curso_id', $cursoId)
            ->whereIn('estudiante_id', $gradebook->pluck('id'))
            ->get()
            ->groupBy('estudiante_id');

        foreach ($gradebook as $est) {
            $notasEst = $notasCurso->get($est->id, collect());
            $libretaEst = $libretaExistente->get($est->id, collect())->keyBy('periodo_id');
            $est->bimestres = [];
            $est->libreta = [];
            foreach ($periodos as $p) {
                $np = $notasEst->where('periodo_id', $p->id);
                $est->bimestres[$p->id] = $np->count() ? promedio_ponderado($np) : null;

                $manual = $libretaEst->get($p->id);
                $est->libreta[$p->id] = [
                    'nivel_logro' => $manual->nivel_logro ?? null,
                    'conclusion' => $manual->conclusion ?? null,
                    'sugerido' => $est->bimestres[$p->id] !== null ? nivel_logro_letra($est->bimestres[$p->id]) : null,
                ];
            }
            $est->promedio_final = $this->promedioFinalDeBimestres($est->bimestres);
            $est->total_evaluaciones = $notasEst->count();
        }

        $pctClases = min(100, round(($clases->count() / self::META_CLASES_ANIO) * 100));

        return view('notas.curso', compact('curso', 'grado', 'seccion', 'asignacion', 'clases', 'gradebook', 'periodos', 'rol', 'pctClases', 'actividades'));
    }

    /** Guarda, para un estudiante y curso, el Nivel de Logro y la conclusión descriptiva de cada bimestre (llenado manual de la libreta por el docente). */
    public function storeLibreta(Request $request, $cursoId)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'periodos' => 'required|array',
            'periodos.*.nivel_logro' => 'nullable|in:AD,A,B,C',
            'periodos.*.conclusion' => 'nullable|string|max:500',
        ]);

        $rol = session('user_rol');
        $docenteId = session('user_id');

        if ($rol === 'docente') {
            $tieneAsignacion = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('curso_id', $cursoId)->where('activo', 1)->exists();
            abort_if(!$tieneAsignacion, 403, 'No tienes asignado este curso.');
        }

        foreach ($request->periodos as $periodoId => $datos) {
            if (empty($datos['nivel_logro']) && empty($datos['conclusion'])) {
                continue;
            }

            $existente = DB::table('libreta_notas')
                ->where('estudiante_id', $request->estudiante_id)
                ->where('curso_id', $cursoId)
                ->where('periodo_id', $periodoId)
                ->first();

            $valores = [
                'nivel_logro' => $datos['nivel_logro'] ?: null,
                'conclusion' => $datos['conclusion'] ?: null,
                'docente_id' => $docenteId,
                'updated_at' => now(),
            ];

            if ($existente) {
                DB::table('libreta_notas')->where('id', $existente->id)->update($valores);
            } else {
                DB::table('libreta_notas')->insert(array_merge($valores, [
                    'id' => (string) Str::uuid(),
                    'estudiante_id' => $request->estudiante_id,
                    'curso_id' => $cursoId,
                    'periodo_id' => $periodoId,
                    'created_at' => now(),
                ]));
            }
        }

        registrar_auditoria('actualizar', 'libreta_notas', null, "Libreta actualizada para estudiante {$request->estudiante_id}");

        return back()->with('success', 'Libreta actualizada correctamente.');
    }

    /** Panel por curso de un estudiante puntual (dentro de un grado/sección): % de clases desarrolladas, promedio y registro rápido de notas. */
    public function estudianteCursos(Request $request, $estudianteId)
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');
        $gradoId = $request->query('grado_id');
        $seccion = $request->query('seccion');

        $estudiante = DB::table('estudiantes')->where('id', $estudianteId)->first();
        abort_if(!$estudiante, 404);

        $grado = $gradoId ? DB::table('grados')->where('id', $gradoId)->first() : null;

        $query = DB::table('asignaciones_docente as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.activo', 1)->where('a.anio_escolar', date('Y'));

        if ($gradoId) {
            $query->where('a.grado_id', $gradoId);
        }
        if ($rol === 'docente') {
            $query->where('a.docente_id', $docenteId);
        }

        $cursosPanel = $query
            ->select('c.id as curso_id', 'c.nombre as curso_nombre', 'u.nombre as docente_nombre', 'a.grado_id')
            ->orderBy('c.nombre')
            ->get();

        abort_if($rol === 'docente' && $cursosPanel->isEmpty(), 403, 'No tienes cursos asignados para este estudiante.');

        $periodos = DB::table('periodos')->where('activo', 1)->orderBy('fecha_inicio')->get();

        $nivelesPorGrado = DB::table('grados')
            ->whereIn('id', $cursosPanel->pluck('grado_id')->unique())
            ->pluck('nivel', 'id');

        $notasEstudiante = DB::table('notas')
            ->where('estudiante_id', $estudianteId)
            ->whereIn('curso_id', $cursosPanel->pluck('curso_id'))
            ->get()
            ->groupBy('curso_id');

        foreach ($cursosPanel as $c) {
            $c->nivel = $nivelesPorGrado->get($c->grado_id);
            $c->es_primaria = es_nivel_primaria($c->nivel);
            $notasC = $notasEstudiante->get($c->curso_id, collect());
            $c->bimestres = [];
            foreach ($periodos as $p) {
                $np = $notasC->where('periodo_id', $p->id);
                $c->bimestres[$p->id] = $np->count() ? promedio_ponderado($np) : null;
            }
            $c->promedio = $this->promedioFinalDeBimestres($c->bimestres);

            // Detalle de todas las evaluaciones individuales del curso, para ver cómo se compone el bimestre.
            $c->evaluaciones = $notasC->map(function ($n) use ($periodos) {
                $n->periodo_nombre = optional($periodos->firstWhere('id', $n->periodo_id))->nombre;
                return $n;
            })->sortByDesc('fecha')->values();

            $totalClases = DB::table('clases_desarrolladas')
                ->where('curso_id', $c->curso_id)->where('grado_id', $c->grado_id)
                ->where(function ($q) use ($seccion) {
                    $q->where('seccion', $seccion)->orWhereNull('seccion');
                })
                ->count();
            $c->total_clases = $totalClases;
            $c->pct_clases = min(100, round(($totalClases / self::META_CLASES_ANIO) * 100));
        }

        return view('notas.estudiante-cursos', compact('estudiante', 'grado', 'seccion', 'cursosPanel', 'periodos', 'rol'));
    }

    /** Promedio final = media simple de los promedios de bimestre que sí tienen notas (no un promedio de todas las evaluaciones mezcladas). */
    private function promedioFinalDeBimestres(array $bimestres): ?float
    {
        $conDatos = array_filter($bimestres, fn ($v) => $v !== null);
        if (!$conDatos) return null;
        return round(array_sum($conDatos) / count($conDatos), 1);
    }

    public function storeClase(Request $request, $cursoId)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'seccion'  => 'required|string|max:10',
            'fecha'    => 'required|date',
            'tema'     => 'required|string|max:160',
            'descripcion' => 'nullable|string',
        ]);

        $rol = session('user_rol');
        $docenteId = session('user_id');

        if ($rol === 'docente') {
            $tieneAsignacion = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('curso_id', $cursoId)
                ->where('grado_id', $request->grado_id)->where('activo', 1)->exists();
            abort_if(!$tieneAsignacion, 403, 'No tienes asignado este curso en este grado.');
        }

        DB::table('clases_desarrolladas')->insert([
            'id'          => Str::uuid(),
            'curso_id'    => $cursoId,
            'grado_id'    => $request->grado_id,
            'seccion'     => $request->seccion_especifica ? $request->seccion : null,
            'docente_id'  => $docenteId,
            'fecha'       => $request->fecha,
            'tema'        => $request->tema,
            'descripcion' => $request->descripcion,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        registrar_auditoria('crear', 'clases_desarrolladas', null, $request->tema);

        return back()->with('success', 'Clase registrada correctamente.');
    }

    public function destroyClase($id)
    {
        $clase = DB::table('clases_desarrolladas')->where('id', $id)->first();
        DB::table('clases_desarrolladas')->where('id', $id)->delete();
        registrar_auditoria('eliminar', 'clases_desarrolladas', $clase?->tema, null);
        return back()->with('success', 'Clase eliminada.');
    }

    public function store(Request $request)
    {
        $matricula = DB::table('matriculas')->where('estudiante_id', $request->estudiante_id)->where('activo', 1)->latest('created_at')->first();
        $grado = $matricula?->grado_id ? DB::table('grados')->where('id', $matricula->grado_id)->first() : null;
        $esPrimaria = es_nivel_primaria($grado->nivel ?? null);

        $request->validate([
            'estudiante_id'     => 'required',
            'curso_id'          => 'required',
            'periodo_id'        => 'required',
            'tipo_evaluacion'   => 'required|in:examen,tarea,practica',
            'nombre_evaluacion' => 'required',
            'nota'              => $esPrimaria ? 'nullable' : 'required|numeric|min:0|max:20',
            'nota_letra'        => $esPrimaria ? 'required|in:AD,A,B,C' : 'nullable|in:AD,A,B,C',
            'nota_maxima'       => 'nullable|numeric|min:1',
            'peso'              => 'nullable|numeric|min:0.1|max:10',
            'fecha'             => 'required|date',
        ]);

        $notaLetra = $esPrimaria ? $request->nota_letra : null;
        $notaNumerica = $esPrimaria ? nota_desde_letra($notaLetra) : $request->nota;

        DB::table('notas')->insert([
            'id'                => Str::uuid(),
            'estudiante_id'     => $request->estudiante_id,
            'curso_id'          => $request->curso_id,
            'docente_id'        => session('user_id'),
            'grado_id'          => $matricula->grado_id ?? null,
            'seccion'           => $matricula->seccion ?? null,
            'periodo_id'        => $request->periodo_id,
            'tipo_evaluacion'   => $request->tipo_evaluacion,
            'nombre_evaluacion' => $request->nombre_evaluacion,
            'nota'              => $notaNumerica,
            'nota_letra'        => $notaLetra,
            'nota_maxima'       => $request->filled('nota_maxima') ? $request->nota_maxima : 20,
            'peso'              => $request->filled('peso') ? $request->peso : peso_por_defecto($request->tipo_evaluacion),
            'fecha'             => $request->fecha,
            'creado_en'         => now(),
        ]);

        registrar_auditoria('crear', 'notas', null, "{$request->nombre_evaluacion}: " . ($notaLetra ?? "{$notaNumerica}/{$request->nota_maxima}"));

        return back()->with('success','Nota registrada correctamente.');
    }

    /** AJAX: devuelve los estudiantes matriculados en un grado */
    public function estudiantesPorGrado(Request $request)
    {
        $gradoId = $request->input('grado_id');
        $estudiantes = DB::table('matriculas')
            ->join('estudiantes','matriculas.estudiante_id','=','estudiantes.id')
            ->where('matriculas.grado_id', $gradoId)
            ->where('matriculas.activo', 1)
            ->where('estudiantes.activo', 1)
            ->select('estudiantes.id', DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as nombre"))
            ->orderBy('estudiantes.apellido')
            ->get();

        return response()->json($estudiantes);
    }

    public function destroy($id)
    {
        $nota = DB::table('notas')->where('id', $id)->first();
        DB::table('notas')->where('id',$id)->delete();
        registrar_auditoria('eliminar', 'notas', $nota?->nombre_evaluacion, null);
        return back()->with('success','Nota eliminada.');
    }

    public function promedios()
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');

        // ── Estudiante: solo su propio resumen (antes esta página mostraba a todos
        // los estudiantes del colegio a cualquiera que entrara, sin restricción). ──
        if ($rol === 'estudiante') {
            $estudiante = DB::table('estudiantes')->where('usuario_id', $docenteId)->first();
            abort_if(!$estudiante, 404);

            $grado = DB::table('matriculas as m')
                ->join('grados as g', 'g.id', '=', 'm.grado_id')
                ->where('m.estudiante_id', $estudiante->id)->where('m.activo', 1)
                ->select('g.nombre', 'g.nivel')->first();

            $cursos = DB::table('notas as n')
                ->join('cursos as c', 'c.id', '=', 'n.curso_id')
                ->where('n.estudiante_id', $estudiante->id)
                ->select('c.nombre', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio'))
                ->groupBy('c.id', 'c.nombre')->orderBy('c.nombre')->get();

            $promedioGeneral = $cursos->count() ? round($cursos->avg('promedio'), 1) : null;

            return view('notas.promedios-alumno', compact('estudiante', 'grado', 'cursos', 'promedioGeneral'));
        }

        // ── Promotor / Docente: panorama institucional (o solo sus grados asignados,
        // si es docente), agrupado por Grado y Sección, con los estudiantes de cada
        // salón en orden alfabético. ─────────────────────────────────────────────
        $gradoIdsPermitidos = null;
        if ($rol === 'docente') {
            $gradoIdsPermitidos = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', date('Y'))
                ->pluck('grado_id')->unique()->values()->all();
        }

        $promediosCursosPorNivel = [];
        foreach (['Primaria', 'Secundaria'] as $nivel) {
            $q = DB::table('notas as n')
                ->join('cursos as c', 'c.id', '=', 'n.curso_id')
                ->join('matriculas as m', function ($j) {
                    $j->on('m.estudiante_id', '=', 'n.estudiante_id')->where('m.activo', 1)->where('m.anio_escolar', date('Y'));
                })
                ->join('grados as g', 'g.id', '=', 'm.grado_id')
                ->where('g.nivel', $nivel);
            if ($gradoIdsPermitidos !== null) $q->whereIn('g.id', $gradoIdsPermitidos ?: ['__ninguno__']);

            $promediosCursosPorNivel[$nivel] = $q
                ->select('c.nombre', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio'))
                ->groupBy('c.id', 'c.nombre')->orderBy('c.nombre')->get();
        }

        $matriculas = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('grados as g', 'g.id', '=', 'm.grado_id')
            ->where('m.activo', 1)->where('m.anio_escolar', date('Y'))->where('e.activo', 1)
            ->when($gradoIdsPermitidos !== null, fn($q) => $q->whereIn('g.id', $gradoIdsPermitidos ?: ['__ninguno__']))
            ->select(
                'e.id as estudiante_id', DB::raw("CONCAT(e.nombre,' ',e.apellido) as estudiante_nombre"),
                'g.id as grado_id', 'g.nombre as grado_nombre', 'g.nivel', 'm.seccion'
            )
            ->orderBy('e.apellido')->orderBy('e.nombre')
            ->get();

        $promediosPorEst = DB::table('notas')
            ->select('estudiante_id', DB::raw('ROUND(SUM(nota*peso)/NULLIF(SUM(peso),0),1) as promedio'))
            ->groupBy('estudiante_id')->get()->keyBy('estudiante_id');

        $porCursoEst = DB::table('notas as n')
            ->join('cursos as c', 'c.id', '=', 'n.curso_id')
            ->select('n.estudiante_id', 'c.nombre as curso', DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio'))
            ->groupBy('n.estudiante_id', 'c.nombre')->get()->groupBy('estudiante_id');

        foreach ($matriculas as $m) {
            $m->promedio = optional($promediosPorEst->get($m->estudiante_id))->promedio;
            $m->cursos = $porCursoEst->get($m->estudiante_id, collect())->pluck('promedio', 'curso');
        }

        $cursoNombres = DB::table('cursos')->where('activo', 1)->orderBy('nombre')->pluck('nombre');

        // Grupos: "Nivel|Grado|Sección" — el orden alfabético del texto ya deja Primaria
        // antes que Secundaria, y los estudiantes dentro de cada grupo ya vienen ordenados
        // por apellido desde la consulta.
        $secciones = $matriculas
            ->groupBy(fn($m) => $m->nivel . '||' . $m->grado_nombre . '||' . $m->seccion)
            ->sortKeys();

        return view('notas.promedios', compact('promediosCursosPorNivel', 'secciones', 'cursoNombres'));
    }
}
