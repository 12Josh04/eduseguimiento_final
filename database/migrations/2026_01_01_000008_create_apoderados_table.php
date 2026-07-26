<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('apoderados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('estudiante_id');
            $table->string('nombre', 80);
            $table->string('parentesco', 20)->default('padre');
            $table->string('telefono', 15)->nullable();
            $table->string('email', 120)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->index('estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apoderados');
    }
};
