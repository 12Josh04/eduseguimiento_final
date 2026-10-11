<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = DB::table('users')
            ->join('roles','users.rol_id','=','roles.id')
            ->select('users.*','roles.nombre as rol')
            ->orderBy('roles.id')->orderBy('users.nombre')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|max:80',
            'email'    => 'required|email|unique:users,email',
            'rol_id'   => 'required|in:1,2,3,4',
            'password' => 'required|min:8',
        ]);

        DB::table('users')->insert([
            'id'             => Str::uuid(),
            'nombre'         => $request->nombre,
            'email'          => $request->email,
            'password_hash'  => Hash::make($request->password),
            'rol_id'         => $request->rol_id,
            'activo'         => 1,
            'creado_en'      => now(),
            'actualizado_en' => now(),
        ]);

        return back()->with('success','Usuario creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|max:80',
            'rol_id' => 'required|in:1,2,3,4',
        ]);

        DB::table('users')->where('id',$id)->update([
            'nombre'         => $request->nombre,
            'rol_id'         => $request->rol_id,
            'activo'         => $request->activo == '1' ? 1 : 0,
            'actualizado_en' => now(),
        ]);

        if ($request->filled('password')) {
            DB::table('users')->where('id',$id)->update([
                'password_hash' => Hash::make($request->password),
            ]);
        }

        return back()->with('success','Usuario actualizado.');
    }

    public function destroy($id)
    {
        if ($id === session('user_id')) {
            return back()->with('error','No puedes desactivar tu propia cuenta.');
        }
        DB::table('users')->where('id',$id)->update(['activo'=>0,'actualizado_en'=>now()]);
        return back()->with('success','Usuario desactivado.');
    }
}
