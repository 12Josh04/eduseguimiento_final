<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropUnique('grados_nombre_anio_escolar_unique');
            $table->unique(['nombre', 'nivel', 'anio_escolar'], 'grados_nombre_nivel_anio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropUnique('grados_nombre_nivel_anio_unique');
            $table->unique(['nombre', 'anio_escolar'], 'grados_nombre_anio_escolar_unique');
        });
    }
};
