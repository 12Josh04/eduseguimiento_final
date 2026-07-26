<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('nombre', 30)->unique();
            $table->string('descripcion', 120)->nullable();
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['id'=>1,'nombre'=>'promotor',  'descripcion'=>'Acceso total al sistema',                  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,'nombre'=>'docente',   'descripcion'=>'Gestión de notas y conductas',             'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,'nombre'=>'psicologa', 'descripcion'=>'Seguimiento conductual y psicológico',     'created_at'=>now(),'updated_at'=>now()],
            ['id'=>4,'nombre'=>'estudiante','descripcion'=>'Acceso de solo lectura (padres incluidos)','created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
