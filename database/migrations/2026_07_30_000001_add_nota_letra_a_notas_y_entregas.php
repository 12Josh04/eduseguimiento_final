<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Primaria se califica directamente en letras (AD/A/B/C); Secundaria en números (0-20).
        // "nota" sigue guardando siempre un valor numérico equivalente (para que los promedios
        // ponderados y el orden de mérito sigan funcionando igual), y "nota_letra" guarda la
        // letra tal como la ingresó el docente cuando corresponde, con prioridad para mostrarla.
        Schema::table('notas', function (Blueprint $table) {
            if (!Schema::hasColumn('notas', 'nota_letra')) {
                $table->enum('nota_letra', ['AD', 'A', 'B', 'C'])->nullable()->after('nota');
            }
        });

        Schema::table('entregas_actividad', function (Blueprint $table) {
            if (!Schema::hasColumn('entregas_actividad', 'nota_letra')) {
                $table->enum('nota_letra', ['AD', 'A', 'B', 'C'])->nullable()->after('nota');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'nota_letra')) {
                $table->dropColumn('nota_letra');
            }
        });

        Schema::table('entregas_actividad', function (Blueprint $table) {
            if (Schema::hasColumn('entregas_actividad', 'nota_letra')) {
                $table->dropColumn('nota_letra');
            }
        });
    }
};
