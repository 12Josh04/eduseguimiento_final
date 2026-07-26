<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CuentaController extends Controller
{
    public function perfil()
    {
        $usuario = DB::table('users')->where('id', session('user_id'))->first();
        return view('cuentas.perfil', compact('usuario'));
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email,' . session('user_id'),
        ]);

        DB::table('users')->where('id', session('user_id'))->update([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'actualizado_en' => now(),
        ]);

        return back()->with('success', 'Datos de perfil actualizados.');
    }

    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'password_actual' => 'required',
            'password_nueva' => 'required|min:8|confirmed',
        ], [
            'password_nueva.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $usuario = DB::table('users')->where('id', session('user_id'))->first();
        if (!$usuario || !Hash::check($request->password_actual, $usuario->password_hash)) {
            return back()->with('error', 'La contraseña actual no es correcta.');
        }

        DB::table('users')->where('id', session('user_id'))->update([
            'password_hash' => Hash::make($request->password_nueva),
            'actualizado_en' => now(),
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
