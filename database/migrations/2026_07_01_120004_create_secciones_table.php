<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grado_id');
            $table->string('nombre', 20);
            $table->unsignedSmallInteger('anio_escolar');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->unique(['grado_id', 'nombre', 'anio_escolar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
