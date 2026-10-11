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
            ->orderBy('c.nombre')
            ->get();

        return view('cursos.index', compact('cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:80',
            'codigo' => 'required|string|max:10|unique:cursos,codigo',
            'area_curricular' => 'nullable|string|max:100',
        ]);

        DB::table('cursos')->insert([
            'id' => Str::uuid(),
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'area_curricular' => $request->area_curricular,
            'nivel' => 'Básica Regular',
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Curso registrado correctamente. Ahora asígnalo a un docente y grado desde Asignaciones.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:80',
            'codigo' => 'required|string|max:10|unique:cursos,codigo,' . $id,
            'area_curricular' => 'nullable|string|max:100',
        ]);

        DB::table('cursos')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'area_curricular' => $request->area_curricular,
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
