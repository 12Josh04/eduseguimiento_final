<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecuperacionController extends Controller
{
    public function index()
    {
        $usuarios = DB::table('users')->where('activo', 1)->orderBy('nombre')->get();
        return view('recuperaciones.index', compact('usuarios'));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'nueva_password' => 'required|min:8|confirmed',
        ]);

        $usuario = DB::table('users')->where('id', $request->usuario_id)->first();
        DB::table('users')->where('id', $request->usuario_id)->update([
            'password_hash' => Hash::make($request->nueva_password),
            'actualizado_en' => now(),
        ]);

        DB::table('auditorias')->insert([
            'id' => Str::uuid(),
            'usuario_id' => session('user_id'),
            'rol' => session('user_rol'),
            'accion' => 'restablecer contraseña',
            'modulo' => 'seguridad',
            'registro_anterior' => 'Usuario: ' . ($usuario->email ?? ''),
            'registro_nuevo' => 'Contraseña restablecida por ' . session('user_name'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Contraseña restablecida y registrada en auditoría.');
    }
}
