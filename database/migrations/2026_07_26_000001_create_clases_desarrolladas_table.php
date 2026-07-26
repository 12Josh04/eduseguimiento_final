<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clases_desarrolladas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_id');
            $table->uuid('grado_id');
            $table->string('seccion', 10)->nullable();
            $table->uuid('docente_id');
            $table->date('fecha');
            $table->string('tema', 160);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->foreign('docente_id')->references('id')->on('users');
            $table->index(['curso_id', 'grado_id', 'seccion']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_desarrolladas');
    }
};
