<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Mostrar frase inspiradora')->hourly();

// Política de habilitación de cuentas: antes del inicio de clases (marzo), las cuentas de
// los estudiantes del año anterior que no se hayan vuelto a matricular quedan deshabilitadas
// automáticamente. Corre todos los días de enero y febrero; el resto del año no hace nada.
Schedule::command('estudiantes:desactivar-sin-matricula')
    ->dailyAt('03:00')
    ->when(fn () => now()->month <= 2);
