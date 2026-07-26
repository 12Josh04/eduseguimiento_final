<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user_id')) return redirect('/dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = DB::table('users')
            ->join('roles', 'users.rol_id', '=', 'roles.id')
            ->select('users.*', 'roles.nombre as rol')
            ->where('users.email', $request->email)
            ->where('users.activo', 1)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        session([
            'user_id'    => $user->id,
            'user_name'  => $user->nombre,
            'user_email' => $user->email,
            'user_rol'   => $user->rol,
        ]);

        registrar_auditoria('inicio de sesión', 'seguridad', null, $user->email);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        $usuario = session('user_name');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($usuario) {
            registrar_auditoria('cierre de sesión', 'seguridad', $usuario, null);
        }
        return redirect('/login')->with('success', 'Sesión cerrada correctamente.');
    }
}
