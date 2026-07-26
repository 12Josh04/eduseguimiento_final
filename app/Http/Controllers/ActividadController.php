<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActividadController extends Controller
{
    /** Crea una actividad (trabajo/tarea/examen con indicaciones) para un curso+grado y genera la entrega pendiente de cada estudiante. */
    public function store(Request $request, $cursoId)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'periodo_id' => 'required|exists:periodos,id',
            'titulo' => 'required|string|max:160',
            'instrucciones' => 'required|string',
            'tipo_evaluacion' => 'required|in:examen,tarea,practica',
            'fecha_entrega' => 'nullable|date',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:8192',
        ]);

        $rol = session('user_rol');
        $docenteId = session('user_id');

        if ($rol === 'docente') {
            $tieneAsignacion = DB::table('asignaciones_docente')
                ->where('docente_id', $docenteId)->where('curso_id', $cursoId)
                ->where('grado_id', $request->grado_id)->where('activo', 1)->exists();
            abort_if(!$tieneAsignacion, 403, 'No tienes asignado este curso en este grado.');
        }

        $seccion = ($request->filled('seccion_especifica') && $request->filled('seccion')) ? $request->seccion : null;

        $archivoPath = $request->hasFile('archivo') ? $request->file('archivo')->store('actividades', 'local') : null;

        $actividadId = (string) Str::uuid();
        DB::table('actividades')->insert([
            'id' => $actividadId,
            'curso_id' => $cursoId,
            'grado_id' => $request->grado_id,
            'seccion' => $seccion,
            'periodo_id' => $request->periodo_id,
            'docente_id' => $docenteId,
            'titulo' => $request->titulo,
            'instrucciones' => $request->instrucciones,
            'tipo_evaluacion' => $request->tipo_evaluacion,
            'fecha_entrega' => $request->fecha_entrega,
            'archivo_path' => $archivoPath,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estudiantesQuery = DB::table('matriculas')->where('grado_id', $request->grado_id)->where('activo', 1);
        if ($seccion) {
            $estudiantesQuery->where('seccion', $seccion);
        }
        $estudiantes = $estudiantesQuery->pluck('estudiante_id');

        $filas = $estudiantes->map(fn ($estId) => [
            'id' => (string) Str::uuid(),
            'actividad_id' => $actividadId,
            'estudiante_id' => $estId,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($filas) {
            DB::table('entregas_actividad')->insert($filas);
        }

        registrar_auditoria('crear', 'actividades', null, $request->titulo);

        return back()->with('success', "Actividad \"{$request->titulo}\" creada para {$estudiantes->count()} estudiante(s).");
    }

    /** Vista de calificación: instrucciones + tabla de entregas de todos los estudiantes. */
    public function show($id)
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');

        $actividad = DB::table('actividades as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('grados as g', 'g.id', '=', 'a.grado_id')
            ->join('periodos as p', 'p.id', '=', 'a.periodo_id')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.id', $id)
            ->select('a.*', 'c.nombre as curso_nombre', 'g.nombre as grado_nombre', 'p.nombre as periodo_nombre', 'u.nombre as docente_nombre')
            ->first();

        abort_if(!$actividad, 404);

        if ($rol === 'docente' && $actividad->docente_id !== $docenteId) {
            abort(403, 'No tienes permiso para ver esta actividad.');
        }

        $entregas = DB::table('entregas_actividad as e')
            ->join('estudiantes as est', 'est.id', '=', 'e.estudiante_id')
            ->where('e.actividad_id', $id)
            ->select('e.*', DB::raw("CONCAT(est.nombre,' ',est.apellido) as estudiante_nombre"))
            ->orderBy('est.apellido')
            ->get();

        return view('notas.actividad', compact('actividad', 'entregas', 'rol'));
    }

    /** Registra la nota de un estudiante para la actividad y sincroniza la nota en el libro de calificaciones. */
    public function calificar(Request $request, $actividadId, $estudianteId)
    {
        $request->validate([
            'nota' => 'required|numeric|min:0|max:20',
            'observacion_docente' => 'nullable|string|max:500',
        ]);

        $rol = session('user_rol');
        $docenteId = session('user_id');

        $actividad = DB::table('actividades')->where('id', $actividadId)->first();
        abort_if(!$actividad, 404);

        if ($rol === 'docente' && $actividad->docente_id !== $docenteId) {
            abort(403, 'No tienes permiso para calificar esta actividad.');
        }

        $entrega = DB::table('entregas_actividad')->where('actividad_id', $actividadId)->where('estudiante_id', $estudianteId)->first();
        abort_if(!$entrega, 404);

        DB::table('entregas_actividad')->where('id', $entrega->id)->update([
            'nota' => $request->nota,
            'observacion_docente' => $request->observacion_docente,
            'estado' => 'calificado',
            'calificado_en' => now(),
            'updated_at' => now(),
        ]);

        $notaExistente = DB::table('notas')->where('actividad_id', $actividadId)->where('estudiante_id', $estudianteId)->first();
        $valoresNota = [
            'curso_id' => $actividad->curso_id,
            'actividad_id' => $actividadId,
            'docente_id' => $actividad->docente_id,
            'periodo_id' => $actividad->periodo_id,
            'grado_id' => $actividad->grado_id,
            'tipo_evaluacion' => $actividad->tipo_evaluacion,
            'nombre_evaluacion' => $actividad->titulo,
            'nota' => $request->nota,
            'nota_maxima' => 20,
            'fecha' => now()->format('Y-m-d'),
        ];

        if ($notaExistente) {
            DB::table('notas')->where('id', $notaExistente->id)->update($valoresNota);
        } else {
            DB::table('notas')->insert(array_merge($valoresNota, [
                'id' => (string) Str::uuid(),
                'estudiante_id' => $estudianteId,
                'creado_en' => now(),
            ]));
        }

        registrar_auditoria('calificar', 'actividades', null, "{$actividad->titulo}: {$request->nota}/20");

        return back()->with('success', 'Nota registrada. Se sumó al bimestre correspondiente.');
    }

    public function destroy($id)
    {
        $rol = session('user_rol');
        $docenteId = session('user_id');

        $actividad = DB::table('actividades')->where('id', $id)->first();
        abort_if(!$actividad, 404);

        if ($rol === 'docente' && $actividad->docente_id !== $docenteId) {
            abort(403, 'No tienes permiso para eliminar esta actividad.');
        }

        DB::table('notas')->where('actividad_id', $id)->delete();
        DB::table('actividades')->where('id', $id)->delete();

        registrar_auditoria('eliminar', 'actividades', $actividad->titulo, null);

        return redirect()->route('notas.curso', ['curso' => $actividad->curso_id, 'grado_id' => $actividad->grado_id, 'seccion' => $actividad->seccion ?? 'A'])
            ->with('success', 'Actividad eliminada.');
    }

    /** Descarga el archivo de indicaciones adjuntado por el docente. */
    public function archivoIndicaciones($id)
    {
        $actividad = DB::table('actividades')->where('id', $id)->first();
        abort_if(!$actividad || !$actividad->archivo_path, 404);

        if (session('user_rol') === 'estudiante') {
            $existe = DB::table('entregas_actividad')
                ->where('actividad_id', $id)->where('estudiante_id', session('estudiante_id'))->exists();
            abort_if(!$existe, 403);
        }

        abort_if(!Storage::disk('local')->exists($actividad->archivo_path), 404, 'El archivo ya no está disponible.');

        return Storage::disk('local')->response($actividad->archivo_path);
    }

    /** Descarga el archivo entregado por el estudiante (visible para el docente/promotor o el propio estudiante). */
    public function archivoEntrega($id)
    {
        $entrega = DB::table('entregas_actividad')->where('id', $id)->first();
        abort_if(!$entrega || !$entrega->archivo_path, 404);

        if (session('user_rol') === 'estudiante') {
            abort_if($entrega->estudiante_id !== session('estudiante_id'), 403);
        } elseif (session('user_rol') === 'docente') {
            $actividad = DB::table('actividades')->where('id', $entrega->actividad_id)->first();
            abort_if(!$actividad || $actividad->docente_id !== session('user_id'), 403);
        }

        abort_if(!Storage::disk('local')->exists($entrega->archivo_path), 404, 'El archivo ya no está disponible.');

        return Storage::disk('local')->response($entrega->archivo_path);
    }

    /** Listado de actividades del estudiante autenticado, agrupadas por curso. */
    public function misActividades()
    {
        $estudianteId = session('estudiante_id');

        $actividades = DB::table('entregas_actividad as e')
            ->join('actividades as a', 'a.id', '=', 'e.actividad_id')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('periodos as p', 'p.id', '=', 'a.periodo_id')
            ->where('e.estudiante_id', $estudianteId)
            ->where('a.activo', 1)
            ->select('e.*', 'a.id as actividad_id', 'a.titulo', 'a.fecha_entrega', 'a.tipo_evaluacion', 'c.nombre as curso_nombre', 'p.nombre as periodo_nombre')
            ->orderByDesc('a.fecha_entrega')
            ->get()
            ->groupBy('curso_nombre');

        return view('alumno.actividades.index', compact('actividades'));
    }

    /** Detalle de una actividad para el estudiante: instrucciones + formulario de entrega o su nota final. */
    public function miActividad($id)
    {
        $estudianteId = session('estudiante_id');

        $actividad = DB::table('actividades as a')
            ->join('cursos as c', 'c.id', '=', 'a.curso_id')
            ->join('periodos as p', 'p.id', '=', 'a.periodo_id')
            ->join('users as u', 'u.id', '=', 'a.docente_id')
            ->where('a.id', $id)
            ->select('a.*', 'c.nombre as curso_nombre', 'p.nombre as periodo_nombre', 'u.nombre as docente_nombre')
            ->first();

        abort_if(!$actividad, 404);

        $entrega = DB::table('entregas_actividad')->where('actividad_id', $id)->where('estudiante_id', $estudianteId)->first();
        abort_if(!$entrega, 403, 'Esta actividad no está asignada a tu sección.');

        return view('alumno.actividades.show', compact('actividad', 'entrega'));
    }

    /** El estudiante entrega (o reemplaza) su trabajo mientras no haya sido calificado. */
    public function entregar(Request $request, $id)
    {
        $request->validate([
            'comentario_estudiante' => 'nullable|string|max:1000',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:8192',
        ]);

        $estudianteId = session('estudiante_id');
        $entrega = DB::table('entregas_actividad')->where('actividad_id', $id)->where('estudiante_id', $estudianteId)->first();
        abort_if(!$entrega, 403);

        if ($entrega->estado === 'calificado') {
            return back()->with('error', 'Esta actividad ya fue calificada y no se puede modificar la entrega.');
        }

        $archivoPath = $entrega->archivo_path;
        if ($request->hasFile('archivo')) {
            $archivoPath = $request->file('archivo')->store('entregas', 'local');
        }

        DB::table('entregas_actividad')->where('id', $entrega->id)->update([
            'estado' => 'entregado',
            'archivo_path' => $archivoPath,
            'comentario_estudiante' => $request->comentario_estudiante,
            'entregado_en' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Trabajo entregado correctamente. Tu docente lo revisará y calificará.');
    }
}
