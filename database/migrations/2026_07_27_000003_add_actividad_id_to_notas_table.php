<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('notas', 'actividad_id')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->uuid('actividad_id')->nullable()->after('curso_id');
                $table->foreign('actividad_id')->references('id')->on('actividades')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notas', 'actividad_id')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->dropForeign(['actividad_id']);
                $table->dropColumn('actividad_id');
            });
        }
    }
};
