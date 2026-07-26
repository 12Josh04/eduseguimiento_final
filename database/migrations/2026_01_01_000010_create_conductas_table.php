<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conductas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('reportador_id');
            $table->enum('tipo', ['positivo','negativo','observacion']);
            $table->text('descripcion');
            $table->string('lugar', 80)->nullable();
            $table->timestamp('fecha_hora');

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('reportador_id')->references('id')->on('users');
            $table->index(['estudiante_id','tipo']);
            $table->index('fecha_hora');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conductas');
    }
};
