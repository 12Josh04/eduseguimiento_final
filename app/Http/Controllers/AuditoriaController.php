<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function index()
    {
        $registros = DB::table('auditorias as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.usuario_id')
            ->select('a.*', 'u.nombre as usuario')
            ->orderByDesc('a.created_at')
            ->paginate(20);

        return view('auditorias.index', compact('registros'));
    }
}
