<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

class ConductaController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('conductas')
            ->join('estudiantes','conductas.estudiante_id','=','estudiantes.id')
            ->join('users','conductas.reportador_id','=','users.id')
            ->join('roles','users.rol_id','=','roles.id')
            ->select('conductas.*',
                DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as estudiante_nombre"),
                'users.nombre as reportador_nombre',
                'roles.nombre as reportador_rol');

        if ($request->tipo)       $query->where('conductas.tipo',$request->tipo);
        if ($request->fecha)      $query->whereDate('conductas.fecha_hora',$request->fecha);
        if ($request->estudiante) $query->where('conductas.estudiante_id',$request->estudiante);

        if (session('user_rol') === 'estudiante') {
            $est = DB::table('estudiantes')->where('usuario_id',session('user_id'))->first();
            if ($est) $query->where('conductas.estudiante_id',$est->id);
        }

        $conductas = $query->orderByDesc('conductas.fecha_hora')->paginate(20);

        $statsBase = DB::table('conductas');
        if (session('user_rol') === 'estudiante') {
            $est = DB::table('estudiantes')->where('usuario_id',session('user_id'))->first();
            if ($est) $statsBase->where('estudiante_id',$est->id);
        }
        $stats = [
            'positivas'    => (clone $statsBase)->where('tipo','positivo')->count(),
            'negativas'    => (clone $statsBase)->where('tipo','negativo')->count(),
            'observaciones'=> (clone $statsBase)->where('tipo','observacion')->count(),
        ];

        $estudiantes = DB::table('estudiantes')->where('activo',1)
            ->select('id', DB::raw("CONCAT(nombre,' ',apellido) as nombre"))
            ->orderBy('apellido')->get();

        return view('conductas.index', compact('conductas','stats','estudiantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required',
            'tipo'          => 'required|in:positivo,negativo,observacion',
            'descripcion'   => 'required|min:10',
        ]);

        DB::table('conductas')->insert([
            'id'            => Str::uuid(),
            'estudiante_id' => $request->estudiante_id,
            'reportador_id' => session('user_id'),
            'tipo'          => $request->tipo,
            'descripcion'   => $request->descripcion,
            'lugar'         => $request->lugar,
            'fecha_hora'    => $request->fecha_hora ?? now(),
        ]);

        registrar_auditoria('crear', 'conductas', null, "{$request->tipo}: {$request->descripcion}");

        return back()->with('success','Conducta registrada correctamente.');
    }

    public function destroy($id)
    {
        $conducta = DB::table('conductas')->where('id',$id)->first();
        DB::table('conductas')->where('id',$id)->delete();
        registrar_auditoria('eliminar', 'conductas', $conducta?->descripcion, null);
        return back()->with('success','Registro eliminado.');
    }

    public function historial()
    {
        $historial = DB::table('conductas')
            ->join('estudiantes','conductas.estudiante_id','=','estudiantes.id')
            ->select(
                DB::raw("CONCAT(estudiantes.nombre,' ',estudiantes.apellido) as nombre"),
                DB::raw("SUM(CASE WHEN conductas.tipo='positivo' THEN 1 ELSE 0 END) as positivas"),
                DB::raw("SUM(CASE WHEN conductas.tipo='negativo' THEN 1 ELSE 0 END) as negativas"),
                DB::raw("SUM(CASE WHEN conductas.tipo='observacion' THEN 1 ELSE 0 END) as observaciones"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('estudiantes.id','estudiantes.nombre','estudiantes.apellido')
            ->orderByDesc('negativas')->orderBy('estudiantes.apellido')->orderBy('estudiantes.nombre')->get();

        return view('conductas.historial', compact('historial'));
    }
}
