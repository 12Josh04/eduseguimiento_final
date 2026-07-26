<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('bloques_horarios', function (Blueprint $table) {
        $table->id();
        $table->string('nombre'); // Ej: "1ra Hora"
        $table->time('hora_inicio');
        $table->time('hora_fin');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloques_horarios');
    }
};
