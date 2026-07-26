<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cursos', 'area_curricular')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->string('area_curricular', 80)->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cursos', 'area_curricular')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->dropColumn('area_curricular');
            });
        }
    }
};
