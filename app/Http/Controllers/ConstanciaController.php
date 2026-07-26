<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ConstanciaController extends Controller
{
    private function datosEstudiante(string $id): array
    {
        $estudiante = DB::table('estudiantes as e')
            ->leftJoin('matriculas as m', fn($j) => $j->on('m.estudiante_id', '=', 'e.id')->where('m.activo', 1))
            ->leftJoin('grados as g', 'g.id', '=', 'm.grado_id')
            ->select('e.*', 'g.nombre as grado', 'g.nivel', 'm.seccion', 'm.anio_escolar', 'm.created_at as fecha_matricula')
            ->where('e.id', $id)->first();

        abort_if(!$estudiante, 404);

        $this->verificarAcceso($id, $estudiante);

        $config = DB::table('configuraciones_institucionales')->pluck('valor', 'clave');

        return [
            'estudiante' => $estudiante,
            'colegio' => $config['nombre_colegio'] ?? 'Colegio José Tola Pasquel',
            'dre' => $config['dre'] ?? 'DRE ANCASH',
            'ugel' => $config['ugel'] ?? 'UGEL Huaraz',
            'codigoModular' => $config['codigo_modular'] ?? '',
            'fechaEmision' => now()->translatedFormat('d \d\e F \d\e Y'),
            'director' => 'La Dirección',
        ];
    }

    /** Restringe el acceso: promotor/docente ven cualquier estudiante; el propio estudiante solo se ve a sí mismo. */
    private function verificarAcceso(string $id, $estudiante): void
    {
        $rol = session('user_rol');
        if ($rol === 'estudiante') {
            $propio = DB::table('estudiantes')->where('usuario_id', session('user_id'))->value('id');
            abort_if($propio !== $id, 403, 'No tienes permiso para descargar la constancia de otro estudiante.');
        }
    }

    public function estudios(string $id)
    {
        $data = $this->datosEstudiante($id);
        $data['titulo'] = 'Constancia de Estudios';
        $data['cuerpo'] = "La Dirección de {$data['colegio']} deja constancia que el/la estudiante "
            . "<strong>" . strtoupper($data['estudiante']->nombre . ' ' . $data['estudiante']->apellido) . "</strong>, "
            . "identificado(a) con código <strong>{$data['estudiante']->codigo}</strong>"
            . ($data['estudiante']->dni ? " y DNI <strong>{$data['estudiante']->dni}</strong>" : '')
            . ", se encuentra matriculado(a) y cursando estudios regulares en el "
            . "<strong>{$data['estudiante']->grado} \"" . ($data['estudiante']->seccion ?? '—') . "\"</strong> de "
            . "{$data['estudiante']->nivel}, durante el año escolar <strong>{$data['estudiante']->anio_escolar}</strong>.";

        registrar_auditoria('descargar', 'constancias', null, "Constancia de estudios: {$data['estudiante']->codigo}");

        return $this->render('estudios', $data);
    }

    public function matricula(string $id)
    {
        $data = $this->datosEstudiante($id);
        $data['titulo'] = 'Constancia de Matrícula';
        $fechaMatricula = $data['estudiante']->fecha_matricula
            ? \Carbon\Carbon::parse($data['estudiante']->fecha_matricula)->translatedFormat('d \d\e F \d\e Y')
            : 'no registrada';
        $data['cuerpo'] = "La Dirección de {$data['colegio']} deja constancia que el/la estudiante "
            . "<strong>" . strtoupper($data['estudiante']->nombre . ' ' . $data['estudiante']->apellido) . "</strong>, "
            . "con código <strong>{$data['estudiante']->codigo}</strong>, se encuentra "
            . "<strong>matriculado(a)</strong> en el <strong>{$data['estudiante']->grado} \"" . ($data['estudiante']->seccion ?? '—') . "\"</strong> "
            . "de {$data['estudiante']->nivel}, para el año escolar <strong>{$data['estudiante']->anio_escolar}</strong>, "
            . "con fecha de matrícula {$fechaMatricula}.";

        registrar_auditoria('descargar', 'constancias', null, "Constancia de matrícula: {$data['estudiante']->codigo}");

        return $this->render('matricula', $data);
    }

    public function conducta(string $id)
    {
        $data = $this->datosEstudiante($id);

        $conteos = DB::table('conductas')->where('estudiante_id', $id)
            ->selectRaw("SUM(tipo='positivo') as positivas, SUM(tipo='negativo') as negativas, SUM(tipo='observacion') as observaciones")
            ->first();

        $data['titulo'] = 'Constancia de Conducta';
        $situacion = ($conteos->negativas ?? 0) == 0
            ? 'no registra observaciones conductuales negativas'
            : "registra {$conteos->negativas} observación(es) conductual(es) negativa(s) en su historial";

        $data['cuerpo'] = "La Dirección de {$data['colegio']} deja constancia que el/la estudiante "
            . "<strong>" . strtoupper($data['estudiante']->nombre . ' ' . $data['estudiante']->apellido) . "</strong>, "
            . "con código <strong>{$data['estudiante']->codigo}</strong>, del <strong>{$data['estudiante']->grado} \"" . ($data['estudiante']->seccion ?? '—') . "\"</strong>, "
            . "durante el año escolar <strong>{$data['estudiante']->anio_escolar}</strong>, {$situacion}, "
            . "registrando además {$conteos->positivas} reconocimiento(s) conductual(es) positivo(s) "
            . "y {$conteos->observaciones} observación(es) de seguimiento.";

        registrar_auditoria('descargar', 'constancias', null, "Constancia de conducta: {$data['estudiante']->codigo}");

        return $this->render('conducta', $data);
    }

    private function render(string $tipo, array $data)
    {
        $pdf = Pdf::loadView('constancias.documento', $data)->setPaper('a4', 'portrait');
        $nombre = 'Constancia_' . ucfirst($tipo) . '_' . str_replace(' ', '_', $data['estudiante']->apellido . '_' . $data['estudiante']->nombre) . '.pdf';
        return $pdf->stream($nombre);
    }
}
