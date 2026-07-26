<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 30);
            $table->enum('tipo', ['bimestre','trimestre'])->default('bimestre');
            $table->unsignedSmallInteger('anio_escolar');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nombre','anio_escolar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
