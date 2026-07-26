<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->string('nombre', 60);
            $table->string('apellido', 60);
            $table->string('codigo', 15)->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('dni', 12)->nullable()->unique();
            $table->string('telefono', 15)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('apellido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
