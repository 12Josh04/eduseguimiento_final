<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoController extends Controller
{
    public function index()
    {
        $cursos = DB::table('cursos as c')
            ->leftJoin('grados as g', 'g.id', '=', 'c.grado_id')
            ->select('c.*', 'g.nombre as grado')
            ->orderBy('c.nombre')
            ->get();

        $grados = DB::table('grados')->where('activo', 1)->orderBy('nombre')->get();
        $secciones = DB::table('secciones')->where('activo', 1)->orderBy('grado_id')->orderBy('nombre')->get();
        return view('cursos.index', compact('cursos', 'grados', 'secciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:80',
            'codigo' => 'required|string|max:10|unique:cursos,codigo',
            'grado_id' => 'required|exists:grados,id',
            'seccion' => 'required|string|max:10',
            'anio_escolar' => 'required|numeric',
        ]);

        DB::table('cursos')->insert([
            'id' => Str::uuid(),
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'grado_id' => $request->grado_id,
            'seccion' => $request->seccion,
            'anio_escolar' => $request->anio_escolar,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Curso registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:80',
            'codigo' => 'required|string|max:10|unique:cursos,codigo,' . $id,
            'grado_id' => 'required|exists:grados,id',
            'seccion' => 'required|string|max:10',
            'anio_escolar' => 'required|numeric',
        ]);

        DB::table('cursos')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'grado_id' => $request->grado_id,
            'seccion' => $request->seccion,
            'anio_escolar' => $request->anio_escolar,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Curso actualizado.');
    }

    public function destroy($id)
    {
        DB::table('cursos')->where('id', $id)->delete();
        return back()->with('success', 'Curso eliminado.');
    }
}
