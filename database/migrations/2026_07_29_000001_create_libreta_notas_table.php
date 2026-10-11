<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('libreta_notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('curso_id');
            $table->uuid('periodo_id');
            $table->enum('nivel_logro', ['AD', 'A', 'B', 'C'])->nullable();
            $table->text('conclusion')->nullable();
            $table->uuid('docente_id')->nullable();
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            $table->foreign('periodo_id')->references('id')->on('periodos')->onDelete('cascade');
            $table->foreign('docente_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['estudiante_id', 'curso_id', 'periodo_id'], 'libreta_notas_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libreta_notas');
    }
};
