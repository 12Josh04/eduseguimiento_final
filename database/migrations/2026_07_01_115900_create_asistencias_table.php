<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('curso_id')->nullable();
            $table->uuid('docente_id');
            $table->uuid('grado_id')->nullable();
            $table->string('seccion', 10)->nullable();
            $table->date('fecha');
            $table->enum('estado', ['presente', 'falta', 'tardanza', 'justificado'])->default('presente');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('curso_id')->references('id')->on('cursos');
            $table->foreign('docente_id')->references('id')->on('users');
            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unique(['estudiante_id', 'fecha', 'docente_id'], 'asistencia_unica');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
