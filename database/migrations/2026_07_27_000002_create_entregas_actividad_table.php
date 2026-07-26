<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entregas_actividad', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actividad_id');
            $table->uuid('estudiante_id');
            $table->enum('estado', ['pendiente', 'entregado', 'calificado'])->default('pendiente');
            $table->string('archivo_path')->nullable();
            $table->text('comentario_estudiante')->nullable();
            $table->decimal('nota', 4, 2)->nullable();
            $table->text('observacion_docente')->nullable();
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('calificado_en')->nullable();
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->onDelete('cascade');
            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->unique(['actividad_id', 'estudiante_id']);
            $table->index(['estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_actividad');
    }
};
