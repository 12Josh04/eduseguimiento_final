<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 80);
            $table->string('email', 120)->unique();
            $table->string('password_hash', 255);
            $table->unsignedTinyInteger('rol_id');
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('rol_id')->references('id')->on('roles');
            $table->index('rol_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
