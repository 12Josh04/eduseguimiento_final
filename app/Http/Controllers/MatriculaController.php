<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MatriculaController extends Controller
{
    /** Formulario público de solicitud de matrícula (sin necesidad de iniciar sesión). */
    public function formulario()
    {
        $grados = DB::table('grados')->where('activo', 1)->where('anio_escolar', now()->year)
            ->orderBy('nivel')->orderBy('nombre')->get();

        return view('matricula.formulario', [
            'grados' => $grados,
            'whatsappLink' => whatsapp_matricula_link(),
            'whatsappNumero' => configuracion_valor('whatsapp_matricula', '51999999999'),
        ]);
    }

    /** Recibe la solicitud de matrícula junto con el comprobante (voucher) de pago. */
    public function enviar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:60',
            'apellido' => 'required|string|max:60',
            'fecha_nacimiento' => 'nullable|date',
            'dni' => 'nullable|string|max:12',
            'grado_id' => 'required|exists:grados,id',
            'apoderado_nombre' => 'required|string|max:100',
            'apoderado_telefono' => 'required|string|max:20',
            'apoderado_email' => 'nullable|email|max:120',
            'monto' => 'nullable|numeric|min:0',
            'voucher' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'voucher.required' => 'Debes adjuntar el comprobante (voucher) de pago.',
            'voucher.mimes' => 'El comprobante debe ser una imagen (JPG, PNG) o un PDF.',
        ]);

        $path = $request->file('voucher')->store('vouchers', 'local');

        $id = (string) Str::uuid();
        DB::table('solicitudes_matricula')->insert([
            'id' => $id,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'dni' => $request->dni,
            'grado_id' => $request->grado_id,
            'apoderado_nombre' => $request->apoderado_nombre,
            'apoderado_telefono' => $request->apoderado_telefono,
            'apoderado_email' => $request->apoderado_email,
            'monto' => $request->monto,
            'voucher_path' => $path,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('matricula.gracias')->with('solicitud_id', $id);
    }

    public function gracias()
    {
        return view('matricula.gracias', [
            'whatsappLink' => whatsapp_matricula_link(),
        ]);
    }

    /** Listado de solicitudes de matrícula (solo Promotor). */
    public function pendientes(Request $request)
    {
        $estado = $request->get('estado', 'pendiente');

        $query = DB::table('solicitudes_matricula as s')
            ->leftJoin('grados as g', 'g.id', '=', 's.grado_id')
            ->select('s.*', 'g.nombre as grado_nombre', 'g.nivel as grado_nivel')
            ->orderByDesc('s.created_at');

        if ($estado !== 'todas') {
            $query->where('s.estado', $estado);
        }

        $solicitudes = $query->paginate(20)->appends(['estado' => $estado]);

        $conteos = DB::table('solicitudes_matricula')
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')->pluck('total', 'estado');

        return view('matricula.pendientes', compact('solicitudes', 'estado', 'conteos'));
    }

    /** Muestra (descarga) el comprobante de pago subido — solo Promotor. */
    public function voucher(string $id)
    {
        $solicitud = DB::table('solicitudes_matricula')->where('id', $id)->first();
        abort_if(!$solicitud || !$solicitud->voucher_path, 404);
        abort_if(!Storage::disk('local')->exists($solicitud->voucher_path), 404, 'El archivo ya no está disponible.');

        return Storage::disk('local')->response($solicitud->voucher_path);
    }

    /** Confirma el pago y matricula formalmente al estudiante (crea usuario, estudiante y matrícula). */
    public function confirmar(string $id)
    {
        $solicitud = DB::table('solicitudes_matricula')->where('id', $id)->first();
        abort_if(!$solicitud, 404);

        if ($solicitud->estado === 'confirmado') {
            return back()->with('error', 'Esta solicitud ya fue confirmada anteriormente.');
        }

        // Si el DNI coincide con un estudiante YA REGISTRADO pero con la cuenta desactivada
        // (por ejemplo, por la política de fin de año sin matrícula), se reactiva esa misma
        // cuenta en vez de crear un duplicado, preservando su historial académico y
        // conductual. Solo aplica si el estudiante encontrado está inactivo: un DNI de un
        // estudiante actualmente activo no debe fusionarse (podría ser un error de tipeo).
        $existente = $solicitud->dni
            ? DB::table('estudiantes')->where('dni', $solicitud->dni)->where('activo', 0)->first()
            : null;

        if ($existente) {
            $estId = $existente->id;
            $codigo = $existente->codigo;

            DB::table('estudiantes')->where('id', $estId)->update([
                'activo' => 1,
                'telefono' => $solicitud->apoderado_telefono,
                'updated_at' => now(),
            ]);
            if ($existente->usuario_id) {
                DB::table('users')->where('id', $existente->usuario_id)->update(['activo' => 1, 'actualizado_en' => now()]);
            }
            $email = DB::table('users')->where('id', $existente->usuario_id)->value('email') ?? '—';
            $passwordTemporal = null;
        } else {
            $passwordTemporal = Str::upper(Str::random(4)) . random_int(100, 999);

            $userId = (string) Str::uuid();
            $email = $solicitud->apoderado_email ?: ('estudiante.' . strtolower(Str::random(6)) . '@eduseguimiento.com');
            DB::table('users')->insert([
                'id' => $userId,
                'nombre' => $solicitud->nombre . ' ' . $solicitud->apellido,
                'email' => $email,
                'password_hash' => Hash::make($passwordTemporal),
                'rol_id' => 4,
                'activo' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            $estId = (string) Str::uuid();
            $codigo = 'EST' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            DB::table('estudiantes')->insert([
                'id' => $estId,
                'usuario_id' => $userId,
                'nombre' => $solicitud->nombre,
                'apellido' => $solicitud->apellido,
                'codigo' => $codigo,
                'fecha_nacimiento' => $solicitud->fecha_nacimiento,
                'dni' => $solicitud->dni,
                'telefono' => $solicitud->apoderado_telefono,
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($solicitud->grado_id) {
            DB::table('matriculas')->insert([
                'id' => (string) Str::uuid(),
                'estudiante_id' => $estId,
                'grado_id' => $solicitud->grado_id,
                'seccion' => null,
                'anio_escolar' => now()->year,
                'activo' => 1,
                'libreta_habilitada' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('apoderados')->insert([
            'id' => (string) Str::uuid(),
            'estudiante_id' => $estId,
            'nombre' => $solicitud->apoderado_nombre,
            'parentesco' => 'padre',
            'telefono' => $solicitud->apoderado_telefono,
            'email' => $solicitud->apoderado_email,
            'es_principal' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('solicitudes_matricula')->where('id', $id)->update([
            'estado' => 'confirmado',
            'estudiante_id' => $estId,
            'confirmado_en' => now(),
            'updated_at' => now(),
        ]);

        registrar_auditoria('matricula_confirmada', 'matricula', null, $codigo);

        return back()->with('success', $existente
            ? "Matrícula confirmada. Se reactivó la cuenta existente del estudiante (código {$codigo}, correo {$email}), preservando su historial."
            : "Matrícula confirmada. Código del estudiante: {$codigo} · Correo: {$email} · Contraseña temporal: {$passwordTemporal}. Compártela con el apoderado y pídele cambiarla en \"Mi cuenta\".");
    }

    /** Rechaza una solicitud de matrícula (por ejemplo, voucher inválido). */
    public function rechazar(Request $request, string $id)
    {
        $request->validate(['observacion_promotor' => 'nullable|string|max:255']);

        DB::table('solicitudes_matricula')->where('id', $id)->update([
            'estado' => 'rechazado',
            'observacion_promotor' => $request->observacion_promotor,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Solicitud de matrícula rechazada.');
    }
}
