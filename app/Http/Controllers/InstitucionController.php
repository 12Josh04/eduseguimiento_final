<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstitucionController extends Controller
{
    public function index()
    {
        $configuraciones = DB::table('configuraciones_institucionales')->pluck('valor', 'clave');
        return view('institucion.index', compact('configuraciones'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nombre_colegio' => 'required|string|max:120',
            'anio_escolar' => 'required|numeric',
            'dre' => 'nullable|string|max:80',
            'ugel' => 'nullable|string|max:80',
            'codigo_modular' => 'nullable|string|max:20',
            'nivel_educativo' => 'nullable|string|max:60',
            'whatsapp_matricula' => 'nullable|string|max:20',
        ]);

        $campos = [
            'nombre_colegio', 'anio_escolar', 'dre', 'ugel',
            'codigo_modular', 'nivel_educativo', 'whatsapp_matricula',
        ];

        foreach ($campos as $clave) {
            if (!$request->filled($clave)) {
                continue;
            }
            $existe = DB::table('configuraciones_institucionales')->where('clave', $clave)->exists();
            if ($existe) {
                DB::table('configuraciones_institucionales')->where('clave', $clave)
                    ->update(['valor' => $request->input($clave), 'updated_at' => now()]);
            } else {
                DB::table('configuraciones_institucionales')->insert([
                    'id' => (string) Str::uuid(),
                    'clave' => $clave,
                    'valor' => $request->input($clave),
                    'tipo' => 'text',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        registrar_auditoria('actualizar', 'institucion', null, $request->only($campos));

        return back()->with('success', 'Datos institucionales actualizados.');
    }
}
