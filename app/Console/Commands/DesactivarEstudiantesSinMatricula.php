<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DesactivarEstudiantesSinMatricula extends Command
{
    protected $signature = 'estudiantes:desactivar-sin-matricula {--anio= : Año escolar a exigir (por defecto, el año actual)} {--dry-run : Solo mostrar qué se desactivaría, sin aplicar cambios}';

    protected $description = 'Desactiva las cuentas de los estudiantes que no tienen una matrícula activa para el año escolar en curso (política de inicio de año).';

    public function handle(): int
    {
        $anio = $this->option('anio') ?: (string) now()->year;
        $dryRun = $this->option('dry-run');

        $estudiantes = DB::table('estudiantes as e')
            ->where('e.activo', 1)
            ->whereNotExists(function ($q) use ($anio) {
                $q->select(DB::raw(1))->from('matriculas as m')
                    ->whereColumn('m.estudiante_id', 'e.id')
                    ->where('m.activo', 1)
                    ->where('m.anio_escolar', $anio);
            })
            ->select('e.id', 'e.usuario_id', 'e.nombre', 'e.apellido', 'e.codigo')
            ->get();

        if ($estudiantes->isEmpty()) {
            $this->info("No hay estudiantes activos sin matrícula vigente para el año {$anio}.");
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Estudiantes sin matrícula activa en {$anio}: {$estudiantes->count()}");
        foreach ($estudiantes as $est) {
            $this->line("  - {$est->codigo}: {$est->nombre} {$est->apellido}");
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        $ids = $estudiantes->pluck('id')->all();
        $usuarioIds = $estudiantes->pluck('usuario_id')->filter()->all();

        DB::transaction(function () use ($ids, $usuarioIds) {
            DB::table('estudiantes')->whereIn('id', $ids)->update(['activo' => 0, 'updated_at' => now()]);
            if ($usuarioIds) {
                DB::table('users')->whereIn('id', $usuarioIds)->update(['activo' => 0, 'actualizado_en' => now()]);
            }
        });

        DB::table('auditorias')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'usuario_id' => null,
            'rol' => 'sistema',
            'accion' => 'desactivar_por_politica_matricula',
            'modulo' => 'estudiantes',
            'registro_anterior' => null,
            'registro_nuevo' => "Desactivados {$estudiantes->count()} estudiantes sin matrícula vigente en {$anio}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Listo: {$estudiantes->count()} cuenta(s) de estudiante desactivada(s).");
        return self::SUCCESS;
    }
}
