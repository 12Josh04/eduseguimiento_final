<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asignacion_id');
            $table->unsignedTinyInteger('dia_semana')
                  ->comment('1=Lunes, 2=Martes, 3=Miercoles, 4=Jueves, 5=Viernes');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 20)->nullable();
            $table->timestamps();

            $table->foreign('asignacion_id')
                  ->references('id')
                  ->on('asignaciones_docente')
                  ->onDelete('cascade');

            $table->index(['asignacion_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
