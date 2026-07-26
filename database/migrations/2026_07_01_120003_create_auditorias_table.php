<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id')->nullable();
            $table->string('rol', 30)->nullable();
            $table->string('accion', 80);
            $table->string('modulo', 80);
            $table->text('registro_anterior')->nullable();
            $table->text('registro_nuevo')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['modulo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
