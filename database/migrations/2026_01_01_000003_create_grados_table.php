<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 20);
            $table->string('nivel', 30)->default('Secundaria');
            $table->unsignedSmallInteger('anio_escolar');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nombre','anio_escolar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
