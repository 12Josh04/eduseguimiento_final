<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use App\Helpers\Auth;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }

        $rol = $usuario->rol ?? 'promotor';

        // Obtener grados para el filtro
        $grados = DB::table('grados')->where('activo', 1)->orderBy('nivel')->orderBy('nombre')->get();
        $secciones = DB::table('secciones')->where('activo', 1)->orderBy('grado_id')->orderBy('nombre')->get();

        $sinGrados = false;
        $gradoIdsPermitidos = [];

        // Si es docente, obtener sus grados asignados
        if ($rol === 'docente') {
            $gradoIdsPermitidos = DB::table('asignaciones_docente')
                ->where('docente_id', $usuario->id)
                ->where('activo', 1)
                ->where('anio_escolar', date('Y'))
                ->pluck('grado_id')
                ->toArray();

            if (empty($gradoIdsPermitidos)) {
                $sinGrados = true;
                $estudiantes = collect([]);
                $paneles = collect();
            }
        }

        // ── Determinar vista: paneles (por defecto) o lista (grado/sección/búsqueda/nivel específicos) ──
        $vista = ($request->filled('grado_id') || $request->filled('search') || $request->filled('nivel')) ? 'lista' : 'paneles';
        $estudiantes = $estudiantes ?? collect();
        $paneles = $paneles ?? collect();

        if (!$sinGrados && $vista === 'paneles') {
            $paneles = $this->construirPaneles($rol, $gradoIdsPermitidos);
        }

        if (!$sinGrados && $vista === 'lista') {
            $query = DB::table('estudiantes')
                ->select(
                    'estudiantes.*',
                    DB::raw('(SELECT g.nombre FROM grados g
                              JOIN matriculas m ON m.grado_id = g.id
                              WHERE m.estudiante_id = estudiantes.id
                              AND m.activo = 1
                              AND m.anio_escolar = ' . date('Y') . '
                              LIMIT 1) as grado'),
                    DB::raw('(SELECT g.nivel FROM grados g
                              JOIN matriculas m ON m.grado_id = g.id
                              WHERE m.estudiante_id = estudiantes.id
                              AND m.activo = 1
                              AND m.anio_escolar = ' . date('Y') . '
                              LIMIT 1) as nivel'),
                    DB::raw('(SELECT m.seccion FROM matriculas m
                              WHERE m.estudiante_id = estudiantes.id
                              AND m.activo = 1
                              AND m.anio_escolar = ' . date('Y') . '
                              LIMIT 1) as seccion'),
                    DB::raw('(SELECT ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) FROM notas n WHERE n.estudiante_id=estudiantes.id) as promedio'),
                    DB::raw('(SELECT COUNT(*) FROM conductas c WHERE c.estudiante_id=estudiantes.id AND c.tipo="positivo") as conductas_pos'),
                    DB::raw('(SELECT COUNT(*) FROM conductas c WHERE c.estudiante_id=estudiantes.id AND c.tipo="negativo") as conductas_neg')
                )
                ->where('estudiantes.activo', 1);

            // 🔥 RESTRICCIÓN PARA DOCENTE: solo estudiantes en sus grados asignados
            if ($rol === 'docente' && !empty($gradoIdsPermitidos)) {
                $query->whereExists(function($q) use ($gradoIdsPermitidos) {
                    $q->select(DB::raw(1))
                        ->from('matriculas')
                        ->whereColumn('matriculas.estudiante_id', 'estudiantes.id')
                        ->where('matriculas.activo', 1)
                        ->where('matriculas.anio_escolar', date('Y'))
                        ->whereIn('matriculas.grado_id', $gradoIdsPermitidos);
                });
            }

            // Aplicar filtros de búsqueda
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function($q) use ($s) {
                    $q->where('estudiantes.nombre', 'like', "%$s%")
                      ->orWhere('estudiantes.apellido', 'like', "%$s%")
                      ->orWhere('estudiantes.codigo', 'like', "%$s%")
                      ->orWhereRaw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) LIKE ?", ["%$s%"])
                      ->orWhereRaw("CONCAT(estudiantes.apellido,' ',estudiantes.nombre) LIKE ?", ["%$s%"]);
                });
            }

            if ($request->filled('grado_id')) {
                $query->whereExists(function($q) use ($request) {
                    $q->select(DB::raw(1))
                        ->from('matriculas')
                        ->whereColumn('matriculas.estudiante_id', 'estudiantes.id')
                        ->where('matriculas.activo', 1)
                        ->where('matriculas.anio_escolar', date('Y'))
                        ->where('matriculas.grado_id', $request->grado_id)
                        ->when($request->filled('seccion'), fn($q2) => $q2->where('matriculas.seccion', $request->seccion));
                });
            }

            if ($request->filled('nivel')) {
                $query->whereExists(function($q) use ($request) {
                    $q->select(DB::raw(1))
                        ->from('matriculas')
                        ->join('grados', 'grados.id', '=', 'matriculas.grado_id')
                        ->whereColumn('matriculas.estudiante_id', 'estudiantes.id')
                        ->where('matriculas.activo', 1)
                        ->where('matriculas.anio_escolar', date('Y'))
                        ->where('grados.nivel', $request->nivel);
                });
            }

            if ($request->get('export') === 'csv') {
                $todos = (clone $query)->orderBy('estudiantes.apellido')->get();
                if ($rol === 'psicologa') {
                    return csv_response(
                        'estudiantes.csv',
                        ['Nombre', 'Apellido', 'Código', 'Grado', 'Nivel', 'Sección', 'Conductas +', 'Conductas -'],
                        $todos->map(fn($e) => [$e->nombre, $e->apellido, $e->codigo, $e->grado, $e->nivel, $e->seccion, $e->conductas_pos, $e->conductas_neg])
                    );
                }
                return csv_response(
                    'estudiantes.csv',
                    ['Nombre', 'Apellido', 'Código', 'Grado', 'Nivel', 'Sección', 'Promedio', 'Conductas +', 'Conductas -'],
                    $todos->map(fn($e) => [$e->nombre, $e->apellido, $e->codigo, $e->grado, $e->nivel, $e->seccion, $e->promedio, $e->conductas_pos, $e->conductas_neg])
                );
            }

            $estudiantes = $query->orderBy('estudiantes.apellido')->paginate(20);
        }

        return view('estudiantes.index', compact(
            'estudiantes',
            'paneles',
            'vista',
            'grados',
            'secciones',
            'rol',
            'sinGrados',
            'gradoIdsPermitidos'
        ));
    }

    /** Agrupa los grados por nivel y calcula, por cada grado+sección, total de estudiantes y promedio. */
    private function construirPaneles(string $rol, array $gradoIdsPermitidos)
    {
        $query = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->leftJoin('matriculas as m', function ($j) {
                $j->on('m.grado_id', '=', 's.grado_id')
                  ->on('m.seccion', '=', 's.nombre')
                  ->where('m.activo', 1)
                  ->where('m.anio_escolar', date('Y'));
            })
            ->leftJoin('notas as n', 'n.estudiante_id', '=', 'm.estudiante_id')
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
                DB::raw('ROUND(SUM(n.nota*n.peso)/NULLIF(SUM(n.peso),0),1) as promedio_seccion')
            )
            ->groupBy('g.id', 'g.nombre', 'g.nivel', 's.nombre')
            ->orderByRaw("FIELD(g.nivel, 'Primaria','Secundaria')")
            ->orderBy('g.nombre')
            ->orderBy('s.nombre')
            ->get()
            ->groupBy('nivel');
    }

    public function show($id)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        $rol = $usuario->rol ?? 'promotor';
        
        $estudiante = DB::table('estudiantes')
            ->leftJoin('matriculas', function($j) {
                $j->on('estudiantes.id', '=', 'matriculas.estudiante_id')
                  ->where('matriculas.activo', 1);
            })
            ->leftJoin('grados', 'matriculas.grado_id', '=', 'grados.id')
            ->select('estudiantes.*', 'grados.nombre as grado', 'grados.nivel as grado_nivel',
                'matriculas.id as matricula_id', 'matriculas.libreta_habilitada', 'matriculas.anio_escolar')
            ->where('estudiantes.id', $id)->first();

        abort_if(!$estudiante, 404);
        
        // 🔥 VERIFICAR ACCESO PARA DOCENTE — y, de paso, averiguar en qué cursos concretos
        // dicta clase a este alumno, para acotar todo lo académico solo a esos cursos
        // (un docente no debe ver el rendimiento del alumno en materias ajenas a él).
        $cursoIdsDocente = null;
        if ($rol === 'docente') {
            $matricula = DB::table('matriculas')
                ->where('estudiante_id', $id)
                ->where('activo', 1)
                ->where('anio_escolar', date('Y'))
                ->first();

            $cursoIdsDocente = $matricula
                ? DB::table('asignaciones_docente')
                    ->where('docente_id', $usuario->id)->where('grado_id', $matricula->grado_id)
                    ->where('activo', 1)->where('anio_escolar', date('Y'))
                    ->pluck('curso_id')->unique()->values()->all()
                : [];

            // Si el estudiante no está matriculado en un grado asignado al docente, denegar acceso
            if (!$matricula || empty($cursoIdsDocente)) {
                abort(403, 'No tienes acceso a este estudiante.');
            }
        }

        $notas = DB::table('notas')
            ->join('cursos', 'notas.curso_id', '=', 'cursos.id')
            ->join('periodos', 'notas.periodo_id', '=', 'periodos.id')
            ->select('notas.*', 'cursos.nombre as curso', 'periodos.nombre as periodo')
            ->where('notas.estudiante_id', $id)
            ->when($cursoIdsDocente !== null, fn($q) => $q->whereIn('notas.curso_id', $cursoIdsDocente))
            ->orderByDesc('notas.fecha')->get();

        $conductas = DB::table('conductas')
            ->join('users', 'conductas.reportador_id', '=', 'users.id')
            ->select('conductas.*', 'users.nombre as reportador')
            ->where('conductas.estudiante_id', $id)->orderByDesc('conductas.fecha_hora')->get();

        $promediosCurso = DB::table('notas')
            ->join('cursos', 'notas.curso_id', '=', 'cursos.id')
            ->select('cursos.nombre', DB::raw('ROUND(AVG(notas.nota),1) as promedio'))
            ->where('notas.estudiante_id', $id)
            ->when($cursoIdsDocente !== null, fn($q) => $q->whereIn('notas.curso_id', $cursoIdsDocente))
            ->groupBy('cursos.id', 'cursos.nombre')->get();

        // Datos de contacto del apoderado: información personal no académica, fuera del
        // ámbito del docente — solo promotor y psicóloga la ven.
        $apoderados = $rol === 'docente' ? collect() : DB::table('apoderados')->where('estudiante_id', $id)->get();

        $evolucion = DB::table('notas')
            ->join('periodos', 'periodos.id', '=', 'notas.periodo_id')
            ->select('periodos.nombre', DB::raw('ROUND(AVG(notas.nota),1) as promedio'))
            ->where('notas.estudiante_id', $id)
            ->when($cursoIdsDocente !== null, fn($q) => $q->whereIn('notas.curso_id', $cursoIdsDocente))
            ->groupBy('periodos.id', 'periodos.nombre', 'periodos.fecha_inicio')
            ->orderBy('periodos.fecha_inicio')
            ->get();

        $sesionesPsicologia = collect();
        if (in_array($rol, ['promotor', 'psicologa'])) {
            $sesionesPsicologia = DB::table('sesiones_psicologia as sp')
                ->join('users as u', 'u.id', '=', 'sp.psicologo_id')
                ->where('sp.estudiante_id', $id)
                ->select('sp.*', 'u.nombre as psicologo_nombre')
                ->orderByDesc('sp.fecha')
                ->get();
        }

        return view('estudiantes.show', compact('estudiante', 'notas', 'conductas', 'promediosCurso', 'apoderados', 'evolucion', 'sesionesPsicologia'));
    }

    public function create()
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        // Solo promotor puede crear estudiantes
        if ($usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para crear estudiantes.');
        }
        
        $grados = DB::table('grados')->where('activo', 1)->orderBy('nombre')->get();
        $secciones = DB::table('secciones')->where('activo', 1)->orderBy('grado_id')->orderBy('nombre')->get();
        return view('estudiantes.create', compact('grados', 'secciones'));
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        // Solo promotor puede crear estudiantes
        if ($usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para crear estudiantes.');
        }
        
        $request->validate([
            'nombre'   => 'required|max:60',
            'apellido' => 'required|max:60',
            'codigo'   => 'required|max:15|unique:estudiantes,codigo',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $userId = Str::uuid();
        DB::table('users')->insert([
            'id'            => $userId,
            'nombre'        => $request->nombre.' '.$request->apellido,
            'email'         => $request->email,
            'password_hash' => Hash::make($request->password),
            'rol_id'        => 4,
            'activo'        => 1,
            'creado_en'     => now(),
            'actualizado_en'=> now(),
        ]);

        $estId = Str::uuid();
        DB::table('estudiantes')->insert([
            'id'               => $estId,
            'usuario_id'       => $userId,
            'nombre'           => $request->nombre,
            'apellido'         => $request->apellido,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'codigo'           => $request->codigo,
            'activo'           => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        if ($request->grado_id) {
            DB::table('matriculas')->insert([
                'id'           => Str::uuid(),
                'estudiante_id'=> $estId,
                'grado_id'     => $request->grado_id,
                'seccion'      => $request->seccion,
                'anio_escolar' => date('Y'),
                'activo'       => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        if ($request->filled('apoderado_nombre')) {
            DB::table('apoderados')->insert([
                'id'            => Str::uuid(),
                'estudiante_id' => $estId,
                'nombre'        => $request->apoderado_nombre,
                'parentesco'    => $request->apoderado_parentesco ?: 'padre',
                'telefono'      => $request->apoderado_telefono,
                'email'         => $request->apoderado_email,
                'es_principal'  => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        registrar_auditoria('crear', 'estudiantes', null, "{$request->nombre} {$request->apellido} ({$request->codigo})");

        return redirect('/estudiantes')->with('success', 'Estudiante registrado correctamente.');
    }

    public function edit($id)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        // Solo promotor puede editar estudiantes
        if ($usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para editar estudiantes.');
        }
        
        $estudiante = DB::table('estudiantes')->where('id', $id)->first();
        abort_if(!$estudiante, 404);
        $grados = DB::table('grados')->where('activo', 1)->orderBy('nombre')->get();
        $secciones = DB::table('secciones')->where('activo', 1)->orderBy('grado_id')->orderBy('nombre')->get();
        $matricula = DB::table('matriculas')->where('estudiante_id', $id)->where('activo', 1)->orderByDesc('created_at')->first();
        return view('estudiantes.edit', compact('estudiante', 'grados', 'secciones', 'matricula'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        // Solo promotor puede actualizar estudiantes
        if ($usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para actualizar estudiantes.');
        }
        
        $request->validate([
            'nombre'   => 'required|max:60',
            'apellido' => 'required|max:60',
        ]);
        DB::table('estudiantes')->where('id', $id)->update([
            'nombre'           => $request->nombre,
            'apellido'         => $request->apellido,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'updated_at'       => now(),
        ]);

        if ($request->grado_id) {
            // Verificar si ya existe una matrícula activa
            $matriculaExistente = DB::table('matriculas')
                ->where('estudiante_id', $id)
                ->where('activo', 1)
                ->first();
            
            if ($matriculaExistente) {
                DB::table('matriculas')
                    ->where('id', $matriculaExistente->id)
                    ->update([
                        'grado_id' => $request->grado_id,
                        'seccion' => $request->seccion,
                        'anio_escolar' => date('Y'),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('matriculas')->insert([
                    'id'           => Str::uuid(),
                    'estudiante_id'=> $id,
                    'grado_id'     => $request->grado_id,
                    'seccion'      => $request->seccion,
                    'anio_escolar' => date('Y'),
                    'activo'       => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        registrar_auditoria('actualizar', 'estudiantes', $id, "{$request->nombre} {$request->apellido}");

        return redirect('/estudiantes')->with('success', 'Estudiante actualizado.');
    }

    public function destroy($id)
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return redirect('/login')->with('error', 'Debes iniciar sesión primero.');
        }
        
        // Solo promotor puede eliminar estudiantes
        if ($usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para eliminar estudiantes.');
        }
        
        $estudiante = DB::table('estudiantes')
            ->where('id', $id)
            ->where('activo', 1)
            ->first();

        abort_if(!$estudiante, 404);

        // Eliminación lógica: desactiva estudiante
        DB::table('estudiantes')->where('id', $id)->update([
            'activo'     => 0,
            'updated_at' => now(),
        ]);

        // Desactiva el usuario asociado para bloquear su acceso
        DB::table('users')->where('id', $estudiante->usuario_id)->update([
            'activo'         => 0,
            'actualizado_en' => now(),
        ]);

        registrar_auditoria('desactivar', 'estudiantes', "{$estudiante->nombre} {$estudiante->apellido}", null);

        return redirect('/estudiantes')->with('success', 'Estudiante eliminado correctamente.');
    }

    /**
     * Asignar un estudiante a una sección (solo promotor)
     */
    public function asignarSeccionEstudiante(Request $request)
    {
        // Verificar autenticación
        if (!Auth::check()) {
            return response()->json(['error' => 'Debes iniciar sesión.'], 401);
        }
        
        $usuario = Auth::user();
        
        if ($usuario->rol !== 'promotor') {
            return response()->json(['error' => 'No tienes permiso.'], 403);
        }

        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'seccion' => 'required|string|max:10',
            'grado_id' => 'required|exists:grados,id',
        ]);

        // Verificar que la sección pertenece al grado
        $seccionValida = DB::table('secciones')
            ->where('grado_id', $request->grado_id)
            ->where('nombre', $request->seccion)
            ->where('activo', 1)
            ->where('anio_escolar', date('Y'))
            ->exists();

        if (!$seccionValida) {
            return response()->json(['error' => 'La sección no es válida para este grado.'], 400);
        }

        // Actualizar la matrícula del estudiante
        DB::table('matriculas')
            ->where('estudiante_id', $request->estudiante_id)
            ->where('grado_id', $request->grado_id)
            ->where('activo', 1)
            ->where('anio_escolar', date('Y'))
            ->update([
                'seccion' => $request->seccion,
                'updated_at' => now()
            ]);

        $estudiante = DB::table('estudiantes')
            ->where('id', $request->estudiante_id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => "Estudiante {$estudiante->nombre} {$estudiante->apellido} asignado a la sección {$request->seccion}"
        ]);
    }

    /**
     * Quitar un estudiante de una sección (solo promotor)
     */
    public function quitarSeccionEstudiante(Request $request)
    {
        // Verificar autenticación
        if (!Auth::check()) {
            return response()->json(['error' => 'Debes iniciar sesión.'], 401);
        }
        
        $usuario = Auth::user();
        
        if ($usuario->rol !== 'promotor') {
            return response()->json(['error' => 'No tienes permiso.'], 403);
        }

        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'grado_id' => 'required|exists:grados,id',
        ]);

        DB::table('matriculas')
            ->where('estudiante_id', $request->estudiante_id)
            ->where('grado_id', $request->grado_id)
            ->where('activo', 1)
            ->where('anio_escolar', date('Y'))
            ->update([
                'seccion' => null,
                'updated_at' => now()
            ]);

        $estudiante = DB::table('estudiantes')
            ->where('id', $request->estudiante_id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => "Estudiante {$estudiante->nombre} {$estudiante->apellido} removido de la sección"
        ]);
    }

    /** Agrega un apoderado a un estudiante ya existente (solo promotor). */
    public function storeApoderado(Request $request, $estudianteId)
    {
        $usuario = Auth::user();
        if (!$usuario || $usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para registrar apoderados.');
        }

        $estudiante = DB::table('estudiantes')->where('id', $estudianteId)->first();
        abort_if(!$estudiante, 404);

        $request->validate([
            'nombre'     => 'required|string|max:80',
            'parentesco' => 'required|in:padre,madre,tutor',
            'telefono'   => 'nullable|string|max:15',
            'email'      => 'nullable|email|max:120',
        ]);

        $yaTieneApoderados = DB::table('apoderados')->where('estudiante_id', $estudianteId)->exists();

        DB::table('apoderados')->insert([
            'id'            => Str::uuid(),
            'estudiante_id' => $estudianteId,
            'nombre'        => $request->nombre,
            'parentesco'    => $request->parentesco,
            'telefono'      => $request->telefono,
            'email'         => $request->email,
            'es_principal'  => !$yaTieneApoderados,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        registrar_auditoria('crear', 'apoderados', null, "{$request->nombre} ({$estudiante->nombre} {$estudiante->apellido})");

        return back()->with('success', 'Apoderado registrado correctamente.');
    }

    /** Elimina un apoderado (solo promotor). */
    public function destroyApoderado($id)
    {
        $usuario = Auth::user();
        if (!$usuario || $usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para eliminar apoderados.');
        }

        $apoderado = DB::table('apoderados')->where('id', $id)->first();
        abort_if(!$apoderado, 404);

        DB::table('apoderados')->where('id', $id)->delete();

        registrar_auditoria('eliminar', 'apoderados', $apoderado->nombre, null);

        return back()->with('success', 'Apoderado eliminado.');
    }

    /**
     * Habilita/deshabilita, para la matrícula vigente de un alumno, su acceso a
     * "Mi Libreta de Notas" (boleta oficial). Solo el promotor puede autorizarlo, y queda
     * atado al año de matrícula: si el alumno se vuelve a matricular más adelante, la
     * nueva matrícula nace habilitada por defecto.
     */
    public function toggleLibreta($estudianteId)
    {
        $usuario = Auth::user();
        if (!$usuario || $usuario->rol !== 'promotor') {
            abort(403, 'No tienes permiso para esta acción.');
        }

        $matricula = DB::table('matriculas')->where('estudiante_id', $estudianteId)->where('activo', 1)->first();
        abort_if(!$matricula, 404, 'El alumno no tiene una matrícula vigente.');

        $nuevoEstado = !$matricula->libreta_habilitada;
        DB::table('matriculas')->where('id', $matricula->id)->update([
            'libreta_habilitada' => $nuevoEstado,
            'updated_at' => now(),
        ]);

        registrar_auditoria('actualizar', 'matriculas', 'libreta_habilitada=' . ($matricula->libreta_habilitada ? 1 : 0), 'libreta_habilitada=' . ($nuevoEstado ? 1 : 0));

        return back()->with('success', $nuevoEstado
            ? 'El alumno ya puede ver su Libreta de Notas.'
            : 'Se deshabilitó el acceso del alumno a su Libreta de Notas.');
    }
}