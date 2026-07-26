<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sesiones_psicologia', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->uuid('psicologo_id');
            $table->date('fecha');
            $table->string('motivo', 160);
            $table->text('observaciones');
            $table->text('acuerdos')->nullable();
            $table->date('proxima_cita')->nullable();
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('psicologo_id')->references('id')->on('users');
            $table->index(['estudiante_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_psicologia');
    }
};
