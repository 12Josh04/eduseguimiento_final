<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cursos', 'grado_id')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->uuid('grado_id')->nullable()->after('codigo');
            });
        }

        if (!Schema::hasColumn('cursos', 'seccion')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->string('seccion', 10)->nullable()->after('grado_id');
            });
        }

        if (!Schema::hasColumn('cursos', 'anio_escolar')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->unsignedSmallInteger('anio_escolar')->nullable()->after('seccion');
            });
        }

        if (!Schema::hasColumn('matriculas', 'seccion')) {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->string('seccion', 10)->nullable()->after('grado_id');
            });
        }

        if (!Schema::hasColumn('asignaciones_docente', 'seccion')) {
            Schema::table('asignaciones_docente', function (Blueprint $table) {
                $table->string('seccion', 10)->nullable()->after('grado_id');
            });
        }

        if (!$this->hasIndex('asignaciones_docente', 'asignaciones_docente_idx')) {
            Schema::table('asignaciones_docente', function (Blueprint $table) {
                $table->index(['docente_id', 'curso_id', 'grado_id', 'seccion', 'anio_escolar'], 'asignaciones_docente_idx');
            });
        }

        if (!Schema::hasColumn('notas', 'grado_id')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->uuid('grado_id')->nullable()->after('curso_id');
            });
        }

        if (!Schema::hasColumn('notas', 'seccion')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->string('seccion', 10)->nullable()->after('grado_id');
            });
        }

        if (!Schema::hasColumn('notas', 'observacion')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->text('observacion')->nullable()->after('seccion');
            });
        }
    }

    public function down(): void
    {
        foreach (['grado_id', 'seccion', 'observacion'] as $column) {
            if (Schema::hasColumn('notas', $column)) {
                Schema::table('notas', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('asignaciones_docente', 'seccion')) {
            Schema::table('asignaciones_docente', function (Blueprint $table) {
                $table->dropColumn('seccion');
            });
        }

        if (Schema::hasColumn('matriculas', 'seccion')) {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->dropColumn('seccion');
            });
        }

        foreach (['grado_id', 'seccion', 'anio_escolar'] as $column) {
            if (Schema::hasColumn('cursos', $column)) {
                Schema::table('cursos', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}`");

        foreach ($indexes as $index) {
            if (($index->Key_name ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
