<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SesionPsicologiaController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('sesiones_psicologia as sp')
            ->join('estudiantes as e', 'e.id', '=', 'sp.estudiante_id')
            ->join('users as u', 'u.id', '=', 'sp.psicologo_id')
            ->leftJoin('matriculas as m', function ($j) {
                $j->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1)->where('m.anio_escolar', date('Y'));
            })
            ->leftJoin('grados as g', 'g.id', '=', 'm.grado_id')
            ->select(
                'sp.*',
                DB::raw("CONCAT(e.nombre,' ',e.apellido) as estudiante_nombre"),
                'u.nombre as psicologo_nombre',
                'g.nombre as grado', 'm.seccion'
            );

        if ($request->filled('estudiante')) {
            $query->where('sp.estudiante_id', $request->estudiante);
        }

        $sesiones = $query->orderByDesc('sp.fecha')->paginate(20)->withQueryString();

        $estudiantes = DB::table('estudiantes')->where('activo', 1)
            ->select('id', DB::raw("CONCAT(nombre,' ',apellido) as nombre"))
            ->orderBy('apellido')->get();

        return view('sesiones-psicologia.index', compact('sesiones', 'estudiantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'fecha'         => 'required|date',
            'motivo'        => 'required|string|max:160',
            'observaciones' => 'required|string|min:10',
            'acuerdos'      => 'nullable|string',
            'proxima_cita'  => 'nullable|date',
        ]);

        DB::table('sesiones_psicologia')->insert([
            'id'             => Str::uuid(),
            'estudiante_id'  => $request->estudiante_id,
            'psicologo_id'   => session('user_id'),
            'fecha'          => $request->fecha,
            'motivo'         => $request->motivo,
            'observaciones'  => $request->observaciones,
            'acuerdos'       => $request->acuerdos,
            'proxima_cita'   => $request->proxima_cita,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        registrar_auditoria('crear', 'sesiones_psicologia', null, "{$request->motivo}");

        return back()->with('success', 'Sesión de psicología registrada correctamente.');
    }

    public function destroy($id)
    {
        $sesion = DB::table('sesiones_psicologia')->where('id', $id)->first();
        DB::table('sesiones_psicologia')->where('id', $id)->delete();
        registrar_auditoria('eliminar', 'sesiones_psicologia', $sesion?->motivo, null);
        return back()->with('success', 'Sesión eliminada.');
    }
}
