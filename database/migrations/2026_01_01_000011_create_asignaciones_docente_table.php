<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asignaciones_docente', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('docente_id');
            $table->uuid('curso_id');
            $table->uuid('grado_id');
            $table->unsignedSmallInteger('anio_escolar');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('docente_id')->references('id')->on('users');
            $table->foreign('curso_id')->references('id')->on('cursos');
            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unique(['docente_id','curso_id','grado_id','anio_escolar'], 'asig_unica');
            $table->index(['docente_id','activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_docente');
    }
};
