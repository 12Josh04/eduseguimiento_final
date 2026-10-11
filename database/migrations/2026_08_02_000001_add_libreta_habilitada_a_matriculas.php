<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Permite al promotor habilitar/deshabilitar, por alumno y año de matrícula, el
        // acceso del propio estudiante a "Mi Libreta de Notas" (boleta oficial). Por
        // defecto queda habilitada; el promotor decide cuándo restringirla.
        Schema::table('matriculas', function (Blueprint $table) {
            if (!Schema::hasColumn('matriculas', 'libreta_habilitada')) {
                $table->boolean('libreta_habilitada')->default(true)->after('activo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            if (Schema::hasColumn('matriculas', 'libreta_habilitada')) {
                $table->dropColumn('libreta_habilitada');
            }
        });
    }
};
