<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_id');
            $table->uuid('grado_id');
            $table->string('seccion', 10)->nullable();
            $table->uuid('periodo_id');
            $table->uuid('docente_id');
            $table->string('titulo', 160);
            $table->text('instrucciones');
            $table->enum('tipo_evaluacion', ['examen', 'tarea', 'practica'])->default('tarea');
            $table->date('fecha_entrega')->nullable();
            $table->string('archivo_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->foreign('periodo_id')->references('id')->on('periodos');
            $table->foreign('docente_id')->references('id')->on('users');
            $table->index(['curso_id', 'grado_id', 'seccion', 'periodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
