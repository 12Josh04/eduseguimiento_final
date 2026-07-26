<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitudes_matricula', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 60);
            $table->string('apellido', 60);
            $table->date('fecha_nacimiento')->nullable();
            $table->string('dni', 12)->nullable();
            $table->uuid('grado_id')->nullable();
            $table->string('apoderado_nombre', 100);
            $table->string('apoderado_telefono', 20);
            $table->string('apoderado_email', 120)->nullable();
            $table->decimal('monto', 8, 2)->nullable();
            $table->string('voucher_path', 255)->nullable();
            $table->enum('estado', ['pendiente', 'confirmado', 'rechazado'])->default('pendiente');
            $table->text('observacion_promotor')->nullable();
            $table->uuid('estudiante_id')->nullable();
            $table->timestamp('confirmado_en')->nullable();
            $table->timestamps();

            $table->foreign('grado_id')->references('id')->on('grados')->nullOnDelete();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_matricula');
    }
};
