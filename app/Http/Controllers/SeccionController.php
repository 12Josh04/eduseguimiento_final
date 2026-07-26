<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeccionController extends Controller
{
    public function index()
    {
        $secciones = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->select('s.*', 'g.nombre as grado')
            ->orderBy('g.nombre')
            ->orderBy('s.nombre')
            ->get();

        $grados = DB::table('grados')->where('activo', 1)->orderBy('nombre')->get();

        return view('secciones.index', compact('secciones', 'grados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'nombre' => 'required|string|max:20',
            'anio_escolar' => 'required|numeric',
        ]);

        DB::table('secciones')->insert([
            'id' => Str::uuid(),
            'grado_id' => $request->grado_id,
            'nombre' => strtoupper($request->nombre),
            'anio_escolar' => $request->anio_escolar,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Sección registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'nombre' => 'required|string|max:20',
            'anio_escolar' => 'required|numeric',
        ]);

        DB::table('secciones')->where('id', $id)->update([
            'grado_id' => $request->grado_id,
            'nombre' => strtoupper($request->nombre),
            'anio_escolar' => $request->anio_escolar,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Sección actualizada.');
    }

    public function destroy($id)
    {
        DB::table('secciones')->where('id', $id)->delete();
        return back()->with('success', 'Sección eliminada.');
    }
}
