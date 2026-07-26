<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuraciones_institucionales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clave', 80)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo', 20)->default('text');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::table('configuraciones_institucionales')->insert([
            [
                'id' => Str::uuid(),
                'clave' => 'nombre_colegio',
                'valor' => 'Colegio José Tola Pasquel',
                'tipo' => 'text',
                'descripcion' => 'Nombre institucional visible en el sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'clave' => 'nombre_corto',
                'valor' => 'CJTP',
                'tipo' => 'text',
                'descripcion' => 'Nombre corto del colegio',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'clave' => 'anio_escolar',
                'valor' => now()->year,
                'tipo' => 'text',
                'descripcion' => 'Año escolar activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_institucionales');
    }
};
