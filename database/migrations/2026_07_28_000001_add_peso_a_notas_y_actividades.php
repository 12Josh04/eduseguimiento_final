<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('notas', 'peso')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->decimal('peso', 5, 2)->default(1.00)->after('nota_maxima');
            });
        }

        if (!Schema::hasColumn('actividades', 'peso')) {
            Schema::table('actividades', function (Blueprint $table) {
                $table->decimal('peso', 5, 2)->default(1.00)->after('tipo_evaluacion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notas', 'peso')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->dropColumn('peso');
            });
        }
        if (Schema::hasColumn('actividades', 'peso')) {
            Schema::table('actividades', function (Blueprint $table) {
                $table->dropColumn('peso');
            });
        }
    }
};
