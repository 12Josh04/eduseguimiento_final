<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('grado_id');
            $table->unsignedSmallInteger('anio_escolar');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unique(['estudiante_id','anio_escolar']);
            $table->index(['grado_id','activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
