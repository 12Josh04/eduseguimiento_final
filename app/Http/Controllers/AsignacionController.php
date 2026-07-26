<?php
// app/Http/Controllers/AsignacionController.php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Models\AsignacionesDocente;
use App\Models\Curso;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use App\Models\Horario;

class AsignacionController extends Controller
{
    public function index()
    {
        try {
            $asignaciones = AsignacionesDocente::with(['docente', 'curso', 'grado', 'horarios'])
                ->where('anio_escolar', now()->year)
                ->get();

            $docentes = User::where('rol_id', 2)->get();
            $cursos = Curso::all();
            $grados = Grado::where('anio_escolar', now()->year)->get();
            $secciones = Seccion::where('anio_escolar', now()->year)->get();
            $seccionesPorGrado = $secciones->groupBy('grado_id')->map(fn($s) => $s->pluck('nombre'));

            return view('asignaciones.index', compact('asignaciones', 'docentes', 'cursos', 'grados', 'secciones', 'seccionesPorGrado'));
            
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'No se pudo cargar el módulo de asignaciones: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        // Validación básica sin la regla 'after' problemática
        $validator = Validator::make($request->all(), [
            'docente_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'anio_escolar' => 'required|integer|min:2000',
            'tipo_asignacion' => 'required|in:general,especifico',
            'grado_id' => 'nullable|exists:grados,id',
            'seccion' => 'nullable|string|max:10',
            'horarios' => 'required|array|min:1',
            'horarios.*.dia' => 'required|integer|min:1|max:6',
            'horarios.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.hora_fin' => 'required|date_format:H:i',
            'observaciones' => 'nullable|string',
        ]);

        // Validación personalizada para horas
        $validator->after(function ($validator) use ($request) {
            foreach ($request->horarios as $index => $horario) {
                $horaInicio = \Carbon\Carbon::parse($horario['hora_inicio']);
                $horaFin = \Carbon\Carbon::parse($horario['hora_fin']);
                
                if ($horaFin <= $horaInicio) {
                    $validator->errors()->add(
                        "horarios.{$index}.hora_fin",
                        "La hora de fin debe ser posterior a la hora de inicio."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $existe = AsignacionesDocente::where('docente_id', $request->docente_id)
                ->where('curso_id', $request->curso_id)
                ->where('anio_escolar', $request->anio_escolar)
                ->exists();

            if ($existe) {
                DB::rollBack();
                return back()->withErrors([
                    'error' => 'Este docente ya tiene asignado este curso para el año ' . $request->anio_escolar . '.'
                ])->withInput();
            }

            $asignacion = AsignacionesDocente::create([
                'id' => Str::uuid(),
                'docente_id' => $request->docente_id,
                'curso_id' => $request->curso_id,
                'grado_id' => $request->tipo_asignacion === 'especifico' ? $request->grado_id : null,
                'seccion' => $request->tipo_asignacion === 'especifico' ? $request->seccion : null,
                'anio_escolar' => $request->anio_escolar,
                'activo' => true,
            ]);

            foreach ($request->horarios as $horario) {
                $cruce = Horario::whereHas('asignacion', function($query) use ($request) {
                    $query->where('docente_id', $request->docente_id)
                        ->where('anio_escolar', $request->anio_escolar);
                })
                ->where('dia_semana', $horario['dia'])
                ->where(function($query) use ($horario) {
                    $query->where('hora_inicio', '<', $horario['hora_fin'])
                        ->where('hora_fin', '>', $horario['hora_inicio']);
                })
                ->exists();

                if ($cruce) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => 'El docente ya tiene un horario en ese día y hora.'
                    ])->withInput();
                }

                Horario::create([
                    'id' => Str::uuid(),
                    'asignacion_id' => $asignacion->id,
                    'dia_semana' => $horario['dia'],
                    'hora_inicio' => $horario['hora_inicio'],
                    'hora_fin' => $horario['hora_fin'],
                    'aula' => $horario['aula'] ?? null,
                    'observaciones' => $request->observaciones ?? null,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Asignación creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la asignación: ' . $e->getMessage()]);
        }
    }

    // ============================================
    // MÉTODO PARA VER DETALLES
    // ============================================
    public function detalles($id)
    {
        try {
            $asignacion = AsignacionesDocente::with(['docente', 'curso', 'grado', 'horarios'])
                ->findOrFail($id);
            
            return view('asignaciones.detalles', compact('asignacion'));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los detalles: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // MÉTODO PARA EDITAR (MUESTRA EL FORMULARIO)
    // ============================================
    public function edit($id)
    {
        try {
            $asignacion = AsignacionesDocente::with(['docente', 'curso', 'grado', 'horarios'])
                ->findOrFail($id);
            
            $docentes = User::where('rol_id', 2)->get();
            $cursos = Curso::all();
            $grados = Grado::where('anio_escolar', $asignacion->anio_escolar)->get();
            $secciones = Seccion::where('anio_escolar', $asignacion->anio_escolar)->get();

            $horariosExistentes = $asignacion->horarios->map(fn($h) => [
                'dia' => $h->dia_semana,
                'hora_inicio' => \Carbon\Carbon::parse($h->hora_inicio)->format('H:i'),
                'hora_fin' => \Carbon\Carbon::parse($h->hora_fin)->format('H:i'),
                'aula' => $h->aula,
            ])->values();

            $seccionesPorGrado = $secciones->groupBy('grado_id')->map(fn($s) => $s->pluck('nombre'));

            return view('asignaciones.edit', compact('asignacion', 'docentes', 'cursos', 'grados', 'secciones', 'horariosExistentes', 'seccionesPorGrado'));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // MÉTODO PARA ACTUALIZAR
    // ============================================
    public function update(Request $request, $id)
    {
        // Validación básica sin la regla 'after' problemática
        $validator = Validator::make($request->all(), [
            'docente_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'anio_escolar' => 'required|integer|min:2000',
            'tipo_asignacion' => 'required|in:general,especifico',
            'grado_id' => 'nullable|exists:grados,id',
            'seccion' => 'nullable|string|max:10',
            'horarios' => 'required|array|min:1',
            'horarios.*.dia' => 'required|integer|min:1|max:6',
            'horarios.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.hora_fin' => 'required|date_format:H:i',
            'observaciones' => 'nullable|string',
        ]);

        // Validación personalizada para horas
        $validator->after(function ($validator) use ($request) {
            foreach ($request->horarios as $index => $horario) {
                $horaInicio = \Carbon\Carbon::parse($horario['hora_inicio']);
                $horaFin = \Carbon\Carbon::parse($horario['hora_fin']);
                
                if ($horaFin <= $horaInicio) {
                    $validator->errors()->add(
                        "horarios.{$index}.hora_fin",
                        "La hora de fin debe ser posterior a la hora de inicio."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $asignacion = AsignacionesDocente::findOrFail($id);

            // Verificar duplicado (excepto la misma asignación)
            $existe = AsignacionesDocente::where('docente_id', $request->docente_id)
                ->where('curso_id', $request->curso_id)
                ->where('anio_escolar', $request->anio_escolar)
                ->where('id', '!=', $id)
                ->exists();

            if ($existe) {
                DB::rollBack();
                return back()->withErrors([
                    'error' => 'Este docente ya tiene asignado este curso para el año ' . $request->anio_escolar . '.'
                ])->withInput();
            }

            // Actualizar asignación
            $asignacion->update([
                'docente_id' => $request->docente_id,
                'curso_id' => $request->curso_id,
                'grado_id' => $request->tipo_asignacion === 'especifico' ? $request->grado_id : null,
                'seccion' => $request->tipo_asignacion === 'especifico' ? $request->seccion : null,
                'anio_escolar' => $request->anio_escolar,
            ]);

            // Eliminar horarios antiguos
            $asignacion->horarios()->delete();

            // Crear nuevos horarios
            foreach ($request->horarios as $horario) {
                // Verificar cruce de horarios (excepto esta asignación)
                $cruce = Horario::whereHas('asignacion', function($query) use ($request, $id) {
                    $query->where('docente_id', $request->docente_id)
                        ->where('anio_escolar', $request->anio_escolar)
                        ->where('id', '!=', $id);
                })
                ->where('dia_semana', $horario['dia'])
                ->where(function($query) use ($horario) {
                    $query->where('hora_inicio', '<', $horario['hora_fin'])
                        ->where('hora_fin', '>', $horario['hora_inicio']);
                })
                ->exists();

                if ($cruce) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => 'El docente ya tiene un horario en ese día y hora.'
                    ])->withInput();
                }

                Horario::create([
                    'id' => Str::uuid(),
                    'asignacion_id' => $asignacion->id,
                    'dia_semana' => $horario['dia'],
                    'hora_inicio' => $horario['hora_inicio'],
                    'hora_fin' => $horario['hora_fin'],
                    'aula' => $horario['aula'] ?? null,
                    'observaciones' => $request->observaciones ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('asignaciones.index')->with('success', 'Asignación actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    // ============================================
    // MÉTODO PARA ELIMINAR
    // ============================================
    public function destroy($id)
    {
        try {
            $asignacion = AsignacionesDocente::findOrFail($id);
            $asignacion->horarios()->delete();
            $asignacion->delete();
            
            return redirect()->back()->with('success', 'Asignación eliminada correctamente.');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }
}