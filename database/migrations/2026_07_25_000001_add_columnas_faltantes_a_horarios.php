<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            if (!Schema::hasColumn('horarios', 'tipo_horario')) {
                $table->string('tipo_horario', 30)->default('clase')->after('aula');
            }
            if (!Schema::hasColumn('horarios', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('tipo_horario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn(['tipo_horario', 'observaciones']);
        });
    }
};
