<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (!function_exists('current_user')) {
    function current_user(): ?object
    {
        if (!session('user_id')) return null;
        return (object) [
            'id'     => session('user_id'),
            'nombre' => session('user_name'),
            'email'  => session('user_email'),
            'rol'    => session('user_rol'),
        ];
    }
}

if (!function_exists('nombre_institucion')) {
    function nombre_institucion(): string
    {
        return DB::table('configuraciones_institucionales')
            ->where('clave', 'nombre_colegio')
            ->value('valor') ?: 'Colegio José Tola Pasquel';
    }
}

if (!function_exists('anio_escolar_actual')) {
    function anio_escolar_actual(): string
    {
        return DB::table('configuraciones_institucionales')
            ->where('clave', 'anio_escolar')
            ->value('valor') ?: now()->year;
    }
}

if (!function_exists('nivel_logro_letra')) {
    /** Convierte un promedio numérico (0-20) al nivel de logro oficial (AD/A/B/C). */
    function nivel_logro_letra(?float $promedio): ?string
    {
        if ($promedio === null) return null;
        if ($promedio >= 18) return 'AD';
        if ($promedio >= 14) return 'A';
        if ($promedio >= 11) return 'B';
        return 'C';
    }
}

if (!function_exists('nivel_logro_texto')) {
    function nivel_logro_texto(?string $letra): string
    {
        return match ($letra) {
            'AD' => 'Logro destacado',
            'A'  => 'Logro esperado',
            'B'  => 'En proceso',
            'C'  => 'En inicio',
            default => 'Sin registro',
        };
    }
}

if (!function_exists('nivel_logro_color')) {
    function nivel_logro_color(?string $letra): string
    {
        return match ($letra) {
            'AD' => '#2e7d32',
            'A'  => '#1565c0',
            'B'  => '#e08a00',
            'C'  => '#c62828',
            default => '#9e9e9e',
        };
    }
}

if (!function_exists('nota_desde_letra')) {
    /** Valor numérico representativo (0-20) de una letra AD/A/B/C, usado internamente para calcular promedios y el orden de mérito cuando la nota se ingresó en letras (Primaria). */
    function nota_desde_letra(?string $letra): ?float
    {
        return match ($letra) {
            'AD' => 19.0,
            'A'  => 16.0,
            'B'  => 12.5,
            'C'  => 8.0,
            default => null,
        };
    }
}

if (!function_exists('es_nivel_primaria')) {
    /** Indica si un grado (por su nombre o su nivel) califica en letras (Primaria) en vez de números (Secundaria). */
    function es_nivel_primaria(?string $nivel): bool
    {
        return $nivel === 'Primaria';
    }
}

if (!function_exists('peso_por_defecto')) {
    /** Peso sugerido según el tipo de evaluación, usado cuando el docente no especifica uno propio. */
    function peso_por_defecto(?string $tipoEvaluacion): float
    {
        return match ($tipoEvaluacion) {
            'examen' => 2.0,
            'practica' => 1.5,
            default => 1.0,
        };
    }
}

if (!function_exists('promedio_ponderado')) {
    /** Promedio ponderado (suma de nota*peso / suma de peso) de una colección de registros con propiedades nota y peso. */
    function promedio_ponderado(\Illuminate\Support\Collection $notas): ?float
    {
        $pesoTotal = $notas->sum(fn ($n) => (float) ($n->peso ?? 1));
        if ($pesoTotal <= 0) return null;
        $sumaPonderada = $notas->sum(fn ($n) => (float) $n->nota * (float) ($n->peso ?? 1));
        return round($sumaPonderada / $pesoTotal, 1);
    }
}

if (!function_exists('configuracion_valor')) {
    function configuracion_valor(string $clave, ?string $default = null): ?string
    {
        return DB::table('configuraciones_institucionales')->where('clave', $clave)->value('valor') ?: $default;
    }
}

if (!function_exists('whatsapp_matricula_link')) {
    /**
     * Enlace "clic para chatear" (wa.me) al número de WhatsApp de matrícula.
     * NOTA: el número configurado es un valor de ejemplo (placeholder) hasta que
     * la institución proporcione el número real en Configuración institucional.
     */
    function whatsapp_matricula_link(?string $nombrePostulante = null): string
    {
        $numero = preg_replace('/\D/', '', configuracion_valor('whatsapp_matricula', '51999999999'));
        $mensaje = $nombrePostulante
            ? "Hola, quisiera enviar el voucher de pago de matrícula de {$nombrePostulante}."
            : 'Hola, quisiera consultar sobre el proceso de matrícula.';

        return 'https://wa.me/' . $numero . '?text=' . urlencode($mensaje);
    }
}

if (!function_exists('csv_response')) {
    /** Genera una respuesta de descarga CSV a partir de encabezados y filas. */
    function csv_response(string $nombreArchivo, array $encabezados, iterable $filas): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM para acentos correctos en Excel
            fputcsv($out, $encabezados);
            foreach ($filas as $fila) {
                fputcsv($out, $fila);
            }
            fclose($out);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

if (!function_exists('notificaciones_navbar')) {
    /**
     * Notificaciones "en vivo" para la campana del navbar: se calculan al vuelo según el
     * rol de la sesión (sin tabla de notificaciones ni estado leído/no leído), mostrando
     * lo más relevante que requiere atención de cada rol en este momento.
     */
    function notificaciones_navbar(): array
    {
        $rol = session('user_rol');
        $items = [];

        if ($rol === 'promotor') {
            $pendientesMatricula = DB::table('solicitudes_matricula')->where('estado', 'pendiente')->count();
            if ($pendientesMatricula > 0) {
                $items[] = [
                    'icono' => 'fa-user-plus', 'color' => 'primary',
                    'texto' => $pendientesMatricula . ' solicitud' . ($pendientesMatricula == 1 ? '' : 'es') . ' de matrícula pendiente' . ($pendientesMatricula == 1 ? '' : 's'),
                    'url' => route('matricula.pendientes'),
                ];
            }

            $conductasNeg = DB::table('conductas as c')
                ->join('estudiantes as e', 'e.id', '=', 'c.estudiante_id')
                ->where('c.tipo', 'negativo')->where('c.fecha_hora', '>=', now()->subDays(3))
                ->select('c.fecha_hora', DB::raw("CONCAT(e.nombre,' ',e.apellido) as estudiante_nombre"))
                ->orderByDesc('c.fecha_hora')->limit(3)->get();
            foreach ($conductasNeg as $c) {
                $items[] = ['icono' => 'fa-triangle-exclamation', 'color' => 'danger', 'texto' => 'Conducta negativa: ' . $c->estudiante_nombre, 'url' => '/conductas', 'tiempo' => $c->fecha_hora];
            }
        } elseif ($rol === 'docente') {
            $docenteId = session('user_id');
            $porCalificar = DB::table('entregas_actividad as ea')
                ->join('actividades as a', 'a.id', '=', 'ea.actividad_id')
                ->where('a.docente_id', $docenteId)->where('ea.estado', 'entregado')
                ->count();
            if ($porCalificar > 0) {
                $items[] = [
                    'icono' => 'fa-clipboard-check', 'color' => 'warning',
                    'texto' => $porCalificar . ' entrega' . ($porCalificar == 1 ? '' : 's') . ' por calificar',
                    'url' => '/notas',
                ];
            }

            $gradoIds = DB::table('asignaciones_docente')->where('docente_id', $docenteId)->where('activo', 1)->where('anio_escolar', date('Y'))->pluck('grado_id')->unique()->values()->all();
            if ($gradoIds) {
                $conductasNeg = DB::table('conductas as c')
                    ->join('estudiantes as e', 'e.id', '=', 'c.estudiante_id')
                    ->join('matriculas as m', fn($j) => $j->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1)->where('m.anio_escolar', date('Y')))
                    ->whereIn('m.grado_id', $gradoIds)
                    ->where('c.tipo', 'negativo')->where('c.fecha_hora', '>=', now()->subDays(3))
                    ->select('c.fecha_hora', DB::raw("CONCAT(e.nombre,' ',e.apellido) as estudiante_nombre"))
                    ->orderByDesc('c.fecha_hora')->limit(3)->get();
                foreach ($conductasNeg as $c) {
                    $items[] = ['icono' => 'fa-triangle-exclamation', 'color' => 'danger', 'texto' => 'Conducta negativa: ' . $c->estudiante_nombre, 'url' => '/conductas', 'tiempo' => $c->fecha_hora];
                }
            }
        } elseif ($rol === 'psicologa') {
            $alertas = DB::table('asistencias as a')
                ->join('estudiantes as e', 'e.id', '=', 'a.estudiante_id')
                ->select('e.id', DB::raw("CONCAT(e.nombre,' ',e.apellido) as nombre"), DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistio"))
                ->groupBy('e.id', 'e.nombre', 'e.apellido')
                ->havingRaw('COUNT(*) >= 3')
                ->havingRaw("(SUM(CASE WHEN a.estado IN ('presente','tardanza') THEN 1 ELSE 0 END) / COUNT(*)) < 0.75")
                ->limit(3)->get();
            foreach ($alertas as $al) {
                $items[] = ['icono' => 'fa-calendar-xmark', 'color' => 'danger', 'texto' => 'Riesgo por inasistencia: ' . $al->nombre, 'url' => '/dashboard'];
            }

            $conductasNeg = DB::table('conductas')->where('tipo', 'negativo')->where('fecha_hora', '>=', now()->subDays(3))->count();
            if ($conductasNeg > 0) {
                $items[] = ['icono' => 'fa-triangle-exclamation', 'color' => 'warning', 'texto' => $conductasNeg . ' conducta' . ($conductasNeg == 1 ? '' : 's') . ' negativa' . ($conductasNeg == 1 ? '' : 's') . ' esta semana', 'url' => '/conductas'];
            }
        } elseif ($rol === 'estudiante') {
            // session('estudiante_id') solo la fija el middleware de las rutas /alumno/*;
            // en otras páginas (como el dashboard) aún no existe, así que se resuelve
            // directamente desde el usuario autenticado.
            $estudianteId = session('estudiante_id') ?: DB::table('estudiantes')->where('usuario_id', session('user_id'))->value('id');
            if ($estudianteId) {
                $pendientes = DB::table('entregas_actividad')->where('estudiante_id', $estudianteId)->where('estado', 'pendiente')->count();
                if ($pendientes > 0) {
                    $items[] = [
                        'icono' => 'fa-tasks', 'color' => 'warning',
                        'texto' => $pendientes . ' actividad' . ($pendientes == 1 ? '' : 'es') . ' pendiente' . ($pendientes == 1 ? '' : 's') . ' de entregar',
                        'url' => route('alumno.actividades'),
                    ];
                }

                $notasRecientes = DB::table('notas as n')
                    ->join('cursos as c', 'c.id', '=', 'n.curso_id')
                    ->where('n.estudiante_id', $estudianteId)->where('n.creado_en', '>=', now()->subDays(5))
                    ->select('c.nombre as curso', 'n.nombre_evaluacion', 'n.creado_en')
                    ->orderByDesc('n.creado_en')->limit(3)->get();
                foreach ($notasRecientes as $n) {
                    $items[] = ['icono' => 'fa-star', 'color' => 'primary', 'texto' => 'Nueva nota en ' . $n->curso . ': ' . $n->nombre_evaluacion, 'url' => route('alumno.notas'), 'tiempo' => $n->creado_en];
                }
            }
        }

        return $items;
    }
}

if (!function_exists('registrar_auditoria')) {
    function registrar_auditoria(string $accion, string $modulo, mixed $anterior = null, mixed $nuevo = null): void
    {
        DB::table('auditorias')->insert([
            'id' => Str::uuid(),
            'usuario_id' => session('user_id'),
            'rol' => session('user_rol'),
            'accion' => $accion,
            'modulo' => $modulo,
            'registro_anterior' => is_scalar($anterior) ? (string) $anterior : json_encode($anterior),
            'registro_nuevo' => is_scalar($nuevo) ? (string) $nuevo : json_encode($nuevo),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
