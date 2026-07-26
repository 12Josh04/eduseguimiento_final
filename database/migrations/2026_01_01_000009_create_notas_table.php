<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('curso_id');
            $table->uuid('docente_id');
            $table->uuid('periodo_id');
            $table->enum('tipo_evaluacion', ['examen', 'tarea', 'practica']);
            $table->string('nombre_evaluacion', 100);
            $table->decimal('nota', 4, 2);
            $table->decimal('nota_maxima', 4, 2)->default(20);
            $table->date('fecha');
            $table->timestamp('creado_en')->useCurrent();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('curso_id')->references('id')->on('cursos');
            $table->foreign('docente_id')->references('id')->on('users');
            $table->foreign('periodo_id')->references('id')->on('periodos');
            $table->index(['estudiante_id', 'curso_id']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
