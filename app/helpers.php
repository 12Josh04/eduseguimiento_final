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
